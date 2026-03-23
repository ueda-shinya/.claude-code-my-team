<?php
namespace OU\StructuredData\Infra\Breadcrumb\Generators;
class ModelGenerator {
  public function build(): array {
    $opt = get_option('ou_schema_common', []);
    $home_label = !empty($opt['breadcrumb']['useSiteTitle']) ? get_bloginfo('name') : ($opt['breadcrumb']['homeLabel'] ?? 'HOME');
    $home_url = home_url('/');
    if (is_front_page() || (is_home() && !is_singular())) { return [['name'=>$home_label,'url'=>'']]; }
    $items=[]; $items[]=['name'=>$home_label,'url'=>$home_url];
    if (is_page()) {
      $anc=array_reverse(get_post_ancestors(get_the_ID())); foreach($anc as $pid){ $items[]=['name'=>get_the_title($pid),'url'=>get_permalink($pid)]; } $items[]=['name'=>get_the_title(),'url'=>''];
    } elseif (is_single()) {
      $cats=get_the_category(); if (!empty($cats)) { $cat=$cats[0]; $anc=array_reverse(get_ancestors($cat->term_id,'category')); foreach($anc as $tid){ $t=get_term($tid,'category'); if($t && !is_wp_error($t)) $items[]=['name'=>$t->name,'url'=>get_term_link($t)]; } $items[]=['name'=>$cat->name,'url'=>get_term_link($cat)]; }
      $items[]=['name'=>get_the_title(),'url'=>''];
    } elseif (is_category()) {
      $cat=get_queried_object(); $anc=array_reverse(get_ancestors($cat->term_id,'category')); foreach($anc as $tid){ $t=get_term($tid,'category'); $items[]=['name'=>$t->name,'url'=>get_term_link($t)]; } $items[]=['name'=>$cat->name,'url'=>''];
    } else { $items[]=['name'=>wp_get_document_title(),'url'=>'']; }
    return $items;
  }
}
