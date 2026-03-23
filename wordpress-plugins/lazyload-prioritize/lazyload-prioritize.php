<?php
/**
 * Plugin Name: LazyLoad Prioritize
 * Plugin URI:  https://officeueda.com
 * Description: 画像遅延読み込みを最適化し、通常画像の読み込み後にLazy画像を優先的にロードする。
 * Version:     1.0.0
 * Author:      Shinya
 * Author URI:  https://officeueda.com
 * License:     GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit; // 直接アクセス禁止
}

// **スクリプトをフロントエンドと管理画面の両方で登録**
function enqueue_lazyload_prioritize_script() {
    $script_path = plugin_dir_path(__FILE__) . 'assets/js/lazyload-prioritize.js';
    $script_url = plugins_url('assets/js/lazyload-prioritize.js', __FILE__);
    $script_version = file_exists($script_path) ? filemtime($script_path) : time(); // キャッシュ防止

    wp_enqueue_script(
        'lazyload-prioritize',
        $script_url,
        [],
        $script_version,
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_lazyload_prioritize_script');
add_action('admin_enqueue_scripts', 'enqueue_lazyload_prioritize_script');

