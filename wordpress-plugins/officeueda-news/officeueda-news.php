<?php
/**
 * Plugin Name: OfficeUeda News
 * Description: お知らせ（最新/一覧/単体）をサーバーサイドで描画するGutenbergブロック＋ショートコード。テンプレとCSSはテーマで上書き可能。CPT/タクソノミ/メタ/メタボックス/一覧制御も同梱。
 * Version:     1.2.1
 * Author:      Office Ueda
 * Text Domain: officeueda-news
 */
if (!defined('ABSPATH')) exit;

// ===== 定数 =====
define('OU_NEWS_VER', '1.2.1');
define('OU_NEWS_PATH', plugin_dir_path(__FILE__));
define('OU_NEWS_URL',  plugin_dir_url(__FILE__));

// ===== 共通ヘルパ =====
function ou_news_bool($v){ return in_array(strtolower((string)$v), ['1','true','yes','on'], true); }
function ou_news_locate_template($template){
    $candidate = 'parts/news/' . sanitize_file_name($template) . '.php';
    $theme = locate_template($candidate, false, false);
    if ($theme) return $theme;
    $plugin = OU_NEWS_PATH . $candidate;
    return file_exists($plugin) ? $plugin : '';
}

/*====================================================
 *  A) データモデル層：CPT/Taxonomy/Meta/Metabox/Archive
 *====================================================*/

// --- CPT & タクソノミ登録（Guard付） ---
add_action('init', 'ou_news_register_types', 5);
function ou_news_register_types() {
    if (!post_type_exists('news')) {
        register_post_type('news', [
            'labels' => [
                'name'               => 'お知らせ',
                'singular_name'      => 'お知らせ',
                'menu_name'          => 'お知らせ',
                'add_new'            => '新規追加',
                'add_new_item'       => 'お知らせを追加',
                'edit_item'          => 'お知らせを編集',
                'new_item'           => '新しいお知らせ',
                'view_item'          => 'お知らせを表示',
                'search_items'       => 'お知らせを検索',
                'not_found'          => 'お知らせが見つかりません',
                'not_found_in_trash' => 'ゴミ箱にありません',
                'all_items'          => 'お知らせ一覧',
            ],
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,   // Gutenberg対応
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-megaphone',
            'has_archive'         => true,
            'rewrite'             => ['slug' => 'news', 'with_front' => false],
            'supports'            => ['title','editor','excerpt','thumbnail','author','revisions'],
            'map_meta_cap'        => true,
        ]);
    }

    if (!taxonomy_exists('news_cat')) {
        register_taxonomy('news_cat', 'news', [
            'labels' => [
                'name'              => 'お知らせカテゴリ',
                'singular_name'     => 'お知らせカテゴリ',
                'search_items'      => 'カテゴリを検索',
                'all_items'         => 'すべてのカテゴリ',
                'edit_item'         => 'カテゴリを編集',
                'update_item'       => 'カテゴリを更新',
                'add_new_item'      => '新規カテゴリを追加',
                'new_item_name'     => '新規カテゴリ名',
                'menu_name'         => 'カテゴリ',
            ],
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => ['slug' => 'news-cat', 'with_front' => false],
            // 既定カテゴリ（保存時に自動付与）
            'default_term'      => [
                'name' => 'お知らせ',
                'slug' => 'oshirase',
                'description' => '標準のお知らせカテゴリ'
            ],
        ]);
    }
}

/**
 * 既定カテゴリの作成（有効化時）
 * - 「お知らせ」（slug: oshirase）
 * - 「重要なお知らせ」（slug: important-news）
 */
function ou_news_register_default_terms() {
    if (!taxonomy_exists('news_cat')) return;

    $defaults = [
        [ 'name' => 'お知らせ',       'slug' => 'oshirase',        'desc' => '標準のお知らせカテゴリ' ],
        [ 'name' => '重要なお知らせ', 'slug' => 'important-news',  'desc' => '重要なお知らせ' ],
    ];
    foreach ($defaults as $d) {
        if (!term_exists($d['slug'], 'news_cat')) {
            wp_insert_term($d['name'], 'news_cat', [
                'slug'        => $d['slug'],
                'description' => $d['desc'],
            ]);
        }
    }
}

