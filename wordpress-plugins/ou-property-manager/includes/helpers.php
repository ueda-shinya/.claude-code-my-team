<?php
if (!defined('ABSPATH')) exit;


function oupm_absf($val)
{
    return max(0.0, floatval($val));
}
function oupm_absi($val)
{
    return max(0, absint($val));
}
function oupm_bool($val)
{
    return filter_var($val, FILTER_VALIDATE_BOOLEAN);
}
function oupm_sanitize_key_array($arr)
{
    return array_map('sanitize_key', (array)$arr);
}


// 単位変換（入力は正規化済の前提）
function oupm_display_price_man($yen)
{
    return number_format(round($yen / 10000)) . '万円';
}
function oupm_display_sqm($sqm)
{
    return esc_html(number_format_i18n($sqm, 2)) . '㎡';
}
