<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/session-init.php';
require_once __DIR__ . '/includes/config.php';

// CSRFトークン生成（未生成の場合のみ）
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// JS無効環境からのフォールバック: submit.php が ?error=1 でリダイレクトしてきた場合にエラーメッセージを表示する
$noJsError = isset($_GET['error']) && $_GET['error'] === '1';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BORN STEM 購入お問い合わせ | BAKUCHIS Corporation</title>
  <link rel="stylesheet" href="assets/css/site.css?v=20260428">
  <link rel="stylesheet" href="assets/css/form.css?v=20260428">
  <!-- JSON-LD: LocalBusiness + WebPage -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "BORN STEM 購入お問い合わせ",
    "url": "https://company.bakuchis.com/bornstem_contact",
    "description": "BORN STEMのご購入に関するお問い合わせはこちらのフォームより承っております。",
    "publisher": {
      "@type": "Organization",
      "name": "BAKUCHIS Corporation"
    }
  }
  </script>
</head>
<body>
  <div class="l-site">

    <header class="l-header" style="display: none;">
      <p class="l-header__logo">BAKUCHIS Corporation</p>
    </header>

    <main class="l-main">
      <div class="l-form-wrap">

        <!-- h1 装飾ダッシュは CSS ::before / ::after で対応 -->
        <h1 class="p-contact__title">BORN STEM 購入お問い合わせ</h1>

        <p class="p-contact__lead">
          BORN STEMのご購入に関するお問い合わせはこちらのフォームより承っております。<br>
          必要事項にご入力の上、送信してください。<br>
          内容を確認しご返信させていただきます。
        </p>

        <?php if ($noJsError): ?>
        <div id="form-message" class="c-form-message c-form-message--error" role="alert" aria-live="polite">
          送信に失敗しました。入力内容をご確認のうえ、再度お試しください。
        </div>
        <?php else: ?>
        <div id="form-message" class="c-form-message" role="alert" aria-live="polite" hidden></div>
        <?php endif; ?>

        <!-- YubinBango.js: class="h-adr" を外側 form に直接付与（入れ子 form は HTML 仕様違反のため不可） -->
        <form id="contact-form" class="h-adr" action="submit.php" method="post" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" class="p-country-name" value="Japan">

          <!-- ハニーポット（ボット対策: CSSで非表示、人間には見えない） -->
          <div class="u-hp-field" aria-hidden="true" tabindex="-1">
            <label for="url_homepage_field">ウェブサイト（入力しないでください）</label>
            <input type="text" id="url_homepage_field" name="url_homepage" autocomplete="off" tabindex="-1" aria-hidden="true">
          </div>

          <!-- 会社名・サロン名 -->
          <div class="c-form-group">
            <label for="company">会社名・サロン名 <span class="c-required-badge">必須</span></label>
            <input type="text" id="company" name="company" autocomplete="organization"
                   maxlength="100" placeholder="例: 株式会社ビューティーサロン"
                   required aria-required="true" aria-describedby="company-error">
            <span class="c-field-error" id="company-error" role="alert"></span>
          </div>

          <!-- 会社名・サロン名カナ -->
          <div class="c-form-group">
            <label for="company_kana">会社名・サロン名カナ <span class="c-required-badge">必須</span></label>
            <input type="text" id="company_kana" name="company_kana"
                   maxlength="100" placeholder="例: カブシキガイシャビューティーサロン"
                   required aria-required="true" aria-describedby="company_kana-error">
            <span class="c-field-error" id="company_kana-error" role="alert"></span>
          </div>

          <!-- 代表者 姓名（横並び2分割） -->
          <div class="c-form-group">
            <span class="c-form-group__label-text">代表者 <span class="c-required-badge">必須</span></span>
            <div class="c-name-row">
              <div class="c-name-row__field">
                <label for="rep_lastname">姓</label>
                <input type="text" id="rep_lastname" name="rep_lastname" autocomplete="family-name"
                       maxlength="50" placeholder="例: 山田"
                       required aria-required="true" aria-describedby="rep_lastname-error">
                <span class="c-field-error" id="rep_lastname-error" role="alert"></span>
              </div>
              <div class="c-name-row__field">
                <label for="rep_firstname">名</label>
                <input type="text" id="rep_firstname" name="rep_firstname" autocomplete="given-name"
                       maxlength="50" placeholder="例: 花子"
                       required aria-required="true" aria-describedby="rep_firstname-error">
                <span class="c-field-error" id="rep_firstname-error" role="alert"></span>
              </div>
            </div>
          </div>

          <!-- 代表者カナ（横並び2分割） -->
          <div class="c-form-group">
            <span class="c-form-group__label-text">代表者カナ <span class="c-required-badge">必須</span></span>
            <div class="c-name-row">
              <div class="c-name-row__field">
                <label for="rep_lastname_kana">セイ</label>
                <input type="text" id="rep_lastname_kana" name="rep_lastname_kana"
                       placeholder="例: ヤマダ"
                       required aria-required="true" aria-describedby="rep_lastname_kana-error">
                <span class="c-field-error" id="rep_lastname_kana-error" role="alert"></span>
              </div>
              <div class="c-name-row__field">
                <label for="rep_firstname_kana">メイ</label>
                <input type="text" id="rep_firstname_kana" name="rep_firstname_kana"
                       placeholder="例: ハナコ"
                       required aria-required="true" aria-describedby="rep_firstname_kana-error">
                <span class="c-field-error" id="rep_firstname_kana-error" role="alert"></span>
              </div>
            </div>
          </div>

          <!-- 担当者 姓名（横並び2分割・任意） -->
          <div class="c-form-group">
            <span class="c-form-group__label-text">担当者</span>
            <div class="c-name-row">
              <div class="c-name-row__field">
                <label for="staff_lastname">姓</label>
                <input type="text" id="staff_lastname" name="staff_lastname"
                       maxlength="50" placeholder="例: 鈴木"
                       aria-describedby="staff_lastname-error">
                <span class="c-field-error" id="staff_lastname-error" role="alert"></span>
              </div>
              <div class="c-name-row__field">
                <label for="staff_firstname">名</label>
                <input type="text" id="staff_firstname" name="staff_firstname"
                       maxlength="50" placeholder="例: 美咲"
                       aria-describedby="staff_firstname-error">
                <span class="c-field-error" id="staff_firstname-error" role="alert"></span>
              </div>
            </div>
          </div>

          <!-- 担当者カナ（横並び2分割・任意） -->
          <div class="c-form-group">
            <span class="c-form-group__label-text">担当者カナ</span>
            <div class="c-name-row">
              <div class="c-name-row__field">
                <label for="staff_lastname_kana">セイ</label>
                <input type="text" id="staff_lastname_kana" name="staff_lastname_kana"
                       placeholder="例: スズキ"
                       aria-describedby="staff_lastname_kana-error">
                <span class="c-field-error" id="staff_lastname_kana-error" role="alert"></span>
              </div>
              <div class="c-name-row__field">
                <label for="staff_firstname_kana">メイ</label>
                <input type="text" id="staff_firstname_kana" name="staff_firstname_kana"
                       placeholder="例: ミサキ"
                       aria-describedby="staff_firstname_kana-error">
                <span class="c-field-error" id="staff_firstname_kana-error" role="alert"></span>
              </div>
            </div>
          </div>

          <!-- 郵便番号 + 都道府県・市区町村・町域（YubinBango.js 対応） -->
          <!-- YubinBango.js は外側 form の class="h-adr" と class="p-*" input で住所を自動補完する -->
          <div class="c-form-group">
              <label for="zipcode">郵便番号 <span class="c-required-badge">必須</span></label>
              <div class="c-zip-row">
                <input type="text" id="zipcode" name="zipcode" autocomplete="postal-code"
                       class="p-postal-code"
                       placeholder="例: 150-0013" maxlength="8"
                       required aria-required="true" aria-describedby="zipcode-error">
              </div>
              <span class="c-field-error" id="zipcode-error" role="alert"></span>
              <span class="c-field-hint" id="zipcode-hint" aria-live="polite"></span>
            </div>

            <div class="c-form-group">
              <label for="prefecture">都道府県 <span class="c-required-badge">必須</span></label>
              <input type="text" id="prefecture" name="prefecture" autocomplete="address-level1"
                     class="p-region" placeholder="例: 東京都"
                     required aria-required="true" aria-describedby="prefecture-error">
              <span class="c-field-error" id="prefecture-error" role="alert"></span>
            </div>

            <div class="c-form-group">
              <label for="city">市区町村 <span class="c-required-badge">必須</span></label>
              <input type="text" id="city" name="city" autocomplete="address-level2"
                     class="p-locality" placeholder="例: 渋谷区恵比寿"
                     required aria-required="true" aria-describedby="city-error">
              <span class="c-field-error" id="city-error" role="alert"></span>
            </div>

            <div class="c-form-group">
              <label for="street">町域・番地 <span class="c-required-badge">必須</span></label>
              <input type="text" id="street" name="street" autocomplete="address-line1"
                     class="p-street-address" placeholder="例: 4-20-3"
                     required aria-required="true" aria-describedby="street-error">
              <span class="c-field-error" id="street-error" role="alert"></span>
            </div>

          <div class="c-form-group">
            <label for="building">ビル建物名など</label>
            <input type="text" id="building" name="building" autocomplete="address-line2"
                   placeholder="例: 恵比寿ガーデンプレイスタワー18階"
                   aria-describedby="building-error">
            <span class="c-field-error" id="building-error" role="alert"></span>
          </div>

          <!-- メールアドレス -->
          <div class="c-form-group">
            <label for="email">メールアドレス <span class="c-required-badge">必須</span></label>
            <input type="email" id="email" name="email" autocomplete="email"
                   placeholder="例: hanako@example.com"
                   required aria-required="true" aria-describedby="email-error">
            <span class="c-field-error" id="email-error" role="alert"></span>
          </div>

          <!-- 電話番号（必須） -->
          <div class="c-form-group">
            <label for="tel">電話番号 <span class="c-required-badge">必須</span></label>
            <input type="tel" id="tel" name="tel" autocomplete="tel"
                   placeholder="例: 03-1234-5678"
                   required aria-required="true" aria-describedby="tel-error">
            <span class="c-field-error" id="tel-error" role="alert"></span>
          </div>

          <!-- 携帯番号（任意） -->
          <div class="c-form-group">
            <label for="mobile">携帯番号</label>
            <input type="tel" id="mobile" name="mobile" autocomplete="tel"
                   placeholder="例: 090-1234-5678"
                   aria-describedby="mobile-error">
            <span class="c-field-error" id="mobile-error" role="alert"></span>
          </div>

          <!-- 業態 / 業種 -->
          <div class="c-form-group">
            <label for="industry">業態 / 業種 <span class="c-required-badge">必須</span></label>
            <input type="text" id="industry" name="industry"
                   maxlength="100" placeholder="例: 美容室・ネイルサロン"
                   required aria-required="true" aria-describedby="industry-error">
            <span class="c-field-error" id="industry-error" role="alert"></span>
          </div>

          <!-- ホームページURL -->
          <div class="c-form-group">
            <label for="website">ホームページURL <span class="c-required-badge">必須</span></label>
            <input type="url" id="website" name="website" autocomplete="url"
                   placeholder="例: https://example.com"
                   required aria-required="true" aria-describedby="website-error">
            <span class="c-field-error" id="website-error" role="alert"></span>
          </div>

          <!-- プライバシーポリシー同意 -->
          <div class="c-form-group c-form-group--privacy">
            <div class="c-privacy-row">
              <label class="c-privacy-row__label">
                <input type="checkbox" id="privacy" name="privacy" value="1"
                       required aria-required="true" aria-describedby="privacy-error">
                プライバシーポリシーに同意する
                <span class="c-required-badge">必須</span>
              </label>
              <button type="button" id="privacy-modal-open" class="c-btn--link" aria-haspopup="dialog">
                プライバシーポリシーを確認する
              </button>
            </div>
            <span class="c-field-error" id="privacy-error" role="alert"></span>
          </div>

          <div class="c-form-actions">
            <button type="submit" id="submit-btn" disabled>送信する</button>
          </div>
        </form>

      </div>
    </main>

    <footer class="l-footer">
      <small>&copy; 2026 BAKUCHIS Corporation</small>
    </footer>

  </div>

  <!-- プライバシーポリシー モーダル -->
  <div id="privacy-modal" class="p-modal" role="dialog" aria-modal="true"
       aria-labelledby="privacy-modal-title" hidden>
    <div class="p-modal__overlay" id="privacy-modal-overlay"></div>
    <div class="p-modal__content" tabindex="-1">
      <button type="button" class="p-modal__close" id="privacy-modal-close" aria-label="閉じる">&times;</button>
      <h2 class="p-modal__title" id="privacy-modal-title">PRIVACY POLICY</h2>
      <div class="p-modal__body">
        <p>
          当サイトは、お客様の個人情報について、<br>
          お客様の承諾が無い限り第三者に開示、提供を一切いたしません。<br>
          お客様から個人情報をご提供していただき、<br>
          お客様へのサービスにご利用させていただく場合があります。<br>
          その目的以外には利用いたしません。<br>
          そして、ご提供いただいた個人情報を取り扱うにあたり適切な管理を行っております。
        </p>
      </div>
    </div>
  </div>

  <!-- YubinBango.js: 郵便番号→住所自動補完（ローカルホスティング・MIT License） -->
  <noscript><p style="color:#d9534f;text-align:center;padding:1em;">JavaScriptを有効にしてください。郵便番号からの住所自動入力や送信ボタンの制御が動作しません。</p></noscript>
  <script src="assets/js/vendor/yubinbango.js?v=20260428" charset="UTF-8"></script>
  <script src="assets/js/form.js?v=20260502"></script>
</body>
</html>
