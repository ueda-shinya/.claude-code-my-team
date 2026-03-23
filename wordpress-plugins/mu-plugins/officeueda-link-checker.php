<?php
/**
 * Plugin Name: OfficeUeda - Internal Link Checker (MU)
 * Description: サイト内のリンク切れ(4xx/5xxや取得エラー)をWP-Cronでバッチ検査し、管理画面(ツール)で一覧/CSV出力。毎日自動スキャン & エラーがあればメール通知（To: 管理者 / Bcc: web-admin@officeueda.com）。401/403/405/406/429/503/520/521/522/525/526 はデフォルト除外で、チェックボックスにより検出対象へ切替可。
 * Author: OfficeUEDA
 * Version: 1.1.0
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

final class OfficeUeda_Internal_Link_Checker {
    const VERSION           = '1.1.0';

    // オプション
    const OPTION_STATE      = 'ou_lc_state';       // 走行状態
    const OPTION_SETTINGS   = 'ou_lc_settings';    // 画面設定（検出対象ステータス）

    // Cron
    const CRON_HOOK         = 'ou_lc_process_queue';   // 毎分バッチ
    const DAILY_CRON_HOOK   = 'ou_lc_daily_kickoff';   // 日次キックオフ

    // DB
    const TABLE_NAME        = 'ou_link_checker';

    // UI/動作
    const ADMIN_SLUG        = 'ou-internal-link-checker';
    const BATCH_SIZE        = 25;                 // 1回で処理するURL数
    const DAILY_LIMIT       = 0;                  // 自動走査のURL上限（0=無制限）
    const BCC_ADDRESSES     = ['web-admin@officeueda.com'];

    // 「セキュリティ起因になりやすいコード」デフォルト除外セット
    const MANAGEABLE_STATUSES       = [401, 403, 405, 406, 429, 503, 520, 521, 522, 525, 526];
    const DEFAULT_IGNORED_STATUSES  = [401, 403, 405, 406, 429, 503, 520, 521, 522, 525, 526];

    private $site_host;

    public function __construct() {
        $this->site_host = wp_parse_url(home_url('/'), PHP_URL_HOST);

        // テーブル作成（Cron起動でも確実に）
        add_action('init', [$this, 'maybe_create_table']);

        // 管理画面
        add_action('admin_menu', [$this, 'add_tools_page']);
        add_action('admin_post_ou_lc_start',  [$this, 'handle_start_scan']);
        add_action('admin_post_ou_lc_stop',   [$this, 'handle_stop_scan']);
        add_action('admin_post_ou_lc_export', [$this, 'handle_export_csv']);
        add_action('admin_post_ou_lc_save_settings', [$this, 'handle_save_settings']);

        // Cron
        add_action('init', [$this, 'ensure_cron_schedule']);
        add_action(self::CRON_HOOK,       [$this, 'process_queue']);
        add_action(self::DAILY_CRON_HOOK, [$this, 'daily_kickoff']);

        // WP-CLI (任意)
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('ou-lc', [$this, 'cli_command']);
        }
    }

    /* ========== DB ========== */
    public function maybe_create_table() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS `$table` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `referer` LONGTEXT NULL,
            `url` LONGTEXT NOT NULL,
            `status` SMALLINT NULL,
            `type` VARCHAR(16) NOT NULL DEFAULT 'href',
            `error` LONGTEXT NULL,
            `checked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `status_idx` (`status`)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /* ========== 設定 ========== */
    private function get_settings() {
        $s = get_option(self::OPTION_SETTINGS, []);
        // detect_statuses: 「検出対象に含める」ステータスの配列（未設定は空=全て除外）
        $detect = isset($s['detect_statuses']) && is_array($s['detect_statuses'])
            ? array_map('intval', $s['detect_statuses'])
            : [];
        $detect = array_values(array_intersect($detect, self::MANAGEABLE_STATUSES));
        return ['detect_statuses' => $detect];
    }

    private function save_settings(array $settings) {
        $detect = isset($settings['detect_statuses']) && is_array($settings['detect_statuses'])
            ? array_map('intval', $settings['detect_statuses'])
            : [];
        $detect = array_values(array_intersect($detect, self::MANAGEABLE_STATUSES));
        update_option(self::OPTION_SETTINGS, ['detect_statuses' => $detect], false);
    }

    private function get_ignored_statuses() {
        // 既定の除外セットから、画面で「検出対象に含める」にされたものを除外リストから外す
        $ignored = self::DEFAULT_IGNORED_STATUSES;
        $detect  = $this->get_settings()['detect_statuses'];
        if (!empty($detect)) {
            $ignored = array_values(array_diff($ignored, $detect));
        }
        // フィルタ拡張
        $ignored = apply_filters('ou_lc_ignored_statuses', array_unique(array_map('intval', $ignored)));
        return $ignored;
    }

    /* ========== 状態 ========== */
    private function get_state() {
        $default = [
            'running' => false,
            'queue'   => [],
            'visited' => [],
            'started' => null,
            'updated' => null,
            'limit'   => 0,
            'report_sent' => false,
        ];
        $state = get_option(self::OPTION_STATE, []);
        return wp_parse_args($state, $default);
    }

    private function save_state($state) {
        $state['updated'] = current_time('mysql');
        update_option(self::OPTION_STATE, $state, false);
    }

    /* ========== 管理画面 ========== */
    public function add_tools_page() {
        add_management_page(
            '内部リンクチェッカー',
            '内部リンクチェッカー',
            'manage_options',
            self::ADMIN_SLUG,
            [$this, 'render_tools_page']
        );
    }

    private function status_label($code) {
        $labels = [
            401 => '401 Unauthorized（認証必須）',
            403 => '403 Forbidden（アクセス拒否/WAF等）',
            405 => '405 Method Not Allowed（メソッド拒否）',
            406 => '406 Not Acceptable（コンテンツ検査等）',
            429 => '429 Too Many Requests（レート制限）',
            503 => '503 Service Unavailable（一時停止/高負荷）',
            520 => '520 CF: Unknown Error',
            521 => '521 CF: Web Server Down',
            522 => '522 CF: Connection Timed Out',
            525 => '525 CF: SSL Handshake Failed',
            526 => '526 CF: Invalid SSL Certificate',
        ];
        return $labels[$code] ?? (string) $code;
    }

    public function render_tools_page() {
        if (!current_user_can('manage_options')) return;

        $state           = $this->get_state();
        $settings        = $this->get_settings();
        $ignored_statuses= $this->get_ignored_statuses();
        $nonce           = wp_create_nonce('ou_lc_actions');

        // 直近のエラー100件（※除外を反映）
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        $ignored_sql = $ignored_statuses ? ' AND status NOT IN (' . implode(',', array_map('intval', $ignored_statuses)) . ')' : '';
        $errors = $wpdb->get_results(
            "SELECT * FROM `$table`
             WHERE (status >= 400 OR status IS NULL) $ignored_sql
             ORDER BY checked_at DESC LIMIT 100",
            ARRAY_A
        );
        ?>
        <div class="wrap">
            <h1>内部リンクチェッカー</h1>
            <p>サイト内部のページ/画像/CSS/JS の参照先を検査し、エラー(4xx/5xx/取得失敗)のみを記録表示します。</p>

            <table class="widefat" style="max-width:960px;margin-top:16px;">
                <tbody>
                <tr><th style="width:200px;">状態</th><td><?php echo $state['running'] ? '実行中' : '停止中'; ?></td></tr>
                <tr><th>開始時刻</th><td><?php echo esc_html($state['started'] ?: '-'); ?></td></tr>
                <tr><th>最終更新</th><td><?php echo esc_html($state['updated'] ?: '-'); ?></td></tr>
                <tr><th>キュー残件数</th><td><?php echo number_format_i18n(count($state['queue'])); ?></td></tr>
                <tr><th>処理済み件数</th><td><?php echo number_format_i18n(count($state['visited'])); ?></td></tr>
                <tr><th>URL上限</th><td><?php echo $state['limit'] ? intval($state['limit']) : '制限なし'; ?></td></tr>
                </tbody>
            </table>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:16px;">
                <input type="hidden" name="action" value="ou_lc_start">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <label>スキャン開始URL（既定はサイトトップ）:
                    <input type="url" name="start_url" value="<?php echo esc_attr(home_url('/')); ?>" size="60" />
                </label>
                <br><label>URL上限（0で無制限・大規模サイトは数千以内推奨）:
                    <input type="number" name="limit" value="0" min="0" step="1" />
                </label>
                <?php submit_button('スキャン開始', 'primary', ''); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                <input type="hidden" name="action" value="ou_lc_stop">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <?php submit_button('停止/キュー破棄', 'secondary', '', false, ['onclick' => "return confirm('停止してキューを破棄します。よろしいですか？');"]); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;margin-left:8px;">
                <input type="hidden" name="action" value="ou_lc_export">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <?php submit_button('CSVエクスポート（エラーのみ）', 'secondary', '', false); ?>
            </form>

            <h2 style="margin-top:32px;">検出対象の設定</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:8px;">
                <input type="hidden" name="action" value="ou_lc_save_settings">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <fieldset style="max-width:960px;padding:12px;border:1px solid #ccd0d4;background:#fff;">
                    <legend>デフォルトは「除外」。検出対象に含めたいコードにチェックを入れてください。</legend>
                    <?php
                    $detect = $settings['detect_statuses'];
                    foreach (self::MANAGEABLE_STATUSES as $code) {
                        $checked = in_array($code, $detect, true) ? 'checked' : '';
                        printf(
                            '<label style="display:inline-block;min-width:280px;margin:6px 12px 6px 0;">
                                <input type="checkbox" name="detect_statuses[]" value="%1$d" %3$s> %2$s
                             </label>',
                            $code,
                            esc_html($this->status_label($code)),
                            $checked
                        );
                    }
                    ?>
                    <div style="margin-top:8px;"><?php submit_button('設定を保存', 'secondary', ''); ?></div>
                    <p class="description">現在の除外ステータス: <?php
                        echo $ignored_statuses ? implode(', ', array_map('intval', $ignored_statuses)) : 'なし';
                    ?></p>
                </fieldset>
            </form>

            <h2 style="margin-top:24px;">直近のエラー（100件・除外反映）</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th style="width:90px;">日時</th>
                        <th>リンク先URL</th>
                        <th style="width:70px;">ステータス</th>
                        <th>参照元(Referer)</th>
                        <th>タイプ</th>
                        <th>エラー</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$errors): ?>
                    <tr><td colspan="6">記録されたエラーはありません（除外設定を確認してください）。</td></tr>
                <?php else: foreach ($errors as $row): ?>
                    <tr>
                        <td><?php echo esc_html(mysql2date('Y-m-d H:i', $row['checked_at'])); ?></td>
                        <td><code style="word-break:break-all;"><?php echo esc_html($row['url']); ?></code></td>
                        <td><?php echo $row['status'] ? intval($row['status']) : 'ERR'; ?></td>
                        <td><code style="word-break:break-all;"><?php echo esc_html($row['referer']); ?></code></td>
                        <td><?php echo esc_html($row['type']); ?></td>
                        <td><?php echo esc_html(mb_strimwidth($row['error'] ?? '', 0, 400, '...')); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_save_settings() {
        if (!current_user_can('manage_options')) wp_die('forbidden');
        check_admin_referer('ou_lc_actions');
        $detect = isset($_POST['detect_statuses']) ? (array) $_POST['detect_statuses'] : [];
        $this->save_settings(['detect_statuses' => $detect]);
        wp_safe_redirect(admin_url('tools.php?page=' . self::ADMIN_SLUG . '&updated=1'));
        exit;
    }

    public function handle_start_scan() {
        if (!current_user_can('manage_options')) wp_die('forbidden');
        check_admin_referer('ou_lc_actions');

        $start_url = isset($_POST['start_url']) ? esc_url_raw($_POST['start_url']) : home_url('/');
        if (!$start_url) $start_url = home_url('/');

        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 0;
        if ($limit < 0) $limit = 0;

        $state = [
            'running' => true,
            'queue'   => [$this->normalize_url($start_url)],
            'visited' => [],
            'started' => current_time('mysql'),
            'updated' => current_time('mysql'),
            'limit'   => $limit,
            'report_sent' => false,
        ];
        $this->save_state($state);

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, 'minute', self::CRON_HOOK);
        }
        wp_safe_redirect(admin_url('tools.php?page=' . self::ADMIN_SLUG));
        exit;
    }

    public function handle_stop_scan() {
        if (!current_user_can('manage_options')) wp_die('forbidden');
        check_admin_referer('ou_lc_actions');

        $this->unschedule_all();
        $state = $this->get_state();
        $state['running'] = false;
        $state['queue']   = [];
        $this->save_state($state);

        wp_safe_redirect(admin_url('tools.php?page=' . self::ADMIN_SLUG));
        exit;
    }

    public function handle_export_csv() {
        if (!current_user_can('manage_options')) wp_die('forbidden');
        check_admin_referer('ou_lc_actions');

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $ignored_statuses = $this->get_ignored_statuses();
        $ignored_sql = $ignored_statuses ? ' AND status NOT IN (' . implode(',', array_map('intval', $ignored_statuses)) . ')' : '';

        $rows = $wpdb->get_results(
            "SELECT checked_at, url, status, referer, type, error
             FROM `$table`
             WHERE (status >= 400 OR status IS NULL) $ignored_sql
             ORDER BY checked_at DESC",
            ARRAY_A
        );

        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=ou_link_errors_' . date('Ymd_His') . '.csv');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, ['checked_at', 'url', 'status', 'referer', 'type', 'error']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['checked_at'],
                $r['url'],
                $r['status'] !== null ? $r['status'] : 'ERR',
                $r['referer'],
                $r['type'],
                $r['error']
            ]);
        }
        fclose($out);
        exit;
    }

    /* ========== Cron ========== */
    public function ensure_cron_schedule() {
        // 毎分スケジュール追加
        add_filter('cron_schedules', function($schedules){
            if (!isset($schedules['minute'])) {
                $schedules['minute'] = ['interval' => 60, 'display' => __('Every Minute')];
            }
            return $schedules;
        });

        // 実行中なら毎分処理を予約
        $state = $this->get_state();
        if ($state['running'] && !wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, 'minute', self::CRON_HOOK);
        }

        // 日次キックオフ（初回は5分後）
        if (!wp_next_scheduled(self::DAILY_CRON_HOOK)) {
            wp_schedule_event(time() + 300, 'daily', self::DAILY_CRON_HOOK);
        }
    }

    private function unschedule_all() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        while ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
            $timestamp = wp_next_scheduled(self::CRON_HOOK);
        }
        // 日次は継続
    }

    public function daily_kickoff() {
        // 実行中なら開始しない
        $current = $this->get_state();
        if (!empty($current['running'])) return;

        $start_url = home_url('/');
        $state = [
            'running' => true,
            'queue'   => [$this->normalize_url($start_url)],
            'visited' => [],
            'started' => current_time('mysql'),
            'updated' => current_time('mysql'),
            'limit'   => intval(self::DAILY_LIMIT),
            'report_sent' => false,
        ];
        $this->save_state($state);

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, 'minute', self::CRON_HOOK);
        }
    }

    public function process_queue() {
        $state = $this->get_state();
        if (!$state['running']) return;

        $processed = 0;
        while ($processed < self::BATCH_SIZE && !empty($state['queue'])) {
            $url  = array_shift($state['queue']);
            $hash = md5($url);
            if (isset($state['visited'][$hash])) continue;
            $state['visited'][$hash] = true;

            // ページ取得
            $page = $this->fetch($url);
            if ($page['error']) {
                $this->record($url, null, 'href', null, $page['error']);
            } else {
                $status = $page['status'];
                if ($status >= 400) {
                    $this->record($url, $status, 'href', null, null);
                }
                $ctype = $page['content_type'];
                if (is_string($ctype) && stripos($ctype, 'text/html') !== false && !empty($page['body'])) {
                    $this->scan_html($url, $page['body'], $state);
                }
            }

            $processed++;

            if ($state['limit'] && count($state['visited']) >= $state['limit']) {
                $state['running'] = false;
                $state['queue']   = [];
                break;
            }
        }

        if (empty($state['queue'])) {
            $state['running'] = false;
            $this->unschedule_all();
            $this->maybe_send_report($state);
        }

        $this->save_state($state);
    }

    /* ========== HTML解析 ========== */
    private function scan_html($base_url, $html, &$state) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();
        if (!$loaded) return;

        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//a[@href]') as $a) {
            $href = trim($a->getAttribute('href'));
            $this->handle_found_url($href, $base_url, 'href', $state);
        }
        foreach ($xpath->query('//img[@src]') as $el) {
            $src = trim($el->getAttribute('src'));
            $this->handle_found_url($src, $base_url, 'img', $state);
        }
        foreach ($xpath->query('//script[@src]') as $el) {
            $src = trim($el->getAttribute('src'));
            $this->handle_found_url($src, $base_url, 'script', $state);
        }
        foreach ($xpath->query('//link[@rel="stylesheet" and @href]') as $el) {
            $href = trim($el->getAttribute('href'));
            $this->handle_found_url($href, $base_url, 'css', $state);
        }
    }

    private function handle_found_url($raw, $base_url, $type, &$state) {
        if ($raw === '' || $raw === '#') return;
        if (preg_match('#^(mailto:|tel:|javascript:)#i', $raw)) return;

        $abs = $this->to_absolute_url($raw, $base_url);
        if (!$abs) return;

        $abs = $this->normalize_url($abs);
        if (!$this->is_internal($abs)) return;
        if ($this->is_excluded($abs)) return;

        $st = $this->head_or_get_status($abs);
        if (!empty($st['error'])) {
            $this->record($abs, null, $type, $base_url, $st['error']);
        } elseif (isset($st['status']) && $st['status'] >= 400) {
            $this->record($abs, $st['status'], $type, $base_url, null);
        }

        if ($type === 'href' && !$this->looks_like_binary($abs)) {
            $hash = md5($abs);
            if (!isset($state['visited'][$hash]) && !in_array($abs, $state['queue'], true)) {
                $state['queue'][] = $abs;
            }
        }
    }

    /* ========== HTTPユーティリティ ========== */
    private function fetch($url) {
        $args = [
            'timeout'     => 10,
            'redirection' => 5,
            'sslverify'   => true,
            'user-agent'  => $this->ua(),
        ];
        $res = wp_remote_get($url, $args);
        if (is_wp_error($res)) {
            return ['status'=>null,'error'=>$res->get_error_message(),'body'=>null,'content_type'=>null];
        }
        $code  = wp_remote_retrieve_response_code($res);
        $body  = wp_remote_retrieve_body($res);
        $ctype = wp_remote_retrieve_header($res, 'content-type');
        return ['status'=>$code,'error'=>null,'body'=>$body,'content_type'=>$ctype];
    }

    private function head_or_get_status($url) {
        $head_args = [
            'timeout'     => 10,
            'redirection' => 5,
            'sslverify'   => true,
            'user-agent'  => $this->ua(),
            'method'      => 'HEAD',
        ];
        $res = wp_remote_request($url, $head_args);
        if (is_wp_error($res)) {
            return ['status'=>null,'error'=>$res->get_error_message()];
        }
        $code = wp_remote_retrieve_response_code($res);

        if (in_array($code, [405, 501], true) || $code === 0) {
            $get_args = [
                'timeout'     => 10,
                'redirection' => 5,
                'sslverify'   => true,
                'user-agent'  => $this->ua(),
            ];
            $res = wp_remote_get($url, $get_args);
            if (is_wp_error($res)) {
                return ['status'=>null,'error'=>$res->get_error_message()];
            }
            $code = wp_remote_retrieve_response_code($res);
        }
        return ['status'=>$code,'error'=>null];
    }

    private function ua() {
        return 'OfficeUeda-LinkChecker/' . self::VERSION . ' (+'. home_url('/') .')';
    }

    /* ========== URLユーティリティ ========== */
    private function to_absolute_url($url, $base) {
        if (preg_match('#^https?://#i', $url)) return $url;
        if (strpos($url, '//') === 0) {
            $scheme = is_ssl() ? 'https:' : 'http:'; return $scheme . $url;
        }
        if (strpos($url, '/') === 0) {
            $parts = wp_parse_url($base); if (!$parts) return null;
            $scheme = isset($parts['scheme']) ? $parts['scheme'] : 'https';
            $host   = $parts['host'] ?? $this->site_host;
            $port   = isset($parts['port']) ? ':' . $parts['port'] : '';
            return "{$scheme}://{$host}{$port}{$url}";
        }
        return $this->resolve_relative($base, $url);
    }

    private function resolve_relative($base, $rel) {
        $p = wp_parse_url($base);
        if (!$p || empty($p['scheme']) || empty($p['host'])) return null;
        $scheme = $p['scheme'];
        $host   = $p['host'];
        $port   = isset($p['port']) ? ':' . $p['port'] : '';
        $path   = isset($p['path']) ? $p['path'] : '/';

        if (substr($path, -1) !== '/') $path = dirname($path) . '/';
        $full  = $path . $rel;
        $segs  = explode('/', $full);
        $stack = [];
        foreach ($segs as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') array_pop($stack); else $stack[] = $seg;
        }
        $newpath = '/' . implode('/', $stack);
        return "{$scheme}://{$host}{$port}{$newpath}";
    }

    private function normalize_url($url) {
        return preg_replace('/#.*$/', '', $url); // フラグメント除去
    }

    private function is_internal($url) {
        $host = wp_parse_url($url, PHP_URL_HOST);
        return $host && (strcasecmp($host, $this->site_host) === 0);
    }

    private function is_excluded($url) {
        $path = wp_parse_url($url, PHP_URL_PATH);
        $defaults = [
            '#^/wp-admin/#',
            '#^/wp-login\.php$#',
            '#^/feed/#',
        ];
        $patterns = apply_filters('ou_lc_exclude_patterns', $defaults);
        foreach ($patterns as $re) {
            if (@preg_match($re, $path) && preg_match($re, $path)) return true;
        }
        return false;
    }

    private function looks_like_binary($url) {
        $path = wp_parse_url($url, PHP_URL_PATH);
        if (!$path) return false;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $bin = ['jpg','jpeg','png','gif','webp','svg','ico','css','js','pdf','zip','woff','woff2','ttf','eot','otf','mp4','webm','mp3'];
        return in_array($ext, $bin, true);
    }

    /* ========== 記録/通知 ========== */
    private function record($url, $status, $type, $referer = null, $error = null) {
        // 成功(2xx/3xx)は記録しない
        if ($error === null && $status !== null && $status < 400) return;

        // 除外ステータスは記録しない
        $ignored = $this->get_ignored_statuses();
        if ($status !== null && in_array((int)$status, $ignored, true)) return;

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        $wpdb->insert($table, [
            'referer'    => $referer,
            'url'        => $url,
            'status'     => $status,
            'type'       => $type,
            'error'      => $error,
            'checked_at' => current_time('mysql'),
        ], [
            '%s','%s','%d','%s','%s','%s'
        ]);
    }

    private function maybe_send_report(array $state) {
        if (!empty($state['report_sent'])) return;

        global $wpdb;
        $table   = $wpdb->prefix . self::TABLE_NAME;
        $started = isset($state['started']) ? $state['started'] : null;
        if (!$started) return;

        $ignored_statuses = $this->get_ignored_statuses();
        $ignored_sql = $ignored_statuses ? ' AND status NOT IN (' . implode(',', array_map('intval', $ignored_statuses)) . ')' : '';

        $sql_count = $wpdb->prepare(
            "SELECT COUNT(*) FROM `$table`
             WHERE ((status >= 400 $ignored_sql) OR status IS NULL)
               AND checked_at >= %s",
            $started
        );
        $count = (int) $wpdb->get_var($sql_count);

        if ($count <= 0) {
            $state['report_sent'] = true;
            $this->save_state($state);
            return;
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT checked_at, url, status, referer, type, error
                 FROM `$table`
                 WHERE ((status >= 400 $ignored_sql) OR status IS NULL)
                   AND checked_at >= %s
                 ORDER BY checked_at DESC
                 LIMIT 50",
                $started
            ),
            ARRAY_A
        );

        $by_status = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COALESCE(CAST(status AS CHAR), 'ERR') AS s, COUNT(*) AS c
                 FROM `$table`
                 WHERE ((status >= 400 $ignored_sql) OR status IS NULL)
                   AND checked_at >= %s
                 GROUP BY s
                 ORDER BY s ASC",
                $started
            ),
            ARRAY_A
        );

        $site_name = get_bloginfo('name');
        $site_url  = home_url('/');
        $subject   = sprintf('[内部リンクチェッカー] リンク切れ検出 (%s)', $site_name);

        $lines   = [];
        $lines[] = sprintf("サイト: %s (%s)", $site_name, $site_url);
        $lines[] = sprintf("走査開始: %s", $started);
        $lines[] = sprintf("検出件数: %d 件", $count);
        if ($by_status) {
            $lines[] = "内訳:";
            foreach ($by_status as $r) {
                $lines[] = sprintf("  - %s: %d", $r['s'], $r['c']);
            }
        }
        $lines[] = "";
        $lines[] = "▼直近のエラー例（最大50件）";
        foreach ($rows as $r) {
            $lines[] = sprintf(
                "- [%s] %s | status:%s | type:%s",
                mysql2date('Y-m-d H:i', $r['checked_at']),
                $r['url'],
                ($r['status'] !== null ? $r['status'] : 'ERR'),
                $r['type']
            );
            if (!empty($r['referer'])) $lines[] = "    referer: " . $r['referer'];
            if (!empty($r['error']))   $lines[] = "    error  : " . $r['error'];
        }
        $lines[] = "";
        $lines[] = "詳細/CSVエクスポートは 管理画面 > ツール > 内部リンクチェッカー をご利用ください。";

        $to       = get_option('admin_email');
        $headers  = ['Content-Type: text/plain; charset=UTF-8'];
        foreach (self::BCC_ADDRESSES as $bcc) {
            if (!empty($bcc)) $headers[] = 'Bcc: ' . $bcc;
        }

        $ok = wp_mail($to, $subject, implode("\n", $lines), $headers);

        $state['report_sent'] = true;
        $this->save_state($state);

        if (!$ok) {
            error_log('[OU LinkChecker] メール送信に失敗しました（To: ' . $to . '）');
        }
    }

    /* ========== WP-CLI（任意） ========== */
    public function cli_command($args, $assoc_args) {
        $start = isset($assoc_args['start']) ? $assoc_args['start'] : home_url('/');
        $limit = isset($assoc_args['limit']) ? intval($assoc_args['limit']) : 0;

        $state = [
            'running' => true,
            'queue'   => [$this->normalize_url($start)],
            'visited' => [],
            'started' => current_time('mysql'),
            'updated' => current_time('mysql'),
            'limit'   => $limit,
            'report_sent' => false,
        ];
        $this->save_state($state);

        \WP_CLI::log('Scanning start: ' . $start . ' limit: ' . ($limit ?: 'none'));
        while ($state['running']) {
            $this->process_queue();
            $state = $this->get_state();
            usleep(200000);
        }
        \WP_CLI::success('Scan finished.');
    }
}

new OfficeUeda_Internal_Link_Checker();
