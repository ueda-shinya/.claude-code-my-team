<?php

/**
 * Plugin Name: User Nicename Editor
 * Plugin URI:  https://officeueda.com
 * Description: ユーザーのプロフィール画面に user_nicename（著者アーカイブのURLスラッグ）を表示し、管理者が編集できるようにします。
 * Version: 1.0.0
 * Author:      Shinya
 * Author URI:  https://officeueda.com
 * License:     GPL-2.0+
 */

if (!defined('ABSPATH')) exit;

// ▼ 管理画面に user_nicename フィールド追加
function ewn_show_user_nicename_field($user)
{
    if (!current_user_can('manage_options')) return; // 管理者のみ
?>
    <h3>ユーザーNicename（URLスラッグ）</h3>
    <table class="form-table">
        <tr>
            <th><label for="user_nicename">Nicename</label></th>
            <td>
                <input type="text" name="user_nicename" id="user_nicename"
                    value="<?php echo esc_attr($user->user_nicename); ?>"
                    class="regular-text" /><br />
                <span class="description">
                    著者ページのURLに使用されるユーザースラッグです。<br>
                    例: https://example.com/author/<strong>この部分</strong>
                </span>
            </td>
        </tr>
    </table>
<?php
}
add_action('show_user_profile', 'ewn_show_user_nicename_field');
add_action('edit_user_profile', 'ewn_show_user_nicename_field');

// ▼ 保存処理（管理者のみ／DB直更新）
function ewn_save_user_nicename_field($user_id)
{
    if (!current_user_can('manage_options')) return;
    if (!isset($_POST['user_nicename'])) return;

    global $wpdb;

    $nicename = sanitize_title($_POST['user_nicename']); // スラッグ化
    $wpdb->update(
        $wpdb->users,
        ['user_nicename' => $nicename],
        ['ID' => $user_id]
    );

    clean_user_cache($user_id); // キャッシュクリア
}
add_action('personal_options_update', 'ewn_save_user_nicename_field');
add_action('edit_user_profile_update', 'ewn_save_user_nicename_field');
