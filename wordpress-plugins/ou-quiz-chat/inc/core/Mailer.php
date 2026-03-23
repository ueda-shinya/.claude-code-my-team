<?php
// inc/Core/Mailer.php
namespace OU\QuizChat\Core;

if (!defined('ABSPATH')) { exit; }

/**
 * Mailer: wp_mail の薄いラッパ
 * - 送信元名/送信元メール（Options）を統一管理
 * - 複数宛先（TO, BCC）対応
 * - text/plain を既定（HTMLにしたい場合は filter で上書き可能）
 * - ヘッダインジェクション対策：改行除去／エンコード
 */
class Mailer
{
    /** @var Options */
    protected $opts;

    public function __construct()
    {
        $this->opts = new Options();
    }

    /**
     * 管理者宛に送る
     * @param array{to:string[], bcc?:string[]} $recipients
     * @param string $subject
     * @param string $bodyPlain
     * @return bool
     */
    public function sendToAdmins(array $recipients, string $subject, string $bodyPlain): bool
    {
        $to  = $this->sanitizeEmails($recipients['to'] ?? []);
        $bcc = $this->sanitizeEmails($recipients['bcc'] ?? []);

        if (empty($to)) {
            // フォールバック：サイト管理者メール
            $admin = get_option('admin_email');
            if ($admin && is_email($admin)) {
                $to = [$admin];
            }
        }
        if (empty($to)) return false;

        $headers = $this->buildHeaders($bcc);

        /**
         * フィルタ：管理者宛のヘッダ・本文などを外部調整
         * - ouq_mail_admin_headers: array $headers
         * - ouq_mail_admin_subject: string $subject
         * - ouq_mail_admin_body: string $bodyPlain
         */
        $headers  = apply_filters('ouq_mail_admin_headers', $headers, $recipients, $subject, $bodyPlain);
        $subject  = apply_filters('ouq_mail_admin_subject', $subject, $recipients, $bodyPlain);
        $bodyPlain= apply_filters('ouq_mail_admin_body',    $bodyPlain, $recipients, $subject);

        return wp_mail($to, $subject, $bodyPlain, $headers);
    }

    /**
     * ユーザー宛に送る
     * @param string $to
     * @param string $subject
     * @param string $bodyPlain
     * @return bool
     */
    public function sendToUser(string $to, string $subject, string $bodyPlain): bool
    {
        $to = trim($to);
        if (!$to || !is_email($to)) return false;

        $headers = $this->buildHeaders(); // BCCなし

        /**
         * フィルタ：ユーザー控えメールの調整
         * - ouq_mail_user_headers
         * - ouq_mail_user_subject
         * - ouq_mail_user_body
         */
        $headers  = apply_filters('ouq_mail_user_headers', $headers, $to, $subject, $bodyPlain);
        $subject  = apply_filters('ouq_mail_user_subject', $subject, $to, $bodyPlain);
        $bodyPlain= apply_filters('ouq_mail_user_body',    $bodyPlain, $to, $subject);

        return wp_mail([$to], $subject, $bodyPlain, $headers);
    }

    /**
     * 送信元ヘッダとContent-Typeを組み立て
     * @param string[] $bcc
     * @return string[]
     */
    protected function buildHeaders(array $bcc = []): array
    {
        $fromName  = (string) $this->opts->get('from_name', get_bloginfo('name'));
        $fromEmail = (string) $this->opts->get('from_email', 'no-reply@' . parse_url(home_url(), PHP_URL_HOST));

        // ヘッダインジェクション防止：改行除去
        $fromName  = $this->stripHeaderBreaks($fromName);
        $fromEmail = $this->stripHeaderBreaks($fromEmail);

        if (!is_email($fromEmail)) {
            $fromEmail = 'no-reply@' . parse_url(home_url(), PHP_URL_HOST);
        }

        $headers = [
            'From: ' . $this->encodeHeader($fromName) . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $bcc = $this->sanitizeEmails($bcc);
        foreach ($bcc as $mail) {
            $headers[] = 'Bcc: ' . $mail;
        }

        return $headers;
    }

    /** @param string[] $emails */
    protected function sanitizeEmails(array $emails): array
    {
        $out = [];
        foreach ($emails as $m) {
            $m = sanitize_email($m);
            if ($m && is_email($m)) $out[] = $m;
        }
        // 重複排除
        return array_values(array_unique($out));
    }

    /** 改行/制御を除去 */
    protected function stripHeaderBreaks(string $s): string
    {
        return preg_replace('/[\r\n]+/', ' ', $s);
    }

    /** ヘッダ用にエンコード（マルチバイト安全） */
    protected function encodeHeader(string $s): string
    {
        // WordPress コアのヘルパに任せる
        return wp_specialchars_decode(esc_html($s), ENT_QUOTES);
    }
}
