<?php
namespace OU\StructuredData\Domain\Schema\Nodes;
class WebSite {
  public static function build(array $site): array {
    $base=rtrim($site['url']??'','/');
    $node=['@type'=>'WebSite','@id'=>$base+'/#website','name'=>$site['name']??'','url'=>$site['url']??'','inLanguage'=>$site['inLanguage']??'ja'];
    if(!empty($site['alternateName'])) $node['alternateName']=$site['alternateName'];
    if(!empty($site['url'])) $node['potentialAction']=['@type'=>'SearchAction','target'=>$base+'/?s={search_term_string}','query-input'=>'required name=search_term_string'];
    return $node;
  }
}
