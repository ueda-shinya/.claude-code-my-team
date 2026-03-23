<?php
if (!defined('ABSPATH')) exit;

add_filter('manage_edit-comments_columns', function ($cols) {
  $new = [];
  foreach ($cols as $key => $label) {
    $new[$key] = $label;
    if ($key === 'comment') {
      $new['rv_person']   = 'ご相談者様について';
      $new['rv_solution'] = '解決手段';
      $new['rv_date']     = '解決時期';
      $new['rv_incident'] = '相談した出来事';
      $new['rv_name']     = 'お名前(非公開)';
      $new['rv_icon']     = 'アイコン';
    }
  }
  return $new;
});

add_action('manage_comments_custom_column', function ($column, $comment_ID) {
  switch ($column) {
    case 'rv_person':
      echo esc_html(get_comment_meta($comment_ID, 'rv_person', true)); break;
    case 'rv_solution':
      echo esc_html(get_comment_meta($comment_ID, 'rv_solution', true)); break;
    case 'rv_date':
      echo esc_html(get_comment_meta($comment_ID, 'rv_date', true)); break;
    case 'rv_incident':
      $txt = get_comment_meta($comment_ID, 'rv_incident', true);
      $txt = wp_strip_all_tags(wp_trim_words($txt, 20, ' …'));
      echo esc_html($txt); break;
    case 'rv_name':
      echo esc_html(get_comment_meta($comment_ID, 'rv_name', true)); break;
    case 'rv_icon':
      $icon_id = (int) get_comment_meta($comment_ID, 'rv_icon_id', true);
      if ($icon_id) echo wp_get_attachment_image($icon_id, [40,40], false, ['style'=>'border-radius:50%']);
      break;
  }
}, 10, 2);
