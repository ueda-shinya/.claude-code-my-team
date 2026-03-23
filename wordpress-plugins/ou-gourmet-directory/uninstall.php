<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

/**
 * ここではオプションのみ削除（投稿や固定ページは保持）
 * ※ 本当に固定ページも削除したい場合は、スラッグを指定して wp_trash_post() などで明示的に削除してください。
 */
delete_option('ou_gd_instagram');
delete_option('ou_gd_deletion_log');
