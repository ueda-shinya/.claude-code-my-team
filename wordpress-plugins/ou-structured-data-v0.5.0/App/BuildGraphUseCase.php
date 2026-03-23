<?php
namespace OU\StructuredData\App;

use OU\StructuredData\Domain\Schema\Nodes\Organization;
use OU\StructuredData\Domain\Schema\Nodes\WebSite;
use OU\StructuredData\Domain\Schema\Nodes\BreadcrumbList;
use OU\StructuredData\Domain\Schema\Nodes\Service;
use OU\StructuredData\Domain\Schema\Nodes\FAQPage;

class BuildGraphUseCase {
  private array $common; private $breadcrumbModel;
  public function __construct(array $common, $breadcrumbModel){ $this->common=$common; $this->breadcrumbModel=$breadcrumbModel; }

  public function build(): array {
    $graph=[]; $org=Organization::build($this->common['org']??[]); $site=WebSite::build($this->common['website']??[]);
    $graph[]=$org; $graph[]=$site;

    $brItems=$this->breadcrumbModel->build();
    $graph[] = BreadcrumbList::build($brItems, $this->common['website']['url'] ?? home_url('/'));

    if (is_page()) {
      $pid = get_the_ID();
      if ($pid && get_post_meta($pid, '_ou_sd_page_type', true) === 'company') {
        $graph[] = ['@type'=>'AboutPage','mainEntity'=>['@id'=>rtrim(($this->common['org']['url']??''),'/').'/#org'],'inLanguage'=>$this->common['website']['inLanguage'] ?? 'ja'];
      }
    }

    if (is_single() && 'post' === get_post_type()) {
      $post_id = get_the_ID();
      $perma   = get_permalink($post_id);
      $title   = get_the_title($post_id);
      $pub     = get_the_date('c', $post_id);
      $mod     = get_the_modified_date('c', $post_id);
      $lang    = $this->common['website']['inLanguage'] ?? 'ja';
      $author_name=''; $author_id = get_post_field('post_author', $post_id);
      if ($author_id) { $u=get_userdata($author_id); if($u && !is_wp_error($u)) $author_name=$u->display_name?:$u->user_login; }
      $image=null; if (has_post_thumbnail($post_id)) { $src=wp_get_attachment_image_src(get_post_thumbnail_id($post_id),'full'); if(is_array($src)) $image=['@type'=>'ImageObject','url'=>$src[0],'width'=>(int)($src[1]??0),'height'=>(int)($src[2]??0)]; }
      $cats=get_the_category($post_id); $section=(!empty($cats) && !is_wp_error($cats))?$cats[0]->name:'';
      $desc=get_the_excerpt($post_id); if(!$desc){ $raw=wp_strip_all_tags(get_post_field('post_content',$post_id)); $desc=mb_substr(trim(preg_replace('/\s+/',' ',$raw)),0,160); }
      $content_raw = wp_strip_all_tags(get_post_field('post_content',$post_id));
      $wordCount = str_word_count( preg_replace('/[\x80-\xff]/', ' ', $content_raw) );
      $article=['@type'=>'BlogPosting','mainEntityOfPage'=>$perma,'headline'=>$title,'datePublished'=>$pub,'dateModified'=>$mod,'inLanguage'=>$lang,'isAccessibleForFree'=>true,'author'=>$author_name?['@type'=>'Person','name'=>$author_name]:['@type'=>'Organization','name'=>($this->common['org']['name']??'')],'publisher'=>['@id'=>rtrim(($this->common['org']['url']??''),'/').'/#org'],'articleSection'=>$section?:null,'description'=>$desc?:null,'wordCount'=>$wordCount>0?$wordCount:null];
      if($image) $article['image']=$image;
      $graph[] = array_filter($article, fn($v)=>!is_null($v) && $v!=='');
    }

    if (is_singular('service')) {
      $pid = get_the_ID();
      $name = get_the_title($pid);
      $desc = get_post_meta($pid, '_ou_sd_service_desc', true);
      if (!$desc) { $raw = wp_strip_all_tags(get_post_field('post_content',$pid)); $desc = mb_substr(trim(preg_replace('/\s+/',' ',$raw)),0,160); }
      $img=null; if (has_post_thumbnail($pid)) { $s=wp_get_attachment_image_src(get_post_thumbnail_id($pid),'full'); if(is_array($s)) $img=['@type'=>'ImageObject','url'=>$s[0],'width'=>(int)($s[1]??0),'height'=>(int)($s[2]??0)]; }
      $graph[] = Service::build(['name'=>$name,'description'=>$desc,'orgUrl'=>$this->common['org']['url']??'','url'=>get_permalink($pid),'image'=>$img]);
    }

    if (is_singular()) {
      $pid = get_the_ID(); $qas = get_transient('ou_sd_faq_qas_'.$pid);
      if ($qas && is_array($qas) && count($qas)>0) {
        $graph[] = FAQPage::build($qas, get_permalink($pid));
        delete_transient('ou_sd_faq_qas_'.$pid);
      }
    }

    return ['@context'=>'https://schema.org','@graph'=>$graph];
  }
}
