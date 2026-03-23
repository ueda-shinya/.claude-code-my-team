<?php

/**
 * Plugin Name: Ban Specific Plugins (MU)
 * Description: 特定プラグインのインストール／有効化／検索表示／ZIPアップロードを禁止します（サイト単体・マルチサイト両対応）
 * Author: OfficeUEDA / ChatGPT
 * Version: 1.0.0
 */

// ==== 設定（ここを編集）==========================================
/**
 * プラグインファイルパスで禁止
 * 例: hello-dolly/hello.php, akismet/akismet.php
 */
$BANNED_PLUGINS = [
    // 既存
    'hello-dolly/hello.php'         => '運用ポリシーにより禁止',
    'akismet/akismet.php'           => '個人情報保護の観点で不許可',

    // ▼ File Manager 系（代表的なものを網羅）
    'wp-file-manager/wp-file-manager.php'        => 'セキュリティ上の理由でファイルマネージャ系は禁止',
    'file-manager-advanced/fm-advanced.php'      => 'セキュリティ上の理由でファイルマネージャ系は禁止',
    'file-manager/file-manager.php'              => 'セキュリティ上の理由でファイルマネージャ系は禁止',
    'advanced-file-manager/advanced-file-manager.php' => 'セキュリティ上の理由でファイルマネージャ系は禁止',
];

/**
 * スラッグで禁止（検索/詳細/ZIP名/展開ディレクトリ名の判定に使用）
 * 例: hello-dolly, akismet
 */
$BANNED_SLUGS = [
    // 既存
    'hello-dolly',
    'akismet',

    // ▼ File Manager 系
    'wp-file-manager',
    'file-manager-advanced',
    'file-manager',
    'advanced-file-manager',
];
// ===============================================================


if (!defined('ABSPATH')) {
    exit;
}

class OU_Ban_Specific_Plugins
{
    private static $instance;
    private $banned_plugins;
    private $banned_slugs;
    private $deactivated_now = [];

    private function __construct(array $banned_plugins, array $banned_slugs)
    {
        $this->banned_plugins = $banned_plugins;
        $this->banned_slugs   = $banned_slugs;

        // 1) インストールの前段でブロック（ダウンロードURL段階）
        add_filter('upgrader_pre_download', [$this, 'block_by_download_url'], 10, 3);

        // 2) 展開されたソースのディレクトリ名でブロック
        add_filter('upgrader_source_selection', [$this, 'block_by_source_dir'], 10, 4);

        // 3) プラグイン検索結果から非表示
        add_filter('plugins_api_result', [$this, 'filter_plugins_search_results'], 10, 3);

        // 4) 詳細モーダル（plugin_information）自体をブロック
        add_filter('plugins_api', [$this, 'block_plugin_information'], 10, 3);

        // 5) 有効化を阻止（サイト単体）
        add_filter('pre_update_option_active_plugins', [$this, 'strip_banned_from_active'], 10, 2);

        // 6) 有効化を阻止（ネットワーク有効化）
        add_filter('pre_update_site_option_active_sitewide_plugins', [$this, 'strip_banned_from_network_active'], 10, 2);

        // 7) 既に有効なものを強制無効化（管理画面アクセス時）
        add_action('admin_init', [$this, 'force_deactivate_if_active']);

        // 8) プラグイン一覧の「有効化」等のリンクを消す
        add_filter('plugin_action_links', [$this, 'remove_activate_links'], 10, 4);
        add_filter('network_admin_plugin_action_links', [$this, 'remove_activate_links'], 10, 4);

        // 9) ZIPアップロードの段階でファイル名から弾く（念のため）
        add_filter('wp_handle_upload_prefilter', [$this, 'block_by_zip_filename']);

        // 10) 無効化したときの管理画面通知
        add_action('admin_notices', [$this, 'admin_notice_deactivated']);
        add_action('network_admin_notices', [$this, 'admin_notice_deactivated']);
    }

    public static function init(array $banned_plugins, array $banned_slugs)
    {
        if (!self::$instance) {
            self::$instance = new self($banned_plugins, $banned_slugs);
        }
        return self::$instance;
    }

