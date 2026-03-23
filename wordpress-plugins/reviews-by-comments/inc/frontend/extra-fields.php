<?php
if (!defined('ABSPATH')) exit;

// このショートコードのフォームにだけ追加フィールドを出すためのフラグ
$GLOBALS['rv_force_rendering'] = false;

/** フォーム追加フィールド（nonce／各項目／CAPTCHA） */
function rvc_force_extra_fields() {
  if (empty($GLOBALS['rv_force_rendering'])) return;
  $nonce = wp_create_nonce('rv_force_nonce'); ?>
  <input type="hidden" name="rv_force_nonce" value="<?php echo esc_attr($nonce); ?>">

  <!-- お名前（保存のみ・公開しない） -->
  <div class="c-field">
    <label class="c-field__label" for="rv_name">お名前 <span class="c-field__note">※口コミには表示されません</span></label>
    <input class="c-field__control" id="rv_name" name="rv_name" type="text" maxlength="60">
  </div>

  <!-- ご相談者様について（セレクト） -->
  <div class="c-field">
    <label class="c-field__label" for="rv_person">ご相談者様について</label>
    <select class="c-field__control" id="rv_person" name="rv_person">
      <option value="">選択してください</option>
      <?php
      $options = [
        '20代/男性','30代/男性','40代/男性','50代/男性','60代/男性',
        '20代/女性','30代/女性','40代/女性','50代/女性','60代/女性',
        'その他'
      ];
      foreach ($options as $opt) {
        echo '<option value="'.esc_attr($opt).'">'.esc_html($opt).'</option>';
      }
      ?>
    </select>
  </div>

  <!-- 解決手段（必須） -->
  <div class="c-field">
    <label class="c-field__label" for="rv_solution">解決手段 <span class="c-badge c-badge--req">必須</span></label>
    <select class="c-field__control" id="rv_solution" name="rv_solution" required aria-required="true">
      <option value="">選択してください</option>
      <?php
      $sols = ['交渉・示談','無料・有利','書類作成','その他'];
      foreach ($sols as $opt) {
        echo '<option value="'.esc_attr($opt).'">'.esc_html($opt).'</option>';
      }
      ?>
    </select>
  </div>

  <!-- 解決時期（必須） -->
  <div class="c-field">
    <label class="c-field__label" for="rv_date">解決時期 <span class="c-badge c-badge--req">必須</span></label>
    <input class="c-field__control" id="rv_date" name="rv_date" type="date" required aria-required="true" inputmode="numeric">
  </div>

  <!-- 相談した出来事（必須） -->
  <div class="c-field">
    <label class="c-field__label" for="rv_incident">相談した出来事 <span class="c-badge c-badge--req">必須</span></label>
    <textarea class="c-field__control" id="rv_incident" name="rv_incident" rows="5" maxlength="2000" required aria-required="true"
      placeholder="※個人名など、後悔したくない情報のご記入はお控えください。"></textarea>
  </div>

  <?php
  // --- CAPTCHA 追加（Really Simple CAPTCHA が有効な場合のみ） ---
  $rsc = rvc_get_rscaptcha_instance();
  if ($rsc) {
    list($captcha, $dir, $url_base) = $rsc;
    $prefix  = wp_generate_password(8, false, false);
    $word    = $captcha->generate_random_word();
    $imgfile = $captcha->generate_image($prefix, $word);
    $img_url = trailingslashit($url_base) . $imgfile;

    // フォールバック照合用：正解（小文字）を保存
    rvc_captcha_store_expected($prefix, $word);
  ?>
    <div class="c-field c-captcha">
      <label class="c-field__label" for="rv_captcha">画像認証 <span class="c-badge c-badge--req">必須</span></label>
      <div class="c-captcha__wrap" style="display:flex;align-items:center;gap:.75rem;">
        <img src="<?php echo esc_url($img_url); ?>" alt="表示された文字を入力してください" class="c-captcha__image" height="40">
        <input class="c-field__control" id="rv_captcha" name="rv_captcha" type="text" inputmode="latin" pattern="[A-Za-z0-9]{4,}" required aria-required="true" placeholder="画像の文字を入力">
      </div>
      <input type="hidden" name="rv_captcha_prefix" value="<?php echo esc_attr($prefix); ?>">
    </div>
  <?php } // CAPTCHA ここまで ?>
<?php }

add_action('comment_form_after_fields', 'rvc_force_extra_fields');
add_action('comment_form_logged_in_after', 'rvc_force_extra_fields');
