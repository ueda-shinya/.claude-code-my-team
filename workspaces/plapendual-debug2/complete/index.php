<?php
/**
 * これはデバッグ用の一時ファイルです（画面直接出力版）。
 * ログ設定なしでも画面で結果を確認できます。
 *
 * 使用手順:
 *   1. 本ファイルを本番の /plapendual/complete/index.debug2.php にアップロード
 *   2. 確認画面で送信する際、form action を /complete/index.debug2.php?g=... に書き換え
 *      （一時的に index.php をリネームする方法でも可）
 *   3. 送信実行
 *   4. ブラウザ画面に STEP ログが並んで表示される
 *   5. 画面の表示内容をそのままコピー or スクリーンショットでアスカに共有
 *   6. 確認後、本ファイルを本番から削除
 *
 * 注意: メール送信処理はそのまま実行されます（ただし admin 宛は成功しても画面は遷移せず表示継続）。
 */

// -----------------------------------------------------------------------
// 出力バッファリング解除（confirm/index.php 内の ob_start() 影響を排除）
// -----------------------------------------------------------------------
while (ob_get_level() > 0) {
  ob_end_clean();
}

// -----------------------------------------------------------------------
// [DEBUG-INIT] エラー表示・ロギングを最大化（デバッグ版のみ）
// -----------------------------------------------------------------------
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// HTML 出力開始
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Debug Output</title>' .
  '<style>pre { background:#f4f4f4; border:1px solid #ccc; padding:6px; margin:4px 0; font-size:13px; white-space:pre-wrap; word-break:break-all; }</style>' .
  '</head><body>' . "\n";
echo '<h2>plapendual debug2 – 画面直接出力版</h2>' . "\n";

echo '<pre>[STEP-INIT] msg=debug_file_started</pre>' . "\n";

