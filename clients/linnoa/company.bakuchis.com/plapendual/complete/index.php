<?php
// 出力バッファリングを即座に開始（BOM / 先頭空白対策）
if (ob_get_level() === 0) {
  ob_start();
}

// -----------------------------------------------------------------------
// complete/index.php
// 確認画面（confirm/index.php）をインクルードし、セッション変数・フォーム値を取得してから
// メール送信・GAS連携・リダイレクトを行う。
//
// 送信方式: mail()（サーバーのsendmail経由、自前エンコード方式）
//   ※ From アドレスはサーバードメインと一致するアドレスを使用すること。
//     現在の From（info@company.bakuchis.com）がさくらサーバーの SPF に含まれているか
//     確認し、不一致の場合はサーバードメインと一致する From への変更を検討してください。
// -----------------------------------------------------------------------

include_once(dirname(__DIR__) . '/confirm/index.php');

// mb_language('Japanese') は mb_send_mail の内部変換(ISO-2022-JP化)を引き起こすため削除。
// mb_internal_encoding は PHP ini で UTF-8 設定済みのサーバーでは不要。
// 明示的に設定したい場合は ini_set('default_charset', 'UTF-8') を使うこと。
mb_internal_encoding('UTF-8');

// -----------------------------------------------------------------------
// 安全な初期化（confirm/index.php からの変数が未定義の場合のフォールバック）
// -----------------------------------------------------------------------
$aMailtoReserve = $aMailtoReserve ?? [];
$aBccToContact  = $aBccToContact ?? [];
$fromReserve    = $fromReserve ?? '';
$fromName       = $fromName ?? '';
$Reply          = $Reply ?? '';
$actionFlag     = $actionFlag ?? '';

$reg_salon_name = $reg_salon_name ?? '';
$reg_name       = $reg_name ?? '';
$reg_tel        = $reg_tel ?? '';
$reg_address    = $reg_address ?? '';
$reg_email      = $reg_email ?? '';
$reg_comment    = $reg_comment ?? '';
$reg_url        = $reg_url ?? '';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// -----------------------------------------------------------------------
// ユーティリティ関数
// -----------------------------------------------------------------------

/**
 * ヘッダーインジェクション攻撃を検出する。
 * 改行・NULL文字・URLエンコード済み改行（%0A / %0D）を検査する。
 * 攻撃が検出された場合はエラーログを記録して処理を打ち切る。
 */
function assertNoHeaderInjection(string $value, string $fieldName): void
{
  // 生の改行・NULL文字チェック
  if (preg_match('/[\r\n\0]/', $value)) {
    error_log('[plapendual] ヘッダーインジェクション試行検知 field=' . $fieldName . ' raw');
    exit;
  }
  // URLエンコード済み改行チェック（%0A / %0D / %00）
  if (preg_match('/%0[adAD]|%00/', $value)) {
    error_log('[plapendual] ヘッダーインジェクション試行検知 field=' . $fieldName . ' url-encoded');
    exit;
  }
}

/**
 * メール本文用サニタイズ（制御文字のみ除去）。
 * htmlspecialchars は画面出力時のみ使用し、メール本文には適用しない。
 */
function sanitizeForMailBody(string $s): string
{
  return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);
}

/**
 * テンプレートファイルからメールの件名と本文を生成する。
 *
 * テンプレートの1行目が "Subject: ..." 形式であれば件名として使用し、
 * 空行の後を本文とする。件名行がない場合はデフォルト件名を使用する。
 *
 * @param  string               $templatePath
 * @param  array<string,string> $vars
 * @param  string               $defaultSubject
 * @return array{subject: string, body: string}
 */
function buildMailFromTemplate(string $templatePath, array $vars, string $defaultSubject): array
{
  $raw = file_get_contents($templatePath);
  if ($raw === false) {
    error_log('[plapendual] テンプレート読み込み失敗: ' . $templatePath);
    return ['subject' => $defaultSubject, 'body' => '（テンプレート読み込み失敗）'];
  }

  // BOM除去（UTF-8 BOM: 0xEF 0xBB 0xBF）
  if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
    $raw = substr($raw, 3);
  }

  // プレースホルダー置換
  foreach ($vars as $key => $value) {
    $raw = str_replace('{{' . $key . '}}', $value, $raw);
  }

  // Subject行の抽出（1行目が "Subject: 〜" で始まり、空行の後が本文）
  $subject = $defaultSubject;
  $body    = $raw;

  if (preg_match('/\ASubject:\s*([^\r\n]+)\r?\n\r?\n(.*)\z/us', $raw, $m)) {
    $extracted = trim($m[1]);
    $subject   = ($extracted !== '') ? $extracted : $defaultSubject;
    $body      = $m[2];
  }

  return ['subject' => $subject, 'body' => $body];
}

