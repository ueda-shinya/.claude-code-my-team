<?php
/*
Plugin Name: OfficeUEDA original
Description: オフィスウエダ 使用プラグイン一覧
Version: 1.00
Author: しんや
Author URI: https://officeueda.com
*/

// 必要なファイルの読み込み
require_once plugin_dir_path(__FILE__) . 'includes/install.php';
require_once plugin_dir_path(__FILE__) . 'includes/utilities.php';
// 管理者設定ファイルの読み込み
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
// ショートコードを定義するファイルのインクルード
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';


// プラグイン有効化時のフック
register_activation_hook(__FILE__, 'officeueda_activate');

// プラグイン無効化時のフック
register_deactivation_hook(__FILE__, 'officeueda_deactivate');


// プラグイン有効化時の処理
function officeueda_activate()
{
}

// プラグイン無効化時の処理
function officeueda_deactivate()
{
}
