<?php
/** uninstall.php
 * 
 * アンインストールハンドラ
 * - 設定 OUQ_UNINSTALL_FLAG が true の場合のみ、設定やカスタムテーブルを削除
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

define('OUQ_OPTION_KEY', 'ouq_settings');
define('OUQ_UNINSTALL_FLAG', 'ouq_uninstall_purge');

$settings = get_option(OUQ_OPTION_KEY);
$purge    = is_array($settings) && !empty($settings[OUQ_UNINSTALL_FLAG]);

// 既定：false（残す）。true のときのみ削除を実施。
if ($purge) {
    // 設定の削除
    delete_option(OUQ_OPTION_KEY);

    // 将来の拡張：カスタムテーブルを作成した場合はここでDROP
    // global $wpdb;
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ouq_leads");
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ouq_answers");
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ouq_consent");
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ouq_queue");

    // 追加のクリーンアップ（オプション名プレフィックス一括削除等）をここに記載可能
}
