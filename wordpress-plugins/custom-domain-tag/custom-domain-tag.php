<?php
/*
Plugin Name: Custom Domain Tag for Contact Form 7
Plugin URI:  https://officeueda.com
Description: Adds a custom tag to Contact Form 7 to include the site's domain name.
Version:     1.0
Version: 1.0.0
Author: しんや
Author URI: https://officeueda.com
*/

// サイトのドメイン名をキャッシュするための変数
$cached_site_domain = null;

// サイトのドメイン名を返す関数
function get_site_domain() {
    global $cached_site_domain;

    // 既にキャッシュされている場合はそれを返す
    if ($cached_site_domain !== null) {
        return $cached_site_domain;
    }

    // サイトURLを取得してパースする
    $site_url = get_site_url();
    $url_parts = parse_url($site_url);

    // エラーハンドリング：パースが失敗した場合
    if ($url_parts === false || !isset($url_parts['host'])) {
        return ''; // 必要に応じてデフォルトの値やエラーメッセージを設定
    }

    // ドメイン部分をサニタイズしてキャッシュする
    $cached_site_domain = sanitize_text_field($url_parts['host']);
    
    return $cached_site_domain;
}

// Contact Form 7 のカスタムメールタグを定義
add_filter('wpcf7_special_mail_tags', 'custom_wpcf7_special_mail_tags', 10, 3);
function custom_wpcf7_special_mail_tags($output, $name, $html) {
    // カスタムタグが 'site_domain' の場合にサイトのドメインを返す
    if ($name === 'site_domain') {
        $output = get_site_domain();
    }
    return $output;
}
