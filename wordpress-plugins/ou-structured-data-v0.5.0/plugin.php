<?php
/**
 * Plugin Name: OU Structured Data
 * Description: Unified JSON-LD (@graph) output for WordPress. Organization/WebSite/Breadcrumb + BlogPosting/Service/FAQ/AboutPage. Settings Assistant included.
 * Version: 0.5.0
 * Author: Your Team
 * Text Domain: ou-structured-data
 * Requires at least: 6.0
 * Requires PHP: 8.1
 */
if (!defined('ABSPATH')) { exit; }
define('OU_SD_VERSION', '0.5.0');
define('OU_SD_FILE', __FILE__);
define('OU_SD_DIR', plugin_dir_path(__FILE__));
define('OU_SD_URL', plugin_dir_url(__FILE__));

spl_autoload_register(function($class) {
    if (strpos($class, 'OU\\StructuredData\\') !== 0) return;
    $rel = str_replace('OU\\StructuredData\\', '', $class);
    $rel = str_replace('\\', DIRECTORY_SEPARATOR, $rel) . '.php';
    $path = OU_SD_DIR . $rel;
    if (file_exists($path)) require_once $path;
});

use OU\StructuredData\Bootstrap\Loader;
add_action('plugins_loaded', function() {
    load_plugin_textdomain('ou-structured-data', false, dirname(plugin_basename(__FILE__)) . '/languages');
    $loader = new Loader(); $loader->boot();
});
register_activation_hook(__FILE__, ['OU\\StructuredData\\Bootstrap\\Loader', 'onActivate']);
register_deactivation_hook(__FILE__, ['OU\\StructuredData\\Bootstrap\\Loader', 'onDeactivate']);
