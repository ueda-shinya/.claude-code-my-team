<?php
/**
 * Plugin Name: Reviews by Comments
 * Description: 固定ページに [reviews_force per_page="10"] を挿入すると、承認済みコメントの一覧＋カスタム投稿フォーム（未ログイン可・追加項目・CAPTCHA）を提供します。
 * Version: 1.0.0
 * Author: OfficeUEDA
 * Text Domain: reviews-by-comments
 */

if (!defined('ABSPATH')) exit;

// ロード順: 定数 → 共通 → フロント → 管理
require_once __DIR__ . '/inc/common/constants.php';

require_once RVC_INC . '/frontend/captcha.php';
require_once RVC_INC . '/frontend/extra-fields.php';
require_once RVC_INC . '/frontend/validation.php';
require_once RVC_INC . '/frontend/save-meta.php';
require_once RVC_INC . '/frontend/shortcode.php';

require_once RVC_INC . '/admin/columns.php';
require_once RVC_INC . '/admin/metaboxes.php';
require_once RVC_INC . '/admin/enqueue.php';

// サイト設定をコードで強制（※元コードの挙動をそのまま踏襲）
add_filter('option_comment_registration', '__return_false');
add_filter('option_require_name_email', '__return_false');
add_filter('option_show_comments_cookies_opt_in', '__return_false');
add_filter('comment_form_default_fields', function ($fields) {
  if (isset($fields['cookies'])) unset($fields['cookies']);
  return $fields;
}, 20);
add_filter('pre_comment_approved', function ($approved) {
  return 0; // 0 = hold（承認待ち）
}, 10, 2);
