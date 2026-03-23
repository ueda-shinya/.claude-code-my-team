<?php

/**
 * Plugin Name: OU Gourmet Directory
 * Description: お店・商品紹介ディレクトリ（ジャンル/都道府県/市区町村で絞込、Instagram埋め込み、Instagramサムネ自動取得・再取得ボタン／詳細エラー表示／ユーザーデータ削除ページ＆Metaデータ削除コールバック対応）
 * Version: 1.4.2
 * Author: OfficeUeda
 * Text Domain: ou-gourmet-directory
 */

if (!defined('ABSPATH')) exit;

class OU_Gourmet_Directory
{
    const VER = '1.4.2';

    const POST_TYPE = 'ou_listing';
    const TAX_TYPE  = 'ou_type';
    const TAX_AREA  = 'ou_area';

    const META_INSTAGRAM       = '_ou_instagram_url';
    const META_ADDRESS         = '_ou_address';
    const META_PHONE           = '_ou_phone';
    const META_LINE            = '_ou_line_url';
    const META_WEBSITE         = '_ou_website_url';
    const META_INSTAGRAM_THUMB = '_ou_instagram_thumb';
    const META_INSTAGRAM_ERR   = '_ou_instagram_last_error';

    const ROUTE_NS = 'ou-gd/v1';

    const OPT_INSTA   = 'ou_gd_instagram';
    const OPT_DEL_LOG = 'ou_gd_deletion_log'; // データ削除リクエストの簡易ログ（確認用）

    // 自動作成ページのスラッグ
    const PAGE_DEL_INFO_SLUG   = 'data-deletion';
    const PAGE_DEL_STATUS_SLUG = 'data-deletion-status';

    public function __construct()
    {
        add_action('init', [$this, 'register_post_type_and_taxonomies']);
        add_action('init', [$this, 'maybe_add_default_terms']);

        register_activation_hook(__FILE__, [$this, 'on_activation']);
        register_deactivation_hook(__FILE__, [$this, 'on_deactivation']);

        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes']);

        add_shortcode('ou_gourmet_directory', [$this, 'shortcode_directory']);
        add_action('wp_enqueue_scripts', [$this, 'register_front_assets']);

        add_action('wp_ajax_ou_get_cities',        [$this, 'ajax_get_cities']);
        add_action('wp_ajax_nopriv_ou_get_cities', [$this, 'ajax_get_cities']);

        add_filter('the_content', [$this, 'append_single_listing_box']);

        add_action('admin_menu',  [$this, 'add_settings_page']);
        add_action('admin_init',  [$this, 'register_settings']);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_ou_gd_refetch_insta', [$this, 'ajax_refetch_insta']);

        // Data Deletion（Meta用コールバック）
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Data Deletion（人向けページ）
        add_shortcode('ou_gd_data_deletion',        [$this, 'sc_data_deletion']);
        add_shortcode('ou_gd_data_deletion_status', [$this, 'sc_data_deletion_status']);

        // App ID/Secret未設定や削除ページ欠落の注意喚起
        add_action('admin_notices', [$this, 'admin_notice_missing_creds_or_pages']);
    }

