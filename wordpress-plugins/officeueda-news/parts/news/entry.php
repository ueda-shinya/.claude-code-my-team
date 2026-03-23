<?php
defined('ABSPATH') || exit;
/**
 * parts/news/entry.php
 * 期待する変数: WP_Post $post_obj
 */
if (empty($post_obj) || !($post_obj instanceof WP_Post)) return;
$lead  = get_post_meta($post_obj->ID, 'lead_text', true) ?: '';
$badge = get_post_meta($post_obj->ID, 'badge', true) ?: '';

// 前後ナビのために一時的に $post を差し替え
global $post;
$__prev = $post;
$post = $post_obj;
setup_postdata($post);
?>
<section class="c-archive c-archive--news">
    <div class="l-inner c-archive__inner">
        <article <?php post_class('p-single p-single--news', $post_obj->ID); ?>>
            <header class="p-single__header">
                <div class="p-single__meta p-news__item-header">
                    <ul class="p-single__cats" aria-label="カテゴリー">
                        <?php $cats = get_the_terms($post_obj->ID, 'news_cat');
                        if ($cats && !is_wp_error($cats)) {
                            foreach ($cats as $c) {
                                echo '<li class="p-single__cat p-news__cat font-red_hat_displya">' . esc_html($c->name) . '</li>';
                            }
                        } ?>
                    </ul>
                    <time class="p-single__date p-news__date font-Montserrat" datetime="<?php echo esc_attr(get_the_date('c', $post_obj)); ?>"><?php echo esc_html(get_the_date('Y.m.d', $post_obj)); ?></time>
                    <?php if ($badge): ?><span class="c-badge c-badge--<?php echo esc_attr($badge); ?>"><?php echo esc_html($badge); ?></span><?php endif; ?>
                </div>
                <h1 class="p-single__title p-news__title-text"><?php the_title(); ?></h1>
                <?php if ($lead): ?><p class="p-single__lead"><?php echo esc_html($lead); ?></p><?php endif; ?>
            </header>
            <div class="p-single__content"><?php the_content(); ?></div>
            <?php if (apply_filters('ou_news_entry_show_nav', true)): ?>
                <nav class="p-single__nav" aria-label="前後の記事">
                    <div class="p-single__nav-inner">
                        <div class="p-single__prev"><?php previous_post_link('%link', '＜ 前の記事へ'); ?></div>
                        <div class="p-single__back"><a href="<?php echo esc_url(get_post_type_archive_link('news')); ?>">一覧に戻る</a></div>
                        <div class="p-single__next"><?php next_post_link('%link', '次の記事へ ＞'); ?></div>
                    </div>
                </nav>
            <?php endif; ?>
        </article>
    </div>
</section>
<?php // 復元
$post = $__prev;
if ($post instanceof WP_Post) {
    setup_postdata($post);
} else {
    wp_reset_postdata();
}
