<?php
/**
 * Plugin Name: OU Parameter Protected Pages
 * Plugin URI:  https://example.com/
 * Description: URLパラメータを簡易パスワードとして扱い、Gutenbergブロックからページ全体を保護するプラグイン。
 * Version:     1.0.0
 * Author:      Office Ueda
 * Author URI:  https://example.com/
 * Text Domain: ou-parameter-protect
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 定数定義
 */
define( 'OUPB_PLUGIN_VERSION', '1.0.0' );
define( 'OUPB_PLUGIN_FILE', __FILE__ );
define( 'OUPB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'OUPB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OUPB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OUPB_OPTIONS_KEY', 'oupb_options' ); // wp_options のキー

/**
 * 必要ファイルの読み込み
 */
require_once OUPB_PLUGIN_DIR . 'includes/class-oupb-plugin.php';
require_once OUPB_PLUGIN_DIR . 'includes/class-oupb-settings.php';
require_once OUPB_PLUGIN_DIR . 'includes/class-oupb-guard.php';
require_once OUPB_PLUGIN_DIR . 'includes/class-oupb-block.php';

/**
 * プラグイン本体初期化
 */
add_action(
	'plugins_loaded',
	static function () {
		// テキストドメイン読み込み
		load_plugin_textdomain(
			'ou-parameter-protect',
			false,
			dirname( OUPB_PLUGIN_BASENAME ) . '/languages'
		);

		// メインクラス初期化
		if ( class_exists( 'OUPB_Plugin' ) ) {
			OUPB_Plugin::init();
		}
	}
);

/**
 * プラグイン有効化フック
 *
 * - デフォルト設定の登録など
 */
register_activation_hook(
	__FILE__,
	static function () {
		if ( class_exists( 'OUPB_Plugin' ) ) {
			OUPB_Plugin::activate();
		}
	}
);

/**
 * プラグイン削除フック
 *
 * - 要件定義に基づき、「設定を残す／削除する」を OUPB_Plugin 側で判定
 * - register_uninstall_hook は 'Class::method' 形式のコールバックを受け付ける
 */
register_uninstall_hook(
	__FILE__,
	'OUPB_Plugin::uninstall'
);
