<?php
// inc/Core/Plugin.php
namespace OU\QuizChat\Core;

if (!defined('ABSPATH')) {
    exit;
}

use OU\QuizChat\Core\Options;
use OU\QuizChat\Core\Assets;

class Plugin
{
    /** @var Options */
    private $options;

    /** メニューのスラッグ（固定） */
    public const MENU_SLUG = 'ou-quiz-chat';

    public function __construct()
    {
        $this->options = new Options();
    }

    /**
     * 起動：フック登録
     */
    public function boot(): void
    {
        // フロント/管理のアセット読み込み
        add_action('wp_enqueue_scripts', [Assets::class, 'enqueue_front']);
        add_action('admin_enqueue_scripts', [Assets::class, 'enqueue_admin']);

        // 設定ページ（Admin\SettingsPage に委譲。未導入時はフォールバック）
        add_action('admin_menu', [$this, 'register_menu'], 9);

        // CSP対応：REST Nonce 等を <meta> で供給（no inline）
        add_action('wp_head', [$this, 'inject_head_meta'], 2);

        // ショートコード登録（初期はプレースホルダ）
        add_action('init', [$this, 'register_shortcodes']);

        // i18n（念のため init でもロード）
        add_action('init', function () {
            load_plugin_textdomain('ou-quiz-chat', false, dirname(OUQ_BASENAME) . '/languages');
        });

        // 質問管理 REST コントローラ（1回だけ初期化）
        if (class_exists(OUQ_NS . '\\Admin\\QuestionsController')) {
            new (\OU\QuizChat\Admin\QuestionsController());
        }

        // 既存の QuestionsController に続けて ResultsController も初期化
        if (class_exists(OUQ_NS . '\\Admin\\ResultsController')) {
            new (\OU\QuizChat\Admin\ResultsController());
        }
        
        // 送信REST
        if (class_exists(OUQ_NS . '\\Frontend\\SubmitController')) {
            new (\OU\QuizChat\Frontend\SubmitController());
        }

        // メール設定 REST
        if (class_exists(OUQ_NS . '\\Admin\\MailSettingsController')) {
        new (\OU\QuizChat\Admin\MailSettingsController());
        }
    }

    /**
     * 設定メニュー登録（Admin\SettingsPage があれば委譲）
     */
    public function register_menu(): void
    {
        if (class_exists(OUQ_NS . '\\Admin\\SettingsPage')) {
            (new (\OU\QuizChat\Admin\SettingsPage()))->register();
            return;
        }

        // フォールバック（未導入時）
        add_menu_page(
            __('OU Quiz Chat', 'ou-quiz-chat'),
            __('OU Quiz Chat', 'ou-quiz-chat'),
            OUQ_CAP_EDIT,
            self::MENU_SLUG,
            [$this, 'render_settings_placeholder'],
            'dashicons-format-chat',
            65
        );
    }

    /**
     * プレースホルダ（後で本実装に置換）
     */
    public function render_settings_placeholder(): void
    {
        if (!current_user_can(OUQ_CAP_EDIT)) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'ou-quiz-chat'));
        }
        echo '<div class="wrap"><h1>OU Quiz Chat</h1>';
        echo '<p>' . esc_html__('Initial setup is complete. Detailed settings UI will be added in subsequent steps.', 'ou-quiz-chat') . '</p>';
        echo '</div>';
    }

    /**
     * REST用 Nonce / ルートなどを <meta> で供給（no inline JS を維持）
     */
    public function inject_head_meta(): void
    {
        // 軽量なので常時出力（必要なら is_singular などで条件分岐可）
        $rest_nonce = wp_create_nonce('wp_rest');
        $meta = [
            ['ouq-rest-nonce', $rest_nonce],
            ['ouq-rest-root', esc_url_raw(get_rest_url())],
            ['ouq-ns', 'ou-quiz-chat'],
            // 安全なフラグのみ露出
            ['ouq-consent-in-progress', $this->options->get_flag('consent_counts_in_progress') ? '1' : '0'],
        ];
        foreach ($meta as [$name, $content]) {
            printf(
                '<meta name="%s" content="%s" />' . "\n",
                esc_attr($name),
                esc_attr($content)
            );
        }
    }

    /**
     * ショートコード登録（後段で templates/frontend/chat.php を組み込み）
     */
    public function register_shortcodes(): void
    {
        add_shortcode('quiz_chat', function ($atts = []) {
            ob_start();
            $tpl = OUQ_DIR . 'templates/frontend/chat.php';
            if (is_readable($tpl)) {
                include $tpl;
            } else {
                // フォールバック（テンプレート未配置時）
                echo '<div class="ouq-chat js-ouq-root" data-ouq="ready">';
                echo '<div class="ouq-chat__placeholder">';
                echo esc_html__('OU Quiz Chat will appear here.', 'ou-quiz-chat');
                echo '</div></div>';
            }
            return ob_get_clean();
        });
    }
}