    // ----------------- ユーティリティ -----------------
    private function is_banned_slug($slug)
    {
        $slug = trim(strtolower($slug));
        return in_array($slug, array_map('strtolower', $this->banned_slugs), true);
    }

    private function is_banned_plugin_file($plugin_file)
    {
        $plugin_file = trim(strtolower($plugin_file));
        return array_key_exists($plugin_file, array_change_key_case($this->banned_plugins, CASE_LOWER));
    }

    private function banned_reason_by_file($plugin_file)
    {
        $lower = array_change_key_case($this->banned_plugins, CASE_LOWER);
        return $lower[$plugin_file] ?? 'このプラグインは組織ポリシーにより禁止されています。';
    }

    private function guess_slug_from_url($url)
    {
        // 例: https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip
        if (preg_match('~/plugin/([^/]+)\.zip$~i', $url, $m)) {
            return strtolower($m[1]);
        }
        // 例: https://wordpress.org/plugins/hello-dolly/
        if (preg_match('~/plugins/([^/]+)/?~i', $url, $m)) {
            return strtolower($m[1]);
        }
        // 例: 任意のURL中の xxx.zip
        if (preg_match('~/([^/]+)\.zip$~i', $url, $m)) {
            return strtolower($m[1]);
        }
        return '';
    }

    private function guess_slug_from_path($path)
    {
        // 展開先ディレクトリ名（/tmp/wordpress-XXXX/hello-dolly/）などから
        $base = basename($path);
        return strtolower($base);
    }

    private function translate_banned_message($slug_or_file, $by = 'slug')
    {
        $msg = 'このプラグインは組織ポリシーにより禁止されています。';
        if ($by === 'file' && $this->is_banned_plugin_file($slug_or_file)) {
            $msg = $this->banned_reason_by_file($slug_or_file);
        } elseif ($by === 'slug' && $this->is_banned_slug($slug_or_file)) {
            $msg = 'このプラグインは組織ポリシーにより禁止されています。（スラッグ: ' . esc_html($slug_or_file) . '）';
        }
        return new WP_Error('ou_banned_plugin', $msg);
    }

    // ----------------- 1) ダウンロードURLでブロック -----------------
    public function block_by_download_url($reply, $package, $upgrader)
    {
        if (is_wp_error($reply)) {
            return $reply;
        }
        $slug = $this->guess_slug_from_url($package);
        if ($slug && $this->is_banned_slug($slug)) {
            return $this->translate_banned_message($slug, 'slug');
        }
        return $reply;
    }

    // ----------------- 2) 展開ソースのディレクトリ名でブロック -----------------
    public function block_by_source_dir($source, $remote_source, $upgrader, $hook_extra)
    {
        $slug = $this->guess_slug_from_path($source);
        if ($slug && $this->is_banned_slug($slug)) {
            return $this->translate_banned_message($slug, 'slug');
        }
        return $source;
    }

    // ----------------- 3) プラグイン検索結果から除去 -----------------
    public function filter_plugins_search_results($res, $action, $args)
    {
        if (is_wp_error($res) || empty($res) || empty($res->plugins)) {
            return $res;
        }
        $res->plugins = array_values(array_filter($res->plugins, function ($p) {
            $slug = isset($p->slug) ? strtolower($p->slug) : '';
            return $slug && !$this->is_banned_slug($slug);
        }));
        // total を再計算
        if (isset($res->info['results'])) {
            $res->info['results'] = count($res->plugins);
        }
        return $res;
    }

    // ----------------- 4) 詳細モーダル自体をブロック -----------------
    public function block_plugin_information($res, $action, $args)
    {
        if ($action === 'plugin_information' && !empty($args->slug)) {
            $slug = strtolower($args->slug);
            if ($this->is_banned_slug($slug)) {
                return $this->translate_banned_message($slug, 'slug');
            }
        }
        return $res;
    }

