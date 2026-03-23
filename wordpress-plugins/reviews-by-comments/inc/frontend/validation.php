<?php
if (!defined('ABSPATH')) exit;

/** サーバー側バリデーション（必須チェック＋CAPTCHA） */
add_filter('preprocess_comment', function ($data) {
  if (!isset($_POST['rv_force_nonce']) || !wp_verify_nonce($_POST['rv_force_nonce'], 'rv_force_nonce')) {
    return $data; // このショートコード経由でないフォームは素通り
  }
  $errors = [];

  if (empty(trim($data['comment_content'] ?? ''))) $errors[] = 'ご感想は必須です。';
  if (empty($_POST['rv_solution'])) $errors[] = '解決手段を選択してください。';
  if (empty($_POST['rv_date']))     $errors[] = '解決時期を入力してください。';
  if (empty(trim($_POST['rv_incident'] ?? ''))) $errors[] = '相談した出来事を入力してください。';

  if (!empty($_POST['rv_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['rv_date'])) {
    $errors[] = '解決時期の日付形式が正しくありません。';
  }

  // CAPTCHA
  $rsc = rvc_get_rscaptcha_instance();
  if ($rsc) {
    if (empty($_POST['rv_captcha']) || empty($_POST['rv_captcha_prefix'])) {
      $errors[] = '画像認証の入力が必要です。';
    } else {
      list($captcha) = $rsc;
      $answer = sanitize_text_field($_POST['rv_captcha']);
      $prefix = sanitize_text_field($_POST['rv_captcha_prefix']);

      if (!$captcha->check($answer, $prefix)) {
        // フォールバック（transient 保持の正解と照合）
        $answer_lc = strtolower($answer);
        if (!rvc_captcha_fallback_check($prefix, $answer_lc)) {
          $errors[] = '画像認証に失敗しました。もう一度お試しください。';
        } else {
          // フォールバック成功時も可能なら掃除
          if (method_exists($captcha, 'remove')) $captcha->remove($prefix);
        }
      } else {
        rvc_captcha_cleanup($captcha, $prefix);
      }
    }
  }

  if ($errors) {
    wp_die(implode('<br>', array_map('esc_html', $errors)), '入力エラー', ['back_link' => true]);
  }
  return $data;
});
