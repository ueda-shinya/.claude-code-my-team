<?php
/** templates/frontend/chat.php
 * FLOCSS/BEM & ARIA-ready chat container
 */
if (!defined('ABSPATH')) { exit; }
?>
<div class="l-chat ouq-chat" data-ouq="ready" aria-live="polite">
  <!-- ヘッダー：タイトル＋進捗 -->
  <header class="l-chat__head c-head">
    <h2 class="c-head__title"><?php echo esc_html__('診断チャット', 'ou-quiz-chat'); ?></h2>
    <div class="c-head__progress" aria-hidden="true">
      <div class="c-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"
           aria-valuenow="0" aria-label="<?php echo esc_attr__('進捗', 'ou-quiz-chat'); ?>">
        <span class="c-progress__bar js-progress-bar" style="width:0%"></span>
      </div>
      <div class="c-head__count">
        <span class="js-q-count">Q 0</span> / <span class="js-q-total">0</span>
      </div>
    </div>
  </header>

  <!-- 本体：メッセージストリーム -->
  <div class="l-chat__body c-stream js-stream" tabindex="0">
    <!-- ここにJSで吹き出しを追加 -->
  </div>

  <!-- フッター（必要になったら入力欄やボタン群を出す、初期は空） -->
  <footer class="l-chat__foot c-foot js-foot" aria-hidden="true"></footer>

  <!-- ユーザー入力（モーダル／1項目ずつ） -->
  <div class="c-modal js-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="ouq-modal-title">
    <div class="c-modal__scrim js-modal-close" tabindex="-1"></div>
    <div class="c-modal__panel" role="document">
      <h3 id="ouq-modal-title" class="c-modal__title"><?php echo esc_html__('入力', 'ou-quiz-chat'); ?></h3>
      <div class="c-modal__body">
        <label class="c-field">
          <span class="c-field__label js-field-label">お名前</span>
          <input type="text" class="c-field__control js-field-input" autocomplete="name">
          <p class="c-field__hint js-field-hint" aria-live="polite"></p>
        </label>
      </div>
      <div class="c-modal__actions">
        <button type="button" class="c-btn c-btn--ghost js-modal-cancel"><?php echo esc_html__('キャンセル', 'ou-quiz-chat'); ?></button>
        <button type="button" class="c-btn c-btn--primary js-modal-ok"><?php echo esc_html__('OK', 'ou-quiz-chat'); ?></button>
      </div>
    </div>
  </div>

  <!-- UIテンプレ（非表示；JSでクローン） -->
  <template id="tpl-bot">
    <div class="c-msg c-msg--bot">
      <div class="c-msg__icon" aria-hidden="true"></div>
      <div class="c-msg__bubble"></div>
    </div>
  </template>

  <template id="tpl-user">
    <div class="c-msg c-msg--user">
      <div class="c-msg__bubble"></div>
    </div>
  </template>

  <template id="tpl-choices">
    <div class="c-choices" role="group" aria-label="<?php echo esc_attr__('選択肢', 'ou-quiz-chat'); ?>">
      <!-- JSがボタンを差し込む -->
    </div>
  </template>

  <template id="tpl-typing">
    <div class="c-msg c-msg--bot is-typing" aria-live="off">
      <div class="c-msg__icon" aria-hidden="true"></div>
      <div class="c-msg__bubble">
        <span class="c-typing"><i></i><i></i><i></i></span>
      </div>
    </div>
  </template>
</div>
