<?php

/**
 * Plugin Name: Weekly Update Notifier (MU)
 * Description: 週1回、未更新のWPコア／プラグイン／テーマがあれば、管理者宛にPHPバージョンと要更新一覧をメール通知します（MUプラグイン）。
 * Author: OfficeUEDA
 * Version: 1.0.0
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

class OfficeUeda_Weekly_Update_Notifier
{

    const CRON_HOOK = 'officeueda_weekly_update_notifier_event';
    const OPTION_LAST_SENT = 'officeueda_weekly_update_notifier_last_sent';
    const BCC_ADDRESSES = ['web-admin@officeueda.com'];

    public function __construct()
    {
        // 1) “weekly”間隔を追加
        add_filter('cron_schedules', [$this, 'add_weekly_schedule']);

        // 2) 初期スケジュール（MUはactivationがないのでinitで保証）
        add_action('init', [$this, 'schedule_event']);

        // 3) 本処理
        add_action(self::CRON_HOOK, [$this, 'run_notifier']);

        // 4) 管理画面から即時テストできるように（管理者のみ、?ou_wun_test=1）
        add_action('admin_init', [$this, 'maybe_run_manual_test']);
    }

    public function add_weekly_schedule($schedules)
    {
        if (!isset($schedules['weekly'])) {
            // 1週間 = 7 * 24 * 60 * 60
            $schedules['weekly'] = [
                'interval' => 7 * 24 * 60 * 60,
                'display'  => __('Once Weekly', 'officeueda')
            ];
        }
        return $schedules;
    }

    public function schedule_event()
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            /**
             * 初回実行タイミング：
             * - 「今日の午前 9:00（WordPressのタイムゾーン）」がまだならそこ
             * - 過ぎていれば 1時間後
             *
             * 以後は weekly 間隔で実行
             */
            $tz   = wp_timezone();
            $now  = new DateTime('now', $tz);
            $next = new DateTime('today 9:00', $tz);
            if ($now >= $next) {
                $next = (clone $now)->modify('+1 hour');
            }
            wp_schedule_event($next->getTimestamp(), 'weekly', self::CRON_HOOK);
        }
    }

    public function run_notifier()
    {
        // 最新の更新情報を取得（必要に応じて update.php をロード）
        if (!function_exists('wp_update_plugins') || !function_exists('wp_update_themes') || !function_exists('wp_version_check')) {
            require_once ABSPATH . WPINC . '/update.php';
        }

        // 更新情報を強制リフレッシュ
        wp_version_check();   // コア
        wp_update_plugins();  // プラグイン
        wp_update_themes();   // テーマ

        // 取得
        $core_updates    = get_site_transient('update_core');
        $plugin_updates  = get_site_transient('update_plugins');
        $theme_updates   = get_site_transient('update_themes');

        $needs = [
            'core'    => $this->collect_core_updates($core_updates),
            'plugins' => $this->collect_plugin_updates($plugin_updates),
            'themes'  => $this->collect_theme_updates($theme_updates),
        ];

        // 1件もなければ送らない（ノイズ削減）
        if (empty($needs['core']) && empty($needs['plugins']) && empty($needs['themes'])) {
            return;
        }

        $admin_email = $this->get_admin_email();
        if (!$admin_email) {
            return; // 管理者メール未設定
        }

        $subject = sprintf(
            '[%s] 週次アップデート通知：更新あり（%s）',
            wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
            wp_parse_url(home_url(), PHP_URL_HOST)
        );

        // 本文
        $body_lines = [];
        $body_lines[] = '以下のコンポーネントに更新があります。';
        $body_lines[] = '・サイトが表示されなくなる。';
        $body_lines[] = '・外部からの攻撃を受けやすくなる。';
        $body_lines[] = 'などのリスクが懸念される状態です。';
        $body_lines[] = '早急にご対応ください。';
        $body_lines[] = '';
        $body_lines[] = 'トラブル等のご相談は、オフィスウエダまで';
        $body_lines[] = 'お問い合わせください。';
        $body_lines[] = 'https://officeueda.com/contact?utm_source=muplugin&utm_medium=weekly_update_notifier&utm_campaign=alarm&utm_content=auto';
        $body_lines[] = '';
        $body_lines[] = 'サイト: ' . home_url('/');
        $body_lines[] = 'サイト名: ' . get_bloginfo('name');
        $body_lines[] = 'PHP: ' . PHP_VERSION;
        $body_lines[] = 'WordPress: ' . get_bloginfo('version');
        $body_lines[] = '';

        if (!empty($needs['core'])) {
            $body_lines[] = '=== WordPressコア ===';
            foreach ($needs['core'] as $c) {
                $body_lines[] = sprintf(
                    '- 現在: %s → 新: %s (%s)',
                    $c['current'],
                    $c['new_version'],
                    $c['locale'] ?? 'locale: n/a'
                );
            }
            $body_lines[] = '';
        }

        if (!empty($needs['plugins'])) {
            $body_lines[] = '=== プラグイン ===';
            foreach ($needs['plugins'] as $p) {
                $body_lines[] = sprintf(
                    '- %s (%s): %s → %s',
                    $p['name'],
                    $p['slug'],
                    $p['current_version'],
                    $p['new_version']
                );
            }
            $body_lines[] = '';
        }

        if (!empty($needs['themes'])) {
            $body_lines[] = '=== テーマ ===';
            foreach ($needs['themes'] as $t) {
                $body_lines[] = sprintf(
                    '- %s: %s → %s',
                    $t['name'],
                    $t['current_version'],
                    $t['new_version']
                );
            }
            $body_lines[] = '';
        }

        $body_lines[] = '—';
        $body_lines[] = '本メールは自動送信されています。';
        $body_lines[] = '通知は「未更新がある場合のみ」週1回送信されます。';

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        foreach (self::BCC_ADDRESSES as $bcc) {
            $headers[] = 'Bcc: ' . $bcc;
        }

        // 二重送信ガード（同一日に複数回実行されても1回に）
        $today = current_time('Y-m-d');
        $last  = get_option(self::OPTION_LAST_SENT);
        if ($last === $today) {
            return;
        }

        $sent = wp_mail($admin_email, $subject, implode("\n", $body_lines), $headers);
        if ($sent) {
            update_option(self::OPTION_LAST_SENT, $today, false);
        }
    }

    private function collect_core_updates($core_updates)
    {
        $list = [];
        if (!is_object($core_updates) || empty($core_updates->updates) || !is_array($core_updates->updates)) {
            return $list;
        }
        foreach ($core_updates->updates as $u) {
            if (!empty($u->response) && in_array($u->response, ['upgrade', 'latest'], true)) {
                $current_version = get_bloginfo('version');
                $new_version = $u->current ?? ($u->version ?? '');

                // ★ バージョン番号が完全一致ならスキップ（翻訳更新は除外）
                if ($new_version === $current_version) {
                    continue;
                }

                $list[] = [
                    'current'     => $current_version,
                    'new_version' => $new_version,
                    'locale'      => $u->locale ?? '',
                ];
            }
        }
        return $list;
    }

    private function collect_plugin_updates($plugin_updates)
    {
        $list = [];
        if (!is_object($plugin_updates) || empty($plugin_updates->response) || !is_array($plugin_updates->response)) {
            return $list;
        }

        // インストール済プラグイン情報
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $installed = get_plugins();

        foreach ($plugin_updates->response as $file => $data) {
            // $data は Plugin_Upgrader のレスポンスオブジェクト
            $slug    = isset($data->slug) ? $data->slug : $file;
            $new_ver = $data->new_version ?? '';
            $name    = $installed[$file]['Name'] ?? ($data->name ?? $slug);
            $cur_ver = $installed[$file]['Version'] ?? '';

            $list[] = [
                'slug'            => $slug,
                'name'            => $name,
                'current_version' => $cur_ver,
                'new_version'     => $new_ver,
            ];
        }
        return $list;
    }

    private function collect_theme_updates($theme_updates)
    {
        $list = [];
        if (!is_object($theme_updates) || empty($theme_updates->response) || !is_array($theme_updates->response)) {
            return $list;
        }

        // インストール済テーマ
        if (!function_exists('wp_get_themes')) {
            require_once ABSPATH . 'wp-includes/theme.php';
        }
        $installed = wp_get_themes();

        foreach ($theme_updates->response as $stylesheet => $data) {
            $theme   = isset($installed[$stylesheet]) ? $installed[$stylesheet] : null;
            $name    = $theme ? $theme->get('Name') : ($data['name'] ?? $stylesheet);
            $cur_ver = $theme ? $theme->get('Version') : '';
            $new_ver = $data['new_version'] ?? '';

            $list[] = [
                'stylesheet'      => $stylesheet,
                'name'            => $name,
                'current_version' => $cur_ver,
                'new_version'     => $new_ver,
            ];
        }
        return $list;
    }

    private function get_admin_email()
    {
        // マルチサイトならネットワーク管理者を優先、なければサイト管理者
        if (is_multisite()) {
            $email = get_site_option('admin_email');
            if (!empty($email)) return $email;
        }
        return get_option('admin_email');
    }

    /**
     * 管理者ログイン中に `?ou_wun_test=1` を付けてアクセスすると、即時実行テストできます。
     * 例: /wp-admin/?ou_wun_test=1
     */
    public function maybe_run_manual_test()
    {
        if (!current_user_can('manage_options')) return;
        if (!isset($_GET['ou_wun_test'])) return;
        $this->run_notifier();
        wp_safe_redirect(remove_query_arg('ou_wun_test'));
        exit;
    }
}

new OfficeUeda_Weekly_Update_Notifier();
