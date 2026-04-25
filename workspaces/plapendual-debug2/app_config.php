<?php
if (!defined("ABSPATH")) {
  // date_default_timezone_set('Asia/Ho_Chi_Minh'); // For Vietnam
  date_default_timezone_set('Asia/Tokyo'); // For Japan
}

$dist = '';
// get protocol.
$url = $_SERVER['HTTP_HOST'] . '/' . $dist;
$protocol = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') || !empty($_SERVER["HTTPS"]) ? 'https://' : 'http://';

// Get dist folder.
$script_name = str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['SCRIPT_NAME']);
$script_filename = str_replace(dirname(__FILE__), '', str_replace('private_html', 'public_html', $_SERVER['SCRIPT_FILENAME']));
$dist = trim(str_replace($script_filename, '', $script_name), "/");
if (!empty($dist)) $dist .= '/';
if (strpos($dist, ".php") !== false || strpos($dist, ".html") !== false || strpos($dist, ".htm") !== false) $dist = "";

// get host.
$app_url = $protocol . $_SERVER['HTTP_HOST'] . '/' . $dist;
define('APP_URL', $app_url);
define('APP_PATH', dirname(__FILE__) . '/');
define('APP_ASSETS', APP_URL);
// define('APP_ASSETS', APP_URL . 'assets/');


define('GOOGLE_MAP_API_KEY', '');
define('GOOGLE_RECAPTCHA_KEY_API', '6LePgHspAAAAAIz64c7HcZLAx4Qh6ff-aCOlxAqs');
define('GOOGLE_RECAPTCHA_KEY_SECRET', '6LePgHspAAAAAJ02IrIU6AkUOJkRnYTRi7uF2YJB');


/* email list for forms */
//contact
// $aMailtoReserve = array('yamamoto@re-ad.co.jp');
$aMailtoReserve = array('info@bakuchis.com');
// $aBccToContact = array('info@pyonkara.com');
$aBccToContact = array('sato@re-ad.co.jp');
$fromName = "BAKUCHIS Corporation株式会社";
$Reply = "info@company.bakuchis.com";
$fromReserve = "info@company.bakuchis.com";
