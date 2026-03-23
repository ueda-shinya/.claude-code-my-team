<?php
/**
 * Plugin Name: Cook Manual Core (No ACF)
 * Description: 調理マニュアル用のCPTとブロック（ACF不要 / Gutenbergブロックで手順の追加・並べ替え）
 * Version: 1.0.0
 * Author: Office Ueda
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) exit;

// ------------------------------
// 1) カスタム投稿タイプ & タクソノミー
// ------------------------------
add_action('init', function () {

    // CPT: cook_manual
    register_post_type('cook_manual', [
        'label'         => '調理マニュアル',
        'labels'        => [
            'name'               => '調理マニュアル',
            'singular_name'      => '調理マニュアル',
            'add_new'            => '新規追加',
            'add_new_item'       => '新規マニュアルを追加',
            'edit_item'          => 'マニュアルを編集',
            'new_item'           => '新規マニュアル',
            'view_item'          => 'マニュアルを表示',
            'search_items'       => 'マニュアルを検索',
            'not_found'          => 'マニュアルが見つかりません',
            'not_found_in_trash' => 'ゴミ箱にマニュアルはありません',
            'all_items'          => 'すべてのマニュアル',
        ],
        'public'        => true,
        'has_archive'   => true,
        'menu_position' => 20,
        'menu_icon'     => 'dashicons-book',
        'supports'      => ['title', 'editor', 'thumbnail', 'revisions'],
        'show_in_rest'  => true,
        'rewrite'       => ['slug' => 'manual', 'with_front' => false],
    ]);

    // Taxonomy: manual_category（任意）
    register_taxonomy('manual_category', 'cook_manual', [
        'label'         => 'マニュアル分類',
        'labels'        => [
            'name'          => 'マニュアル分類',
            'singular_name' => 'マニュアル分類',
            'search_items'  => '分類を検索',
            'all_items'     => 'すべての分類',
            'edit_item'     => '分類を編集',
            'update_item'   => '分類を更新',
            'add_new_item'  => '新規分類を追加',
            'new_item_name' => '新規分類名',
            'menu_name'     => '分類',
        ],
        'hierarchical'  => true,
        'show_in_rest'  => true,
        'rewrite'       => ['slug' => 'manual-category', 'with_front' => false],
        'public'        => true,
    ]);
});

// ------------------------------
// 2) アセット読み込み
// ------------------------------
add_action('enqueue_block_editor_assets', function () {
    $base = plugin_dir_url(__FILE__) . 'assets/';
    wp_enqueue_script(
        'cook-manual-editor',
        $base . 'editor.js',
        [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-block-editor' ],
        '1.0.0',
        true
    );
    wp_enqueue_style(
        'cook-manual-style',
        $base . 'style.css',
        [],
        '1.0.0'
    );
});

// フロント側（シングル・アーカイブなど）
add_action('wp_enqueue_scripts', function () {
    $base = plugin_dir_url(__FILE__) . 'assets/';
    // 調理マニュアル投稿で読み込み（必要なら is_singular('cook_manual') に限定）
    if (is_singular('cook_manual') || is_post_type_archive('cook_manual')) {
        wp_enqueue_style(
            'cook-manual-style',
            $base . 'style.css',
            [],
            '1.0.0'
        );
        wp_enqueue_script(
            'cook-manual-frontend',
            $base . 'frontend.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }
});
