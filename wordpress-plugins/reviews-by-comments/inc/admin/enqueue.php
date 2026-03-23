<?php
if (!defined('ABSPATH')) exit;

add_action('admin_enqueue_scripts', function(){
  $s = function_exists('get_current_screen') ? get_current_screen() : null;
  if ($s && $s->id === 'comment') {
    wp_enqueue_media();
    wp_enqueue_script('jquery');
  }
});
