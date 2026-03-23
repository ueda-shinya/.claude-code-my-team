<?php
// uninstall.php - プラグイン削除時に実行される
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

$option_key = 'jsonld_sd_options';
$options = get_option( $option_key );

if ( isset( $options['delete_on_uninstall'] ) && $options['delete_on_uninstall'] ) {
  delete_option( $option_key );
}
