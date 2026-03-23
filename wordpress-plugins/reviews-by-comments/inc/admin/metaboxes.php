<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes_comment', function () {
  add_meta_box('rv_fields_box','口コミ情報','rvc_fields_box_cb','comment','normal','high');
  add_meta_box('rv_icon_box','口コミアイコン画像','rvc_icon_box_cb','comment','normal','high');
});

add_filter('default_hidden_meta_boxes', function($hidden, $screen){
  if ($screen && $screen->id === 'comment') {
    $hidden = array_diff($hidden, ['rv_fields_box','rv_icon_box']);
  }
  return $hidden;
}, 10, 2);

function rvc_fields_box_cb($comment){
  $cid      = $comment->comment_ID;
  $name     = get_comment_meta($cid, 'rv_name', true);
  $person   = get_comment_meta($cid, 'rv_person', true);
  $solution = get_comment_meta($cid, 'rv_solution', true);
  $date     = get_comment_meta($cid, 'rv_date', true);
  $incident = get_comment_meta($cid, 'rv_incident', true);
  wp_nonce_field('rv_fields_save', 'rv_fields_nonce'); ?>
  <table class="form-table">
    <tr>
      <th><label for="rv_name">お名前（非公開）</label></th>
      <td><input type="text" class="regular-text" id="rv_name" name="rv_name" value="<?php echo esc_attr($name); ?>"></td>
    </tr>
    <tr>
      <th><label for="rv_person">ご相談者様について</label></th>
      <td>
        <select id="rv_person" name="rv_person">
          <?php
            $options = ['','20代/男性','30代/男性','40代/男性','50代/男性','60代/男性','20代/女性','30代/女性','40代/女性','50代/女性','60代/女性','その他'];
            foreach ($options as $opt) {
              printf('<option value="%s"%s>%s</option>',
                esc_attr($opt),
                selected($person, $opt, false),
                $opt ? esc_html($opt) : '（空）'
              );
            }
          ?>
        </select>
      </td>
    </tr>
    <tr>
      <th><label for="rv_solution">解決手段</label></th>
      <td>
        <select id="rv_solution" name="rv_solution">
          <?php
            $sols = ['','交渉・示談','無料・有利','書類作成','その他'];
            foreach ($sols as $opt) {
              printf('<option value="%s"%s>%s</option>',
                esc_attr($opt),
                selected($solution, $opt, false),
                $opt ? esc_html($opt) : '（空）'
              );
            }
          ?>
        </select>
      </td>
    </tr>
    <tr>
      <th><label for="rv_date">解決時期</label></th>
      <td><input type="date" id="rv_date" name="rv_date" value="<?php echo esc_attr($date); ?>"></td>
    </tr>
    <tr>
      <th><label for="rv_incident">相談した出来事</label></th>
      <td><textarea id="rv_incident" name="rv_incident" rows="4" class="large-text"><?php echo esc_textarea($incident); ?></textarea></td>
    </tr>
  </table>
<?php }

function rvc_icon_box_cb($comment){
  $cid      = (int) $comment->comment_ID;
  $icon_id  = (int) get_comment_meta($cid, 'rv_icon_id', true);
  $img_html = $icon_id ? wp_get_attachment_image($icon_id, 'thumbnail', false, ['style'=>'max-width:100px;height:auto;border-radius:8px']) : '';
  wp_nonce_field('rv_icon_save','rv_icon_nonce'); ?>
  <div id="rv-icon-field">
    <div class="rv-icon-preview" style="margin-bottom:8px;"><?php echo $img_html ?: '<em>未設定</em>'; ?></div>
    <input type="hidden" id="rv_icon_id" name="rv_icon_id" value="<?php echo esc_attr($icon_id); ?>">
    <button type="button" class="button" id="rv_icon_select">画像を選択</button>
    <button type="button" class="button" id="rv_icon_clear" <?php disabled(!$icon_id); ?>>クリア</button>
    <p class="description">※ 正方形の画像を推奨（例: 200×200px）。</p>
  </div>
  <script>
    (function($){
      $(function(){
        var frame;
        $('#rv_icon_select').on('click', function(e){
          e.preventDefault();
          if (frame) { frame.open(); return; }
          frame = wp.media({
            title: 'アイコン画像を選択',
            button: { text: 'この画像を使用' },
            library: { type: 'image' },
            multiple: false
          });
          frame.on('select', function(){
            var att = frame.state().get('selection').first().toJSON();
            $('#rv_icon_id').val(att.id);
            var url = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
            $('.rv-icon-preview').html('<img src="'+url+'" style="max-width:100px;height:auto;border-radius:8px;">');
            $('#rv_icon_clear').prop('disabled', false);
          });
          frame.open();
        });
        $('#rv_icon_clear').on('click', function(){
          $('#rv_icon_id').val(''); $('.rv-icon-preview').html('<em>未設定</em>'); $(this).prop('disabled', true);
        });
      });
    })(jQuery);
  </script>
<?php }

/** コメント編集で保存（口コミ情報・アイコン画像） */
add_action('edit_comment', function ($comment_ID) {
  if (!current_user_can('edit_comment', $comment_ID)) return;

  if (isset($_POST['rv_fields_nonce']) && wp_verify_nonce($_POST['rv_fields_nonce'], 'rv_fields_save')) {
    $map = [
      'rv_name'     => 'sanitize_text_field',
      'rv_person'   => 'sanitize_text_field',
      'rv_solution' => 'sanitize_text_field',
      'rv_date'     => 'sanitize_text_field',
      'rv_incident' => function($v){ return wp_kses_post($v); },
    ];
    foreach ($map as $key => $sanitizer) {
      if (array_key_exists($key, $_POST)) {
        $val = is_callable($sanitizer) ? $sanitizer($_POST[$key]) : sanitize_text_field($_POST[$key]);
        ($val === '') ? delete_comment_meta($comment_ID, $key) : update_comment_meta($comment_ID, $key, $val);
      }
    }
  }

  if (isset($_POST['rv_icon_nonce']) && wp_verify_nonce($_POST['rv_icon_nonce'], 'rv_icon_save')) {
    $icon_id = isset($_POST['rv_icon_id']) ? intval($_POST['rv_icon_id']) : 0;
    if ($icon_id > 0) {
      $mime = get_post_mime_type($icon_id);
      if ($mime && strpos($mime, 'image/') === 0) {
        update_comment_meta($comment_ID, 'rv_icon_id', $icon_id);
      }
    } else {
      delete_comment_meta($comment_ID, 'rv_icon_id');
    }
  }
});
