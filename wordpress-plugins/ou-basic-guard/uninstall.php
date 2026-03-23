<?php
/**
 * OU Basic Guard - アンインストール処理
 * プラグインを削除した際に WordPress options を消去します。
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
  exit;
}

delete_option( 'ou_basic_guard_enabled' );
delete_option( 'ou_basic_guard_username' );
delete_option( 'ou_basic_guard_password_hash' );
delete_option( 'ou_basic_guard_targets' );
delete_option( 'ou_basic_guard_legacy_detected' );

// 旧 mu-plugin が残したオプション
delete_option( 'ou_basic_guard_authuserfile_path' );
delete_option( 'ou_basic_guard_status' );
delete_option( 'ou_basic_guard_status_message' );
delete_option( 'ou_basic_guard_last_applied_by' );
delete_option( 'ou_basic_guard_last_applied_at' );
delete_option( 'ou_basic_guard_auto_healed_once' );
delete_option( 'ou_basic_guard_auto_heal_popup' );
