<?php

/**
 * Plugin Name: HTAccess Cache Control
 * Description: .htaccessファイルを編集しキャッシュを無効にします。テスト時以外は無効にしてください。
 * Version: 1.00
 * Author: しんや
 * Author URI: https://officeueda.com
 */

function custom_htaccess_write($enable)
{
    $htaccess = ABSPATH . '.htaccess';
    $backup_htaccess = $htaccess . '.bak-hcc';
    $rules = "
# BEGIN Cache Control
<IfModule mod_headers.c>
Header set Cache-Control \"no-cache, no-store, must-revalidate\"
Header set Pragma \"no-cache\"
Header set Expires 0
</IfModule>
# END Cache Control
";

    if (!file_exists($backup_htaccess)) {
        copy($htaccess, $backup_htaccess);
    }

    $htaccess_contents = file_get_contents($htaccess);
    if ($enable) {
        if (strpos($htaccess_contents, '# BEGIN Cache Control') === false) {
            $htaccess_contents .= $rules;
        }
    } else {
        $pattern = "/
# BEGIN Cache Control.*# END Cache Control
/s";
        $htaccess_contents = preg_replace($pattern, '', $htaccess_contents);
    }

    file_put_contents($htaccess, $htaccess_contents);
}

function htaccess_cache_control_activate()
{
    custom_htaccess_write(true);
}

function htaccess_cache_control_deactivate()
{
    custom_htaccess_write(false);
}

register_activation_hook(__FILE__, 'htaccess_cache_control_activate');
register_deactivation_hook(__FILE__, 'htaccess_cache_control_deactivate');

add_action('admin_menu', 'htaccess_cache_control_menu');

function htaccess_cache_control_menu()
{
    add_menu_page('HTAccess Cache Control Settings', 'HTAccess Cache Control', 'manage_options', 'htaccess-cache-control', 'htaccess_cache_control_options_page', 'dashicons-admin-generic', 99);
}

function htaccess_cache_control_options_page()
{
    $backup_htaccess = ABSPATH . '.htaccess.bak-hcc';
    echo '<div class="wrap"><h2>HTAccess Cache Control Settings</h2>';
    if (file_exists($backup_htaccess)) {
        echo '<p><strong>バックアップファイルが存在します:</strong> ' . esc_html($backup_htaccess) . '</p><p>予期しないトラブルが起きた場合、このバックアップファイルを使用して`.htaccess`ファイルを復旧してください。</p>';
    } else {
        echo '<p>バックアップファイルが見つかりませんでした。プラグインの再アクティベーションを試みてください。</p>';
    }
    echo '</div>';
}
