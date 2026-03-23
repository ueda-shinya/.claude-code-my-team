<?php
namespace OU\StructuredData\Admin\Assets;
class Assets {
  public function enqueue($hook): void {
    if (strpos($hook, 'ou-structured-data') === false) return;
    wp_enqueue_style('ou-sd-admin', plugins_url('assets/admin.css', OU_SD_FILE), [], OU_SD_VERSION);
    wp_enqueue_script('ou-sd-admin', plugins_url('assets/admin.js', OU_SD_FILE), ['jquery'], OU_SD_VERSION, true);
  }
}
