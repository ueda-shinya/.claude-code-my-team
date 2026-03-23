<?php
if (!defined('ABSPATH')) exit;

class OU_PMP_Search
{
    public static function init()
    {
        add_action('rest_api_init', [__CLASS__, 'register_route']);
        add_shortcode('oupm_search', [__CLASS__, 'shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets']);
    }

    public static function assets()
    {
        wp_register_style('oupm-style', OUPM_URL . 'assets/css/oupm.css', [], OUPM_VER);
        wp_register_script('oupm-search', OUPM_URL . 'assets/js/oupm-search.js', [], OUPM_VER, true);
    }

    public static function shortcode($atts)
    {
        wp_enqueue_style('oupm-style');
        wp_enqueue_script('oupm-search');

        ob_start(); ?>
        <form id="oupm-form" class="l-form c-form" aria-label="物件検索フォーム">
            <div class="c-form__row">
                <label class="c-form__label" for="price_min">価格(万円)</label>
                <input class="c-form__input" type="number" id="price_min" name="price_min" placeholder="下限"> -
                <input class="c-form__input" type="number" id="price_max" name="price_max" placeholder="上限">
            </div>
            <div class="c-form__row">
                <label class="c-form__label" for="pref">都道府県</label>
                <input class="c-form__input" type="text" id="pref" name="pref" placeholder="例: 広島県">
                <label class="c-form__label" for="city">市区町村</label>
                <input class="c-form__input" type="text" id="city" name="city" placeholder="例: 呉市">
            </div>
            <div class="c-form__row">
                <label class="c-form__label" for="walk_max">駅徒歩(分以下)</label>
                <input class="c-form__input" type="number" id="walk_max" name="walk_max" min="0">
            </div>
            <div class="c-form__row">
                <label class="c-form__label" for="land_min">土地(㎡)</label>
                <input class="c-form__input" type="number" id="land_min" name="land_min" placeholder="下限"> -
                <input class="c-form__input" type="number" id="land_max" name="land_max" placeholder="上限">
            </div>
            <div class="c-form__row">
                <label class="c-form__label" for="structure">構造</label>
                <select class="c-form__select" id="structure" name="structure">
                    <option value="">指定なし</option>
                    <option value="wood">木造</option>
                    <option value="lgs">軽量鉄骨</option>
                    <option value="steel">鉄骨</option>
                    <option value="rc">RC</option>
                </select>
                <label class="c-form__label"><input type="checkbox" name="parking" value="1"> 駐車場あり</label>
            </div>
            <div class="c-form__row">
                <button class="c-btn" type="submit">検索</button>
                <select class="c-form__select" id="orderby" name="orderby" aria-label="並び替え">
                    <option value="date">新着順</option>
                    <option value="price_yen">価格</option>
                    <option value="land_area_sqm">土地面積</option>
                    <option value="walk_min">駅徒歩</option>
                </select>
                <select class="c-form__select" id="order" name="order" aria-label="昇順降順">
                    <option value="DESC">降順</option>
                    <option value="ASC">昇順</option>
                </select>
            </div>
        </form>
        <div id="oupm-results" class="l-grid"></div>
<?php
        return ob_get_clean();
    }

    public static function register_route()
    {
        register_rest_route('oupm/v1', '/search', [
            'methods'  => 'GET',
            'callback' => [__CLASS__, 'handle_search'],
            'permission_callback' => '__return_true', // 公開データのみ
            'args' => [
                'page'     => ['validate_callback' => 'is_numeric', 'default' => 1],
                'per_page' => ['validate_callback' => 'is_numeric', 'default' => 12],
            ]
        ]);
    }

