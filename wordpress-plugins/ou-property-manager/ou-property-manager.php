<?php

/**
 * Plugin Name: OU Property Manager
 * Description: 物件管理（CPT + ACF + 絞り込み検索 + PDF表示/自動JPG）
 * Version: 1.0.0
 * Author: Office Ueda
 * Text Domain: oupm
 */
if (!defined('ABSPATH')) {
    exit;
}


// 定数
define('OUPM_VER', '1.0.0');
define('OUPM_DIR', plugin_dir_path(__FILE__));
define('OUPM_URL', plugin_dir_url(__FILE__));


require_once OUPM_DIR . 'includes/helpers.php';
require_once OUPM_DIR . 'includes/class-oupm-cpt.php';
require_once OUPM_DIR . 'includes/class-oupm-acf.php';
require_once OUPM_DIR . 'includes/class-oupm-admin.php';
require_once OUPM_DIR . 'includes/class-oupm-search.php';
require_once OUPM_DIR . 'includes/class-oupm-pdf.php';


// 起動
add_action('plugins_loaded', function () {
    load_plugin_textdomain('oupm', false, dirname(plugin_basename(__FILE__)) . '/languages');


    OU_PMP_CPT::init();
    OU_PMP_Admin::init();
    OU_PMP_Search::init();
    OU_PMP_PDF::init();


    // ACF は存在時のみ登録
    if (function_exists('acf_add_local_field_group')) {
        OU_PMP_ACF::init();
    } else {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning"><p>OU Property Manager: ACF PRO が有効化されていないため、項目入力UIは表示されません。</p></div>';
        });
    }
});


// 有効化時：リライトルール
register_activation_hook(__FILE__, function () {
    OU_PMP_CPT::register();
    flush_rewrite_rules();
});


register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
