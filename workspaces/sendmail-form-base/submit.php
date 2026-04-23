<?php
declare(strict_types=1);

mb_language('Japanese');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/includes/session-init.php';
require_once __DIR__ . '/includes/config.php';

// -----------------------------------------------------------------------
// POST メソッド以外は早期リジェクト（M15: Allow ヘッダ付与）
// -----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(['success' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
  exit;
}

// -----------------------------------------------------------------------
// ユーティリティ
// -----------------------------------------------------------------------

/**
 * JSONレスポンスを返して終了する。
 */
function respond(bool $success, string $message): void
{
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * アクセス元IPを取得する。
 * プロキシ経由は信頼しない（ヘッダー偽装可能なため）。
 * CDN背後（Cloudflare等）で運用する場合は HTTP_CF_CONNECTING_IP に切り替えること。
 */
function getClientIp(): string
{
  return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
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
 * メールヘッダーインジェクション攻撃の試みを検出する。
 * 改行・NULL 文字が含まれる場合は即座に処理を打ち切る。
 */
function assertNoHeaderInjection(string $value, string $fieldName): void
{
  if (preg_match('/[\r\n\0]/', $value)) {
    error_log("[sendmail-form] ヘッダーインジェクション試行検知 field={$fieldName}");
    respond(false, '不正なリクエストです。');
  }
}

/**
 * ログディレクトリのパーミッションを設定する（Linux のみ有効）。
 */
function ensureLogDirectory(string $dir, int $perm): void
{
  // Windows は NTFS ACL 管理のためスキップ
  if (stripos(PHP_OS, 'WIN') === 0) {
    return;
  }
  if (!is_dir($dir)) {
    if (!@mkdir($dir, $perm, true) && !is_dir($dir)) {
      error_log("[sendmail-form] ログディレクトリ作成失敗: {$dir}");
      return;
    }
  }
  if (@chmod($dir, $perm) === false) {
    error_log("[sendmail-form] ログディレクトリ権限設定失敗: {$dir}");
  }
}

/**
 * ログをJSON Lines形式でファイルに追記する。
 * ファイル書き込み前後にパーミッションを設定する。
 *
 * @param array<string, mixed> $entry
 */
function writeLog(array $entry): void
{
  $logDir  = rtrim(LOG_DIR, '/\\'); // L18: 末尾スラッシュを防御的に除去
  $rateLimitDir = $logDir . DIRECTORY_SEPARATOR . 'rate-limit'; // L22: DIRECTORY_SEPARATOR使用

  // ディレクトリのパーミッション確保（Linux のみ）
  ensureLogDirectory($logDir, LOG_DIR_PERMISSION);
  ensureLogDirectory($rateLimitDir, LOG_DIR_PERMISSION);

  $file = $logDir . DIRECTORY_SEPARATOR . 'contact-' . date('Ym') . '.log';
  $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);

  // ログファイルのパーミッション設定（Linux のみ）
  if (stripos(PHP_OS, 'WIN') !== 0 && file_exists($file)) {
    if (@chmod($file, LOG_FILE_PERMISSION) === false) {
      error_log("[sendmail-form] ログファイル権限設定失敗: {$file}");
    }
  }
}

// -----------------------------------------------------------------------
// バリデーション
// -----------------------------------------------------------------------

/**
 * POSTパラメータを取得してトリムする。
 *
 * @return array<string, string>
 */
function collectInput(): array
{
  $fields = ['name', 'tel', 'email', 'zip', 'address', 'privacy', 'url_homepage', 'csrf_token'];
  $input  = [];
  foreach ($fields as $field) {
    $input[$field] = trim((string)($_POST[$field] ?? ''));
  }
  return $input;
}

/**
 * 入力値をバリデートし、エラーがあれば配列で返す。
 * ヘッダーインジェクション防止のため、全フィールドで改行・NULL を拒否する。
 *
 * @param  array<string, string> $input
 * @return string[]
 */
function validate(array $input): array
{
  $errors = [];

  // M10: サーバー側文字数上限
  $limits = ['name' => 100, 'tel' => 20, 'email' => 254, 'zip' => 8, 'address' => 200];
  foreach ($limits as $field => $max) {
    if (mb_strlen($input[$field], 'UTF-8') > $max) {
      $errors[] = "{$field} の入力値が長すぎます（最大{$max}文字）。";
    }
  }

  // 改行・NULL 文字チェック（全フィールド）
  foreach (['name', 'tel', 'email', 'zip', 'address'] as $field) {
    if (preg_match('/[\r\n\0]/', $input[$field])) {
      $errors[] = '入力値に無効な文字が含まれています。';
    }
  }

  if ($input['name'] === '') {
    $errors[] = 'お名前は必須です。';
  }

  // M9: 電話番号バリデーション強化（数字部分を抽出して10〜13桁チェック）
  if ($input['tel'] === '') {
    $errors[] = '電話番号は必須です。';
  } else {
    $digitsOnly = preg_replace('/[^\d]/', '', mb_convert_kana($input['tel'], 'n', 'UTF-8'));
    $len = strlen($digitsOnly);
    if ($len < 10 || $len > 13) {
      $errors[] = '電話番号は10〜13桁の数字で入力してください。';
    }
  }

  if ($input['email'] === '') {
    $errors[] = 'メールアドレスは必須です。';
  } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'メールアドレスの形式が正しくありません。';
  } elseif (preg_match('/[%\r\n\0]/', $input['email'])) {
    // FILTER_VALIDATE_EMAIL の抜け穴（%0A/%0D）対応
    $errors[] = 'メールアドレスに無効な文字が含まれています。';
  }

  if ($input['zip'] === '') {
    $errors[] = '郵便番号は必須です。';
  } elseif (!preg_match('/\A\d{3}-?\d{4}\z/', $input['zip'])) {
    $errors[] = '郵便番号は7桁（ハイフンありなし両対応）で入力してください。';
  }

  if ($input['address'] === '') {
    $errors[] = '住所は必須です。';
  }

  if ($input['privacy'] !== '1') {
    $errors[] = 'プライバシーポリシーへの同意が必要です。';
  }

  return $errors;
}