    public static function handle_search(WP_REST_Request $r)
    {
        // ページング
        $page = max(1, (int)$r->get_param('page'));
        $pp   = min(50, max(1, (int)$r->get_param('per_page')));

        // メタ条件
        $meta = ['relation' => 'AND'];

        // 価格（万円→円）
        $pmin = $r->get_param('price_min');
        $pmax = $r->get_param('price_max');
        if ($pmin !== null && $pmin !== '') $meta[] = ['key' => 'price_yen', 'value' => ((int)$pmin) * 10000, 'compare' => '>=', 'type' => 'NUMERIC'];
        if ($pmax !== null && $pmax !== '') $meta[] = ['key' => 'price_yen', 'value' => ((int)$pmax) * 10000, 'compare' => '<=', 'type' => 'NUMERIC'];

        // 駅徒歩
        $walk = $r->get_param('walk_max');
        if ($walk !== null && $walk !== '') $meta[] = ['key' => 'walk_min', 'value' => (int)$walk, 'compare' => '<=', 'type' => 'NUMERIC'];

        // 土地面積
        $lmin = $r->get_param('land_min');
        $lmax = $r->get_param('land_max');
        if ($lmin !== null && $lmin !== '') $meta[] = ['key' => 'land_area_sqm', 'value' => (float)$lmin, 'compare' => '>=', 'type' => 'NUMERIC'];
        if ($lmax !== null && $lmax !== '') $meta[] = ['key' => 'land_area_sqm', 'value' => (float)$lmax, 'compare' => '<=', 'type' => 'NUMERIC'];

        // 構造
        $structure = sanitize_key((string)$r->get_param('structure'));
        if ($structure) $meta[] = ['key' => 'structure', 'value' => $structure, 'compare' => '='];

        // 駐車場
        if ($r->get_param('parking')) $meta[] = ['key' => 'parking', 'value' => 1, 'compare' => '='];

        // 住所（LIKE）
        $pref = sanitize_text_field((string)$r->get_param('pref'));
        $city = sanitize_text_field((string)$r->get_param('city'));
        if ($pref) $meta[] = ['key' => 'addr_pref', 'value' => $pref, 'compare' => 'LIKE'];
        if ($city) $meta[] = ['key' => 'addr_city', 'value' => $city, 'compare' => 'LIKE'];

        // 掲載期限フィルタ（期限切れ除外）
        $meta[] = [
            'relation' => 'OR',
            ['key' => 'publish_until', 'compare' => 'NOT EXISTS'],
            ['key' => 'publish_until', 'value' => current_time('Y-m-d'), 'compare' => '>=', 'type' => 'DATE'],
        ];

        // 並び替え
        $orderby = sanitize_key((string)$r->get_param('orderby')) ?: 'date';
        $order   = (strtoupper((string)$r->get_param('order')) === 'ASC') ? 'ASC' : 'DESC';

        $args = [
            'post_type'      => 'property',
            'post_status'    => 'publish',
            'paged'          => $page,
            'posts_per_page' => $pp,
            'meta_query'     => $meta,
            'orderby'        => $orderby === 'date' ? 'date' : 'meta_value_num',
            'order'          => $order,
        ];
        if ($orderby !== 'date') $args['meta_key'] = $orderby;

        $q = new WP_Query($args);

        $items = [];
        while ($q->have_posts()) {
            $q->the_post();
            $id     = get_the_ID();
            $pdf_id = (int)get_field('media_pdf', $id);
            $jpg_id = (int)get_field('media_sheet_jpg', $id);

            $items[] = [
                'id'        => $id,
                'title'     => get_the_title(),
                'permalink' => get_permalink(),
                'thumb'     => $jpg_id ? wp_get_attachment_image_url($jpg_id, 'large') : get_the_post_thumbnail_url($id, 'large'),
                'price'     => (bool)get_field('price_open', $id) ? '応相談' : oupm_display_price_man((int)get_field('price_yen', $id)),
                'addr'      => trim(get_field('addr_pref', $id) . ' ' . get_field('addr_city', $id)),
                'land'      => (float)get_field('land_area_sqm', $id),
                'walk'      => (int)get_field('walk_min', $id),
                'pdf'       => $pdf_id ? wp_get_attachment_url($pdf_id) : null,
            ];
        }
        wp_reset_postdata();

        return new WP_REST_Response([
            'items'    => $items,
            'total'    => (int)$q->found_posts,
            'max_page' => (int)$q->max_num_pages,
            'page'     => $page,
        ], 200);
    }
}
