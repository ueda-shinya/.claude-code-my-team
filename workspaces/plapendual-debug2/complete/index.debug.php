<?php
/**
 * これはデバッグ用の一時ファイルです。本番には絶対に残さないこと。
 *
 * 使用手順:
 *   1. 本ファイルを本番の /plapendual/complete/index.debug.php にアップロード
 *   2. 確認画面（confirm/index.php）の form action を以下のどちらかに変更してテスト:
 *        a) HTML を直接編集:
 *           action="../complete/index.debug.php?g=<gtimeの値>"
 *        b) または index.debug.php を index.php にリネームして差し替え
 *           ※差し替え前に必ず元の index.php を index.php.bak 等でバックアップすること
 *   3. 確認画面から「送信」ボタンを押す
 *   4. さくらサーバーのエラーログで "[plapendual debug]" を grep してログを確認
 *        例: grep "\[plapendual debug\]" /path/to/php_error.log
 *   5. 確認後、本ファイルを本番から必ず削除すること
 *
 * 注意:
 *   - display_errors=1 を有効にしているため、このファイルは必ず削除してください
 *   - 個人情報はマスク処理済み（メールアドレス先頭3文字 + *** / 氏名は文字数のみ）
 */

// -----------------------------------------------------------------------
// [DEBUG-INIT] エラー表示・ロギングを最大化（デバッグ版のみ）
// -----------------------------------------------------------------------
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

error_log('[plapendual debug] step=INIT msg=debug_file_started');

// -----------------------------------------------------------------------
// [DEBUG-ENV] PHP 関数・設定の存在確認
// -----------------------------------------------------------------------
error_log('[plapendual debug] step=ENV msg=function_check mb_send_mail=' . (function_exists('mb_send_mail') ? 'exists' : 'NOT_FOUND'));
error_log('[plapendual debug] step=ENV msg=function_check mb_encode_mimeheader=' . (function_exists('mb_encode_mimeheader') ? 'exists' : 'NOT_FOUND'));
error_log('[plapendual debug] step=ENV msg=sendmail_path=' . addslashes(ini_get('sendmail_path')));
error_log('[plapendual debug] step=ENV msg=allow_url_fopen=' . ini_get('allow_url_fopen'));
error_log('[plapendual debug] step=ENV msg=php_version=' . PHP_VERSION);

// -----------------------------------------------------------------------
// confirm/index.php のインクルード
// -----------------------------------------------------------------------
error_log('[plapendual debug] step=BEFORE_INCLUDE msg=about_to_include_confirm');

include_once(dirname(__DIR__) . '/confirm/index.php');

error_log('[plapendual debug] step=AFTER_INCLUDE msg=confirm_included_ok');

// -----------------------------------------------------------------------
// mb_* の言語・エンコーディング設定
// -----------------------------------------------------------------------
mb_language('Japanese');
mb_internal_encoding('UTF-8');

error_log('[plapendual debug] step=MB_SETUP msg=mb_language_and_encoding_set');

// -----------------------------------------------------------------------
// 安全な初期化（confirm/index.php からの変数が未定義の場合のフォールバック）
// -----------------------------------------------------------------------
$aMailtoReserve = $aMailtoReserve ?? [];
$aBccToContact  = $aBccToContact  ?? [];
$fromReserve    = $fromReserve    ?? '';
$fromName       = $fromName       ?? '';
$Reply          = $Reply          ?? '';
$actionFlag     = $actionFlag     ?? '';

$reg_salon_name = $reg_salon_name ?? '';
$reg_name       = $reg_name       ?? '';
$reg_tel        = $reg_tel        ?? '';
$reg_address    = $reg_address    ?? '';
$reg_email      = $reg_email      ?? '';
$reg_comment    = $reg_comment    ?? '';
$reg_url        = $reg_url        ?? '';