/**
 * mail() でメールを送信する。
 *
 * $to / $toName にヘッダーインジェクション攻撃が試みられた場合は即時中断する。
 *
 * @param string   $to      宛先メールアドレス
 * @param string   $toName  宛先表示名
 * @param string   $from    送信元メールアドレス
 * @param string   $fromDisplayName 送信元表示名
 * @param string   $subject 件名
 * @param string   $body    本文
 * @param string[] $bcc     BCCアドレスの配列（空可）
 * @param string   $replyTo Reply-Toアドレス（空可）
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
    // mb_encode_mimeheader() は長い名前の Base64 結果を RFC 2047 に従い
    // "\r\n\t" または "\r\n " で折り返す。これは正規のヘッダ折り返しで
    // インジェクションではないため、検査前に除去する。
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

  // mb_send_mail ではなく mail() を使う理由:
  // mb_language('Japanese') を設定している環境で mb_send_mail を呼ぶと、
  // 本文を ISO-2022-JP に変換しようとして、既に base64 エンコード済みの本文が
  // 二重変換されて文字化けする。
  // 本文・件名・ヘッダーはすべて自前でエンコード済みのため、
  // mail() で直接送信するのが最も安全。
  $result = mail(
    $encodedToName . ' <' . $to . '>',
    $encodedSubject,
    $encodedBody,
    $headers
  );

  return $result;
}

// -----------------------------------------------------------------------
// メイン処理
// -----------------------------------------------------------------------

if ($actionFlag === 'send') {
  $actionFlag = 'comp';

  $aMailto = $aMailtoReserve;
  $aBccTo  = (!empty($aBccToContact) && is_array($aBccToContact)) ? $aBccToContact : [];

  $entry_time = date('Y/m/d H:i:s');
  $entry_ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

  // メールテンプレート用変数（メール本文にはサニタイズのみ適用）
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

  /**
   * Google Apps Script 連携
   * 失敗してもメール送信自体は止めない
   */
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

  try {
    $response_json = @file_get_contents($gas_url, false, stream_context_create($gas_context));
    if ($response_json === false) {
      $err = error_get_last();
      error_log('[plapendual] GAS request failed: ' . ($err['message'] ?? 'unknown'));
    } else {
      $response_data = json_decode($response_json, true);
    }
  } catch (Throwable $e) {
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

      if (!ctype_digit((string)$gtime_step2) || strlen((string)$cur_time) !== strlen((string)$gtime_step2)) {
        throw new Exception("G request's not a time");
      } elseif (
        isset($_SESSION['ses_gtime_step2']) &&
        $_SESSION['ses_gtime_step2'] == $gtime_step2 &&
        ($cur_time - (int)$gtime_step2 < 1)
      ) {
        throw new Exception('Checking confirm too fast');
      }
    }

    // 必須チェック（comment は任意のため除外）
    if (empty($reg_salon_name) || empty($reg_name) || empty($reg_tel) || empty($reg_address) || empty($reg_email)) {
      throw new Exception('Miss required field');
    }

    // 最大文字数チェック（DoS / メール本文肥大化対策）
    $maxLengths = [
      'salon_name' => 100,
      'name'       => 100,
      'tel'        => 20,
      'address'    => 200,
      'email'      => 254,
      'comment'    => 2000,
    ];
    foreach ($maxLengths as $field => $max) {
      $varName = 'reg_' . $field;
      if (mb_strlen($$varName, 'UTF-8') > $max) {
        throw new Exception('Field ' . $field . ' exceeds max length');
      }
    }

    // 電話番号形式チェック（半角数字・ハイフンのみ）
    if (!preg_match('/^[0-9\-]+$/', $reg_tel)) {
      throw new Exception('Tel format invalid: 電話番号の形式が正しくありません。');
    }
    // 先頭3桁で携帯・IP電話か固定電話かを判定し、桁数を検証する
    $tel_digits = str_replace('-', '', $reg_tel);
    if (preg_match('/\A(090|080|070|050|0800)/', $tel_digits)) {
      // 携帯・IP電話・フリーダイヤル(0800): 11桁必須
      if (!preg_match('/\A0\d{10}\z/', $tel_digits)) {
        throw new Exception('Tel format invalid: 携帯電話・フリーダイヤル(0800)（090, 080, 070, 050, 0800）は11桁で入力してください。');
      }
    } else {
      // 固定電話: 10桁必須
      if (!preg_match('/\A0\d{9}\z/', $tel_digits)) {
        throw new Exception('Tel format invalid: 固定電話番号は10桁で入力してください。');
      }
    }

    // メール形式チェック
    if (!filter_var($reg_email, FILTER_VALIDATE_EMAIL)) {
      throw new Exception('Email format is invalid');
    }

    // 全フィールドのヘッダーインジェクション検査（早期検知のためスパム検知段階で実施）
    $injectionCheckFields = [
      'salon_name' => $reg_salon_name,
      'name'       => $reg_name,
      'tel'        => $reg_tel,
      'address'    => $reg_address,
      'email'      => $reg_email,
      'comment'    => $reg_comment,
    ];
    foreach ($injectionCheckFields as $field => $value) {
      if (preg_match('/[\r\n\0]/', $value) || preg_match('/%0[adAD]|%00/', $value)) {
        throw new Exception('Header injection attempt: ' . $field);
      }
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
    error_log('[plapendual] Mail validation blocked: ' . $e->getMessage());
  }

  // -----------------------------------------------------------------------
  // メール送信（mail() 直叩き、自前エンコード方式）
  // -----------------------------------------------------------------------
  // 送信結果フラグ（初期値: 失敗）
  $adminSent     = false;
  $autoReplySent = false;

  if ($allow_send_email) {
    try {
      // 1通目: ユーザー宛（自動返信）
      $autoreplyMail = buildMailFromTemplate(
        $templateDir . 'autoreply-mail.txt',
        $vars,
        '【バクチスコーポレーション株式会社】お問い合わせ・ご予約ありがとうございました。'
      );

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

      if (!$autoReplySent) {
        error_log('[plapendual] User autoreply mail send failed');
      }

      // 2通目: 管理者宛
      $adminMail = buildMailFromTemplate(
        $templateDir . 'admin-mail.txt',
        $vars,
        '[BAKUCHIS] LPプラペンデュアル お問い合わせ'
      );

      // 管理者アドレスへの送信は1件目のみ To に設定し、追加分はBCCに混ぜる
      $adminTo      = !empty($aMailto[0]) ? $aMailto[0] : $fromReserve;
      $adminBcc     = array_merge(
        array_slice($aMailto, 1),  // 管理者2件目以降
        $aBccTo                    // BCC指定アドレス
      );
      // Reply-To にユーザーのメールアドレスを設定
      $adminReplyTo = (!empty($reg_email) && !empty($reg_name))
        ? mb_encode_mimeheader($reg_name, 'UTF-8', 'B') . ' <' . $reg_email . '>'
        : '';

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

      if (!$adminSent) {
        error_log('[plapendual] Admin mail send failed');
      }

    } catch (Throwable $e) {
      error_log('[plapendual] Mail send exception: ' . $e->getMessage());
    }
  }

  // -----------------------------------------------------------------------
  // 送信結果に応じたフロー分岐
  // -----------------------------------------------------------------------

  if ($allow_send_email && $adminSent) {
    // 管理者宛成功 → thanks.php へ遷移（二重送信ガード用フラグを立てる）
    // ユーザー宛のみ失敗した場合も管理者側で手動追跡できるため遷移は継続する
    $_SESSION['ses_step3'] = true;
    $_SESSION['statusFlag'] = 1;
    // リトライカウンタのクリア（セッション汚染防止）
    unset($_SESSION['retry_count']);
  } elseif ($allow_send_email && !$adminSent) {
    // 管理者宛送信失敗 → リトライカウンタをインクリメント
    $_SESSION['retry_count'] = ($_SESSION['retry_count'] ?? 0) + 1;

    if ($_SESSION['retry_count'] >= 3) {
      // 3回上限到達 → 再送不可。代替連絡先（メール）を案内して確認画面へ
      error_log('[plapendual] Mail retry limit exceeded');
      $_SESSION['send_error'] = '送信に失敗しました。お手数ですが info@bakuchis.com までメールにてお問い合わせください。';
      $_SESSION['ses_step3'] = true;
      // セッション汚染防止: retry_count / retry_data をクリア
      unset($_SESSION['retry_count']);
      unset($_SESSION['retry_data']);
      $redirectUrl = 'https://company.bakuchis.com/plapendual/confirm/';
      header('location: ' . $redirectUrl);
      exit;
    }

    // 3回未満: 再送フロー（入力値をセッションに退避して確認画面へ戻す）
    $_SESSION['send_error'] = '送信に失敗しました。お手数ですが再度お試しください。';
    // 確認画面再表示のために入力値をセッションに退避する（POST値はリダイレクト後消える）
    $_SESSION['retry_data'] = [
      'salon_name' => $reg_salon_name,
      'name'       => $reg_name,
      'tel'        => $reg_tel,
      'address'    => $reg_address,
      'email'      => $reg_email,
      'comment'    => $reg_comment,
    ];
    // ses_from_step2 / ses_gtime_step2 はここでは消さずに確認画面で引き続き使えるようにする
    $redirectUrl = 'https://company.bakuchis.com/plapendual/confirm/';
    header('location: ' . $redirectUrl);
    exit;
  }
  // $allow_send_email === 0（バリデーション弾き）の場合は statusFlag なしで後続へ
}

if (!empty($_SESSION['statusFlag'])) {
  unset($_SESSION['statusFlag']);
}

unset($_SESSION['ses_gtime_step2']);
unset($_SESSION['ses_from_step2']);
unset($_SESSION['ses_step3']);

$finalUrl = 'https://company.bakuchis.com/plapendual/complete/thanks.php?reserve_date=' . date('Ymd_Hi');
header('location: ' . $finalUrl);
exit;
