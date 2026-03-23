<?php
// inc/Frontend/SubmitController.php
namespace OU\QuizChat\Frontend;

if (!defined('ABSPATH')) { exit; }

use WP_REST_Request;
use WP_REST_Response;
use OU\QuizChat\Core\Options;
use OU\QuizChat\Core\Mailer;

/**
 * 診断の最終送信エンドポイント
 * - ルート: POST /ouq/v1/submit
 * - 役割: バリデーション → 管理者メール送信 →（任意）ユーザー控え送信 → JSON応答
 * - セキュリティ:
 *   - X-WP-Nonce: ログイン時は照合、未ログインでも同一オリジンの Origin/Referer を緩和条件として許可
 *   - 入力はすべてサニタイズ
 *   - メールヘッダインジェクション防止（Mailer側）
 */
class SubmitController
{
    const REST_NS = 'ouq/v1';

    /** @var Options */
    protected $opts;

    /** @var Mailer */
    protected $mailer;

    public function __construct()
    {
        $this->opts   = new Options();
        $this->mailer = new Mailer();

        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route(self::REST_NS, '/submit', [
            [
                'methods'  => 'POST',
                'callback' => [$this, 'handle_submit'],
                'permission_callback' => '__return_true', // パブリック受付（下で自前チェック）
                'args' => [
                    // JSONボディ受領のため args は厳密には不要。内容は handle 内で精査。
                ],
            ],
        ]);
    }

    /**
     * 送信ハンドラ
     */
    public function handle_submit(WP_REST_Request $req): WP_REST_Response
    {
        // --- CSRF 的チェック（可能なら） ---
        $nonce    = $req->get_header('X-WP-Nonce') ?: $req->get_header('x-wp-nonce') ?: '';
        $nonce_ok = $nonce && wp_verify_nonce($nonce, 'wp_rest');
        if (!$nonce_ok) {
            // 未ログイン等でnonceが使えない場合は、Origin/Referer をチェック（同一オリジン想定）
            if (!$this->allow_via_origin($req)) {
                return new WP_REST_Response(['ok'=>false, 'error'=>'forbidden'], 403);
            }
        }

        // --- JSONボディ取得 ---
        $raw  = $req->get_body();
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return new WP_REST_Response(['ok'=>false, 'error'=>'invalid_json'], 400);
        }

        // --- 入力を取り出し＆サニタイズ ---
        $name    = $this->s($data['name']    ?? '');
        $company = $this->s($data['company'] ?? '');
        $phone   = $this->s_phone($data['phone'] ?? '');
        $email   = sanitize_email($data['email'] ?? '');

        $consent = !empty($data['consent']);

        $score   = isset($data['score']) ? intval($data['score']) : 0;
        $band    = is_array($data['band'] ?? null) ? $this->sanitize_band($data['band']) : null;

        $answers = [];
        if (!empty($data['answers']) && is_array($data['answers'])) {
            foreach ($data['answers'] as $row) {
                $answers[] = [
                    'q'     => $this->s($row['q'] ?? ''),
                    'a'     => $this->s($row['a'] ?? ''),
                    'score' => isset($row['score']) ? intval($row['score']) : 0,
                ];
            }
        }

        $page_url = esc_url_raw($data['page_url'] ?? '');
        $query    = [];
        if (!empty($data['query']) && is_array($data['query'])) {
            foreach ($data['query'] as $k => $v) {
                $query[$this->s($k)] = $this->s($v);
            }
        }

        // --- バリデーション（最低限） ---
        $errors = [];
        if (!$consent) $errors[] = 'consent_required';
        if (!$name)    $errors[] = 'name_required';
        if (!$email || !is_email($email)) $errors[] = 'email_invalid';
        if (mb_strlen($name) > 100)    $errors[] = 'name_too_long';
        if (mb_strlen($company) > 150) $errors[] = 'company_too_long';
        if ($phone && !preg_match('/^\+?\d[\d\-\s]{6,}$/', $phone)) $errors[] = 'phone_invalid';

        if (!empty($errors)) {
            return new WP_REST_Response(['ok'=>false, 'error'=>'validation', 'fields'=>$errors], 422);
        }

        // --- レート制限（IP+メールで60秒） ---
        $ip   = $this->client_ip();
        $key  = 'ouq_submit_' . md5($ip . '|' . strtolower($email));
        if (get_transient($key)) {
            return new WP_REST_Response(['ok'=>false,'error'=>'rate_limited'], 429);
        }
        set_transient($key, 1, 60);

        // --- UA/Referer ---
        $ua      = isset($_SERVER['HTTP_USER_AGENT']) ? $this->s($_SERVER['HTTP_USER_AGENT']) : '';
        $referer = isset($_SERVER['HTTP_REFERER'])    ? esc_url_raw($_SERVER['HTTP_REFERER'])  : '';
        $dt      = current_time('mysql');

        // --- 管理者宛先（Options） ---
        $adminRecipients = $this->load_admin_recipients();

        // --- 管理者宛 Subject / Body ---
        $subject_admin = sprintf('[OU Quiz Chat] 新規診断 %s / Score:%d', $dt, $score);