// -----------------------------------------------------------------------
// [DEBUG-VARS] include 直後の主要変数を記録
// -----------------------------------------------------------------------
error_log('[plapendual debug] step=VARS msg=actionFlag=' . addslashes($actionFlag));
error_log('[plapendual debug] step=VARS msg=fromReserve=' . addslashes($fromReserve));
error_log('[plapendual debug] step=VARS msg=aMailtoReserve=' . addslashes(implode(',', $aMailtoReserve)));
error_log('[plapendual debug] step=VARS msg=aBccToContact=' . addslashes(implode(',', $aBccToContact)));
error_log('[plapendual debug] step=VARS msg=reg_email_prefix=' . (strlen($reg_email) > 3 ? substr($reg_email, 0, 3) . '***' : '***'));
error_log('[plapendual debug] step=VARS msg=reg_name_len=' . mb_strlen($reg_name));
error_log('[plapendual debug] step=VARS msg=APP_URL=' . (defined('APP_URL') ? addslashes(APP_URL) : 'NOT_DEFINED'));

// -----------------------------------------------------------------------
// セッション確保
// -----------------------------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

error_log('[plapendual debug] step=SESSION msg=session_active ses_from_step2=' . (isset($_SESSION['ses_from_step2']) ? (int)$_SESSION['ses_from_step2'] : 'not_set'));
error_log('[plapendual debug] step=SESSION msg=ses_step3=' . (isset($_SESSION['ses_step3']) ? var_export($_SESSION['ses_step3'], true) : 'not_set'));
error_log('[plapendual debug] step=SESSION msg=ses_gtime_step2=' . ($_SESSION['ses_gtime_step2'] ?? 'not_set'));
error_log('[plapendual debug] step=SESSION msg=GET_g=' . addslashes($_GET['g'] ?? ''));

// -----------------------------------------------------------------------
// ユーティリティ関数（本体と同一）
// -----------------------------------------------------------------------

/**
 * ヘッダーインジェクション攻撃を検出する。
 */
function assertNoHeaderInjection(string $value, string $fieldName): void
{
  if (preg_match('/[\r\n\0]/', $value)) {
    error_log('[plapendual debug] step=SECURITY msg=header_injection_detected field=' . $fieldName . ' type=raw');
    error_log('[plapendual] ヘッダーインジェクション試行検知 field=' . $fieldName . ' raw');
    exit;
  }
  if (preg_match('/%0[adAD]|%00/', $value)) {
    error_log('[plapendual debug] step=SECURITY msg=header_injection_detected field=' . $fieldName . ' type=url-encoded');
    error_log('[plapendual] ヘッダーインジェクション試行検知 field=' . $fieldName . ' url-encoded');
    exit;
  }
}

/**
 * メール本文用サニタイズ（制御文字のみ除去）。
 */
function sanitizeForMailBody(string $s): string
{
  return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);
}

/**
 * テンプレートファイルからメールの件名と本文を生成する。
 */
function buildMailFromTemplate(string $templatePath, array $vars, string $defaultSubject): array
{
  $raw = file_get_contents($templatePath);
  if ($raw === false) {
    error_log('[plapendual debug] step=TEMPLATE msg=read_failed path=' . addslashes($templatePath));
    error_log('[plapendual] テンプレート読み込み失敗: ' . $templatePath);
    return ['subject' => $defaultSubject, 'body' => '（テンプレート読み込み失敗）'];
  }

  error_log('[plapendual debug] step=TEMPLATE msg=read_ok path=' . addslashes($templatePath) . ' size=' . strlen($raw));

  // BOM除去（UTF-8 BOM: 0xEF 0xBB 0xBF）
  if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
    $raw = substr($raw, 3);
    error_log('[plapendual debug] step=TEMPLATE msg=bom_stripped path=' . addslashes($templatePath));
  }

  // プレースホルダー置換
  foreach ($vars as $key => $value) {
    $raw = str_replace('{{' . $key . '}}', $value, $raw);
  }

  // Subject行の抽出
  $subject = $defaultSubject;
  $body    = $raw;

  if (preg_match('/\ASubject:\s*(.+)\r?\n\r?\n(.*)\z/su', $raw, $m)) {
    $extracted = trim($m[1]);
    $subject   = ($extracted !== '') ? $extracted : $defaultSubject;
    $body      = $m[2];
  }

  return ['subject' => $subject, 'body' => $body];
}

/**
 * mb_send_mail でメールを送信する。
 */
