<?php
if (!defined('ABSPATH')) exit;

/**
 * Really Simple CAPTCHA ヘルパー（未導入なら null）
 * 生成先: uploads/rscaptcha
 */
function rvc_get_rscaptcha_instance() {
  if (!class_exists('ReallySimpleCaptcha')) return null;

  $captcha = new ReallySimpleCaptcha();

  $upload = wp_get_upload_dir();
  $dir    = trailingslashit($upload['basedir']) . 'rscaptcha';
  $url    = trailingslashit($upload['baseurl']) . 'rscaptcha';

  if (!file_exists($dir)) {
    wp_mkdir_p($dir);
  }
  $captcha->tmp_dir = $dir;

  // 見た目・難易度（元コード準拠）
  $captcha->img_size    = array(120, 40);
  $captcha->base        = array(6, 18);
  $captcha->font_size   = 16;
  $captcha->char_length = 5;
  $captcha->bg          = array(255, 255, 255);
  $captcha->fg          = array(60, 60, 60);

  return array($captcha, $dir, $url);
}

/**
 * 生成時に正解を transient に保存（フォールバック照合用）
 */
function rvc_captcha_store_expected($prefix, $word) {
  set_transient('rv_cpt_' . $prefix, strtolower($word), 10 * MINUTE_IN_SECONDS);
}

/**
 * フォールバック照合（check() が失敗したときに使用）
 */
function rvc_captcha_fallback_check($prefix, $answer_lc) {
  $expect = get_transient('rv_cpt_' . $prefix);
  if ($expect && $expect === $answer_lc) {
    delete_transient('rv_cpt_' . $prefix);
    return true;
  }
  return false;
}

/**
 * 成功時のクリーンアップ
 */
function rvc_captcha_cleanup($captcha, $prefix) {
  delete_transient('rv_cpt_' . $prefix);
  if (method_exists($captcha, 'remove')) {
    $captcha->remove($prefix);
  }
}
