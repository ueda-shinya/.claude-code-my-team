<?php
/**
 * Plugin Name: Zipcode Address Autofill
 * Description: Contactページで郵便番号を入力すると自動で住所を入力するプラグイン
 * Version: 1.0
 * Author: しんや
 * Author URI: https://officeueda.com
 */

function enqueue_custom_contact_script() {
    // プラグインのディレクトリ URI を取得
    $plugin_uri = plugin_dir_url(__FILE__);

    // Contactページのみスクリプトを読み込む
    if (is_page('contact')) { // 'contact'はページのスラッグ
        wp_enqueue_script('auto-address-fill', $plugin_uri . 'js/autoAddressFill.js', array('jquery'), null, true);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_contact_script');
?>
