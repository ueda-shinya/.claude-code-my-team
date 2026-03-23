<?php
if (!defined('ABSPATH')) exit;

/** 本体ショートコード：一覧＋投稿フォーム（承認済みのみ表示） */
add_shortcode('reviews_force', function ($atts) {
  if (!is_singular()) return '';

  $atts = shortcode_atts([
    'per_page'     => 0, // 0=全件取得
    'show_initial' => 3, // 初期表示件数（per_page=0 のとき有効）
  ], $atts, 'reviews_force');

  $per_page     = max(0, intval($atts['per_page']));
  $show_initial = max(0, intval($atts['show_initial']));
  $post_id      = get_the_ID();
  $page         = max(1, isset($_GET['rvpage']) ? intval($_GET['rvpage']) : 1);

  // 件数
  $total = get_comments(['post_id' => $post_id, 'status' => 'approve', 'count' => true]);

  // 承認済みコメントの取得
  $args = [
    'post_id' => $post_id,
    'status'  => 'approve',
    'orderby' => 'comment_date_gmt',
    'order'   => 'DESC',
  ];
  if ($per_page > 0) {
    $args['number'] = $per_page;
    $args['offset'] = ($page - 1) * $per_page;
  }
  $comments = get_comments($args);

  // “もっとみる” のためのユニークID
  $uid = 'rvmore-' . $post_id . '-' . wp_generate_password(4, false, false);

  ob_start(); ?>
  <section class="p-reviews" id="reviews">
    <h2 class="p-reviews__title">口コミ投稿はこちら</h2>

    <?php if ($comments): ?>
      <?php
        $use_more = ($per_page === 0 && $show_initial > 0 && count($comments) > $show_initial);
        $first    = $use_more ? array_slice($comments, 0, $show_initial) : $comments;
        $remains  = $use_more ? array_slice($comments, $show_initial) : [];
      ?>

      <ol class="p-review-list">
        <?php foreach ($first as $c):
          $cid      = $c->comment_ID;
          $person   = get_comment_meta($cid, 'rv_person', true);
          $solution = get_comment_meta($cid, 'rv_solution', true);
          $date     = get_comment_meta($cid, 'rv_date', true);
          $incident = get_comment_meta($cid, 'rv_incident', true);
          $icon_id  = (int) get_comment_meta($cid, 'rv_icon_id', true);
          $icon     = $icon_id ? wp_get_attachment_image($icon_id, 'thumbnail', false, ['class' => 'p-review-card__avatar', 'alt' => '']) : '';

          // 名前があれば表示、なければ「匿名」
          $name           = trim((string) get_comment_meta($cid, 'rv_name', true));
          $display_name   = ($name !== '') ? esc_html($name) : '匿名';
        ?>
          <li id="comment-<?php echo (int)$cid; ?>" class="p-review-item">
            <article class="p-review-card">
              <header class="p-review-card__head">
                <?php if ($icon) echo $icon; ?>
                <span class="p-review-card__author"><?php echo $display_name; ?></span>
                <time class="p-review-card__date" datetime="<?php echo esc_attr(get_comment_date('c', $c)); ?>">
                  <?php echo esc_html(get_comment_date('', $c)); ?>
                </time>
              </header>

              <dl class="p-review-card__meta">
                <?php if ($person): ?>
                  <div class="p-review-card__row"><dt class="p-review-card__dt">ご相談者様について</dt><dd class="p-review-card__dd"><?php echo esc_html($person); ?></dd></div>
                <?php endif; ?>
                <?php if ($solution): ?>
                  <div class="p-review-card__row"><dt class="p-review-card__dt">解決手段</dt><dd class="p-review-card__dd"><?php echo esc_html($solution); ?></dd></div>
                <?php endif; ?>
                <?php if ($date): ?>
                  <div class="p-review-card__row"><dt class="p-review-card__dt">解決時期</dt><dd class="p-review-card__dd"><?php echo esc_html($date); ?></dd></div>
                <?php endif; ?>
              </dl>

              <?php if ($incident): ?>
                <section class="p-review-card__section">
                  <h4 class="p-review-card__subtitle">相談した出来事</h4>
                  <div class="p-review-card__text"><?php echo wpautop(wp_kses_post($incident)); ?></div>
                </section>
              <?php endif; ?>

              <section class="p-review-card__section">
                <h4 class="p-review-card__subtitle">ご感想</h4>
                <div class="p-review-card__text"><?php echo wpautop(wp_kses_post($c->comment_content)); ?></div>
              </section>
            </article>
          </li>
        <?php endforeach; ?>
      </ol>

      <?php if ($use_more): ?>
        <div class="p-review-more">
          <button id="<?php echo esc_attr($uid); ?>-btn" class="c-btn c-btn--ghost" type="button"
                  aria-expanded="false" aria-controls="<?php echo esc_attr($uid); ?>-box">
            もっとみる（残り<?php echo (int)count($remains); ?>件）
          </button>
        </div>

        <div id="<?php echo esc_attr($uid); ?>-box" class="p-review-remains" hidden>
          <ol class="p-review-list">
            <?php foreach ($remains as $c):
              $cid      = $c->comment_ID;
              $person   = get_comment_meta($cid, 'rv_person', true);
              $solution = get_comment_meta($cid, 'rv_solution', true);
              $date     = get_comment_meta($cid, 'rv_date', true);
              $incident = get_comment_meta($cid, 'rv_incident', true);
              $icon_id  = (int) get_comment_meta($cid, 'rv_icon_id', true);
              $icon     = $icon_id ? wp_get_attachment_image($icon_id, 'thumbnail', false, ['class' => 'p-review-card__avatar', 'alt' => '']) : '';

              $name           = trim((string) get_comment_meta($cid, 'rv_name', true));
              $display_name   = ($name !== '') ? esc_html($name) : '匿名';
            ?>
              <li id="comment-<?php echo (int)$cid; ?>" class="p-review-item">
                <article class="p-review-card">
                  <header class="p-review-card__head">
                    <?php if ($icon) echo $icon; ?>
                    <span class="p-review-card__author"><?php echo $display_name; ?></span>
                    <time class="p-review-card__date" datetime="<?php echo esc_attr(get_comment_date('c', $c)); ?>">
                      <?php echo esc_html(get_comment_date('', $c)); ?>
                    </time>
                  </header>

                  <dl class="p-review-card__meta">
                    <?php if ($person): ?>
                      <div class="p-review-card__row"><dt class="p-review-card__dt">ご相談者様について</dt><dd class="p-review-card__dd"><?php echo esc_html($person); ?></dd></div>
                    <?php endif; ?>
                    <?php if ($solution): ?>
                      <div class="p-review-card__row"><dt class="p-review-card__dt">解決手段</dt><dd class="p-review-card__dd"><?php echo esc_html($solution); ?></dd></div>
                    <?php endif; ?>
                    <?php if ($date): ?>
                      <div class="p-review-card__row"><dt class="p-review-card__dt">解決時期</dt><dd class="p-review-card__dd"><?php echo esc_html($date); ?></dd></div>
                    <?php endif; ?>
                  </dl>

                  <?php if ($incident): ?>
                    <section class="p-review-card__section">
                      <h4 class="p-review-card__subtitle">相談した出来事</h4>
                      <div class="p-review-card__text"><?php echo wpautop(wp_kses_post($incident)); ?></div>
                    </section>
                  <?php endif; ?>

                  <section class="p-review-card__section">
                    <h4 class="p-review-card__subtitle">ご感想</h4>
                    <div class="p-review-card__text"><?php echo wpautop(wp_kses_post($c->comment_content)); ?></div>
                  </section>
                </article>
              </li>
            <?php endforeach; ?>
          </ol>
        </div>

        <noscript>
          <p><a href="<?php echo esc_url( get_permalink($post_id) ); ?>#reviews">全ての口コミを見る（JavaScriptが無効です）</a></p>
        </noscript>

        <script>
          (function(){
            var btn = document.getElementById('<?php echo esc_js($uid); ?>-btn');
            var box = document.getElementById('<?php echo esc_js($uid); ?>-box');
            if(!btn || !box) return;
            btn.addEventListener('click', function(){
              var hidden = box.hasAttribute('hidden');
              if (hidden) {
                box.removeAttribute('hidden');
                btn.setAttribute('aria-expanded', 'true');
                btn.textContent = '閉じる';
              } else {
                box.setAttribute('hidden','');
                btn.setAttribute('aria-expanded', 'false');
                btn.textContent = 'もっとみる（残り<?php echo (int)count($remains); ?>件）';
              }
            });
          })();
        </script>

      <?php elseif ($per_page > 0 && $total > $per_page): ?>
        <?php
          $total_pages = (int)ceil($total / $per_page);
          $links = paginate_links([
            'base'      => esc_url(remove_query_arg('rvpage')) . '%_%',
            'format'    => (strpos($_SERVER['REQUEST_URI'], '?') === false ? '?rvpage=%#%' : '&rvpage=%#%'),
            'current'   => $page,
            'total'     => $total_pages,
            'type'      => 'list',
            'prev_text' => '« 前へ',
            'next_text' => '次へ »',
          ]);
          if ($links) {
            $links = preg_replace('~href="([^"]+)"~', 'href="$1#reviews"', $links);
            echo '<nav class="p-review-pagination" aria-label="口コミのページネーション">'.$links.'</nav>';
          }
        ?>
      <?php endif; ?>

    <?php else: ?>
      <p class="c-empty">まだ投稿された口コミはありません。</p>
    <?php endif; ?>

    <div class="p-reviews__form" id="reviews-form">
      <?php
      $GLOBALS['rv_force_rendering'] = true;
      comment_form([
        'title_reply'          => '',
        'label_submit'         => '送信',
        'comment_field'        => '<div class="c-field"><label class="c-field__label" for="comment">ご感想 <span class="c-badge c-badge--req">必須</span></label><textarea id="comment" name="comment" class="c-field__control" rows="6" required aria-required="true" placeholder="※個人名など、後悔したくない情報のご記入はお控えください。"></textarea></div>',
        'fields'               => ['author'=>'','email'=>'','url'=>''],
        'comment_notes_before' => '',
        'comment_notes_after'  => '',
        'cookies'              => '',
      ], $post_id);
      $GLOBALS['rv_force_rendering'] = false;
      ?>
    </div>
  </section>
  <?php
  return ob_get_clean();
});
