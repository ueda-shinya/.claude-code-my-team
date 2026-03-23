<?php
// Assets.php
namespace OU\QuizChat\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSS/JSの読み込み
 * - フロント：ショートコードDOMがある可能性のあるページで（当面は全ページ、後で最適化）
 * - 管理画面：当プラグインの設定ページのみ
 * - いずれも "no inline" 方針を維持（localize_scriptは使わない）
 */
class Assets
{
    public static function enqueue_front(): void
    {
        // CSS
        wp_register_style(
            'ouq-frontend',
            OUQ_URL . 'assets/css/frontend.css',
            [],
            OUQ_VERSION
        );
        wp_enqueue_style('ouq-frontend');

        // JS
        wp_register_script(
            'ouq-frontend',
            OUQ_URL . 'assets/js/frontend.js',
            [],
            OUQ_VERSION,
            true // footer
        );
        // defer属性を付与（WPは自動付与しないため、filterでタグを書き換え）
        add_filter('script_loader_tag', [self::class, 'add_defer'], 10, 3);
        wp_enqueue_script('ouq-frontend');
    }

    public static function enqueue_admin($hook = ''): void
    {
        // 当プラグインの設定ページだけで読み込む
        if ($hook !== 'toplevel_page_ou-quiz-chat') {
            return;
        }

        wp_register_style(
            'ouq-admin',
            OUQ_URL . 'assets/css/admin.css',
            [],
            OUQ_VERSION
        );
        wp_enqueue_style('ouq-admin');

        wp_register_script(
            'ouq-admin',
            OUQ_URL . 'assets/js/admin.js',
            [],
            OUQ_VERSION,
            true
        );
        add_filter('script_loader_tag', [self::class, 'add_defer'], 10, 3);
        wp_enqueue_script('ouq-admin');
    }

    /**
     * deferを付与（このプラグインのスクリプトに限定）
     */
    public static function add_defer(string $tag, string $handle, string $src): string
    {
        if ($handle === 'ouq-frontend' || $handle === 'ouq-admin') {
            // type属性はWPが自動、asyncは使わずdefer
            $tag = sprintf('<script src="%s" id="%s-js" defer></script>' . "\n",
                esc_url($src),
                esc_attr($handle)
            );
        }
        return $tag;
    }
}
