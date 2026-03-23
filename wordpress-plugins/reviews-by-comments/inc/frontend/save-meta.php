<?php
if (!defined('ABSPATH')) exit;

/** 追加項目の保存（コメントメタ） */
add_action('comment_post', function ($comment_id) {
  if (!isset($_POST['rv_force_nonce']) || !wp_verify_nonce($_POST['rv_force_nonce'], 'rv_force_nonce')) return;

  $name     = isset($_POST['rv_name'])     ? sanitize_text_field($_POST['rv_name']) : '';
  $person   = isset($_POST['rv_person'])   ? sanitize_text_field($_POST['rv_person']) : '';
  $solution = isset($_POST['rv_solution']) ? sanitize_text_field($_POST['rv_solution']) : '';
  $date     = isset($_POST['rv_date'])     ? sanitize_text_field($_POST['rv_date']) : '';
  $incident = isset($_POST['rv_incident']) ? wp_kses_post($_POST['rv_incident']) : '';

  if ($name !== '')     update_comment_meta($comment_id, 'rv_name', $name);       // 非公開・保存のみ
  if ($person !== '')   update_comment_meta($comment_id, 'rv_person', $person);
  if ($solution !== '') update_comment_meta($comment_id, 'rv_solution', $solution);
  if ($date !== '')     update_comment_meta($comment_id, 'rv_date', $date);
  if ($incident !== '') update_comment_meta($comment_id, 'rv_incident', $incident);
});
