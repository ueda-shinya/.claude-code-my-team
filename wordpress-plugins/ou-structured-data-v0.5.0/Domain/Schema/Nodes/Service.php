<?php
namespace OU\StructuredData\Domain\Schema\Nodes;
class Service {
  public static function build(array $data): array {
    $node=['@type'=>'Service','name'=>$data['name']??'','serviceType'=>$data['serviceType']??($data['name']??''),'provider'=>['@id'=>rtrim($data['orgUrl']??'','/').'/#org']];
    if(!empty($data['description'])) $node['description']=$data['description'];
    if(!empty($data['areaServed'])) $node['areaServed']=$data['areaServed'];
    if(!empty($data['url'])) $node['url']=$data['url'];
    if(!empty($data['image'])) $node['image']=$data['image'];
    return $node;
  }
}