// -----------------------------------------------------------------------
// スパム対策
// -----------------------------------------------------------------------

/**
 * CSRFトークンを検証する。
 */
function verifyCsrf(string $token): bool
{
  return isset($_SESSION['csrf_token'])
    && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ハニーポットが空か検証する（ボット検知）。
 */
function verifyHoneypot(string $value): bool
{
  return $value === '';
}

/**
 * 同一IPからの連投をファイルベースで制限する。
 *
 * logs/rate-limit/ 配下にJSON保存する。
 * flock(LOCK_EX) で読み書きを一体化して並行処理レースを防ぐ。
 */
function checkRateLimit(string $ip): bool
{
  $safeIp    = preg_replace('/[^a-zA-Z0-9\.\:\-]/', '_', $ip);
  $rateLimitDir = rtrim(LOG_DIR, '/\\') . DIRECTORY_SEPARATOR . 'rate-limit';
  $file      = $rateLimitDir . DIRECTORY_SEPARATOR . 'rl_' . md5($safeIp) . '.json';
  $now       = time();

  // ディレクトリが存在しない場合は作成
  ensureLogDirectory($rateLimitDir, LOG_DIR_PERMISSION);

  $fp = @fopen($file, 'c+');
  if ($fp === false) {
    // ファイルを開けない場合は制限なしで通過（サイレント失敗より継続優先）
    error_log("[sendmail-form] レートリミットファイルのオープン失敗: {$file}");
    return true;
  }

  if (!flock($fp, LOCK_EX)) {
    fclose($fp);
    return true;
  }

  $history = [];
  $raw     = stream_get_contents($fp);
  if ($raw !== false && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
      $history = $decoded;
    }
  }

  // 制限時間外のエントリを削除
  $history = array_values(array_filter($history, function (int $ts) use ($now): bool {
    return ($now - $ts) < RATE_LIMIT_SECONDS;
  }));

  $allowed = count($history) < RATE_LIMIT_COUNT;

  if ($allowed) {
    $history[] = $now;
  }

  // ファイル先頭に戻って上書き
  ftruncate($fp, 0);
  rewind($fp);
  fwrite($fp, json_encode($history));
  flock($fp, LOCK_UN);
  fclose($fp);

  // レートリミットファイルのパーミッション設定（Linux のみ）
  if (stripos(PHP_OS, 'WIN') !== 0 && file_exists($file)) {
    if (@chmod($file, LOG_FILE_PERMISSION) === false) {
      error_log("[sendmail-form] レートリミットファイル権限設定失敗: {$file}");
    }
  }

  return $allowed;
}

// -----------------------------------------------------------------------
// メール送信
// -----------------------------------------------------------------------

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
    return ['subject' => $defaultSubject, 'body' => '（テンプレート読み込み失敗）'];
  }

  // M12: BOM 除去（UTF-8 BOM: 0xEF 0xBB 0xBF）
  if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
    $raw = substr($raw, 3);
  }

  // プレースホルダー置換
  foreach ($vars as $key => $value) {
    $raw = str_replace('{{' . $key . '}}', $value, $raw);
  }

  // Subject行の抽出
  $subject = $defaultSubject;
  $body    = $raw;

  if (preg_match('/\ASubject:\s*(.+)\r?\n\r?\n(.*)\z/su', $raw, $m)) {
    $subject = trim($m[1]);
    $body    = $m[2];
  }

  return ['subject' => $subject, 'body' => $body];
}

