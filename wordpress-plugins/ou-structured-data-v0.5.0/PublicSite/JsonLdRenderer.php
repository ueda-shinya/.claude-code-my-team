<?php
namespace OU\StructuredData\PublicSite;
class JsonLdRenderer {
  private $usecase; public function __construct($usecase){ $this->usecase=$usecase; }
  public function output(): void { if (is_admin()) return; $graph=$this->usecase->build(); $json=wp_json_encode($graph, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); $json=str_replace('</script>','<\/script>',$json); echo "\n<script type=\"application/ld+json\">{$json}</script>\n"; }
}