function sendMail(
  string $to,
  string $toName,
  string $from,
  string $fromDisplayName,
  string $subject,
  string $body,
  array $bcc = [],
  string $replyTo = ''
): bool {
  // 全ヘッダー構成フィールドのインジェクション検査
  assertNoHeaderInjection($to, 'to');
  assertNoHeaderInjection($toName, 'toName');
  assertNoHeaderInjection($from, 'from');
  assertNoHeaderInjection($fromDisplayName, 'fromName');
  assertNoHeaderInjection($subject, 'subject');
  if ($replyTo !== '') {
    $replyToForCheck = preg_replace('/\r\n[\t ]/', '', $replyTo);
    assertNoHeaderInjection($replyToForCheck, 'replyTo');
  }
  foreach ($bcc as $i => $b) {
    assertNoHeaderInjection($b, "bcc[{$i}]");
  }

  $encodedSubject  = mb_encode_mimeheader($subject, 'UTF-8', 'B');
  $encodedToName   = mb_encode_mimeheader($toName, 'UTF-8', 'B');
  $encodedFromName = mb_encode_mimeheader($fromDisplayName, 'UTF-8', 'B');

  $headers  = 'From: ' . $encodedFromName . ' <' . $from . '>' . "\r\n";
  $headers .= 'Return-Path: ' . $from . "\r\n";
  $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
  $headers .= 'Content-Transfer-Encoding: base64' . "\r\n";

  if (!empty($replyTo)) {
    $headers .= 'Reply-To: ' . $replyTo . "\r\n";
  }

  if (!empty($bcc)) {
    $headers .= 'Bcc: ' . implode(', ', $bcc) . "\r\n";
  }

  $encodedBody = chunk_split(base64_encode($body));

  error_log('[plapendual debug] step=SEND_MAIL msg=mb_send_mail_calling to_prefix=' . (strlen($to) > 3 ? substr($to, 0, 3) . '***' : '***'));

  $result = mb_send_mail(
    $encodedToName . ' <' . $to . '>',
    $encodedSubject,
    $encodedBody,
    $headers
  );

  error_log('[plapendual debug] step=SEND_MAIL msg=mb_send_mail_returned result=' . ($result ? 'true' : 'false'));

  return $result;
}

// -----------------------------------------------------------------------
// メイン処理
// -----------------------------------------------------------------------

error_log('[plapendual debug] step=MAIN_BRANCH msg=checking_actionFlag actionFlag=' . addslashes($actionFlag));