/**
 * メールを送信する。
 * $to / $toName にヘッダーインジェクション攻撃が試みられた場合は即時中断する。
 *
 * @param string $to      宛先メールアドレス
 * @param string $toName  宛先名
 * @param string $subject 件名
 * @param string $body    本文
 */
function sendMail(string $to, string $toName, string $subject, string $body): bool
{
  // C2: ユーザー入力由来の $to / $toName のヘッダーインジェクション検査
  assertNoHeaderInjection($to, 'to');
  assertNoHeaderInjection($toName, 'toName');

  $encodedSubject  = mb_encode_mimeheader($subject, 'UTF-8', 'B');
  $encodedToName   = mb_encode_mimeheader($toName, 'UTF-8', 'B');
  $encodedFromName = mb_encode_mimeheader(FROM_NAME, 'UTF-8', 'B');

  $headers  = 'From: ' . $encodedFromName . ' <' . FROM_EMAIL . '>' . "\r\n";
  $headers .= 'Return-Path: ' . FROM_EMAIL . "\r\n";
  $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
  $headers .= 'Content-Transfer-Encoding: base64' . "\r\n";

  $encodedBody = chunk_split(base64_encode($body));

  return mb_send_mail(
    $encodedToName . ' <' . $to . '>',
    $encodedSubject,
    $encodedBody,
    $headers
  );
}

// -----------------------------------------------------------------------
// メイン処理
// -----------------------------------------------------------------------

$input = collectInput();
$ip    = getClientIp();
$now   = date('Y-m-d H:i:s');

// CSRF検証
if (!verifyCsrf($input['csrf_token'])) {
  respond(false, '不正なリクエストです。ページを再読み込みして再度お試しください。');
}
// H6: CSRF unset は送信完全成功時のみ実施（ここでは削除しない）

// ハニーポット検証（L21: name を url_homepage に変更済み）
if (!verifyHoneypot($input['url_homepage'])) {
  // ボットとみなすが、エラーは返さず成功を装う
  respond(true, 'お問い合わせを受け付けました。');
}

// レートリミット
if (!checkRateLimit($ip)) {
  respond(false, '送信が集中しています。しばらく時間をおいてから再度お試しください。');
}

// サーバーサイドバリデーション
$errors = validate($input);
if (!empty($errors)) {
  respond(false, implode(' ', $errors));
}

// C1: メール本文用変数にはサニタイズのみ（htmlspecialchars は適用しない）
$vars = [
  'name'      => sanitizeForMailBody($input['name']),
  'tel'       => sanitizeForMailBody($input['tel']),
  'email'     => sanitizeForMailBody($input['email']),
  'zip'       => sanitizeForMailBody($input['zip']),
  'address'   => sanitizeForMailBody($input['address']),
  'ip'        => $ip,
  'datetime'  => $now,
  'site_name' => SITE_NAME,
  'site_url'  => SITE_URL,
];

$templateDir = __DIR__ . '/templates/';

// 管理者宛メール
$adminMail = buildMailFromTemplate(
  $templateDir . 'admin-mail.txt',
  $vars,
  ADMIN_SUBJECT
);
$adminSent = sendMail(ADMIN_EMAIL, ADMIN_NAME, $adminMail['subject'], $adminMail['body']);

// 自動返信メール
$autoreplyMail = buildMailFromTemplate(
  $templateDir . 'autoreply-mail.txt',
  $vars,
  AUTOREPLY_SUBJECT
);
$autoreplySuccess = sendMail($input['email'], $input['name'], $autoreplyMail['subject'], $autoreplyMail['body']);

// H7: メール送信失敗時のエラー詳細をログに含める
$logEntry = [
  'timestamp' => date('c'),
  'result'    => $adminSent ? 'success' : 'error',
  'ip'        => $ip,
  'name'      => $input['name'],
  'email'     => $input['email'],
];
if (!$adminSent) {
  $logEntry['error']        = '管理者宛メール送信失敗';
  $logEntry['error_detail'] = 'mb_send_mail returned false';
}
if (!$autoreplySuccess) {
  $logEntry['autoreply_error']        = '自動返信メール送信失敗';
  $logEntry['autoreply_error_detail'] = 'mb_send_mail returned false';
}
writeLog($logEntry);

if (!$adminSent) {
  respond(false, 'メールの送信に失敗しました。お手数ですが、直接ご連絡ください。');
}

// H6: 完全送信成功時のみ CSRF トークンを回転させる
unset($_SESSION['csrf_token']);

respond(true, 'お問い合わせを受け付けました。確認メールをお送りしましたのでご確認ください。');
