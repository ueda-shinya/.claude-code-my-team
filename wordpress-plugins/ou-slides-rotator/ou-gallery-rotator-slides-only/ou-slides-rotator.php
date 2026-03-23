<?php
/**
 * Plugin Name: OU Slides Rotator
 * Description: .slides 配下の .slide を順番にフェード切替（中央重ね表示）。Diviの行(.et_pb_row)に .slides、画像モジュールに .slide を付与した現在のHTML構造に最適化。
 * Version: 1.2.0
 * Author: Office Ueda
 * License: GPL-2.0+
 * Text Domain: ou-slides-rotator
 */

if (!defined('ABSPATH')) exit;

final class OU_Slides_Rotator {
    const VER         = '1.2.0';
    const HANDLE_CSS  = 'ousr-style';
    const HANDLE_JS   = 'ousr-script';

    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function enqueue_assets() {
        if (is_admin()) return;

        $base = plugin_dir_url(__FILE__) . 'assets/';

        wp_enqueue_style(
            self::HANDLE_CSS,
            $base . 'css/ou-slides-rotator.css',
            [],
            self::VER
        );

        wp_enqueue_script(
            self::HANDLE_JS,
            $base . 'js/ou-slides-rotator.js',
            [],
            self::VER,
            true
        );

        // この版は .slides / .slide のみを対象にし、副作用を避けます
        wp_localize_script(self::HANDLE_JS, 'OU_SLIDES_ROTATOR_DEFAULTS', [
            'interval'   => 3500, // 1枚の表示時間(ms)
            'fade'       => 1200, // フェード時間(ms)
            'minItems'   => 2,    // 回す最低枚数
            'selector'   => '.slides',
            'itemSel'    => '.slide',
            'rootAttr'   => 'data-ousr-root',
            'activeCls'  => 'ousr__item--active',
            'pausedCls'  => 'ousr--paused'
        ]);
    }
}
OU_Slides_Rotator::init();
