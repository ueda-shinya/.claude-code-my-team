<?php
namespace OU\StructuredData\Admin\Preview;
class PreviewPage {
  private $usecase;
  public function __construct($usecase){ $this->usecase=$usecase; }
  public function registerMenu(): void {
    add_submenu_page('ou-structured-data', __('JSON-LD Preview','ou-structured-data'), __('Preview','ou-structured-data'), 'manage_options', 'ou-structured-data-preview', [$this,'render']);
  }
  public function render(): void {
    if (!current_user_can('manage_options')) return;
    $graph = $this->usecase->build();
    echo '<div class="wrap"><h1>JSON-LD Preview</h1>';
    echo '<textarea style="width:100%;height:420px" readonly>'.esc_textarea(wp_json_encode($graph, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).'</textarea>';
    echo '</div>';
  }
}