    /* =========================================================
     * Register CPT / Taxonomies
     * ======================================================= */
    public function register_post_type_and_taxonomies()
    {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'お店・商品紹介',
                'singular_name' => '紹介',
                'add_new' => '新規追加',
                'add_new_item' => '紹介を追加',
                'edit_item' => '紹介を編集',
                'new_item' => '新規紹介',
                'view_item' => '紹介を表示',
                'search_items' => '紹介を検索',
                'not_found' => '見つかりません',
                'menu_name' => 'お店紹介',
            ],
            'public' => true,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-store',
            'has_archive' => true,
            'rewrite' => ['slug' => 'listing', 'with_front' => false],
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'show_in_rest' => true,
            'capability_type' => 'post',
        ]);

        register_taxonomy(self::TAX_TYPE, self::POST_TYPE, [
            'labels' => [
                'name' => 'ジャンル',
                'singular_name' => 'ジャンル',
                'search_items' => 'ジャンルを検索',
                'all_items' => 'すべてのジャンル',
                'edit_item' => 'ジャンルを編集',
                'add_new_item' => 'ジャンルを追加',
                'menu_name' => 'ジャンル',
            ],
            'public' => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'type'],
        ]);

        register_taxonomy(self::TAX_AREA, self::POST_TYPE, [
            'labels' => [
                'name' => 'エリア（都道府県→市区町村）',
                'singular_name' => 'エリア',
                'search_items' => 'エリアを検索',
                'all_items' => 'すべてのエリア',
                'edit_item' => 'エリアを編集',
                'add_new_item' => 'エリアを追加',
                'menu_name' => 'エリア',
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'area'],
        ]);
    }

    public function on_activation()
    {
        $this->register_post_type_and_taxonomies();
        $this->maybe_add_default_terms();
        $this->ensure_data_deletion_pages();
        flush_rewrite_rules();
    }
    public function on_deactivation()
    {
        flush_rewrite_rules();
    }

    public function maybe_add_default_terms()
    {
        $default_types = ['プリン' => 'purin', '焼肉' => 'yakiniku', 'カフェ' => 'cafe'];
        foreach ($default_types as $name => $slug) {
            if (!term_exists($name, self::TAX_TYPE)) {
                wp_insert_term($name, self::TAX_TYPE, ['slug' => $slug]);
            }
        }
        $pref_name = '広島県';
        $pref_slug = 'hiroshima';
        $pref = term_exists($pref_name, self::TAX_AREA);
        if (!$pref) $pref = wp_insert_term($pref_name, self::TAX_AREA, ['slug' => $pref_slug]);
        $pref_id = is_array($pref) ? (int)$pref['term_id'] : (int)$pref->term_id;
        $cities = [
            ['広島市', 'hiroshima-shi'],
            ['東広島市', 'higashihiroshima-shi'],
            ['呉市', 'kure-shi'],
            ['福山市', 'fukuyama-shi'],
            ['尾道市', 'onomichi-shi'],
            ['三原市', 'mihara-shi'],
            ['廿日市市', 'hatsukaichi-shi'],
            ['三次市', 'miyoshi-shi'],
        ];
        foreach ($cities as [$name, $slug]) {
            if (!term_exists($name, self::TAX_AREA)) {
                wp_insert_term($name, self::TAX_AREA, ['slug' => $slug, 'parent' => $pref_id]);
            }
        }
    }

    /* =========================================================
     * Meta Boxes
     * ======================================================= */
    public function add_meta_boxes()
    {
        add_meta_box('ou_listing_info', '店舗/投稿情報', [$this, 'render_meta_box'], self::POST_TYPE, 'normal', 'high');
    }

    public function render_meta_box($post)
    {
        wp_nonce_field('ou_listing_meta_nonce', 'ou_listing_meta_nonce_field');

        $instagram = get_post_meta($post->ID, self::META_INSTAGRAM, true);
        $address   = get_post_meta($post->ID, self::META_ADDRESS, true);
        $phone     = get_post_meta($post->ID, self::META_PHONE, true);
        $line      = get_post_meta($post->ID, self::META_LINE, true);
        $website   = get_post_meta($post->ID, self::META_WEBSITE, true);
        $thumb_now = get_post_meta($post->ID, self::META_INSTAGRAM_THUMB, true);
        $last_err  = get_post_meta($post->ID, self::META_INSTAGRAM_ERR, true);

        if ($last_err) {
            echo '<div class="notice notice-error"><p><strong>Instagramサムネ取得エラー:</strong> ' . esc_html($last_err) . '</p></div>';
        }

        list($app_id, $app_secret) = $this->get_app_creds();
        $creds_ok = ($app_id && $app_secret);

?>
        <table class="form-table">
            <tr>
                <th><label for="ou_instagram_url">Instagram投稿URL</label></th>
                <td><input type="url" id="ou_instagram_url" name="ou_instagram_url" class="regular-text" value="<?php echo esc_attr($instagram); ?>" placeholder="https://www.instagram.com/p/XXXXXXXXX/"></td>
            </tr>
            <tr>
                <th><label for="ou_address">住所</label></th>
                <td>
                    <input type="text" id="ou_address" name="ou_address" class="regular-text" value="<?php echo esc_attr($address); ?>" placeholder="例）広島県東広島市...">
                    <p class="description">フロントでは Googleマップ検索リンクを自動生成します。</p>
                </td>
            </tr>
            <tr>
                <th><label for="ou_phone">電話番号</label></th>
                <td><input type="text" id="ou_phone" name="ou_phone" class="regular-text" value="<?php echo esc_attr($phone); ?>" placeholder="082-xxx-xxxx"></td>
            </tr>
            <tr>
                <th><label for="ou_line_url">公式LINE URL</label></th>
                <td><input type="url" id="ou_line_url" name="ou_line_url" class="regular-text" value="<?php echo esc_url($line); ?>" placeholder="https://lin.ee/..."></td>
            </tr>
            <tr>
                <th><label for="ou_website_url">WebサイトURL</label></th>
                <td><input type="url" id="ou_website_url" name="ou_website_url" class="regular-text" value="<?php echo esc_url($website); ?>" placeholder="https://example.com"></td>
            </tr>
        </table>

        <hr>
        <h3>Instagramサムネイル</h3>
        <div id="ou-insta-thumb-wrap" style="display:flex;align-items:center;gap:16px;">
            <div id="ou-insta-thumb-preview" style="max-width:180px;">
                <?php if ($thumb_now): ?>
                    <img src="<?php echo esc_url($thumb_now); ?>" alt="" style="width:100%;height:auto;display:block;border:1px solid #ddd;">
                <?php else: ?>
                    <em>（未取得）</em>
                <?php endif; ?>
            </div>
            <div>
                <?php if ($creds_ok): ?>
                    <button type="button"
                        class="button button-secondary"
                        id="ou-insta-refetch-btn"
                        data-post="<?php echo esc_attr($post->ID); ?>">
                        Instagramサムネを再取得
                    </button>
                    <label style="margin-left:8px;">
                        <input type="checkbox" id="ou-insta-force-media" value="1">
                        強制取り込み＆アイキャッチに設定
                    </label>
                    <div id="ou-insta-refetch-msg" style="margin-top:6px;color:#555;"></div>
                <?php else: ?>
                    <p style="margin:8px 0 0;color:#666;">App ID/Secret未設定のため、サムネ自動取得は利用できません。<br>一覧カードの画像はアイキャッチをご利用ください。</p>
                <?php endif; ?>
            </div>
        </div>
    <?php
    }

    public function save_meta_boxes($post_id)
    {
        if (!isset($_POST['ou_listing_meta_nonce_field']) || !wp_verify_nonce($_POST['ou_listing_meta_nonce_field'], 'ou_listing_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $old_insta = get_post_meta($post_id, self::META_INSTAGRAM, true);
        $old_thumb = get_post_meta($post_id, self::META_INSTAGRAM_THUMB, true);

        $insta = isset($_POST['ou_instagram_url']) ? $this->normalize_instagram_url(esc_url_raw(trim($_POST['ou_instagram_url']))) : '';
        $addr  = isset($_POST['ou_address']) ? sanitize_text_field(trim($_POST['ou_address'])) : '';
        $phone = isset($_POST['ou_phone']) ? preg_replace('/[^0-9+\-\(\)\s]/u', '', $_POST['ou_phone']) : '';
        $line  = isset($_POST['ou_line_url']) ? esc_url_raw(trim($_POST['ou_line_url'])) : '';
        $web   = isset($_POST['ou_website_url']) ? esc_url_raw(trim($_POST['ou_website_url'])) : '';

        if ($insta && !$this->is_valid_instagram_url($insta)) $insta = '';

        update_post_meta($post_id, self::META_INSTAGRAM, $insta);
        update_post_meta($post_id, self::META_ADDRESS,   $addr);
        update_post_meta($post_id, self::META_PHONE,     $phone);
        update_post_meta($post_id, self::META_LINE,      $line);
        update_post_meta($post_id, self::META_WEBSITE,   $web);

        delete_post_meta($post_id, self::META_INSTAGRAM_ERR);

        $opt = get_option(self::OPT_INSTA, []);

        if (!empty($insta)) {
            if ($insta !== $old_insta || empty($old_thumb)) {
                $thumb = $this->fetch_instagram_thumbnail($insta);
                if (is_wp_error($thumb)) {
                    update_post_meta($post_id, self::META_INSTAGRAM_ERR, $thumb->get_error_message());
                } else {
                    update_post_meta($post_id, self::META_INSTAGRAM_THUMB, $thumb);
                    if (empty($old_thumb) && !has_post_thumbnail($post_id) && !empty($opt['import_to_media'])) {
                        $att_id = $this->sideload_to_media($thumb, $post_id);
                        if ($att_id) set_post_thumbnail($post_id, (int)$att_id);
                    }
                }
            }
        } else {
            delete_post_meta($post_id, self::META_INSTAGRAM_THUMB);
        }
    }

    /* =========================================================
     * Assets (Front/Admin)
     * ======================================================= */
    public function register_front_assets()
    {
        wp_register_style('ou_gd_css', plugins_url('assets/css/directory.css', __FILE__), [], self::VER);
        wp_register_script('ou_gd_js', plugins_url('assets/js/directory.js', __FILE__), ['jquery'], self::VER, true);
        wp_localize_script('ou_gd_js', 'OU_GD', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ou_gd_nonce'),
        ]);
        wp_register_script('ou_gd_instagram_embed', 'https://www.instagram.com/embed.js', [], null, true);
    }

    public function enqueue_admin_assets($hook)
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== self::POST_TYPE) return;

        wp_register_script('ou_gd_admin_insta', plugins_url('assets/js/admin-insta.js', __FILE__), ['jquery'], self::VER, true);
        wp_localize_script('ou_gd_admin_insta', 'OU_GD_ADMIN', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ou_gd_admin'),
            'i18n'     => ['working' => '再取得中…', 'done' => '再取得が完了しました', 'failed' => '再取得に失敗しました'],
        ]);
        wp_enqueue_script('ou_gd_admin_insta');
    }

    /* =========================================================
     * AJAX
     * ======================================================= */
    public function ajax_get_cities()
    {
        check_ajax_referer('ou_gd_nonce', 'nonce');

        $pref_id = isset($_POST['pref_id']) ? intval($_POST['pref_id']) : 0;
        if ($pref_id <= 0) wp_send_json_error(['message' => 'invalid pref_id']);

        $children = get_terms(['taxonomy' => self::TAX_AREA, 'hide_empty' => false, 'parent' => $pref_id]);
        if (is_wp_error($children)) wp_send_json_error(['message' => 'taxonomy error']);

        $options = [];
        foreach ($children as $term) $options[] = ['id' => (int)$term->term_id, 'name' => $term->name, 'slug' => $term->slug];
        wp_send_json_success(['cities' => $options]);
    }

    public function ajax_refetch_insta()
    {
        check_ajax_referer('ou_gd_admin', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $force_media = !empty($_POST['force_media']);
        $input_insta = isset($_POST['insta_url']) ? $this->normalize_instagram_url(esc_url_raw(trim((string)$_POST['insta_url']))) : '';

        if ($post_id <= 0 || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => '権限エラー']);
        }

        $insta = get_post_meta($post_id, self::META_INSTAGRAM, true);
        if (empty($insta) && $input_insta && $this->is_valid_instagram_url($input_insta)) {
            $insta = $input_insta;
            update_post_meta($post_id, self::META_INSTAGRAM, $insta);
        }
        if (empty($insta)) wp_send_json_error(['message' => 'Instagram URLが設定されていません']);

        $thumb = $this->fetch_instagram_thumbnail($insta);
        if (is_wp_error($thumb)) {
            $data = $thumb->get_error_data();
            $debug = [
                'http_code'  => isset($data['http_code']) ? (int)$data['http_code'] : 0,
                'api_type'   => isset($data['api_error']['type']) ? (string)$data['api_error']['type'] : '',
                'api_code'   => isset($data['api_error']['code']) ? (int)$data['api_error']['code'] : 0,
                'api_subcode' => isset($data['api_error']['error_subcode']) ? (int)$data['api_error']['error_subcode'] : 0,
                'api_message' => isset($data['api_error']['message']) ? (string)$data['api_error']['message'] : '',
            ];
            update_post_meta($post_id, self::META_INSTAGRAM_ERR, $thumb->get_error_message());
            wp_send_json_error(['message' => $thumb->get_error_message(), 'debug' => $debug]);
        }

        delete_post_meta($post_id, self::META_INSTAGRAM_ERR);
        update_post_meta($post_id, self::META_INSTAGRAM_THUMB, $thumb);

        $opt = get_option(self::OPT_INSTA, []);
        $should_import = $force_media || (!has_post_thumbnail($post_id) && !empty($opt['import_to_media']));

        $att_id = 0;
        if ($should_import) {
            $att_id = $this->sideload_to_media($thumb, $post_id);
            if ($att_id) set_post_thumbnail($post_id, (int)$att_id);
        }

        wp_send_json_success([
            'thumb_url'     => esc_url_raw($thumb),
            'attachment_id' => (int)$att_id,
            'message'       => '再取得が完了しました',
        ]);
    }

    /* =========================================================
     * Shortcode (List + Filters)
     * ======================================================= */
    public function shortcode_directory($atts)
    {
        wp_enqueue_style('ou_gd_css');
        wp_enqueue_script('ou_gd_js');

        $atts = shortcode_atts(['per_page' => 12], $atts, 'ou_gourmet_directory');

        $type_slug = isset($_GET['type']) ? sanitize_title($_GET['type']) : '';
        $pref_id   = isset($_GET['pref']) ? intval($_GET['pref']) : 0;
        $city_id   = isset($_GET['city']) ? intval($_GET['city']) : 0;
        $paged     = max(1, get_query_var('paged') ? get_query_var('paged') : (isset($_GET['paged']) ? intval($_GET['paged']) : 1));

        $tax_query = ['relation' => 'AND'];
        if ($type_slug) $tax_query[] = ['taxonomy' => self::TAX_TYPE, 'field' => 'slug', 'terms' => [$type_slug]];
        if ($city_id > 0) {
            $tax_query[] = ['taxonomy' => self::TAX_AREA, 'field' => 'term_id', 'terms' => [$city_id], 'include_children' => false];
        } elseif ($pref_id > 0) {
            $tax_query[] = ['taxonomy' => self::TAX_AREA, 'field' => 'term_id', 'terms' => [$pref_id], 'include_children' => true];
        }

        $q = new WP_Query([
            'post_type' => self::POST_TYPE,
            'posts_per_page' => intval($atts['per_page']),
            'paged' => $paged,
            'tax_query' => count($tax_query) > 1 ? $tax_query : [],
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => false,
        ]);

        ob_start(); ?>
        <section class="p-directory">
            <form class="c-filter" method="get" action="">
                <fieldset class="c-filter__fieldset" aria-label="絞り込み">
                    <div class="l-grid l-grid--gap">
                        <label class="c-filter__label" for="ou_type">ジャンル</label>
                        <select id="ou_type" name="type" class="c-filter__select" aria-label="ジャンルを選択">
                            <option value="">すべて</option>
                            <?php
                            $types = get_terms(['taxonomy' => self::TAX_TYPE, 'hide_empty' => false]);
                            foreach ($types as $t) {
                                printf('<option value="%s"%s>%s</option>', esc_attr($t->slug), selected($type_slug, $t->slug, false), esc_html($t->name));
                            } ?>
                        </select>

                        <label class="c-filter__label" for="ou_pref">都道府県</label>
                        <select id="ou_pref" name="pref" class="c-filter__select" aria-label="都道府県を選択" data-current="<?php echo esc_attr($pref_id); ?>">
                            <option value="">すべて</option>
                            <?php
                            $prefs = get_terms(['taxonomy' => self::TAX_AREA, 'hide_empty' => false, 'parent' => 0]);
                            foreach ($prefs as $p) {
                                printf('<option value="%d"%s>%s</option>', (int)$p->term_id, selected($pref_id, $p->term_id, false), esc_html($p->name));
                            } ?>
                        </select>

                        <label class="c-filter__label" for="ou_city">市区町村</label>
                        <select id="ou_city" name="city" class="c-filter__select" aria-label="市区町村を選択" data-current="<?php echo esc_attr($city_id); ?>">
                            <option value="">すべて</option>
                            <?php if ($pref_id) {
                                $cities = get_terms(['taxonomy' => self::TAX_AREA, 'hide_empty' => false, 'parent' => $pref_id]);
                                foreach ($cities as $c) {
                                    printf('<option value="%d"%s>%s</option>', (int)$c->term_id, selected($city_id, $c->term_id, false), esc_html($c->name));
                                }
                            } ?>
                        </select>

                        <button class="c-filter__submit" type="submit">検索</button>
                    </div>
                </fieldset>
            </form>

            <div class="c-result" role="status" aria-live="polite">
                <p class="c-result__count"><?php echo number_format_i18n($q->found_posts); ?>件ヒット</p>
            </div>

            <div class="l-grid l-grid--cards">
                <?php if ($q->have_posts()) : while ($q->have_posts()) : $q->the_post();
                        $pid = get_the_ID();
                        $address = get_post_meta($pid, self::META_ADDRESS, true);
                        $phone   = get_post_meta($pid, self::META_PHONE, true);
                        $line    = get_post_meta($pid, self::META_LINE, true);
                        $website = get_post_meta($pid, self::META_WEBSITE, true);
                        $insta_thumb = get_post_meta($pid, self::META_INSTAGRAM_THUMB, true);
                        $map_url = $address ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address) : '';
                        $opts = get_option(self::OPT_INSTA, []);
                ?>
                        <article class="c-card c-card--listing">
                            <a class="c-card__link" href="<?php the_permalink(); ?>">
                                <div class="c-card__media">
                                    <?php
                                    if (!empty($opts['prefer_instagram_thumb']) && $insta_thumb) {
                                        echo '<img src="' . esc_url($insta_thumb) . '" class="c-card__img" alt="' . esc_attr(get_the_title()) . '" loading="lazy">';
                                    } elseif (has_post_thumbnail()) {
                                        the_post_thumbnail('medium_large', ['class' => 'c-card__img', 'alt' => esc_attr(get_the_title()), 'loading' => 'lazy']);
                                    } else {
                                        echo '<div class="c-card__placeholder" aria-hidden="true"></div>';
                                    } ?>
                                </div>
                                <div class="c-card__body">
                                    <h3 class="c-card__title"><?php the_title(); ?></h3>
                                    <div class="c-card__meta">
                                        <?php if ($address): ?>
                                            <div class="c-meta c-meta--addr"><span class="c-meta__label">住所</span><a class="c-meta__value" href="<?php echo esc_url($map_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($address); ?></a></div>
                                        <?php endif;
                                        if ($phone): ?>
                                            <div class="c-meta c-meta--tel"><span class="c-meta__label">電話</span><a class="c-meta__value" href="tel:<?php echo esc_attr(preg_replace('/\D+/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a></div>
                                        <?php endif; ?>
                                        <div class="c-meta c-meta--links">
                                            <?php if ($line): ?><a class="c-badge" href="<?php echo esc_url($line); ?>" target="_blank" rel="noopener">LINE</a><?php endif; ?>
                                            <?php if ($website): ?><a class="c-badge" href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener">Web</a><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endwhile;
                    wp_reset_postdata();
                else: ?>
                    <p>該当する紹介がありません。</p>
                <?php endif; ?>
            </div>

            <?php
            $big = 999999999;
            $paginate = paginate_links([
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => $q->max_num_pages,
                'type' => 'list',
            ]);
            if ($paginate) echo '<nav class="c-pagination" aria-label="ページネーション">' . $paginate . '</nav>';
            ?>
        </section>
    <?php
        return ob_get_clean();
    }

    /* =========================================================
     * Single: Info Box + Instagram Embed
     * ======================================================= */
    public function append_single_listing_box($content)
    {
        if (!is_singular(self::POST_TYPE) || !in_the_loop() || !is_main_query()) return $content;
        $pid = get_the_ID();
        $instagram = get_post_meta($pid, self::META_INSTAGRAM, true);
        $address   = get_post_meta($pid, self::META_ADDRESS, true);
        $phone     = get_post_meta($pid, self::META_PHONE, true);
        $line      = get_post_meta($pid, self::META_LINE, true);
        $website   = get_post_meta($pid, self::META_WEBSITE, true);
        $map_url = $address ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address) : '';

        ob_start(); ?>
        <aside class="p-single-info" aria-labelledby="ou-info-head">
            <h2 id="ou-info-head" class="p-single-info__hdg">店舗情報</h2>
            <dl class="p-single-info__list">
                <?php if ($address): ?><div class="p-single-info__row">
                        <dt>住所</dt>
                        <dd><a href="<?php echo esc_url($map_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($address); ?></a></dd>
                    </div><?php endif; ?>
                <?php if ($phone): ?><div class="p-single-info__row">
                        <dt>電話</dt>
                        <dd><a href="tel:<?php echo esc_attr(preg_replace('/\D+/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a></dd>
                    </div><?php endif; ?>
                <div class="p-single-info__row">
                    <dt>リンク</dt>
                    <dd class="p-single-info__links">
                        <?php if ($line): ?><a class="c-badge" href="<?php echo esc_url($line); ?>" target="_blank" rel="noopener">LINE</a><?php endif; ?>
                        <?php if ($website): ?><a class="c-badge" href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener">Web</a><?php endif; ?>
                    </dd>
                </div>
            </dl>
            <?php if ($instagram): ?>
                <div class="p-single-info__insta" aria-label="Instagram埋め込み">
                    <blockquote class="instagram-media" data-instgrm-permalink="<?php echo esc_url($instagram); ?>" data-instgrm-version="14"></blockquote>
                </div>
                <?php wp_enqueue_script('ou_gd_instagram_embed'); ?>
            <?php endif; ?>
        </aside>
    <?php
        return $content . ob_get_clean();
    }

    /* =========================================================
     * Settings (Instagram)
     * ======================================================= */
    public function add_settings_page()
    {
        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE,
            'Instagram連携設定',
            'Instagram連携',
            'manage_options',
            'ou-gd-instagram',
            [$this, 'render_settings_page']
        );
    }
    public function register_settings()
    {
        register_setting(self::OPT_INSTA, self::OPT_INSTA, [
            'type' => 'array',
            'sanitize_callback' => function ($in) {
                return [
                    'app_id' => sanitize_text_field($in['app_id'] ?? ''),
                    'app_secret' => sanitize_text_field($in['app_secret'] ?? ''),
                    'prefer_instagram_thumb' => !empty($in['prefer_instagram_thumb']) ? 1 : 0,
                    'import_to_media' => !empty($in['import_to_media']) ? 1 : 0,
                ];
            }
        ]);
        add_settings_section('ou_gd_instagram_main', 'Instagram oEmbed', function () {
            echo '<p>Instagram投稿URLからサムネイルを取得します（Meta(Facebook)の App ID / App Secret 必要）。公開投稿のみ対応。</p>';
        }, 'ou-gd-instagram');
        add_settings_field('app_id', 'App ID', function () {
            $opt = get_option(self::OPT_INSTA, []);
            printf('<input type="text" name="ou_gd_instagram[app_id]" value="%s" class="regular-text" autocomplete="off"/>', esc_attr($opt['app_id'] ?? ''));
        }, 'ou-gd-instagram', 'ou_gd_instagram_main');
        add_settings_field('app_secret', 'App Secret', function () {
            $opt = get_option(self::OPT_INSTA, []);
            printf('<input type="password" name="ou_gd_instagram[app_secret]" value="%s" class="regular-text" autocomplete="new-password"/>', esc_attr($opt['app_secret'] ?? ''));
        }, 'ou-gd-instagram', 'ou_gd_instagram_main');
        add_settings_field('prefer_instagram_thumb', '一覧でInstagramサムネを優先', function () {
            $opt = get_option(self::OPT_INSTA, []);
            printf('<label><input type="checkbox" name="ou_gd_instagram[prefer_instagram_thumb]" value="1" %s> 有効</label>', checked(!empty($opt['prefer_instagram_thumb']), true, false));
        }, 'ou-gd-instagram', 'ou_gd_instagram_main');
        add_settings_field('import_to_media', '取得サムネをメディアに取り込み（アイキャッチ未設定時のみ）', function () {
            $opt = get_option(self::OPT_INSTA, []);
            printf('<label><input type="checkbox" name="ou_gd_instagram[import_to_media]" value="1" %s> 有効</label>', checked(!empty($opt['import_to_media']), true, false));
        }, 'ou-gd-instagram', 'ou_gd_instagram_main');
    }
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) return; ?>
        <div class="wrap">
            <h1>Instagram連携設定</h1>
            <form method="post" action="options.php">
                <?php settings_fields(self::OPT_INSTA);
                do_settings_sections('ou-gd-instagram');
                submit_button(); ?>
            </form>
            <p style="margin-top:1em;color:#555;">※ Liveモード/Meta oEmbed Read権限が未承認だと取得に失敗します。取得後はCDN期限切れ対策としてメディア取り込み推奨。</p>
        </div>
    <?php
    }

    /* =========================================================
     * Data Deletion（Pages & REST）
     * ======================================================= */
    private function ensure_data_deletion_pages()
    {
        // 1) /data-deletion
        $this->maybe_create_page(self::PAGE_DEL_INFO_SLUG, 'User Data Deletion', '[ou_gd_data_deletion]');
        // 2) /data-deletion-status
        $this->maybe_create_page(self::PAGE_DEL_STATUS_SLUG, 'Data Deletion Status', '[ou_gd_data_deletion_status]');
    }
    private function maybe_create_page($slug, $title, $content)
    {
        $page = get_page_by_path($slug);
        if ($page) return;
        wp_insert_post([
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => $content,
        ]);
    }

    public function register_rest_routes()
    {
        register_rest_route(self::ROUTE_NS, '/meta-data-deletion', [
            'methods'  => 'POST',
            'callback' => [$this, 'rest_meta_data_deletion'],
            'permission_callback' => '__return_true',
            'args' => [
                'signed_request' => ['required' => false],
                'facebook_user_id' => ['required' => false],
            ],
        ]);
    }

    public function rest_meta_data_deletion(\WP_REST_Request $req)
    {
        // 1) 署名つき（推奨：Metaの正式コールバック）
        $signed = $req->get_param('signed_request');
        $fb_user_id = '';
        if ($signed) {
            list($app_id, $app_secret) = $this->get_app_creds();
            if (!$app_secret) {
                return new \WP_REST_Response(['error' => 'App Secret not set'], 400);
            }
            $data = $this->parse_signed_request($signed, $app_secret);
            if (!$data) return new \WP_REST_Response(['error' => 'Invalid signed_request'], 400);
            $fb_user_id = isset($data['user_id']) ? (string)$data['user_id'] : '';
        }

        // 2) 署名なし（任意：人向けの手続きで使う場合）
        if (!$fb_user_id) {
            $fb_user_id = sanitize_text_field((string)$req->get_param('facebook_user_id'));
        }

        // 確認コード発行・ログ保存（実削除はワークフロー次第）
        $code = 'del_' . wp_generate_password(12, false, false);
        $now  = current_time('mysql');

        $log = get_option(self::OPT_DEL_LOG, []);
        if (!is_array($log)) $log = [];
        $log[$code] = [
            'time' => $now,
            'facebook_user_id' => $fb_user_id,
            'status' => 'processing', // 実削除処理に合わせて更新していく
        ];
        update_option(self::OPT_DEL_LOG, $log, false);

        $status_url = home_url('/' . self::PAGE_DEL_STATUS_SLUG . '/?id=' . rawurlencode($code));

        // Meta規定のレスポンス
        return new \WP_REST_Response([
            'url' => $status_url,
            'confirmation_code' => $code,
        ], 200);
    }

    private function parse_signed_request($signed_request, $secret)
    {
        $parts = explode('.', $signed_request, 2);
        if (count($parts) !== 2) return false;
        list($encoded_sig, $payload) = $parts;
        $sig = $this->base64_url_decode($encoded_sig);
        $data = json_decode($this->base64_url_decode($payload), true);
        if (!is_array($data) || empty($data['algorithm']) || strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
            return false;
        }
        $expected_sig = hash_hmac('sha256', $payload, $secret, true);
        if (!hash_equals($expected_sig, $sig)) return false;
        return $data;
    }
    private function base64_url_decode($input)
    {
        $replaced = strtr($input, '-_', '+/');
        $padding = strlen($replaced) % 4;
        if ($padding) $replaced .= str_repeat('=', 4 - $padding);
        return base64_decode($replaced);
    }

    public function sc_data_deletion($atts = [])
    {
        ob_start(); ?>
        <section class="p-data-deletion">
            <h2>ユーザーデータの削除について</h2>
            <p>当サイトのアプリケーションは、Meta（Facebook/Instagram）のポリシーに従い、ユーザーからの<strong>個人データ削除リクエスト</strong>を受け付けています。</p>
            <h3>Meta（Facebook/Instagram）経由での削除リクエスト</h3>
            <p>Facebook/Instagram のアプリ設定から「データの削除」を実行すると、Meta から当サイトの <code>データ削除コールバック</code> に通知されます。削除が開始されると、<strong>確認コード</strong>と<strong>ステータス確認URL</strong>が発行されます。</p>
            <ul>
                <li>データ削除コールバックURL：<code><?php echo esc_html(home_url('/wp-json/' . self::ROUTE_NS . '/meta-data-deletion')); ?></code></li>
                <li>ステータス確認URL：<code><?php echo esc_html(home_url('/' . self::PAGE_DEL_STATUS_SLUG . '/')); ?></code></li>
            </ul>
            <h3>サイトから直接の削除リクエスト</h3>
            <p>Meta経由での削除が難しい場合、下記フォームからもリクエストできます（Facebook User ID を任意で入力可能）。<br>送信すると<strong>確認コード</strong>と<strong>ステータスURL</strong>を表示します。</p>
            <form id="ou-gd-del-form">
                <label for="ou_fb_uid">Facebook User ID（任意）</label><br>
                <input type="text" id="ou_fb_uid" name="facebook_user_id" style="max-width:320px;">
                <button type="submit" style="margin-left:8px;">削除をリクエスト</button>
            </form>
            <div id="ou-gd-del-result" style="margin-top:10px;"></div>
            <script>
                (function() {
                    var f = document.getElementById('ou-gd-del-form');
                    if (!f) return;
                    f.addEventListener('submit', function(ev) {
                        ev.preventDefault();
                        var uid = document.getElementById('ou_fb_uid').value || '';
                        var out = document.getElementById('ou-gd-del-result');
                        out.textContent = '送信中…';
                        fetch('<?php echo esc_js(home_url('/wp-json/' . self::ROUTE_NS . '/meta-data-deletion')); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'facebook_user_id=' + encodeURIComponent(uid)
                        }).then(function(r) {
                            return r.json();
                        }).then(function(j) {
                            if (j && j.confirmation_code && j.url) {
                                out.innerHTML = '受付しました。<br>確認コード: <code>' + j.confirmation_code + '</code><br>ステータス: <a href="' + j.url + '">' + j.url + '</a>';
                            } else if (j && j.error) {
                                out.textContent = 'エラー: ' + j.error;
                            } else {
                                out.textContent = 'エラーが発生しました。';
                            }
                        }).catch(function() {
                            out.textContent = '送信に失敗しました。';
                        });
                    });
                })();
            </script>
            <p style="margin-top:16px;color:#666;">※ 実際のデータ削除（ログ・バックアップ等を含む）は、当サイトの運用手順に従い順次実施します。</p>
        </section>
    <?php
        return ob_get_clean();
    }

    public function sc_data_deletion_status($atts = [])
    {
        $code = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        $log  = get_option(self::OPT_DEL_LOG, []);
        $item = (is_array($log) && $code && isset($log[$code])) ? $log[$code] : null;

        ob_start(); ?>
        <section class="p-data-deletion-status">
            <h2>データ削除ステータス</h2>
            <?php if ($item): ?>
                <dl>
                    <dt>確認コード</dt>
                    <dd><code><?php echo esc_html($code); ?></code></dd>
                    <dt>ステータス</dt>
                    <dd><?php echo esc_html($item['status']); ?></dd>
                    <dt>受付日時</dt>
                    <dd><?php echo esc_html($item['time']); ?></dd>
                    <?php if (!empty($item['facebook_user_id'])): ?>
                        <dt>Facebook User ID</dt>
                        <dd><?php echo esc_html($item['facebook_user_id']); ?></dd>
                    <?php endif; ?>
                </dl>
                <p>※ ステータスが <code>completed</code> になるまで数日要する場合があります。</p>
            <?php else: ?>
                <p>該当する確認コードが見つかりません。URLを再確認してください。</p>
            <?php endif; ?>
        </section>
<?php
        return ob_get_clean();
    }

    /* =========================================================
     * Admin Notices
     * ======================================================= */
    public function admin_notice_missing_creds_or_pages()
    {
        if (!current_user_can('manage_options')) return;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_plugin_area = $screen && (
            ($screen->post_type ?? '') === self::POST_TYPE
            || ($screen->id ?? '') === 'ou_listing_page_ou-gd-instagram'
            || ($screen->id ?? '') === 'edit-' . self::POST_TYPE
        );

        if (!$is_plugin_area) return;

        // App creds
        list($app_id, $app_secret) = $this->get_app_creds();
        if (!$app_id || !$app_secret) {
            $url = admin_url('edit.php?post_type=' . self::POST_TYPE . '&page=ou-gd-instagram');
            echo '<div class="notice notice-warning"><p><strong>OU Gourmet Directory:</strong> InstagramのApp ID / App Secretが未設定です。'
                . ' サムネ自動取得は無効化されます。<a href="' . esc_url($url) . '">設定はこちら</a>。</p></div>';
        }

        // Deletion pages
        $info   = get_page_by_path(self::PAGE_DEL_INFO_SLUG);
        $status = get_page_by_path(self::PAGE_DEL_STATUS_SLUG);
        if (!$info || !$status) {
            echo '<div class="notice notice-warning"><p><strong>OU Gourmet Directory:</strong> データ削除ページが見つかりません。'
                . ' プラグインの再有効化、または <code>[ou_gd_data_deletion]</code> と <code>[ou_gd_data_deletion_status]</code> を貼った固定ページを手動で作成してください。</p></div>';
        }
    }

    /* =========================================================
     * Helpers（Instagram）
     * ======================================================= */
    private function get_app_creds()
    {
        if (defined('OU_GD_APP_ID') && defined('OU_GD_APP_SECRET')) {
            return [trim(constant('OU_GD_APP_ID')), trim(constant('OU_GD_APP_SECRET'))];
        }
        $opt = get_option(self::OPT_INSTA, []);
        return [$opt['app_id'] ?? '', $opt['app_secret'] ?? ''];
    }

    private function normalize_instagram_url($url)
    {
        if (!$url) return '';
        $url = trim($url);
        if (stripos($url, 'http://') === 0) $url = 'https://' . substr($url, 7);
        $parts = wp_parse_url($url);
        if (!$parts || empty($parts['host'])) return '';
        $host = strtolower($parts['host']);
        if (substr($host, -10) === 'instagr.am') $host = 'www.instagram.com';
        if ($host === 'instagram.com') $host = 'www.instagram.com';
        $scheme = 'https';
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? ('?' . $parts['query']) : '';
        return $scheme . '://' . $host . $path . $query;
    }
    private function is_valid_instagram_url($url)
    {
        $host = wp_parse_url($url, PHP_URL_HOST);
        if (!$host) return false;
        return (substr(strtolower($host), -13) === 'instagram.com');
    }

    private function fetch_instagram_thumbnail($insta_url)
    {
        $insta_url = $this->normalize_instagram_url(esc_url_raw($insta_url));
        if (!$insta_url || !$this->is_valid_instagram_url($insta_url)) {
            return new WP_Error('no_url', 'URL missing or invalid');
        }
        list($app_id, $app_secret) = $this->get_app_creds();
        if (!$app_id || !$app_secret) return new WP_Error('no_creds', 'No App credentials');

        $access_token = $app_id . '|' . $app_secret;
        $endpoint = add_query_arg([
            'url' => $insta_url,
            'maxwidth' => 640,
            'omitscript' => 'true',
            'fields' => 'thumbnail_url',
            'access_token' => $access_token,
        ], 'https://graph.facebook.com/v20.0/instagram_oembed');

        $res = wp_remote_get($endpoint, [
            'timeout' => 10,
            'headers' => ['Accept' => 'application/json', 'User-Agent' => 'OU-Gourmet-Directory/' . self::VER . '; ' . home_url('/')],
        ]);
        if (is_wp_error($res)) return $res;

        $code = (int)wp_remote_retrieve_response_code($res);
        $body = (string)wp_remote_retrieve_body($res);
        $json = json_decode($body, true);

        if ($code !== 200 || !is_array($json)) {
            $api_error = [];
            if (is_array($json) && isset($json['error'])) $api_error = (array)$json['error'];
            $msg_parts = ["oEmbed error HTTP {$code}"];
            if (!empty($api_error['type']))    $msg_parts[] = "type: {$api_error['type']}";
            if (!empty($api_error['code']))    $msg_parts[] = "code: {$api_error['code']}";
            if (!empty($api_error['error_subcode'])) $msg_parts[] = "subcode: {$api_error['error_subcode']}";
            if (!empty($api_error['message'])) $msg_parts[] = "message: {$api_error['message']}";
            $msg = implode(' / ', $msg_parts);
            return new WP_Error('bad_response', $msg, ['http_code' => $code, 'api_error' => $api_error]);
        }

        $thumb = $json['thumbnail_url'] ?? '';
        if (!$thumb) return new WP_Error('no_thumb', 'No thumbnail in response');
        $scheme = wp_parse_url($thumb, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) return new WP_Error('bad_thumb', 'Invalid thumbnail URL');
        return esc_url_raw($thumb);
    }

    private function sideload_to_media($image_url, $post_id)
    {
        if (!function_exists('media_sideload_image')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        $att_id = media_sideload_image($image_url, $post_id, 'Instagram thumbnail', 'id');
        if (is_wp_error($att_id)) return 0;
        return (int)$att_id;
    }
}

new OU_Gourmet_Directory();