    // ----------------- 5) 6) 有効化の保存段階で除去 -----------------
    public function strip_banned_from_active($new_value, $old_value)
    {
        if (!is_array($new_value)) {
            return $new_value;
        }
        $lower_keys = array_change_key_case($this->banned_plugins, CASE_LOWER);
        $new_value = array_values(array_filter($new_value, function ($plugin_file) use ($lower_keys) {
            return !array_key_exists(strtolower($plugin_file), $lower_keys);
        }));
        return $new_value;
    }

    public function strip_banned_from_network_active($new_value, $old_value)
    {
        if (!is_array($new_value)) {
            return $new_value;
        }
        foreach (array_keys($new_value) as $plugin_file) {
            if ($this->is_banned_plugin_file($plugin_file)) {
                unset($new_value[$plugin_file]);
            }
        }
        return $new_value;
    }

    // ----------------- 7) 既に有効なものを強制無効化 -----------------
    public function force_deactivate_if_active()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        // サイト単体
        if (!is_multisite()) {
            foreach ($this->banned_plugins as $plugin_file => $reason) {
                if (is_plugin_active($plugin_file)) {
                    deactivate_plugins($plugin_file, true); // サイレント
                    $this->deactivated_now[] = $plugin_file;
                }
            }
        } else {
            // マルチサイト: ネットワーク有効化も解除
            foreach ($this->banned_plugins as $plugin_file => $reason) {
                if (is_plugin_active_for_network($plugin_file)) {
                    deactivate_plugins($plugin_file, true, true);
                    $this->deactivated_now[] = $plugin_file;
                }
                // 個別サイトで有効なケースにも対応
                if (is_plugin_active($plugin_file)) {
                    deactivate_plugins($plugin_file, true);
                    $this->deactivated_now[] = $plugin_file;
                }
            }
        }

        if ($this->deactivated_now) {
            set_transient('ou_banned_plugins_deactivated', $this->deactivated_now, 60);
        }
    }

    // ----------------- 8) 一覧のアクションリンクを削除 -----------------
    public function remove_activate_links($actions, $plugin_file, $plugin_data, $context)
    {
        if ($this->is_banned_plugin_file($plugin_file)) {
            unset($actions['activate']);
            unset($actions['activate-network']);
            // インストール済みでも削除は任意。必要なら下行をコメント解除。
            // unset($actions['delete']);
            // 情報リンク/詳細画面に飛ばせないように "View details" 相当も除去
            unset($actions['plugin_information']);
            // 禁止メモを表示
            $reason = esc_html($this->banned_reason_by_file($plugin_file));
            $actions['ou_banned'] = '<span style="color:#d63638;">禁止中: ' . $reason . '</span>';
        }
        return $actions;
    }

    // ----------------- 9) ZIPアップロード名でブロック -----------------
    public function block_by_zip_filename($file)
    {
        if (empty($file['name'])) {
            return $file;
        }
        $name = strtolower($file['name']);
        if (substr($name, -4) === '.zip') {
            foreach ($this->banned_slugs as $slug) {
                if (strpos($name, strtolower($slug) . '.zip') !== false || strpos($name, '/' . $slug . '.zip') !== false) {
                    $file['error'] = 'このZIPは禁止されているプラグインです（' . $slug . '）。';
                    return $file;
                }
            }
        }
        return $file;
    }

    // ----------------- 10) 無効化通知 -----------------
    public function admin_notice_deactivated()
    {
        $list = get_transient('ou_banned_plugins_deactivated');
        if (!$list || !is_array($list)) {
            return;
        }
        delete_transient('ou_banned_plugins_deactivated');

        echo '<div class="notice notice-error"><p>';
        echo '禁止プラグインを検出したため、以下を無効化しました：';
        echo '<ul style="margin:6px 0 0 18px;">';
        foreach ($list as $pf) {
            $reason = esc_html($this->banned_reason_by_file($pf));
            echo '<li><code>' . esc_html($pf) . '</code> — ' . $reason . '</li>';
        }
        echo '</ul>';
        echo '</p></div>';
    }
}

// 初期化
OU_Ban_Specific_Plugins::init($BANNED_PLUGINS, $BANNED_SLUGS);
