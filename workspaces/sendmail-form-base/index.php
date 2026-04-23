<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/session-init.php';
require_once __DIR__ . '/includes/config.php';

// CSRFトークン生成（未生成の場合のみ）
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken      = $_SESSION['csrf_token'];
$privacyPolicyUrl = htmlspecialchars(PRIVACY_POLICY_URL, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>お問い合わせ</title>
  <link rel="stylesheet" href="assets/css/form.css">
</head>
<body>
  <main class="form-wrap">
    <h1>お問い合わせ</h1>

    <div id="form-message" class="form-message" role="alert" aria-live="polite" hidden></div>

    <form id="contact-form" action="submit.php" method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <!-- ハニーポット（ボット対策: CSSで非表示、人間には見えない） -->
      <div class="hp-field" aria-hidden="true" tabindex="-1">
        <label for="url_homepage_field">ウェブサイト（入力しないでください）</label>
        <input type="text" id="url_homepage_field" name="url_homepage" autocomplete="off" tabindex="-1">
      </div>

      <div class="form-group">
        <label for="name">お名前 <span class="required">必須</span></label>
        <input type="text" id="name" name="name" autocomplete="name"
               required aria-required="true" aria-describedby="name-error">
        <span class="field-error" id="name-error" role="alert"></span>
      </div>

      <div class="form-group">
        <label for="tel">電話番号 <span class="required">必須</span></label>
        <input type="tel" id="tel" name="tel" autocomplete="tel"
               placeholder="例: 090-1234-5678"
               required aria-required="true" aria-describedby="tel-error">
        <span class="field-error" id="tel-error" role="alert"></span>
      </div>

      <div class="form-group">
        <label for="email">メールアドレス <span class="required">必須</span></label>
        <input type="email" id="email" name="email" autocomplete="email"
               required aria-required="true" aria-describedby="email-error">
        <span class="field-error" id="email-error" role="alert"></span>
      </div>

      <div class="form-group">
        <label for="zip">郵便番号 <span class="required">必須</span></label>
        <div class="zip-row">
          <input type="text" id="zip" name="zip" autocomplete="postal-code"
                 placeholder="例: 1234567 または 123-4567" maxlength="8"
                 required aria-required="true" aria-describedby="zip-error">
          <button type="button" id="zip-search" class="btn-secondary">住所を検索</button>
        </div>
        <span class="field-error" id="zip-error" role="alert"></span>
        <span class="field-hint" id="zip-hint" aria-live="polite"></span>
      </div>

      <div class="form-group">
        <label for="address">住所 <span class="required">必須</span></label>
        <input type="text" id="address" name="address" autocomplete="street-address"
               placeholder="郵便番号検索後、番地・部屋番号を追記してください"
               required aria-required="true" aria-describedby="address-error">
        <span class="field-error" id="address-error" role="alert"></span>
      </div>

      <div class="form-group form-group--check">
        <label class="check-label">
          <input type="checkbox" id="privacy" name="privacy" value="1"
                 required aria-required="true" aria-describedby="privacy-error">
          <a href="<?= $privacyPolicyUrl ?>" target="_blank" rel="noopener">プライバシーポリシー</a>に同意する
          <span class="required">必須</span>
        </label>
        <span class="field-error" id="privacy-error" role="alert"></span>
      </div>

      <div class="form-actions">
        <button type="submit" id="submit-btn" disabled>送信する</button>
      </div>
    </form>
  </main>

  <script src="assets/js/form.js"></script>
</body>
</html>
