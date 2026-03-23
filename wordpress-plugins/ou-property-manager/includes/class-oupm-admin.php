<?php
if (!defined('ABSPATH')) exit;


class OU_PMP_Admin
{
    public static function init()
    {
        add_filter('manage_property_posts_columns', [__CLASS__, 'cols']);
        add_action('manage_property_posts_custom_column', [__CLASS__, 'col_content'], 10, 2);
        add_filter('manage_edit-property_sortable_columns', [__CLASS__, 'sortable']);


        // 期限超過の注意
        add_action('admin_notices', [__CLASS__, 'notice_expired']);
    }
    public static function cols($cols)
    {
        $new = [];
        foreach ($cols as $k => $v) {
            $new[$k] = $v;
            if ($k === 'title') {
                $new['price'] = '価格';
                $new['addr'] = '所在地';
                $new['publish_until'] = '掲載期限';
            }
        }
        return $new;
    }
    public static function col_content($col, $post_id)
    {
        if ($col === 'price') {
            $yen = (int)get_field('price_yen', $post_id);
            $open = (bool)get_field('price_open', $post_id);
            echo $open ? '応相談' : esc_html(oupm_display_price_man($yen));
        } elseif ($col === 'addr') {
            echo esc_html(get_field('addr_pref', $post_id) . ' ' . get_field('addr_city', $post_id));
        } elseif ($col === 'publish_until') {
            echo esc_html((string)get_field('publish_until', $post_id));
        }
    }
    public static function sortable($cols)
    {
        $cols['price'] = 'price';
        return $cols;
    }
    public static function notice_expired()
    {
        global $pagenow;
        if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'property') {
            echo '<div class="notice notice-info"><p>掲載期限が過ぎた物件はフロントの検索結果から自動除外されます。</p></div>';
        }
    }
}
