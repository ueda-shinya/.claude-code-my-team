<?php
namespace OU\StructuredData\Domain\Schema\Nodes;
class Organization {
  public static function build(array $org): array {
    $node=['@type'=>'Organization','@id'=>rtrim($org['url']??'','/').'/#org','name'=>$org['name']??'','url'=>$org['url']??'','inLanguage'=>$org['inLanguage']??'ja'];
    if(!empty($org['logo'])){ $img=['@type'=>'ImageObject','url'=>$org['logo']]; if(!empty($org['logo_w'])) $img['width']=(int)$org['logo_w']; if(!empty($org['logo_h'])) $img['height']=(int)$org['logo_h']; $node['logo']=$img; }
    if(!empty($org['telephone_e164'])) $node['telephone']=$org['telephone_e164']; elseif(!empty($org['telephone'])) $node['telephone']=$org['telephone'];
    if (!empty($org['address']['ja']['addressRegion']) || !empty($org['address']['ja']['streetAddress'])) { $ja=$org['address']['ja']; $node['address']=['@type'=>'PostalAddress','postalCode'=>$ja['postalCode']??'','addressRegion'=>$ja['addressRegion']??'','addressLocality'=>$ja['addressLocality']??'','streetAddress'=>trim(($ja['streetAddress']??'').' '.($ja['building']??'')),'addressCountry'=>'JP']; }
    if (!empty($org['openingHoursSpecification'])) { $specs=[]; foreach($org['openingHoursSpecification'] as $r){ $specs[]=['@type'=>'OpeningHoursSpecification','dayOfWeek'=>$r['dayOfWeek']??[],'opens'=>$r['opens']??'','closes'=>$r['closes']??'']; } if($specs) $node['openingHoursSpecification']=$specs; }
    if (!empty($org['sameAs'])) $node['sameAs']=array_values(array_filter($org['sameAs']));
    if (!empty($org['contactPoint'])) { $cps=[]; foreach($org['contactPoint'] as $cp){ $row=['@type'=>'ContactPoint']; if(!empty($cp['contactType'])) $row['contactType']=$cp['contactType']; if(!empty($cp['telephone'])) $row['telephone']=$cp['telephone']; if(!empty($cp['url'])) $row['url']=$cp['url']; if(!empty($cp['availableLanguage'])) $row['availableLanguage']=$cp['availableLanguage']; if(count($row)>1) $cps[]=$row; } if($cps) $node['contactPoint']=$cps; }
    return $node;
  }
}
