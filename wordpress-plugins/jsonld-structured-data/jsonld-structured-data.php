<?php
/**
 * Plugin Name: JSON-LD Structured Data Output
 * Description: 投稿や固定ページに schema.org BlogPosting 構造化データを出力する WordPress プラグイン。
 * Version: 1.0.0
 * Author: オフィスウエダ
 * Author URI: https://officeueda.com
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 定数定義
define( 'JSONLD_SD_VERSION', '1.0.0' );
define( 'JSONLD_SD_DIR', plugin_dir_path( __FILE__ ) );
define( 'JSONLD_SD_URL', plugin_dir_url( __FILE__ ) );

// ファイル読み込み
require_once JSONLD_SD_DIR . 'includes/settings-handler.php';
require_once JSONLD_SD_DIR . 'includes/settings-page.php';
require_once JSONLD_SD_DIR . 'includes/output-jsonld.php';

// 設定用JSの読み込み（メディアボタン用）
function jsonld_sd_enqueue_admin_scripts($hook) {
  if ($hook === 'settings_page_jsonld_sd_settings') {
    wp_enqueue_media();
    wp_enqueue_script(
      'jsonld-sd-media',
      JSONLD_SD_URL . 'assets/js/media-button.js',
      ['jquery'],
      JSONLD_SD_VERSION,
      true
    );
  }
}
add_action('admin_enqueue_scripts', 'jsonld_sd_enqueue_admin_scripts');