// -----------------------------------------------------------------------
// [DEBUG-ENV] PHP 関数・設定の存在確認
// -----------------------------------------------------------------------
echo '<pre>[STEP-ENV] mb_send_mail=' . (function_exists('mb_send_mail') ? 'exists' : 'NOT_FOUND') . '</pre>' . "\n";
echo '<pre>[STEP-ENV] mb_encode_mimeheader=' . (function_exists('mb_encode_mimeheader') ? 'exists' : 'NOT_FOUND') . '</pre>' . "\n";
echo '<pre>[STEP-ENV] sendmail_path=' . htmlspecialchars(addslashes(ini_get('sendmail_path')), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
echo '<pre>[STEP-ENV] allow_url_fopen=' . ini_get('allow_url_fopen') . '</pre>' . "\n";
echo '<pre>[STEP-ENV] php_version=' . PHP_VERSION . '</pre>' . "\n";

// -----------------------------------------------------------------------
// confirm/index.php のインクルード
// -----------------------------------------------------------------------
echo '<pre>[STEP-BEFORE_INCLUDE] msg=about_to_include_confirm</pre>' . "\n";

include_once(dirname(__DIR__) . '/confirm/index.php');

echo '<pre>[STEP-AFTER_INCLUDE] msg=confirm_included_ok</pre>' . "\n";

// -----------------------------------------------------------------------
// mb_* の言語・エンコーディング設定
// -----------------------------------------------------------------------
mb_language('Japanese');
mb_internal_encoding('UTF-8');

echo '<pre>[STEP-MB_SETUP] msg=mb_language_and_encoding_set</pre>' . "\n";

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
// [DEBUG-VARS] include 直後の主要変数を出力
// -----------------------------------------------------------------------
echo '<pre>[STEP-VARS] actionFlag=' . htmlspecialchars(addslashes($actionFlag), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
echo '<pre>[STEP-VARS] fromReserve=' . htmlspecialchars(addslashes($fromReserve), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
echo '<pre>[STEP-VARS] aMailtoReserve=' . htmlspecialchars(addslashes(implode(',', $aMailtoReserve)), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
echo '<pre>[STEP-VARS] aBccToContact=' . htmlspecialchars(addslashes(implode(',', $aBccToContact)), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
echo '<pre>[STEP-VARS] reg_email_prefix=' . htmlspecialchars((strlen($reg_email) > 3 ? substr($reg_email, 0, 3) . '***' : '***'), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
echo '<pre>[STEP-VARS] reg_name_len=' . mb_strlen($reg_name) . '</pre>' . "\n";
echo '<pre>[STEP-VARS] APP_URL=' . htmlspecialchars((defined('APP_URL') ? addslashes(APP_URL) : 'NOT_DEFINED'), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";

// -----------------------------------------------------------------------
// セッション確保
// -----------------------------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

echo '<pre>[STEP-SESSION] ses_from_step2=' . (isset($_SESSION['ses_from_step2']) ? (int)$_SESSION['ses_from_step2'] : 'not_set') . '</pre>' . "\n";
echo '<pre>[STEP-SESSION] ses_step3=' . (isset($_SESSION['ses_step3']) ? htmlspecialchars(var_export($_SESSION['ses_step3'], true), ENT_QUOTES, 'UTF-8') : 'not_set') . '</pre>' . "\n";
echo '<pre>[STEP-SESSION] ses_gtime_step2=' . htmlspecialchars($_SESSION['ses_gtime_step2'] ?? 'not_set', ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
echo '<pre>[STEP-SESSION] GET_g=' . htmlspecialchars(addslashes($_GET['g'] ?? ''), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";

// -----------------------------------------------------------------------
// ユーティリティ関数（本体と同一 + exit 箇所を可視化）
// -----------------------------------------------------------------------

/**
 * ヘッダーインジェクション攻撃を検出する。
 */
function assertNoHeaderInjection(string $value, string $fieldName): void
{
  if (preg_match('/[\r\n\0]/', $value)) {
    echo '<pre>[STEP-SECURITY] msg=header_injection_detected field=' . htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') . ' type=raw → EXIT ここで処理停止</pre>' . "\n";
    echo '</body></html>';
    exit;
  }
  if (preg_match('/%0[adAD]|%00/', $value)) {
    echo '<pre>[STEP-SECURITY] msg=header_injection_detected field=' . htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') . ' type=url-encoded → EXIT ここで処理停止</pre>' . "\n";
    echo '</body></html>';
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
    echo '<pre>[STEP-TEMPLATE] msg=read_failed path=' . htmlspecialchars(addslashes($templatePath), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
    return ['subject' => $defaultSubject, 'body' => '（テンプレート読み込み失敗）'];
  }

  echo '<pre>[STEP-TEMPLATE] msg=read_ok path=' . htmlspecialchars(addslashes($templatePath), ENT_QUOTES, 'UTF-8') . ' size=' . strlen($raw) . '</pre>' . "\n";

  // BOM除去（UTF-8 BOM: 0xEF 0xBB 0xBF）
  if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
    $raw = substr($raw, 3);
    echo '<pre>[STEP-TEMPLATE] msg=bom_stripped path=' . htmlspecialchars(addslashes($templatePath), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
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

  echo '<pre>[STEP-SEND_MAIL] msg=mb_send_mail_calling to_prefix=' . htmlspecialchars((strlen($to) > 3 ? substr($to, 0, 3) . '***' : '***'), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";

  $result = mb_send_mail(
    $encodedToName . ' <' . $to . '>',
    $encodedSubject,
    $encodedBody,
    $headers
  );

  echo '<pre>[STEP-SEND_MAIL] msg=mb_send_mail_returned result=' . ($result ? 'true' : 'false') . '</pre>' . "\n";

  return $result;
}

// -----------------------------------------------------------------------
// メイン処理
// -----------------------------------------------------------------------

echo '<pre>[STEP-MAIN_BRANCH] actionFlag=' . htmlspecialchars(addslashes($actionFlag), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";

if ($actionFlag === 'send') {
  echo '<pre>[STEP-MAIN_BRANCH] msg=entered_send_block</pre>' . "\n";

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
  echo '<pre>[STEP-TEMPLATE_CHECK] templateDir=' . htmlspecialchars(addslashes($templateDir), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
  echo '<pre>[STEP-TEMPLATE_CHECK] admin_mail_txt=' . (file_exists($templateDir . 'admin-mail.txt') ? 'exists' : 'NOT_FOUND') . '</pre>' . "\n";
  echo '<pre>[STEP-TEMPLATE_CHECK] autoreply_mail_txt=' . (file_exists($templateDir . 'autoreply-mail.txt') ? 'exists' : 'NOT_FOUND') . '</pre>' . "\n";

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

  echo '<pre>[STEP-GAS_BEFORE] msg=about_to_call_gas gas_url=' . htmlspecialchars($gas_url, ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";

  try {
    $response_json = @file_get_contents($gas_url, false, stream_context_create($gas_context));
    if ($response_json === false) {
      $err = error_get_last();
      echo '<pre>[STEP-GAS_AFTER] msg=request_failed reason=' . htmlspecialchars(addslashes($err['message'] ?? 'unknown'), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
    } else {
      echo '<pre>[STEP-GAS_AFTER] msg=request_ok response_len=' . strlen($response_json) . '</pre>' . "\n";
      $response_data = json_decode($response_json, true);
    }
  } catch (Throwable $e) {
    echo '<pre>[STEP-GAS_AFTER] msg=exception exception=' . htmlspecialchars(addslashes($e->getMessage()), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
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
    echo '<pre>[STEP-SPAM_CHECK] msg=blocked reason=' . htmlspecialchars(addslashes($e->getMessage()), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
  }

  echo '<pre>[STEP-SPAM_CHECK_RESULT] allow_send_email=' . $allow_send_email . '</pre>' . "\n";

  // -----------------------------------------------------------------------
  // メール送信（mb_send_mail）
  // -----------------------------------------------------------------------
  $adminSent     = false;
  $autoReplySent = false;

  if ($allow_send_email) {
    echo '<pre>[STEP-MAIL_SEND] msg=allow_send_email_is_1_entering_mail_block</pre>' . "\n";

    try {
      // 1通目: ユーザー宛（自動返信）
      echo '<pre>[STEP-AUTOREPLY_BEFORE] msg=building_autoreply_template</pre>' . "\n";
      $autoreplyMail = buildMailFromTemplate(
        $templateDir . 'autoreply-mail.txt',
        $vars,
        '【バクチスコーポレーション株式会社】お問い合わせ・ご予約ありがとうございました。'
      );
      echo '<pre>[STEP-AUTOREPLY_BEFORE] msg=autoreply_subject=' . htmlspecialchars(addslashes($autoreplyMail['subject']), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";

      echo '<pre>[STEP-AUTOREPLY_BEFORE] msg=calling_sendMail_for_autoreply</pre>' . "\n";
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

      echo '<pre>[STEP-AUTOREPLY_AFTER] autoReplySent=' . ($autoReplySent ? 'true' : 'false') . '</pre>' . "\n";

      // 2通目: 管理者宛
      echo '<pre>[STEP-ADMIN_MAIL_BEFORE] msg=building_admin_template</pre>' . "\n";
      $adminMail = buildMailFromTemplate(
        $templateDir . 'admin-mail.txt',
        $vars,
        '[BAKUCHIS] LPプラペンデュアル お問い合わせ'
      );
      echo '<pre>[STEP-ADMIN_MAIL_BEFORE] admin_subject=' . htmlspecialchars(addslashes($adminMail['subject']), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";

      $adminTo  = !empty($aMailto[0]) ? $aMailto[0] : $fromReserve;
      $adminBcc = array_merge(
        array_slice($aMailto, 1),
        $aBccTo
      );
      $adminReplyTo = (!empty($reg_email) && !empty($reg_name))
        ? mb_encode_mimeheader($reg_name, 'UTF-8', 'B') . ' <' . $reg_email . '>'
        : '';

      echo '<pre>[STEP-ADMIN_MAIL_BEFORE] adminTo_prefix=' . htmlspecialchars((strlen($adminTo) > 3 ? substr($adminTo, 0, 3) . '***' : '***'), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";
      echo '<pre>[STEP-ADMIN_MAIL_BEFORE] adminBcc_count=' . count($adminBcc) . '</pre>' . "\n";
      // adminReplyTo は folding 有無を検査するため addslashes でエスケープして出力
      echo '<pre>[STEP-ADMIN_MAIL_BEFORE] adminReplyTo_escaped=' . htmlspecialchars(addslashes($adminReplyTo), ENT_QUOTES, 'UTF-8') . '</pre>' . "\n";

      echo '<pre>[STEP-ADMIN_MAIL_BEFORE] msg=calling_sendMail_for_admin</pre>' . "\n";
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

      echo '<pre>[STEP-ADMIN_MAIL_AFTER] adminSent=' . ($adminSent ? 'true' : 'false') . '</pre>' . "\n";

    } catch (Throwable $e) {
      echo '<pre>[STEP-MAIL_EXCEPTION] caught exception=' . htmlspecialchars(addslashes($e->getMessage()), ENT_QUOTES, 'UTF-8') . ' file=' . htmlspecialchars(addslashes($e->getFile()), ENT_QUOTES, 'UTF-8') . ' line=' . $e->getLine() . '</pre>' . "\n";
    }
  } else {
    echo '<pre>[STEP-MAIL_SEND] msg=skipped_because_allow_send_email_is_0</pre>' . "\n";
  }

  // -----------------------------------------------------------------------
  // 送信結果に応じたフロー分岐
  // -----------------------------------------------------------------------
  echo '<pre>[STEP-FLOW_BRANCH] allow_send_email=' . $allow_send_email . ' adminSent=' . ($adminSent ? '1' : '0') . ' autoReplySent=' . ($autoReplySent ? '1' : '0') . '</pre>' . "\n";

  if ($allow_send_email && $adminSent) {
    echo '<pre>[STEP-FLOW_BRANCH] msg=entered_success_branch</pre>' . "\n";
    $_SESSION['ses_step3'] = true;
    $_SESSION['statusFlag'] = 1;
    unset($_SESSION['retry_count']);

  } elseif ($allow_send_email && !$adminSent) {
    echo '<pre>[STEP-FLOW_BRANCH] msg=entered_admin_fail_branch</pre>' . "\n";
    $_SESSION['retry_count'] = ($_SESSION['retry_count'] ?? 0) + 1;

    echo '<pre>[STEP-FLOW_BRANCH] retry_count=' . $_SESSION['retry_count'] . '</pre>' . "\n";

    if ($_SESSION['retry_count'] >= 3) {
      echo '<pre>[STEP-FLOW_BRANCH] msg=retry_limit_exceeded</pre>' . "\n";
      $_SESSION['send_error'] = '送信に失敗しました。お手数ですが info@bakuchis.com までメールにてお問い合わせください。';
      $_SESSION['ses_step3'] = true;
      unset($_SESSION['retry_count']);
      unset($_SESSION['retry_data']);
      $redirect_url = (defined('APP_URL') ? APP_URL : '') . 'confirm/';
      echo '<pre>[STEP-REDIRECT] REDIRECT TO: ' . htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8') . ' （リダイレクト無効化中 – 実際には遷移しません）</pre>' . "\n";
      // header('location: ' . $redirect_url); // デバッグ版ではリダイレクトを無効化
      // exit; // デバッグ版では停止せず表示継続
    } else {
      $_SESSION['send_error'] = '送信に失敗しました。お手数ですが再度お試しください。';
      $_SESSION['retry_data'] = [
        'salon_name' => $reg_salon_name,
        'name'       => $reg_name,
        'tel'        => $reg_tel,
        'address'    => $reg_address,
        'email'      => $reg_email,
        'comment'    => $reg_comment,
      ];
      $redirect_url = (defined('APP_URL') ? APP_URL : '') . 'confirm/';
      echo '<pre>[STEP-REDIRECT] REDIRECT TO: ' . htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8') . ' （リダイレクト無効化中 – 実際には遷移しません）</pre>' . "\n";
      // header('location: ' . $redirect_url); // デバッグ版ではリダイレクトを無効化
      // exit; // デバッグ版では停止せず表示継続
    }

  } else {
    // $allow_send_email === 0（バリデーション弾き）
    echo '<pre>[STEP-FLOW_BRANCH] msg=entered_validation_blocked_branch no_statusFlag_set</pre>' . "\n";
  }
}

// -----------------------------------------------------------------------
// if ($actionFlag === 'send') ブロック外の処理
// -----------------------------------------------------------------------
echo '<pre>[STEP-POST_SEND_BLOCK] statusFlag=' . (isset($_SESSION['statusFlag']) ? (int)$_SESSION['statusFlag'] : 'not_set') . '</pre>' . "\n";

if (!empty($_SESSION['statusFlag'])) {
  unset($_SESSION['statusFlag']);
}

unset($_SESSION['ses_gtime_step2']);
unset($_SESSION['ses_from_step2']);
unset($_SESSION['ses_step3']);

// -----------------------------------------------------------------------
// 最終リダイレクト（デバッグ版では無効化して遷移先を画面に表示）
// -----------------------------------------------------------------------
$final_redirect = (defined('APP_URL') ? APP_URL : '') . 'complete/thanks.php?reserve_date=' . date('Ymd_Hi');
echo '<pre>[STEP-FINAL_REDIRECT] REDIRECT TO: ' . htmlspecialchars($final_redirect, ENT_QUOTES, 'UTF-8') . ' （リダイレクト無効化中 – 実際には遷移しません）</pre>' . "\n";
// header('location: ' . $final_redirect); // デバッグ版ではリダイレクトを無効化
// exit; // デバッグ版では停止せず表示継続

echo '<pre>[STEP-END] msg=debug2_all_steps_completed</pre>' . "\n";
echo '</body></html>';