        $lines_admin = [];
        $lines_admin[] = '=== 診断 送信内容（管理者控え） =====================';
        $lines_admin[] = '送信日時: ' . $dt;
        $lines_admin[] = '';
        $lines_admin[] = '[お客様情報]';
        $lines_admin[] = 'お名前: ' . $name;
        $lines_admin[] = '会社名: ' . ($company ?: '-');
        $lines_admin[] = '電話: '   . ($phone ?: '-');
        $lines_admin[] = 'メール: ' . $email;
        $lines_admin[] = '';
        $lines_admin[] = '[診断結果]';
        $lines_admin[] = 'スコア: ' . $score;
        if ($band) {
            $lines_admin[] = 'レンジ: ' . sprintf('%d〜%d', $band['min'], $band['max']);
            if ($band['title'])   $lines_admin[] = '診断タイトル: ' . $band['title'];
            if ($band['summary']) $lines_admin[] = 'サマリ: ' . $band['summary'];
            if ($band['estimate_timeline']) $lines_admin[] = '目安期間: ' . $band['estimate_timeline'];
            if ($band['estimate_budget'])   $lines_admin[] = '概算: '     . $band['estimate_budget'];
        }
        $lines_admin[] = '';
        $lines_admin[] = '[回答ログ]';
        if ($answers) {
            foreach ($answers as $i => $row) {
                $lines_admin[] = sprintf('- Q%d: %s / A: %s / +%d',
                    $i+1, $row['q'], $row['a'], $row['score']
                );
            }
        } else {
            $lines_admin[] = '- （回答ログなし）';
        }
        $lines_admin[] = '';
        $lines_admin[] = '[トラッキング]';
        $lines_admin[] = 'ページURL: ' . ($page_url ?: '-');
        $lines_admin[] = 'パラメータ: ' . ($query ? wp_json_encode($query, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '-');
        $lines_admin[] = 'IP: ' . $ip;
        $lines_admin[] = 'UA: ' . ($ua ?: '-');
        if ($referer) $lines_admin[] = 'Referer: ' . $referer;
        $lines_admin[] = '====================================================';

        // --- メール送信：管理者 → ユーザー控え ---
        $okAdmin = false;
        $okUser  = true; // ユーザー控え無効ならtrue扱い
        try {
            $okAdmin = $this->mailer->sendToAdmins($adminRecipients, $subject_admin, implode("\n", $lines_admin));
        } catch (\Throwable $e) {
            $okAdmin = false;
            $this->log('mail_admin_error', $e->getMessage(), ['subj'=>$subject_admin]);
        }

        // ユーザー控え（オプションがONのとき）
        $user_copy_enabled = (bool) $this->opts->get_flag('user_copy_enabled');
        if ($okAdmin && $user_copy_enabled) {
            $userSubject = '【診断結果】ご回答ありがとうございます';

            $u = [];
            $u[] = 'この度は診断にご回答ありがとうございます。';
            $u[] = '';
            $u[] = '――――――――――――――――――――';
            $u[] = '診断結果（サマリ）';
            $u[] = 'スコア: ' . $score;
            if ($band) {
                if ($band['title'])   $u[] = '診断タイトル: ' . $band['title'];
                if ($band['summary']) $u[] = 'サマリ: ' . $band['summary'];
                if ($band['estimate_timeline']) $u[] = '目安期間: ' . $band['estimate_timeline'];
                if ($band['estimate_budget'])   $u[] = '概算: '     . $band['estimate_budget'];
            }
            $u[] = '――――――――――――――――――――';
            $u[] = '';
            $u[] = '※本メールにお心当たりが無い場合は、本メールを破棄してください。';

            try {
                $okUser = $this->mailer->sendToUser($email, $userSubject, implode("\n", $u));
            } catch (\Throwable $e) {
                $okUser = false;
                $this->log('mail_user_error', $e->getMessage(), ['to'=>$email]);
            }
        }

        // --- 応答メッセージ（フロント表示用） ---
        $succ_template = (string) $this->opts->get_dot('post_send_notice.success_text', '診断結果を送信しました。{email} 宛のメールをご確認ください。');
        $err_template  = (string) $this->opts->get_dot('post_send_notice.error_text',   '送信に失敗しました。時間をおいて再度お試しください。');

        $successText = strtr($succ_template, [
            '{email}'      => $email,
            '{name}'       => $name,
            '{score}'      => (string) $score,
            '{band_title}' => $band['title'] ?? '',
        ]);
        $errorText   = $err_template;

        $ok = ($okAdmin && $okUser);

        // 完了フック（外部連携等）
        do_action('ouq_quiz_chat_submitted', [
            'ok'      => $ok,
            'admin'   => $okAdmin,
            'user'    => $okUser,
            'payload' => [
                'name'    => $name,
                'company' => $company,
                'phone'   => $phone,
                'email'   => $email,
                'consent' => $consent,
                'score'   => $score,
                'band'    => $band,
                'answers' => $answers,
                'page_url'=> $page_url,
                'query'   => $query,
                'ip'      => $ip,
                'ua'      => $ua,
                'referer' => $referer,
            ],
        ]);

        // 予約リンク（設定）を応答にも同梱
        $resv = [
            'enabled' => (bool) $this->opts->get_dot('post_send_reservation.enabled', false),
            'url'     => (string) $this->opts->get_dot('post_send_reservation.url', ''),
            'label'   => (string) $this->opts->get_dot('post_send_reservation.label', '無料相談を予約する'),
        ];
        // 結果レンジ側のCTAがあれば優先（URL/ラベル）
        if ($band && !empty($band['cta_url']))  $resv['url']   = $band['cta_url'];
        if ($band && !empty($band['cta_label']))$resv['label'] = $band['cta_label'];

        if (!$ok) {
            return new WP_REST_Response(['ok'=>false, 'message'=>$errorText, 'reservation'=>$resv], 200);
        }

        return new WP_REST_Response(['ok'=>true, 'message'=>$successText, 'reservation'=>$resv], 200);
    }

