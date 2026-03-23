<?php
if (!defined('ABSPATH')) exit;


class OU_PMP_CPT
{
    public static function init()
    {
        add_action('init', [__CLASS__, 'register']);
    }
    public static function register()
    {
        // CPT: property
        $labels = [
            'name' => '物件',
            'singular_name' => '物件',
            'add_new' => '新規追加',
            'add_new_item' => '物件を追加',
            'edit_item' => '物件を編集',
            'new_item' => '新規物件',
            'view_item' => '物件を表示',
            'search_items' => '物件を検索',
            'not_found' => '物件がありません',
            'all_items' => '物件一覧'
        ];
        register_post_type('property', [
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-building',
            'supports' => ['title', 'editor', 'thumbnail', 'revisions'],
            'show_in_rest' => true,
        ]);


        // Taxonomy: 物件種別
        register_taxonomy('property_type', 'property', [
            'labels' => ['name' => '物件種別'],
            'hierarchical' => true,
            'show_in_rest' => true
        ]);


        // Taxonomy: エリア（都道府県>市区町村）
        register_taxonomy('area', 'property', [
            'labels' => ['name' => 'エリア'],
            'hierarchical' => true,
            'show_in_rest' => true
        ]);


        // Taxonomy: 特徴タグ
        register_taxonomy('feature', 'property', [
            'labels' => ['name' => '特徴タグ'],
            'hierarchical' => false,
            'show_in_rest' => true
        ]);
    }
}
