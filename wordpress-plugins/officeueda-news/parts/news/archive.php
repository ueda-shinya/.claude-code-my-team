<?php
defined('ABSPATH') || exit;
/**
 * parts/news/archive.php
 * 期待する変数: WP_Post[] $posts, int $max, int $cur, string $page_qs
 */
$n_length   = (int) apply_filters('ou_news_archive_title_width', 100);
$show_filter= (bool) apply_filters('ou_news_archive_show_filter', false);
$term_obj   = function_exists('get_queried_object') ? get_queried_object() : null;
?>
<div class="l-inner l-archive-header__inner">
  <h1 class="c-archive-header__title  c-title">NEWS</h1>
  <?php if (is_tax('news_cat') && $term_obj instanceof WP_Term): ?>
    <p class="c-archive-header__desc"><?php echo esc_html($term_obj->name); ?> の記事</p>
  <?php endif; ?>
</div>

<?php if ($show_filter): ?>
<section class="c-filter" aria-label="お知らせの絞り込み">
  <div class="l-inner">
    <form class="c-filter__form" method="get" action="<?php echo esc_url(get_post_type_archive_link('news')); ?>">
      <label class="c-filter__label" for="news_cat">カテゴリー</label>
      <select class="c-filter__select" id="news_cat" name="news_cat" aria-label="カテゴリーを選択">
        <option value="">すべて</option>
        <?php $terms = get_terms(['taxonomy'=>'news_cat','hide_empty'=>true]); if(!is_wp_error($terms)){ foreach($terms as $t){ printf('<option value="%1$s">%2$s</option>', esc_attr($t->slug), esc_html($t->name)); } } ?>
      </select>
      <button type="submit" class="c-filter__submit">絞り込む</button>
    </form>
  </div>
</section>
<?php endif; ?>

<section class="c-archive c-archive--news">
  <div class="l-inner c-archive__inner">
    <?php if (!empty($posts)): ?>
      <ul class="c-card-list c-card-list--news p-news__list" role="list">
        <?php foreach ($posts as $p):
          $badge = get_post_meta($p->ID,'badge',true) ?: '';
          $ext   = get_post_meta($p->ID,'external_url',true) ?: '';
          $blank = (bool) get_post_meta($p->ID,'external_blank',true);
          $href  = $ext ? esc_url($ext) : esc_url(get_permalink($p));
          $tgt   = $blank ? ' target="_blank" rel="noopener"' : '';
          $cats  = get_the_terms($p->ID,'news_cat');
          $cat_name='未分類'; if($cats && !is_wp_error($cats)){ $first=array_shift($cats); if($first) $cat_name=$first->name; }
          $title_text = wp_strip_all_tags(get_the_title($p));
          $trimmed = (mb_strwidth($title_text,'UTF-8') > $n_length) ? mb_strimwidth($title_text,0,$n_length,'…','UTF-8') : $title_text;
        ?>
        <li class="c-card-list__item p-news__item">
          <article class="c-card c-card--news">
            <a class="c-card__link p-news__link" href="<?php echo $href; ?>"<?php echo $tgt; ?>>
              <div class="c-card__head p-news__item-header">
                <span class="p-news__cat font-red_hat_displya"><?php echo esc_html($cat_name); ?></span>
                <time class="c-card__date p-news__date font-Montserrat" datetime="<?php echo esc_attr(get_the_date('c',$p)); ?>"><?php echo esc_html(get_the_date('Y.m.d',$p)); ?></time>
              </div>
              <h2 class="p-news__title-text"><?php echo esc_html($trimmed); ?></h2>
            </a>
          </article>
        </li>
        <?php endforeach; ?>
      </ul>

      <?php
      // ===== ページネーション（ショートコード/ブロック運用時） =====
      if (isset($max, $cur, $page_qs) && $max > 1) {
        // 現在URLからページ用クエリだけ除去
        $base_url = remove_query_arg($page_qs);

        // 引き継ぐクエリをホワイトリストで限定
        $persist = [];
        if (isset($_GET['news_cat'])) {
          $persist['news_cat'] = sanitize_text_field( wp_unslash($_GET['news_cat']) );
        }

        // ベースURLを構築し、ページ番号のプレースホルダを付与
        $base_url = add_query_arg($persist, $base_url);
        $base     = add_query_arg($page_qs, '%#%', $base_url);
        ?>
        <nav class="c-pagination" aria-label="ページナビゲーション">
          <?php echo paginate_links([
            'base'      => esc_url($base),
            'format'    => '',
            'total'     => (int) $max,
            'current'   => (int) $cur,
            'mid_size'  => 1,
            'prev_text' => '＜',
            'next_text' => '＞',
            'type'      => 'list',
          ]); ?>
        </nav>
      <?php
      // ===== 通常のアーカイブテンプレとして使う場合 =====
      } else { ?>
        <nav class="c-pagination" aria-label="ページナビゲーション">
          <?php echo paginate_links([ 'mid_size' => 1, 'prev_text' => '＜', 'next_text' => '＞' ]); ?>
        </nav>
      <?php } ?>

    <?php else: ?>
      <p class="c-archive__empty">該当するお知らせはありません。</p>
    <?php endif; ?>

  </div>
</section>
