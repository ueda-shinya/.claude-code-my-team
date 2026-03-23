<?php
namespace OU\StructuredData\Domain\Schema\Nodes;
class FAQPage {
  public static function build(array $qas, string $pageUrl): array {
    $node=['@type'=>'FAQPage','mainEntity'=>[]];
    foreach ($qas as $qa) {
      $q = trim($qa['q'] ?? ''); $a = trim($qa['a'] ?? '');
      if (!$q || !$a) continue;
      $node['mainEntity'][] = [
        '@type'=>'Question',
        'name'=>$q,
        'acceptedAnswer'=>['@type'=>'Answer','text'=>$a]
      ];
    }
    if (!empty($pageUrl)) $node['mainEntityOfPage']=$pageUrl;
    return $node;
  }
}
