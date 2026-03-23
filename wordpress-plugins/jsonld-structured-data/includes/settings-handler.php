<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// オプションキー
const JSONLD_SD_OPTION_KEY = 'jsonld_sd_options';

// デフォルト値
function jsonld_sd_get_default_options() {
  return [
    'publisher_name' => '',
    'logo_url' => '',
    'default_image' => '',
    'post_types' => ['post', 'page'],
    'use_excerpt_fallback' => true,
    'accessibility_flag' => 'none', // none / free / paid
    'delete_on_uninstall' => false,
  ];
}

// オプション取得
function jsonld_sd_get_options() {
  $defaults = jsonld_sd_get_default_options();
  $saved = get_option(JSONLD_SD_OPTION_KEY, []);
  return wp_parse_args($saved, $defaults);
}

// サニタイズと保存処理
function jsonld_sd_sanitize_and_save($input) {
  $options = [];

  $options['publisher_name'] = sanitize_text_field($input['publisher_name'] ?? '');
  $options['logo_url'] = esc_url_raw($input['logo_url'] ?? '');
  $options['default_image'] = esc_url_raw($input['default_image'] ?? '');
  $options['post_types'] = array_map('sanitize_key', $input['post_types'] ?? []);
  $options['use_excerpt_fallback'] = !empty($input['use_excerpt_fallback']);

  $access = $input['accessibility_flag'] ?? 'none';
  $options['accessibility_flag'] = in_array($access, ['none', 'free', 'paid'], true) ? $access : 'none';

  $options['delete_on_uninstall'] = !empty($input['delete_on_uninstall']);

  update_option(JSONLD_SD_OPTION_KEY, $options);
  return $options;
}
