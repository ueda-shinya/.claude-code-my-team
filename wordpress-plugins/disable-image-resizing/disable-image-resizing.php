<?php
/*
Plugin Name: Disable Image Resizing
Description: This plugin disables the automatic image resizing feature in WordPress.
Version: 1.0
Author: しんや
Author URI: https://officeueda.com
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

add_filter("big_image_size_threshold", "__return_false");