    // ===== Helpers =====

    /** 緩和条件：Origin/Referer が同一オリジンなら許可（匿名利用想定） */
    protected function allow_via_origin(WP_REST_Request $req): bool
    {
        $origin  = $req->get_header('origin');
        $referer = $req->get_header('referer');

        $site = home_url('/');
        $ok = true;

        if ($origin) {
            $ok = $ok && $this->same_origin($origin, $site);
        }
        if ($referer) {
            $ok = $ok && $this->same_origin($referer, $site);
        }
        return $ok;
    }

    protected function same_origin(string $a, string $b): bool
    {
        $ha = wp_parse_url($a);
        $hb = wp_parse_url($b);
        if (!$ha || !$hb) return false;

        $pa = sprintf('%s://%s', $ha['scheme'] ?? 'https', $ha['host'] ?? '');
        $pb = sprintf('%s://%s', $hb['scheme'] ?? 'https', $hb['host'] ?? '');
        return (rtrim($pa,'/') === rtrim($pb,'/'));
    }

    /** X-Forwarded-For を考慮してIP取得（簡易） */
    protected function client_ip(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $v = trim((string) $_SERVER[$key]);
                // XFF の場合は先頭を取る
                if ($key === 'HTTP_X_FORWARDED_FOR') {
                    $parts = explode(',', $v);
                    $v = trim($parts[0]);
                }
                return $v;
            }
        }
        return '';
    }

    /** band 配列のサニタイズ */
    protected function sanitize_band(array $b): array
    {
        return [
            'id'                => isset($b['id']) ? intval($b['id']) : 0,
            'min'               => isset($b['min']) ? intval($b['min']) : 0,
            'max'               => isset($b['max']) ? intval($b['max']) : 0,
            'title'             => $this->s($b['title'] ?? ''),
            'summary'           => $this->s($b['summary'] ?? ''),
            'recommend_pages'   => $this->sanitize_string_array($b['recommend_pages'] ?? []),
            'recommend_features'=> $this->sanitize_string_array($b['recommend_features'] ?? []),
            'estimate_timeline' => $this->s($b['estimate_timeline'] ?? ''),
            'estimate_budget'   => $this->s($b['estimate_budget'] ?? ''),
            'cta_label'         => $this->s($b['cta_label'] ?? ''),
            'cta_url'           => esc_url_raw($b['cta_url'] ?? ''),
        ];
    }

    /** 文字列配列のサニタイズ */
    protected function sanitize_string_array($arr): array
    {
        $out = [];
        if (is_array($arr)) {
            foreach ($arr as $v) {
                $v = $this->s($v);
                if ($v !== '') $out[] = $v;
            }
        }
        return $out;
    }

    /** テキストサニタイズ（1行想定）：strip_tags + trim */
    protected function s($v): string
    {
        return trim(wp_strip_all_tags((string) $v));
    }

    /** 電話番号の簡易サニタイズ */
    protected function s_phone($v): string
    {
        $v = preg_replace('/[^\d\+\-\s]/', '', (string) $v);
        return trim($v);
    }

    /** 軽量ログ（WP_DEBUG_LOGがtrueならファイル出力されます） */
    protected function log(string $tag, string $message, array $context = []): void
    {
        $line = sprintf('[OUQ][%s] %s %s', $tag, $message, $context ? wp_json_encode($context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '');
        if (function_exists('error_log')) {
            error_log($line);
        }
        do_action('ouq_quiz_chat_log', $tag, $message, $context);
    }

    /** 管理者宛の宛先（TO/BCC）を Options から取得 */
    protected function load_admin_recipients(): array
    {
        $to  = $this->opts->get('admin_recipients', []);
        $bcc = []; // UIが無いので既定は空。必要なら filter で加える

        /**
         * フィルタ：BCC などを外部から補完可能にする
         * - ouq_mail_admin_recipients
         */
        return apply_filters('ouq_mail_admin_recipients', [
            'to'  => is_array($to) ? $to : [],
            'bcc' => $bcc,
        ]);
    }
}
