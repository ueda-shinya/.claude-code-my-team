<?php
/*
Plugin Name:       Post Update Column
Plugin URI:        https://officeueda.com
Description:       投稿一覧に「更新日（最終更新日時）」カラムを追加する軽量プラグインです。
Version:           1.0.0
Author:            Shinya
Author URI:        https://officeueda.com
License:           GPL-2.0+
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       post-update-column
Domain Path:       /languages
*/

if (!defined('ABSPATH')) {
    exit; // 直アクセス防止
}

// カラムを追加
function puc_add_modified_date_column($columns) {
    $columns['modified_date'] = '更新日';
    return $columns;
}
add_filter('manage_posts_columns', 'puc_add_modified_date_column');

// カラムの表示内容
function puc_show_modified_date_column($column_name, $post_id) {
    if ($column_name === 'modified_date') {
        echo get_the_modified_time('Y-m-d H:i', $post_id);
    }
}
add_action('manage_posts_custom_column', 'puc_show_modified_date_column', 10, 2);

// ソート可能に
function puc_modified_date_column_sortable($columns) {
    $columns['modified_date'] = 'modified';
    return $columns;
}
add_filter('manage_edit-post_sortable_columns', 'puc_modified_date_column_sortable');
