<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 管理画面にメニュー追加
function jsonld_sd_add_settings_menu() {
  add_options_page(
    'JSON-LD構造化データ設定',
    'JSON-LD構造化データ',
    'manage_options',
    'jsonld_sd_settings',
    'jsonld_sd_render_settings_page'
  );
}
add_action('admin_menu', 'jsonld_sd_add_settings_menu');

// 設定画面の出力
function jsonld_sd_render_settings_page() {
  if (!current_user_can('manage_options')) return;

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('jsonld_sd_save_settings')) {
    $saved = jsonld_sd_sanitize_and_save($_POST);
    echo '<div class="updated"><p>設定を保存しました。</p></div>';
  }

  $options = jsonld_sd_get_options();
  $post_types = get_post_types(['public' => true], 'objects');
  ?>
  <div class="wrap">
    <h1>JSON-LD構造化データ設定</h1>
    <form method="post">
      <?php wp_nonce_field('jsonld_sd_save_settings'); ?>

      <h2>基本情報</h2>
      <table class="form-table">
        <tr>
          <th><label for="publisher_name">運営者名</label></th>
          <td><input type="text" name="publisher_name" id="publisher_name" value="<?php echo esc_attr($options['publisher_name']); ?>" class="regular-text"></td>
        </tr>
        <tr>
          <th><label for="logo_url">ロゴ画像URL</label></th>
          <td>
            <input type="text" name="logo_url" id="logo_url" value="<?php echo esc_url($options['logo_url']); ?>" class="regular-text">
            <button type="button" class="button jsonld-sd-select-media" data-target="logo_url">メディアから選択</button>
          </td>
        </tr>
        <tr>
          <th><label for="default_image">デフォルト画像URL</label></th>
          <td>
            <input type="text" name="default_image" id="default_image" value="<?php echo esc_url($options['default_image']); ?>" class="regular-text">
            <button type="button" class="button jsonld-sd-select-media" data-target="default_image">メディアから選択</button>
          </td>
        </tr>
      </table>

      <h2>出力対象投稿タイプ</h2>
      <table class="form-table">
        <tr>
          <th>対象とする投稿タイプ</th>
          <td>
            <?php foreach ($post_types as $type): ?>
              <label><input type="checkbox" name="post_types[]" value="<?php echo esc_attr($type->name); ?>" <?php checked(in_array($type->name, $options['post_types'], true)); ?>> <?php echo esc_html($type->label); ?></label><br>
            <?php endforeach; ?>
          </td>
        </tr>
        <tr>
          <th><label for="use_excerpt_fallback">抜粋がない場合は本文冒頭を使用</label></th>
          <td><input type="checkbox" name="use_excerpt_fallback" id="use_excerpt_fallback" value="1" <?php checked($options['use_excerpt_fallback']); ?>></td>
        </tr>
      </table>

      <h2>モバイル対応と削除設定</h2>
      <table class="form-table">
        <tr>
          <th><label for="accessibility_flag">無料/有料フラグの出力</label></th>
          <td>
            <label><input type="radio" name="accessibility_flag" value="none" <?php checked($options['accessibility_flag'], 'none'); ?>> 出力しない</label><br>
            <label><input type="radio" name="accessibility_flag" value="free" <?php checked($options['accessibility_flag'], 'free'); ?>> 無料として出力</label><br>
            <label><input type="radio" name="accessibility_flag" value="paid" <?php checked($options['accessibility_flag'], 'paid'); ?>> 有料として出力</label><br>
          </td>
        </tr>
        <tr>
          <th><label for="delete_on_uninstall">プラグイン削除時に設定を削除</label></th>
          <td><input type="checkbox" name="delete_on_uninstall" id="delete_on_uninstall" value="1" <?php checked($options['delete_on_uninstall']); ?>></td>
        </tr>
      </table>

      <p class="submit">
        <input type="submit" class="button-primary" value="設定を保存">
      </p>
    </form>
  </div>
  <?php
}
