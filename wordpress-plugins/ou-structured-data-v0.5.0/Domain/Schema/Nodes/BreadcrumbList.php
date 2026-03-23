<?php
namespace OU\StructuredData\Domain\Schema\Nodes;
class BreadcrumbList {
  public static function build(array $items, string $siteUrl): array {
    $list=['@type'=>'BreadcrumbList','@id'=>rtrim($siteUrl,'/').'/#breadcrumbs','itemListElement'=>[]];
    $pos=1; foreach($items as $it){ $entry=['@type'=>'ListItem','position'=>$pos++,'name'=>$it['name']]; if(!empty($it['url'])) $entry['item']=$it['url']; $list['itemListElement'][]=$entry; }
    return $list;
  }
}
