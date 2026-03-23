<?php
/**
 * Plugin Name: OU Quiz Chat
 * Plugin URI:  https://example.com/
 * Description: 診断チャットLP用プラグイン（分岐なし・加点式）。同意ゲート、質問管理、結果レンジ、メール送信、計測・保持など。
 * Version:     1.0.0
 * Author:      Office Ueda
 * Author URI:  https://officeueda.com/
 * Text Domain: ou-quiz-chat
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ─────────────────────────────────────────────────────────────
 *  環境チェック（PHP / WP）
 * ─────────────────────────────────────────────────────────────
 */
const OUQ_MIN_PHP = '8.0';
const OUQ_MIN_WP  = '6.2';

if (version_compare(PHP_VERSION, OUQ_MIN_PHP, '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>OU Quiz Chat: PHP '
            . esc_html(OUQ_MIN_PHP) . ' 以上が必要です。現在: '
            . esc_html(PHP_VERSION) . '</p></div>';
    });
    return;
}

global $wp_version;
if (isset($wp_version) && version_compare($wp_version, OUQ_MIN_WP, '<')) {
    add_action('admin_notices', function () use ($wp_version) {
        echo '<div class="notice notice-error"><p>OU Quiz Chat: WordPress '
            . esc_html(OUQ_MIN_WP) . ' 以上が必要です。現在: '
            . esc_html($wp_version) . '</p></div>';
    });
    return;
}

/**
 * ─────────────────────────────────────────────────────────────
 *  定数
 * ─────────────────────────────────────────────────────────────
 */
define('OUQ_VERSION',        '1.0.0');
define('OUQ_FILE',           __FILE__);
define('OUQ_DIR',            plugin_dir_path(__FILE__));
define('OUQ_URL',            plugin_dir_url(__FILE__));
define('OUQ_BASENAME',       plugin_basename(__FILE__));
define('OUQ_NS',             'OU\\QuizChat');
define('OUQ_OPTION_KEY',     'ouq_settings');         // 設定を1配列に集約
define('OUQ_CAP_EDIT',       'manage_ou_quiz');       // 設定編集用
define('OUQ_CAP_VIEW_LOGS',  'view_ou_quiz_logs');    // ログ閲覧用（後段で使用）
define('OUQ_UNINSTALL_FLAG', 'ouq_uninstall_purge');  // 削除時に全消去するか（true/false）

/**
 * ─────────────────────────────────────────────────────────────
 *  PSR-4 風オートローダ（Composerなし運用）
 * ─────────────────────────────────────────────────────────────
 */
spl_autoload_register(function ($class) {
    if (strpos($class, OUQ_NS . '\\') !== 0) {
        return;
    }
    $rel = substr($class, strlen(OUQ_NS . '\\'));       // Core\Plugin など
    $rel = str_replace('\\', DIRECTORY_SEPARATOR, $rel);
    $file = OUQ_DIR . 'inc/' . $rel . '.php';
    if (is_readable($file)) {
        require_once $file;
    }
});

/**
 * ─────────────────────────────────────────────────────────────
 *  起動：i18n / フック登録 / 既定オプションの投入
 * ─────────────────────────────────────────────────────────────
 */
add_action('plugins_loaded', function () {
    load_plugin_textdomain('ou-quiz-chat', false, dirname(OUQ_BASENAME) . '/languages');

    // Core\Plugin が存在すれば初期化（後続の実装で中身を足していく）
    if (class_exists(OUQ_NS . '\\Core\\Plugin')) {
        (new (OUQ_NS . '\\Core\\Plugin')())->boot();
    }
});

/**
 * 有効化フック：既定オプション投入 / 権限付与
 */
register_activation_hook(__FILE__, function () {
    // 既定オプション（要件定義 v1.4 に準拠）
    $defaults = [
        'consent_counts_in_progress' => false, // 同意を進捗に含めない（既定）
        'policy_url'                 => '',
        'brand_color'                => '#2563eb',
        'user_copy_enabled'          => true,  // 本人控えメール
        'admin_recipients'           => [],    // To/CC/BCC を後で設定
        'utm_cookie_days'            => 90,
        'failed_queue_days'          => 30,
        'db_persist_enabled'         => false, // 応募データは既定で保存しない
        'post_send_reservation'      => [
            'enabled'     => false,
            'url'         => '',
            'label'       => '無料相談を予約する',
            'note'        => '',
            'score_bands' => [], // 表示条件（空=常時）
        ],
        'pre_input_notice'           => [
            'enabled' => true,
            'text'    => '診断結果をメールでお送りします。続いてお名前とご連絡先の入力をお願いします。',
        ],
        'post_send_notice'           => [
            'success_enabled' => true,
            'success_text'    => '診断結果を送信しました。{email} 宛てのメールをご確認ください。届かない場合は迷惑メールもご確認ください。',
            'failure_enabled' => true,
            'failure_text'    => '送信に失敗しました。お手数ですが時間をおいて再度お試しください。',
        ],
        OUQ_UNINSTALL_FLAG           => false, // アンインストール時は既定で残す
    ];

    $current = get_option(OUQ_OPTION_KEY);
    if (!is_array($current)) {
        add_option(OUQ_OPTION_KEY, $defaults, '', false);
    } else {
        // 既存に欠けているキーだけ補完（上書きはしない）
        $merged = $current + $defaults;
        update_option(OUQ_OPTION_KEY, $merged, false);
    }

    // 権限ロール：管理者にカスタム権限を付与
    if ($role = get_role('administrator')) {
        $role->add_cap(OUQ_CAP_EDIT);
        $role->add_cap(OUQ_CAP_VIEW_LOGS);
    }
});

/**
 * 無効化フック：今回は何もしない（将来、wp_cron解除などを追加可）
 */
register_deactivation_hook(__FILE__, function () {
    // no-op
});

/**
 * 管理画面：設定ページのプレースホルダ（最小）
 * - 後段で Admin\SettingsPage に置き換える
 */
add_action('admin_menu', function () {
    add_menu_page(
        __('OU Quiz Chat', 'ou-quiz-chat'),
        __('OU Quiz Chat', 'ou-quiz-chat'),
        OUQ_CAP_EDIT,
        'ou-quiz-chat',
        function () {
            echo '<div class="wrap"><h1>OU Quiz Chat</h1><p>初期セットアップは完了しています。詳細設定・UIは順次追加します。</p></div>';
        },
        'dashicons-format-chat',
        65
    );
});
