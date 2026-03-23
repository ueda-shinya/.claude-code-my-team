<?php
if (!defined('ABSPATH')) exit;


class OU_PMP_ACF
{
    public static function init()
    {
        add_action('acf/init', [__CLASS__, 'register_fields']);
    }
    public static function register_fields()
    {
        if (!function_exists('acf_add_local_field_group')) return;


        acf_add_local_field_group([
            'key' => 'group_oupm_property',
            'title' => '物件情報',
            'fields' => [
                ['key' => 'field_prop_id', 'label' => '物件ID/管理番号', 'name' => 'prop_id', 'type' => 'text'],
                ['key' => 'field_price_yen', 'label' => '価格（円）', 'name' => 'price_yen', 'type' => 'number', 'required' => 1, 'min' => 0],
                ['key' => 'field_price_open', 'label' => '価格未公開', 'name' => 'price_open', 'type' => 'true_false', 'ui' => 1],
                ['key' => 'field_addr_pref', 'label' => '都道府県', 'name' => 'addr_pref', 'type' => 'text', 'required' => 1],
                ['key' => 'field_addr_city', 'label' => '市区町村', 'name' => 'addr_city', 'type' => 'text', 'required' => 1],
                ['key' => 'field_addr_town', 'label' => '町字番地', 'name' => 'addr_town', 'type' => 'text'],
                ['key' => 'field_lat', 'label' => '緯度', 'name' => 'lat', 'type' => 'number', 'step' => '0.000001'],
                ['key' => 'field_lng', 'label' => '経度', 'name' => 'lng', 'type' => 'number', 'step' => '0.000001'],
                ['key' => 'field_traffic_line', 'label' => '路線名', 'name' => 'traffic_line', 'type' => 'text'],
                ['key' => 'field_traffic_station', 'label' => '最寄駅', 'name' => 'traffic_station', 'type' => 'text'],
                ['key' => 'field_walk_min', 'label' => '駅徒歩（分）', 'name' => 'walk_min', 'type' => 'number', 'min' => 0, 'max' => 240],
                ['key' => 'field_bus_stop', 'label' => '最寄バス停', 'name' => 'bus_stop', 'type' => 'text'],
                ['key' => 'field_bus_walk_min', 'label' => 'バス停徒歩（分）', 'name' => 'bus_walk_min', 'type' => 'number', 'min' => 0, 'max' => 240],
                ['key' => 'field_land_area_sqm', 'label' => '土地面積（㎡）', 'name' => 'land_area_sqm', 'type' => 'number', 'min' => 0],
                ['key' => 'field_floor_area_total', 'label' => '延床面積（㎡）', 'name' => 'floor_area_total', 'type' => 'number', 'min' => 0],
                ['key' => 'field_floor_area_1f', 'label' => '1階面積（㎡）', 'name' => 'floor_area_1f', 'type' => 'number', 'min' => 0],
                ['key' => 'field_floor_area_2f', 'label' => '2階面積（㎡）', 'name' => 'floor_area_2f', 'type' => 'number', 'min' => 0],
                ['key' => 'field_layout_rooms', 'label' => '間取り（部屋数）', 'name' => 'layout_rooms', 'type' => 'number', 'min' => 0, 'max' => 10],
                ['key' => 'field_layout_type', 'label' => '間取りタイプ', 'name' => 'layout_type', 'type' => 'select', 'choices' => ['K' => 'K', 'DK' => 'DK', 'LDK' => 'LDK', 'SLDK' => 'SLDK'], 'allow_null' => 1],
                ['key' => 'field_built_ym', 'label' => '築年月（YYYY-MM/不明）', 'name' => 'built_ym', 'type' => 'text'],
                ['key' => 'field_age_months', 'label' => '築年数（月）', 'name' => 'age_months', 'type' => 'number', 'min' => 0],
                [
                    'key' => 'field_structure',
                    'label' => '構造',
                    'name' => 'structure',
                    'type' => 'select',
                    'allow_null' => 1,
                    'choices' => ['wood' => '木造', 'lgs' => '軽量鉄骨', 'steel' => '鉄骨', 'rc' => 'RC', 'other' => 'その他']
                ],
                ['key' => 'field_stories', 'label' => '階数', 'name' => 'stories', 'type' => 'number', 'min' => 0, 'max' => 5],
                [
                    'key' => 'field_land_right',
                    'label' => '土地権利',
                    'name' => 'land_right',
                    'type' => 'select',
                    'allow_null' => 1,
                    'choices' => ['own' => '所有権', 'lease' => '借地権', 'surface' => '地上権']
                ],
                [
                    'key' => 'field_land_cat',
                    'label' => '地目',
                    'name' => 'land_cat',
                    'type' => 'select',
                    'allow_null' => 1,
                    'choices' => ['宅地' => '宅地', '畑' => '畑', '田' => '田', '山林' => '山林', 'その他' => 'その他']
                ],
                [
                    'key' => 'field_urban_plan',
                    'label' => '都市計画',
                    'name' => 'urban_plan',
                    'type' => 'select',
                    'allow_null' => 1,
                    'choices' => ['city' => '市街化区域', 'control' => '市街化調整区域', 'non' => '非線引', 'outer' => '都市外']
                ],
                ['key' => 'field_use_zone', 'label' => '用途地域', 'name' => 'use_zone', 'type' => 'text'],
                ['key' => 'field_bcr', 'label' => '建ぺい率（%）', 'name' => 'bcr', 'type' => 'number', 'min' => 0, 'max' => 100],
                ['key' => 'field_far', 'label' => '容積率（%）', 'name' => 'far', 'type' => 'number', 'min' => 0, 'max' => 1000],
                [
                    'key' => 'field_road_type',
                    'label' => '接道（公道/私道）',
                    'name' => 'road_type',
                    'type' => 'select',
                    'allow_null' => 1,
                    'choices' => ['public' => '公道', 'private' => '私道']
                ],
                [
                    'key' => 'field_road_dir',
                    'label' => '接道方向',
                    'name' => 'road_dir',
                    'type' => 'select',
                    'allow_null' => 1,
                    'choices' => ['east' => '東', 'west' => '西', 'south' => '南', 'north' => '北', 'other' => 'その他']
                ],
                ['key' => 'field_road_width_m', 'label' => '道路幅員（m）', 'name' => 'road_width_m', 'type' => 'number', 'min' => 0, 'step' => '0.1'],
                ['key' => 'field_road_contact_m', 'label' => '接面長（m）', 'name' => 'road_contact_m', 'type' => 'number', 'min' => 0, 'step' => '0.1'],
                ['key' => 'field_parking', 'label' => '駐車場（有/無）', 'name' => 'parking', 'type' => 'true_false', 'ui' => 1],
                ['key' => 'field_school_zone', 'label' => '学区', 'name' => 'school_zone', 'type' => 'text'],
                ['key' => 'field_utilities', 'label' => '設備', 'name' => 'utilities', 'type' => 'checkbox', 'choices' => [
                    'elec' => '電気',
                    'water' => '上水道',
                    'sewer' => '下水道',
                    'tank' => '浄化槽',
                    'citygas' => '都市ガス',
                    'propane' => 'プロパン',
                    'geyser' => '給湯',
                    'heater' => '温水器'
                ], 'layout' => 'vertical'],
                [
                    'key' => 'field_status_now',
                    'label' => '現況',
                    'name' => 'status_now',
                    'type' => 'select',
                    'allow_null' => 1,
                    'choices' => ['owner' => '所有者居住中', 'vacant' => '空家', 'rented' => '賃貸中', 'new' => '未完成']
                ],
                [
                    'key' => 'field_handover',
                    'label' => '引渡',
                    'name' => 'handover',
                    'type' => 'select',
                    'allow_null' => 1,
                    'choices' => ['now' => '即時', '相談' => '相談', 'date' => '期日指定']
                ],
                [
                    'key' => 'field_broker_type',
                    'label' => '取引態様',
                    'name' => 'broker_type',
                    'type' => 'select',
                    'allow_null' => 1,
                    'choices' => ['owner' => '売主', 'agent' => '代理', 'senin' => '専任', 'senzo' => '専属', 'ippan' => '一般']
                ],
                ['key' => 'field_publish_until', 'label' => '掲載期限', 'name' => 'publish_until', 'type' => 'date_picker', 'display_format' => 'Y-m-d', 'return_format' => 'Y-m-d'],
                ['key' => 'field_media_pdf', 'label' => '媒体PDF', 'name' => 'media_pdf', 'type' => 'file', 'mime_types' => 'pdf'],
                ['key' => 'field_media_sheet_jpg', 'label' => '媒体画像（自動/手動）', 'name' => 'media_sheet_jpg', 'type' => 'image', 'return_format' => 'id'],
                ['key' => 'field_note', 'label' => '備考', 'name' => 'note', 'type' => 'textarea'],
            ],
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'property']]],
            'position' => 'normal',
            'style' => 'default',
            'active' => true,
        ]);


        // 保存時：built_ym→age_months 自動算出
        add_action('acf/save_post', function ($post_id) {
            if (get_post_type($post_id) !== 'property') return;
            $ym = trim((string) get_field('built_ym', $post_id));
            if ($ym && preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) {
                $y = (int)$m[1];
                $mm = (int)$m[2];
                $now = new DateTime('now', wp_timezone());
                $diff = ((int)$now->format('Y')) * 12 + (int)$now->format('n') - ($y * 12 + $mm);
                if ($diff >= 0) update_field('age_months', $diff, $post_id);
            }
        }, 20);
    }
}
