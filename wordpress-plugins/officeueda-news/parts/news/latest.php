<?php
defined('ABSPATH') || exit;
/**
 * parts/news/latest.php
 * 期待する変数: WP_Post[] $posts
 */
if (empty($posts)) return;
$n_length = (int) apply_filters('ou_news_latest_title_width', 100);
?>
<section class="l-news p-news" aria-labelledby="p-news-title">
  <div class="l-inner">
    <h2 id="p-news-title" class="p-news__title c-title">NEWS</h2>
    <ul class="p-news__list" role="list">
      <?php foreach ($posts as $p):
        $badge = get_post_meta($p->ID, 'badge', true) ?: '';
        $ext   = get_post_meta($p->ID, 'external_url', true) ?: '';
        $blank = (bool) get_post_meta($p->ID, 'external_blank', true);
        $href  = $ext ? esc_url($ext) : get_permalink($p);
        $tgt   = ($ext && $blank) ? ' target="_blank" rel="noopener"' : '';
        $cats  = get_the_terms($p->ID, 'news_cat');
        $cat_name = '未分類'; if ($cats && !is_wp_error($cats)) { $first = array_shift($cats); if($first) $cat_name=$first->name; }
        $title_text = wp_strip_all_tags(get_the_title($p));
        $trimmed = (mb_strwidth($title_text,'UTF-8') > $n_length) ? mb_strimwidth($title_text,0,$n_length,'…','UTF-8') : $title_text;
      ?>
      <li class="p-news__item">
        <a class="p-news__link" href="<?php echo $href; ?>"<?php echo $tgt; ?>>
          <div class="p-news__item-header">
            <?php if ($badge): ?><span class="p-news__badge"><?php echo esc_html($badge); ?></span><?php endif; ?>
            <span class="p-news__cat font-red_hat_displya"><?php echo esc_html($cat_name); ?></span>
            <time class="p-news__date font-Montserrat" datetime="<?php echo esc_attr(get_the_date('c',$p)); ?>"><?php echo esc_html(get_the_date('Y.m.d',$p)); ?></time>
          </div>
          <span class="p-news__title-text"><?php echo esc_html($trimmed); ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
    <p class="p-news__more"><a href="<?php echo esc_url(get_post_type_archive_link('news')); ?>">＞ お知らせ一覧</a></p>
  </div>
</section>