// --- 有効化/無効化でリライトルール再生成＆既定カテゴリ作成 ---
register_activation_hook(__FILE__, function(){
    ou_news_register_types();            // 先にタクソノミを登録
    ou_news_register_default_terms();    // 既定カテゴリを作成
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function(){ flush_rewrite_rules(); });

// --- 投稿メタ登録（型/REST/サニタイズ） ---
add_action('init', 'ou_news_register_meta', 8);
function ou_news_register_meta(){
    $to_bool = function($v){ return (bool)$v; };
    $sanitize_ymd = function($v){
        $v = is_string($v) ? $v : '';
        $digits = preg_replace('/\D/', '', $v);
        return (strlen($digits) === 8) ? $digits : '';
    };

    register_post_meta('news','is_pinned', [
        'type'=>'boolean','single'=>true,'show_in_rest'=>true,
        'sanitize_callback'=>$to_bool,'auth_callback'=>'__return_true'
    ]);
    foreach(['publish_start','publish_end'] as $key){
        register_post_meta('news',$key,[
            'type'=>'string','single'=>true,'show_in_rest'=>true,
            'sanitize_callback'=>$sanitize_ymd,'auth_callback'=>'__return_true'
        ]);
    }
    register_post_meta('news','lead_text',[
        'type'=>'string','single'=>true,'show_in_rest'=>true,
        'sanitize_callback'=>'sanitize_text_field','auth_callback'=>'__return_true'
    ]);
    register_post_meta('news','external_url',[
        'type'=>'string','single'=>true,'show_in_rest'=>true,
        'sanitize_callback'=>'esc_url_raw','auth_callback'=>'__return_true'
    ]);
    register_post_meta('news','external_blank',[
        'type'=>'boolean','single'=>true,'show_in_rest'=>true,
        'sanitize_callback'=>$to_bool,'auth_callback'=>'__return_true'
    ]);
    register_post_meta('news','badge',[
        'type'=>'string','single'=>true,'show_in_rest'=>true,
        'sanitize_callback'=>'sanitize_text_field','auth_callback'=>'__return_true'
    ]);
}

// --- メタボックス（UI） ---
add_action('add_meta_boxes', function(){
    add_meta_box('ou_news_meta_box','お知らせ設定','ou_news_render_meta_box','news','normal','high');
});
function ou_news_render_meta_box(WP_Post $post){
    $is_pinned      = (bool) get_post_meta($post->ID,'is_pinned',true);
    $publish_start  = get_post_meta($post->ID,'publish_start',true);
    $publish_end    = get_post_meta($post->ID,'publish_end',true);
    $lead_text      = get_post_meta($post->ID,'lead_text',true);
    $external_url   = get_post_meta($post->ID,'external_url',true);
    $external_blank = (bool) get_post_meta($post->ID,'external_blank',true);
    $badge          = get_post_meta($post->ID,'badge',true);

    $ymd_to_input = function($ymd){ if(!$ymd||strlen($ymd)!==8) return ''; return substr($ymd,0,4).'-'.substr($ymd,4,2).'-'.substr($ymd,6,2); };
    wp_nonce_field('ou_news_meta_box_nonce','ou_news_meta_box_nonce_field');
    ?>
    <style>.news-meta-grid{display:grid;gap:12px;grid-template-columns:1fr 2fr;align-items:center}.news-meta-grid label{font-weight:600}.news-meta-desc{color:#666;font-size:12px}</style>
    <div class="news-meta-grid">
      <label for="news_is_pinned">先頭固定</label>
      <div><label><input type="checkbox" id="news_is_pinned" name="news_is_pinned" value="1" <?php checked($is_pinned); ?>> 一覧の先頭に固定表示</label></div>

      <label for="news_publish_start">掲載開始日</label>
      <div>
        <input type="date" id="news_publish_start" name="news_publish_start" value="<?php echo esc_attr($ymd_to_input($publish_start)); ?>">
        <p class="news-meta-desc">この日付以降に一覧へ表示（未設定は非表示）</p>
      </div>

      <label for="news_publish_end">掲載終了日</label>
      <div>
        <input type="date" id="news_publish_end" name="news_publish_end" value="<?php echo esc_attr($ymd_to_input($publish_end)); ?>">
        <p class="news-meta-desc">空の場合は無期限で表示</p>
      </div>

      <label for="news_lead_text">サマリー（短文）</label>
      <div>
        <input type="text" id="news_lead_text" name="news_lead_text" class="widefat" value="<?php echo esc_attr($lead_text); ?>" maxlength="200">
      </div>

      <label for="news_external_url">外部リンクURL</label>
      <div>
        <input type="url" id="news_external_url" name="news_external_url" class="widefat" value="<?php echo esc_attr($external_url); ?>">
        <label style="display:block;margin-top:6px;"><input type="checkbox" name="news_external_blank" value="1" <?php checked($external_blank); ?>> 新規タブで開く（target="_blank"）</label>
      </div>

      <label for="news_badge">バッジ表示</label>
      <div>
        <select id="news_badge" name="news_badge">
          <?php foreach (['','重要','メンテ','採用'] as $opt){ printf('<option value="%s" %s>%s</option>', esc_attr($opt), selected($badge,$opt,false), $opt===''?'（なし）':esc_html($opt)); } ?>
        </select>
      </div>
    </div>
    <?php
}
add_action('save_post_news','ou_news_save_meta',10,2);
function ou_news_save_meta($post_id, WP_Post $post){
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (!current_user_can('edit_post',$post_id)) return;
    if (!isset($_POST['ou_news_meta_box_nonce_field']) || !wp_verify_nonce($_POST['ou_news_meta_box_nonce_field'],'ou_news_meta_box_nonce')) return;

    $bool = function($key){ return isset($_POST[$key]) ? (bool) wp_unslash($_POST[$key]) : false; };
    $date_norm = function($key){ $raw = isset($_POST[$key]) ? (string) wp_unslash($_POST[$key]) : ''; $digits = preg_replace('/\D/','',$raw); return strlen($digits)===8 ? $digits : ''; };

    update_post_meta($post_id,'is_pinned',      $bool('news_is_pinned') ? 1 : 0);
    update_post_meta($post_id,'publish_start',  $date_norm('news_publish_start'));
    update_post_meta($post_id,'publish_end',    $date_norm('news_publish_end'));
    update_post_meta($post_id,'lead_text',      isset($_POST['news_lead_text']) ? sanitize_text_field(wp_unslash($_POST['news_lead_text'])) : '' );
    update_post_meta($post_id,'external_url',   isset($_POST['news_external_url']) ? esc_url_raw(wp_unslash($_POST['news_external_url'])) : '' );
    update_post_meta($post_id,'external_blank', $bool('news_external_blank') ? 1 : 0);
    update_post_meta($post_id,'badge',          isset($_POST['news_badge']) ? sanitize_text_field(wp_unslash($_POST['news_badge'])) : '' );
}

// --- 一覧制御（メインクエリのみ：フロント） ---
add_action('pre_get_posts','ou_news_archive_main_query');
function ou_news_archive_main_query(WP_Query $q){
    if (is_admin() || !$q->is_main_query()) return;
    if ($q->is_post_type_archive('news') || $q->is_tax('news_cat')) {
        $q->set('posts_per_page', 12);
        $today = current_time('Ymd');
        $q->set('meta_query', [
            'relation' => 'AND',
            [ 'key'=>'publish_start','value'=>$today,'compare'=>'<=','type'=>'NUMERIC' ],
            [ 'relation'=>'OR',
                [ 'key'=>'publish_end','value'=>$today,'compare'=>'>=','type'=>'NUMERIC' ],
                [ 'key'=>'publish_end','compare'=>'NOT EXISTS' ],
                [ 'key'=>'publish_end','value'=>'','compare'=>'=' ],
            ],
        ]);
        $q->set('meta_key','is_pinned');
        $q->set('orderby',[ 'meta_value_num'=>'DESC', 'date'=>'DESC' ]);
        if (isset($_GET['news_cat']) && $_GET['news_cat']!=='') {
            $q->set('tax_query', [[
                'taxonomy'=>'news_cat','field'=>'slug','terms'=> sanitize_text_field( wp_unslash($_GET['news_cat']) )
            ]]);
        }
    }
}

// --- サムネイル取得ヘルパ ---
function ou_news_get_card_thumbnail_html(int $post_id, string $size='medium_large'): string {
    if (has_post_thumbnail($post_id)) {
        return get_the_post_thumbnail($post_id, $size, ['loading'=>'lazy','decoding'=>'async']);
    }
    $content = get_post_field('post_content',$post_id);
    if ($content) {
        if (function_exists('parse_blocks')) {
            $blocks = parse_blocks($content);
            $first_id = ou_news_find_first_image_attachment_id_from_blocks($blocks);
            if ($first_id) return wp_get_attachment_image($first_id,$size,false,['loading'=>'lazy','decoding'=>'async']);
        }
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i',$content,$m)) {
            $src = esc_url($m[1]); if ($src) { $alt = esc_attr(get_the_title($post_id)); return sprintf('<img src="%s" alt="%s" loading="lazy" decoding="async" />',$src,$alt); }
        }
    }
    $attachments = get_attached_media('image',$post_id);
    if ($attachments) { $attachment = reset($attachments); if ($attachment instanceof WP_Post) return wp_get_attachment_image($attachment->ID,$size,false,['loading'=>'lazy','decoding'=>'async']); }
    $placeholder = get_stylesheet_directory_uri().'/assets/images/placeholder-640x360.webp';
    return sprintf('<img src="%s" alt="" loading="lazy" decoding="async" />', esc_url($placeholder));
}
function ou_news_find_first_image_attachment_id_from_blocks(array $blocks){
    foreach ($blocks as $block) {
        if (!is_array($block)) continue;
        $name  = $block['blockName'] ?? '';
        $attrs = $block['attrs'] ?? [];
        if ($name === 'core/image' && !empty($attrs['id'])) return (int)$attrs['id'];
        if ($name === 'core/gallery') {
            if (!empty($block['innerBlocks']) && is_array($block['innerBlocks'])) {
                foreach ($block['innerBlocks'] as $ib) { $ib_attrs = $ib['attrs'] ?? []; if (!empty($ib_attrs['id'])) return (int)$ib_attrs['id']; }
            }
            if (!empty($attrs['ids']) && is_array($attrs['ids'])) return (int) reset($attrs['ids']);
        }
        if (!empty($block['innerBlocks']) && is_array($block['innerBlocks'])) {
            $found = ou_news_find_first_image_attachment_id_from_blocks($block['innerBlocks']);
            if ($found) return (int)$found;
        }
    }
    return 0;
}

/*====================================================
 *  B) 表示層：CSS切替(A/B/C)・ブロック登録・テンプレ描画
 *====================================================*/

// ===== 管理画面：設定ページ =====
add_action('admin_menu', function(){
    add_options_page('News Block 設定','News Block 設定','manage_options','ou-news-settings','ou_news_settings_page_render');
});
add_action('admin_init', function(){
    register_setting('ou_news_settings','ou_news_css_mode',[
        'type'=>'string','sanitize_callback'=>function($v){ return in_array($v,['A','B','C'],true)?$v:'A'; },'default'=>'A'
    ]);
    add_settings_section('ou_news_css_section','CSS読み込み方式',function(){
        echo '<p>フロントのCSSを A/B/C から選択します。A=テーマ優先, B=差分上書き, C=プラグインCSSを読まない。</p>';
    },'ou_news_settings');
    add_settings_field('ou_news_css_mode','方式',function(){
        $mode=get_option('ou_news_css_mode','A'); ?>
      <label><input type="radio" name="ou_news_css_mode" value="A" <?php checked($mode,'A'); ?>> 方式A：テーマ <code>assets/css/news.css</code> があればそれを優先、なければプラグインCSS</label><br>
      <label><input type="radio" name="ou_news_css_mode" value="B" <?php checked($mode,'B'); ?>> 方式B：プラグインCSS → テーマ <code>assets/css/news-override.css</code> で差分上書き</label><br>
      <label><input type="radio" name="ou_news_css_mode" value="C" <?php checked($mode,'C'); ?>> 方式C：プラグインCSSを読み込まない（テーマで完全管理）</label>
    <?php },'ou_news_settings','ou_news_css_section');
});
function ou_news_settings_page_render(){ if(!current_user_can('manage_options')) return; ?>
  <div class="wrap"><h1>News Block 設定</h1><form method="post" action="options.php"><?php settings_fields('ou_news_settings'); do_settings_sections('ou_news_settings'); submit_button(); ?></form></div>
<?php }

// ===== CSS 読み込み（フロント） =====
add_action('wp_enqueue_scripts', function(){
    if (!apply_filters('ou_news_enqueue_css', true)) return;
    $mode = get_option('ou_news_css_mode','A');
    if ($mode === 'C') return; // プラグインCSSは読まない
    if ($mode === 'B') {
        wp_enqueue_style('ou-news', OU_NEWS_URL.'assets/css/news.css', [], OU_NEWS_VER);
        $override = get_stylesheet_directory().'/assets/css/news-override.css';
        if (file_exists($override)) {
            wp_enqueue_style('ou-news-override', get_stylesheet_directory_uri().'/assets/css/news-override.css', ['ou-news'], filemtime($override));
        }
        return;
    }
    $theme_css = locate_template('assets/css/news.css', false, false);
    if ($theme_css) {
        $path = get_stylesheet_directory().'/assets/css/news.css';
        wp_enqueue_style('ou-news', get_stylesheet_directory_uri().'/assets/css/news.css', [], file_exists($path)?filemtime($path):OU_NEWS_VER);
    } else {
        wp_enqueue_style('ou-news', OU_NEWS_URL.'assets/css/news.css', [], OU_NEWS_VER);
    }
},10);

// ===== エディタ用（ブロックUI） =====
add_action('enqueue_block_editor_assets', function(){
    wp_enqueue_style('ou-news-editor', OU_NEWS_URL.'build/editor.css', [], OU_NEWS_VER);
    wp_enqueue_script('ou-news-editor', OU_NEWS_URL.'build/editor.js', ['wp-blocks','wp-element','wp-components','wp-i18n','wp-block-editor'], OU_NEWS_VER, true);
});

// ===== ブロック登録 =====
add_action('init', function(){ register_block_type(__DIR__, [ 'render_callback' => 'ou_news_block_render' ]); });

// ====== レンダー関数群（ブロック＆ショートコード共通） ======
function ou_news_block_render($attributes, $content, $block){
    $a = wp_parse_args($attributes, [
        'mode'        => 'latest',   // latest|archive|entry
        'limit'       => 5,
        'cat'         => '',
        'pinnedFirst' => true,
        'perPage'     => 12,
        'pageQs'      => 'npage',
        'postId'      => 0,
        'slug'        => '',
        'template'    => 'latest',
    ]);
    $mode = in_array($a['mode'], ['latest','archive','entry'], true) ? $a['mode'] : 'latest';
    $template = sanitize_file_name($a['template']);
    if ($mode === 'latest')  return ou_news_render_latest($a, $template);
    if ($mode === 'archive') return ou_news_render_archive($a, $template);
    return ou_news_render_entry($a, $template);
}

function ou_news_render_latest($a, $template){
    $limit       = max(1, (int)$a['limit']);
    $pinnedFirst = (bool)$a['pinnedFirst'];
    $today       = current_time('Ymd');
    $args = [
        'post_type'      => 'news',
        'posts_per_page' => $limit,
        'post_status'    => 'publish',
        'no_found_rows'  => true,
        'meta_query'     => [
            'relation' => 'AND',
            ['key'=>'publish_start','value'=>$today,'compare'=>'<=','type'=>'NUMERIC'],
            ['relation'=>'OR',
                ['key'=>'publish_end','value'=>$today,'compare'=>'>=','type'=>'NUMERIC'],
                ['key'=>'publish_end','compare'=>'NOT EXISTS'],
                ['key'=>'publish_end','value'=>'','compare'=>'='],
            ],
        ],
    ];
    if (!empty($a['cat'])) {
        $args['tax_query'] = [[ 'taxonomy'=>'news_cat','field'=>'slug','terms'=>sanitize_text_field($a['cat']) ]];
    }
    if ($pinnedFirst) { $args['meta_key'] = 'is_pinned'; $args['orderby'] = ['meta_value_num'=>'DESC','date'=>'DESC']; }
    else { $args['orderby'] = 'date'; $args['order'] = 'DESC'; }

    $q = new WP_Query($args);
    ob_start(); $tmpl = ou_news_locate_template($template);
    if ($tmpl) { $posts = $q->posts; include $tmpl; } else { echo '<p>テンプレートが見つかりません。</p>'; }
    wp_reset_postdata(); return ob_get_clean();
}

function ou_news_render_archive($a, $template){
    $today = current_time('Ymd');
    $paged = isset($_GET[$a['pageQs']]) ? max(1, (int) $_GET[$a['pageQs']]) : 1;
    $args = [
        'post_type'      => 'news',
        'posts_per_page' => max(1,(int)$a['perPage']),
        'paged'          => $paged,
        'post_status'    => 'publish',
        'meta_query'     => [
            'relation' => 'AND',
            ['key'=>'publish_start','value'=>$today,'compare'=>'<=','type'=>'NUMERIC'],
            ['relation'=>'OR',
                ['key'=>'publish_end','value'=>$today,'compare'=>'>=','type'=>'NUMERIC'],
                ['key'=>'publish_end','compare'=>'NOT EXISTS'],
                ['key'=>'publish_end','value'=>'','compare'=>'='],
            ],
        ],
        'meta_key' => 'is_pinned',
        'orderby'  => ['meta_value_num'=>'DESC','date'=>'DESC'],
    ];
    if (!empty($a['cat'])) { $args['tax_query'] = [[ 'taxonomy'=>'news_cat','field'=>'slug','terms'=>sanitize_text_field($a['cat']) ]]; }
    $q = new WP_Query($args);
    ob_start(); $tmpl = ou_news_locate_template($template);
    if ($tmpl) { $posts = $q->posts; $max = $q->max_num_pages; $cur = $paged; $page_qs = sanitize_key($a['pageQs']); include $tmpl; }
    else { echo '<p>テンプレートが見つかりません。</p>'; }
    wp_reset_postdata(); return ob_get_clean();
}

function ou_news_render_entry($a, $template){
    $p = null;
    if (!empty($a['postId'])) $p = get_post((int)$a['postId']);
    if (!$p && !empty($a['slug'])) $p = get_page_by_path(sanitize_title($a['slug']), OBJECT, 'news');
    if (!$p || $p->post_type !== 'news' || $p->post_status !== 'publish') return '';
    $today = current_time('Ymd');
    $start = get_post_meta($p->ID, 'publish_start', true);
    $end   = get_post_meta($p->ID, 'publish_end', true);
    if (!($start && $start <= $today)) return '';
    if ($end && $end < $today) return '';
    ob_start(); $tmpl = ou_news_locate_template($template);
    if ($tmpl) { $post_obj = $p; include $tmpl; } else { echo '<p>テンプレートが見つかりません。</p>'; }
    return ob_get_clean();
}

// ===== ショートコード =====
add_shortcode('news_latest', function($atts){
    $a = shortcode_atts([ 'limit'=>5, 'cat'=>'', 'pinned_first'=>'true', 'template'=>'latest' ], $atts, 'news_latest');
    $a['pinnedFirst']=ou_news_bool($a['pinned_first']); unset($a['pinned_first']);
    return ou_news_render_latest($a, $a['template']);
});
add_shortcode('news_archive', function($atts){
    $a = shortcode_atts([ 'per_page'=>12, 'cat'=>'', 'page_qs'=>'npage', 'template'=>'archive' ], $atts, 'news_archive');
    $a['perPage']=(int)$a['per_page']; unset($a['per_page']);
    $a['pageQs']=$a['page_qs']; unset($a['page_qs']);
    return ou_news_render_archive($a, $a['template']);
});
add_shortcode('news_entry',   function($atts){
    $a = shortcode_atts([ 'id'=>'', 'slug'=>'', 'template'=>'entry' ], $atts, 'news_entry');
    $a['postId']=(int)$a['id']; unset($a['id']);
    return ou_news_render_entry($a, $a['template']);
});

/*====================================================
 *  C) 管理画面：投稿一覧（edit.php?post_type=news）にID列を追加
 *====================================================*/

// 列の定義：タイトルの次に「ID」列を挿入
add_filter('manage_edit-news_columns', function($columns){
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'title') {
            $new['news_id'] = 'ID';
        }
    }
    return $new;
});

// 列の中身を出力
add_action('manage_news_posts_custom_column', function($column, $post_id){
    if ($column === 'news_id') {
        echo (int) $post_id;
    }
}, 10, 2);

// 並べ替え可能にする
add_filter('manage_edit-news_sortable_columns', function($columns){
    $columns['news_id'] = 'news_id';
    return $columns;
});

// 並べ替えキーを実カラム（ID）にマップ
add_action('pre_get_posts', function(WP_Query $q){
    if (!is_admin() || !$q->is_main_query()) return;
    if ($q->get('post_type') === 'news' && $q->get('orderby') === 'news_id') {
        $q->set('orderby', 'ID');
    }
});

// 見た目の調整（列幅）
add_action('admin_head', function(){
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && $screen->id === 'edit-news') {
        echo '<style>.column-news_id{width:90px}</style>';
    }
});
