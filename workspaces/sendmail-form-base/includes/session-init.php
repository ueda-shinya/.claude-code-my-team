<?php
declare(strict_types=1);

/**
 * セッション初期化モジュール。
 * session_start() より前に必ず require する。
 * contact.php / submit.php の両エントリポイントで共通利用する。
 */
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'domain'   => '',
  'secure'   => !empty($_SERVER['HTTPS']),
  'httponly' => true,
  'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_start();