if ($actionFlag === 'send') {
  error_log('[plapendual debug] step=MAIN_BRANCH msg=entered_send_block');

  $actionFlag = 'comp';

  $aMailto = $aMailtoReserve;
  $aBccTo  = (!empty($aBccToContact) && is_array($aBccToContact)) ? $aBccToContact : [];

  $entry_time = date('Y/m/d H:i:s');
  $entry_ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

  // メールテンプレート用変数
  $vars = [
    'name'       => sanitizeForMailBody($reg_name),
    'salon_name' => sanitizeForMailBody($reg_salon_name),
    'tel'        => sanitizeForMailBody($reg_tel),
    'address'    => sanitizeForMailBody($reg_address),
    'email'      => sanitizeForMailBody($reg_email),
    'comment'    => sanitizeForMailBody($reg_comment),
    'datetime'   => $entry_time,
    'ip'         => $entry_ip,
    'site_name'  => 'バクチスコーポレーション株式会社',
    'site_url'   => 'https://company.bakuchis.com/plapendual/',
  ];

  $templateDir = dirname(__DIR__) . '/templates/';

  // -----------------------------------------------------------------------
  // [DEBUG-TEMPLATE] テンプレートディレクトリとファイルの存在確認
  // -----------------------------------------------------------------------
  error_log('[plapendual debug] step=TEMPLATE_CHECK msg=templateDir=' . addslashes($templateDir));
  error_log('[plapendual debug] step=TEMPLATE_CHECK msg=admin_mail_txt=' . (file_exists($templateDir . 'admin-mail.txt') ? 'exists' : 'NOT_FOUND'));
  error_log('[plapendual debug] step=TEMPLATE_CHECK msg=autoreply_mail_txt=' . (file_exists($templateDir . 'autoreply-mail.txt') ? 'exists' : 'NOT_FOUND'));

  // -----------------------------------------------------------------------
  // GAS リクエスト
  // -----------------------------------------------------------------------
  $gas_url = 'https://script.google.com/macros/s/AKfycbxoWdqJ94_T6t-BQ2RN597WMtLmDPaFiShERnzEEw0D22UPHYp0f0s73zuhEo9cltZu/exec';

  $gas_data = [
    'cp'         => mb_convert_encoding('フォーム送信', 'UTF-8'),
    'salon_name' => mb_convert_encoding($reg_salon_name, 'UTF-8'),
    'name'       => mb_convert_encoding($reg_name, 'UTF-8'),
    'tel'        => $reg_tel,
    'address'    => mb_convert_encoding($reg_address, 'UTF-8'),
    'mail'       => $reg_email,
    'comment'    => mb_convert_encoding($reg_comment, 'UTF-8'),
    'date'       => $entry_time,
    'num'        => 0,
  ];

  $gas_context = [
    'http' => [
      'method'  => 'POST',
      'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
      'content' => http_build_query($gas_data),
      'timeout' => 10,
    ]
  ];

  error_log('[plapendual debug] step=GAS_BEFORE msg=about_to_call_gas gas_url=' . $gas_url);

  try {
    $response_json = @file_get_contents($gas_url, false, stream_context_create($gas_context));
    if ($response_json === false) {
      $err = error_get_last();
      error_log('[plapendual debug] step=GAS_AFTER msg=request_failed reason=' . addslashes($err['message'] ?? 'unknown'));
      error_log('[plapendual] GAS request failed: ' . ($err['message'] ?? 'unknown'));
    } else {
      error_log('[plapendual debug] step=GAS_AFTER msg=request_ok response_len=' . strlen($response_json));
      $response_data = json_decode($response_json, true);
    }
  } catch (Throwable $e) {
    error_log('[plapendual debug] step=GAS_AFTER msg=exception exception=' . addslashes($e->getMessage()));
    error_log('[plapendual] GAS request failed: ' . $e->getMessage());
  }

  // -----------------------------------------------------------------------
  // スパム検知
  // -----------------------------------------------------------------------
  $allow_send_email = 1;

  try {
    // 確認画面を経由しているか
    if (empty($_SESSION['ses_from_step2'])) {
      throw new Exception('Step confirm must be display');
    }

    // g パラメータチェック
    $gtime_step2 = $_GET['g'] ?? '';

    if ($gtime_step2 === '') {
      throw new Exception('Miss g request');
    } else {
      $cur_time = time();

      if (strlen((string)$cur_time) !== strlen((string)$gtime_step2)) {
        throw new Exception("G request's not a time");
      } elseif (
        isset($_SESSION['ses_gtime_step2']) &&
        $_SESSION['ses_gtime_step2'] == $gtime_step2 &&
        ($cur_time - (int)$gtime_step2 < 1)
      ) {
        throw new Exception('Checking confirm too fast');
      }
    }

    // 必須チェック
    if (empty($reg_name) || empty($reg_email)) {
      throw new Exception('Miss reg_name or reg_email');
    }

    // メール形式チェック
    if (!filter_var($reg_email, FILTER_VALIDATE_EMAIL)) {
      throw new Exception('Email format is invalid');
    }

    // ヘッダーインジェクション対策
    if (preg_match('/[\r\n\0]/', $reg_email) || preg_match('/%0[adAD]|%00/', $reg_email)) {
      throw new Exception("Email's not correct");
    }

    // ハニーポット
    if ($reg_url !== '') {
      throw new Exception('Url request must be empty');
    }

    // 二重送信対策
    if (!isset($_SESSION['ses_step3'])) {
      $_SESSION['ses_step3'] = false;
    }

    if ($_SESSION['ses_step3']) {
      throw new Exception('Session step 3 must be destroy');
    }

  } catch (Exception $e) {
    $allow_send_email = 0;
    error_log('[plapendual debug] step=SPAM_CHECK msg=blocked reason=' . addslashes($e->getMessage()));
    error_log('[plapendual] Mail validation blocked: ' . $e->getMessage());
  }

  // -----------------------------------------------------------------------
  // [DEBUG] スパム検知後の allow_send_email の値を記録
  // -----------------------------------------------------------------------
  error_log('[plapendual debug] step=SPAM_CHECK_RESULT msg=allow_send_email=' . $allow_send_email);

  // -----------------------------------------------------------------------
  // メール送信（mb_send_mail）
  // -----------------------------------------------------------------------
  $adminSent     = false;
  $autoReplySent = false;

  if ($allow_send_email) {
    error_log('[plapendual debug] step=MAIL_SEND msg=allow_send_email_is_1_entering_mail_block');

    try {
      // 1通目: ユーザー宛（自動返信）
      error_log('[plapendual debug] step=AUTOREPLY_BEFORE msg=building_autoreply_template');
      $autoreplyMail = buildMailFromTemplate(
        $templateDir . 'autoreply-mail.txt',
        $vars,
        '【バクチスコーポレーション株式会社】お問い合わせ・ご予約ありがとうございました。'
      );
      error_log('[plapendual debug] step=AUTOREPLY_BEFORE msg=autoreply_subject=' . addslashes($autoreplyMail['subject']));

      error_log('[plapendual debug] step=AUTOREPLY_BEFORE msg=calling_sendMail_for_autoreply');
      $autoReplySent = sendMail(
        $reg_email,
        $reg_name,
        $fromReserve,
        $fromName,
        $autoreplyMail['subject'],
        $autoreplyMail['body'],
        [],           // BCCなし
        $fromReserve  // Reply-To
      );

      error_log('[plapendual debug] step=AUTOREPLY_AFTER msg=autoReplySent=' . ($autoReplySent ? 'true' : 'false'));

      if (!$autoReplySent) {
        error_log('[plapendual] User autoreply mail send failed');
      }

      // 2通目: 管理者宛
      error_log('[plapendual debug] step=ADMIN_MAIL_BEFORE msg=building_admin_template');
      $adminMail = buildMailFromTemplate(
        $templateDir . 'admin-mail.txt',
        $vars,
        '[BAKUCHIS] LPプラペンデュアル お問い合わせ'
      );
      error_log('[plapendual debug] step=ADMIN_MAIL_BEFORE msg=admin_subject=' . addslashes($adminMail['subject']));

      $adminTo  = !empty($aMailto[0]) ? $aMailto[0] : $fromReserve;
      $adminBcc = array_merge(
        array_slice($aMailto, 1),
        $aBccTo
      );
      $adminReplyTo = (!empty($reg_email) && !empty($reg_name))
        ? mb_encode_mimeheader($reg_name, 'UTF-8', 'B') . ' <' . $reg_email . '>'
        : '';

      // -----------------------------------------------------------------------
      // [DEBUG] 管理者宛メール送信前の主要変数を記録
      // -----------------------------------------------------------------------
      error_log('[plapendual debug] step=ADMIN_MAIL_BEFORE msg=adminTo_prefix=' . (strlen($adminTo) > 3 ? substr($adminTo, 0, 3) . '***' : '***'));
      error_log('[plapendual debug] step=ADMIN_MAIL_BEFORE msg=adminBcc_count=' . count($adminBcc));
      // adminReplyTo は folding 有無を検査するため addslashes でエスケープして出力
      error_log('[plapendual debug] step=ADMIN_MAIL_BEFORE msg=adminReplyTo_escaped=' . addslashes($adminReplyTo));

      error_log('[plapendual debug] step=ADMIN_MAIL_BEFORE msg=calling_sendMail_for_admin');
      $adminSent = sendMail(
        $adminTo,
        'BAKUCHIS Corporation',
        $fromReserve,
        $fromName,
        $adminMail['subject'],
        $adminMail['body'],
        $adminBcc,
        $adminReplyTo
      );

      error_log('[plapendual debug] step=ADMIN_MAIL_AFTER msg=adminSent=' . ($adminSent ? 'true' : 'false'));

      if (!$adminSent) {
        error_log('[plapendual] Admin mail send failed');
      }

    } catch (Throwable $e) {
      error_log('[plapendual debug] step=MAIL_EXCEPTION msg=caught exception=' . addslashes($e->getMessage()) . ' file=' . addslashes($e->getFile()) . ' line=' . $e->getLine());
      error_log('[plapendual] Mail send exception: ' . $e->getMessage());
    }
  } else {
    error_log('[plapendual debug] step=MAIL_SEND msg=skipped_because_allow_send_email_is_0');
  }

  // -----------------------------------------------------------------------
  // 送信結果に応じたフロー分岐
  // -----------------------------------------------------------------------
  error_log('[plapendual debug] step=FLOW_BRANCH msg=allow_send_email=' . $allow_send_email . ' adminSent=' . ($adminSent ? '1' : '0') . ' autoReplySent=' . ($autoReplySent ? '1' : '0'));

  if ($allow_send_email && $adminSent) {
    error_log('[plapendual debug] step=FLOW_BRANCH msg=entered_success_branch');
    $_SESSION['ses_step3'] = true;
    $_SESSION['statusFlag'] = 1;
    unset($_SESSION['retry_count']);

  } elseif ($allow_send_email && !$adminSent) {
    error_log('[plapendual debug] step=FLOW_BRANCH msg=entered_admin_fail_branch');
    $_SESSION['retry_count'] = ($_SESSION['retry_count'] ?? 0) + 1;

    error_log('[plapendual debug] step=FLOW_BRANCH msg=retry_count=' . $_SESSION['retry_count']);

    if ($_SESSION['retry_count'] >= 3) {
      error_log('[plapendual debug] step=FLOW_BRANCH msg=retry_limit_exceeded_redirecting_to_confirm');
      error_log('[plapendual] Mail retry limit exceeded');
      $_SESSION['send_error'] = '送信に失敗しました。お手数ですが info@bakuchis.com までメールにてお問い合わせください。';
      $_SESSION['ses_step3'] = true;
      unset($_SESSION['retry_count']);
      unset($_SESSION['retry_data']);
      $redirect_url = APP_URL . 'confirm/';
      error_log('[plapendual debug] step=REDIRECT msg=rate_limit_redirect_to=' . addslashes($redirect_url));
      header('location: ' . $redirect_url);
      exit;
    }

    $_SESSION['send_error'] = '送信に失敗しました。お手数ですが再度お試しください。';
    $_SESSION['retry_data'] = [
      'salon_name' => $reg_salon_name,
      'name'       => $reg_name,
      'tel'        => $reg_tel,
      'address'    => $reg_address,
      'email'      => $reg_email,
      'comment'    => $reg_comment,
    ];
    $redirect_url = APP_URL . 'confirm/';
    error_log('[plapendual debug] step=REDIRECT msg=retry_redirect_to=' . addslashes($redirect_url));
    header('location: ' . $redirect_url);
    exit;

  } else {
    // $allow_send_email === 0（バリデーション弾き）
    error_log('[plapendual debug] step=FLOW_BRANCH msg=entered_validation_blocked_branch no_statusFlag_set');
  }
}

// -----------------------------------------------------------------------
// if ($actionFlag === 'send') ブロック外の処理
// -----------------------------------------------------------------------
error_log('[plapendual debug] step=POST_SEND_BLOCK msg=statusFlag=' . (isset($_SESSION['statusFlag']) ? (int)$_SESSION['statusFlag'] : 'not_set'));

if (!empty($_SESSION['statusFlag'])) {
  unset($_SESSION['statusFlag']);
}

unset($_SESSION['ses_gtime_step2']);
unset($_SESSION['ses_from_step2']);
unset($_SESSION['ses_step3']);

// -----------------------------------------------------------------------
// [DEBUG] 最終リダイレクト直前
// -----------------------------------------------------------------------
$final_redirect = APP_URL . 'complete/thanks.php?reserve_date=' . date('Ymd_Hi');
error_log('[plapendual debug] step=FINAL_REDIRECT msg=about_to_redirect url=' . addslashes($final_redirect));

header('location: ' . $final_redirect);
exit;
