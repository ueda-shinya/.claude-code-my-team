<?php
declare(strict_types=1);

// mb_language('Japanese') は mb_send_mail の内部変換（ISO-2022-JP 化）を引き起こすため設定しない。
// mb_send_mail を使う場合、mb_language('Japanese') 設定下では本文が二重変換されて文字化けする。
// 本文・件名・ヘッダーは自前でエンコードし、mail() で直接送信する。
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/includes/session-init.php';
require_once __DIR__ . '/includes/config.php';

// -----------------------------------------------------------------------
// POST メソッド以外は早期リジェクト（Allow ヘッダ付与）
// -----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(['success' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
  exit;
}

// -----------------------------------------------------------------------
// AJAX / 通常POSTの判定
// クライアント側 form.js が X-Requested-With: XMLHttpRequest を付与するため、
// それを優先検査する。付与されていない場合は Accept ヘッダーで補完判定する。
// -----------------------------------------------------------------------
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
  || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

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
 * 電話番号をメール表示用にハイフン区切り形式へ整形する。
 * 入力: 0312345678 / 03-1234-5678 → 出力: 03-1234-5678
 * 全角数字・全角ハイフンは正規化済みの値を想定。既にハイフン含む場合はそのまま返す。
 */
function formatTelDisplay(string $tel): string
{
  if ($tel === '' || $tel === '（未入力）') {
    return $tel;
  }
  $digits = normalizeTelDigits($tel);
  // 市外局番パターン別にハイフン挿入（一般的な日本国内番号のみ対応）
  if (preg_match('/\A(0\d{9})\z/', $digits)) {
    // 10桁: 固定電話（市外局番は2〜5桁で可変だが、先頭2桁で代表パターン分岐）
    if (preg_match('/\A(0[789]0)(\d{4})(\d{4})\z/', $digits, $m)) {
      // 0X0-XXXX-XXXX は携帯系フォールバック（090/080/070系 10桁は通常ない）
      return $m[1] . '-' . $m[2] . '-' . $m[3];
    }
    // 0X-XXXX-XXXX (市外局番2桁)
    return preg_replace('/\A(\d{2})(\d{4})(\d{4})\z/', '$1-$2-$3', $digits);
  }
  if (preg_match('/\A(0\d{10})\z/', $digits)) {
    // 11桁: 090/080/070/050/0800
    if (preg_match('/\A(0800)(\d{3})(\d{4})\z/', $digits, $m)) {
      return $m[1] . '-' . $m[2] . '-' . $m[3];
    }
    return preg_replace('/\A(\d{3})(\d{4})(\d{4})\z/', '$1-$2-$3', $digits);
  }
  // 変換できない場合は元の値をサニタイズして返す
  return sanitizeForMailBody($tel);
}

/**
 * メールヘッダーインジェクション攻撃の試みを検出する。
 * 生の改行・NULL 文字、および URLエンコード済みの改行（%0A/%0D/%00）が含まれる場合は
 * 即座に処理を打ち切る。
 */
function assertNoHeaderInjection(string $value, string $fieldName): void
{
  // 生の改行・NULL 文字チェック
  if (preg_match('/[\r\n\0]/', $value)) {
    error_log("[bornstem-contact] ヘッダーインジェクション試行検知 field={$fieldName} raw");
    respond(false, '不正なリクエストです。');
  }
  // URLエンコード済み改行チェック（%0A / %0D / %00）
  if (preg_match('/%0[adAD]|%00/', $value)) {
    error_log("[bornstem-contact] ヘッダーインジェクション試行検知 field={$fieldName} url-encoded");
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
      error_log("[bornstem-contact] ログディレクトリ作成失敗: {$dir}");
      return;
    }
  }
  if (@chmod($dir, $perm) === false) {
    error_log("[bornstem-contact] ログディレクトリ権限設定失敗: {$dir}");
  }
}

/**
 * ログをJSON Lines形式でファイルに追記する。
 *
 * @param array<string, mixed> $entry
 */
function writeLog(array $entry): void
{
  $logDir       = rtrim(LOG_DIR, '/\\');
  $rateLimitDir = $logDir . DIRECTORY_SEPARATOR . 'rate-limit';

  ensureLogDirectory($logDir, LOG_DIR_PERMISSION);
  ensureLogDirectory($rateLimitDir, LOG_DIR_PERMISSION);

  $file = $logDir . DIRECTORY_SEPARATOR . 'contact-' . date('Ym') . '.log';
  $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);

  if (stripos(PHP_OS, 'WIN') !== 0 && file_exists($file)) {
    if (@chmod($file, LOG_FILE_PERMISSION) === false) {
      error_log("[bornstem-contact] ログファイル権限設定失敗: {$file}");
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
  $fields = [
    // 会社情報
    'company', 'company_kana',
    // 代表者
    'rep_lastname', 'rep_firstname', 'rep_lastname_kana', 'rep_firstname_kana',
    // 担当者（任意）
    'staff_lastname', 'staff_firstname', 'staff_lastname_kana', 'staff_firstname_kana',
    // 住所
    'zipcode', 'prefecture', 'city', 'street', 'building',
    // 連絡先
    'email', 'tel', 'mobile',
    // 業種・URL
    'industry', 'website',
    // スパム対策・同意
    'privacy', 'url_homepage', 'csrf_token',
  ];
  $input = [];
  foreach ($fields as $field) {
    $input[$field] = trim((string)($_POST[$field] ?? ''));
  }
  return $input;
}

/**
 * 電話番号を正規化する。
 * 全角数字・全角ハイフン・半角スペース・全角スペース・ハイフン各種を除去し、
 * 半角数字のみにして返す。
 */
function normalizeTelDigits(string $tel): string
{
  // 全角数字→半角
  $normalized = mb_convert_kana($tel, 'n', 'UTF-8');
  // ハイフン各種（半角 - / 全角 － / 長音 ー / 中黒ハイフン ‐ ‑ ‒ – — ）とスペースを除去
  $normalized = preg_replace('/[\-\－\ー\‐\‑\‒\–\—\s]/u', '', $normalized);
  return $normalized;
}

/**
 * 電話番号バリデーション（ハイフンあり/なし・全角数字・全角ハイフン・スペース対応）。
 *
 * 0120（フリーダイヤル）は10桁体系のため固定電話判定で通る（10桁必須）。
 * 0800（フリーダイヤル）は11桁体系のため携帯・IP電話グループで判定する。
 *
 * @return string エラーメッセージ。問題なければ空文字列
 */
function validateTel(string $tel, string $fieldLabel): string
{
  if ($tel === '') {
    return "{$fieldLabel}を入力してください。";
  }
  $digits = normalizeTelDigits($tel);
  // 正規化後に数字以外が残っていれば形式エラー
  if (!preg_match('/^\d+$/', $digits)) {
    return "{$fieldLabel}の形式が正しくありません（数字のみで入力してください）。";
  }
  // 先頭4桁(0800)または先頭3桁で携帯・IP電話・フリーダイヤル(0800)か固定電話かを判定
  if (preg_match('/\A(090|080|070|050|0800)/', $digits)) {
    // 携帯・IP電話・フリーダイヤル(0800): 11桁必須
    if (!preg_match('/\A0\d{10}\z/', $digits)) {
      return "{$fieldLabel}（090/080/070/050/0800）は11桁で入力してください。";
    }
  } else {
    // 固定電話・0120（10桁）: 10桁必須
    if (!preg_match('/\A0\d{9}\z/', $digits)) {
      return "{$fieldLabel}は10桁で入力してください。";
    }
  }
  return '';
}

/**
 * 全角カタカナバリデーション（スペース不可・A案）。
 *
 * 全角カタカナ・長音符のみ許容。半角スペース・全角スペースは完全不許可。
 * `+` 量化子により1文字以上が担保されるため、セカンダリチェック不要。
 *
 * @return string エラーメッセージ。問題なければ空文字列
 */
function validateKatakana(string $value, string $fieldLabel, bool $required): string
{
  if ($value === '') {
    return $required ? "{$fieldLabel}は必須です。" : '';
  }
  // 全角カタカナ・長音符のみ許容（スペース不可）
  if (!preg_match('/\A[ァ-ヶー]+\z/u', $value)) {
    return "{$fieldLabel}は全角カタカナで入力してください（スペース不可）。";
  }
  return '';
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

  // 改行・NULL 文字チェック（全テキストフィールド）
  $checkFields = [
    'company', 'company_kana', 'rep_lastname', 'rep_firstname',
    'rep_lastname_kana', 'rep_firstname_kana',
    'staff_lastname', 'staff_firstname', 'staff_lastname_kana', 'staff_firstname_kana',
    'zipcode', 'prefecture', 'city', 'street', 'building',
    'email', 'tel', 'mobile', 'industry', 'website',
  ];
  $invalidCharFound = false;
  foreach ($checkFields as $field) {
    if (preg_match('/[\r\n\0]/', $input[$field]) || preg_match('/%0[adAD]|%00/', $input[$field])) {
      $invalidCharFound = true;
      break;
    }
  }
  if ($invalidCharFound) {
    $errors[] = '入力値に無効な文字が含まれています。';
    return $errors; // 以降のバリデーションを打ち切る
  }

  // 文字数上限チェック
  $limits = [
    'company'            => 100,
    'company_kana'       => 100,
    'rep_lastname'       => 50,
    'rep_firstname'      => 50,
    'rep_lastname_kana'  => 50,
    'rep_firstname_kana' => 50,
    'staff_lastname'     => 50,
    'staff_firstname'    => 50,
    'staff_lastname_kana'  => 50,
    'staff_firstname_kana' => 50,
    'zipcode'            => 8,
    'prefecture'         => 20,
    'city'               => 100,
    'street'             => 200,
    'building'           => 200,
    'email'              => 254,
    'tel'                => 20,
    'mobile'             => 20,
    'industry'           => 100,
    'website'            => 2083, // URLの実用上限
  ];
  foreach ($limits as $field => $max) {
    if (mb_strlen($input[$field], 'UTF-8') > $max) {
      $errors[] = "{$field} の入力値が長すぎます（最大{$max}文字）。";
    }
  }

  // 会社名・サロン名（必須）
  if ($input['company'] === '') {
    $errors[] = '会社名・サロン名は必須です。';
  }

  // 会社名・サロン名カナ（必須・全角カタカナ）
  $e = validateKatakana($input['company_kana'], '会社名・サロン名カナ', true);
  if ($e !== '') $errors[] = $e;

  // 代表者 姓名（必須）
  if ($input['rep_lastname'] === '') $errors[] = '代表者の姓は必須です。';
  if ($input['rep_firstname'] === '') $errors[] = '代表者の名は必須です。';

  // 代表者カナ（必須・全角カタカナ）
  $e = validateKatakana($input['rep_lastname_kana'], '代表者カナ（セイ）', true);
  if ($e !== '') $errors[] = $e;
  $e = validateKatakana($input['rep_firstname_kana'], '代表者カナ（メイ）', true);
  if ($e !== '') $errors[] = $e;

  // 担当者 姓名（任意・入力ありなら文字数は上限チェック済み）
  // 担当者カナ（任意・入力ありなら全角カタカナチェック）
  $e = validateKatakana($input['staff_lastname_kana'], '担当者カナ（セイ）', false);
  if ($e !== '') $errors[] = $e;
  $e = validateKatakana($input['staff_firstname_kana'], '担当者カナ（メイ）', false);
  if ($e !== '') $errors[] = $e;

  // 郵便番号（必須・ハイフンあり/なし両対応。サーバー側はハイフン削除して7桁数字で保存）
  if ($input['zipcode'] === '') {
    $errors[] = '郵便番号は必須です。';
  } elseif (!preg_match('/\A\d{3}-?\d{4}\z/', $input['zipcode'])) {
    $errors[] = '郵便番号は7桁（ハイフンありなし両対応）で入力してください。';
  }

  // 都道府県・市区町村・町域（必須）
  if ($input['prefecture'] === '') $errors[] = '都道府県は必須です。';
  if ($input['city'] === '') $errors[] = '市区町村は必須です。';
  if ($input['street'] === '') $errors[] = '町域・番地は必須です。';

  // メールアドレス（必須）
  if ($input['email'] === '') {
    $errors[] = 'メールアドレスは必須です。';
  } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'メールアドレスの形式が正しくありません。';
  } elseif (preg_match('/[%\r\n\0]/', $input['email'])) {
    // FILTER_VALIDATE_EMAIL は "%" を含む文字列を通過させる場合がある（例: user%40example.com）。
    // URLエンコードによるヘッダーインジェクション（%0A=%0D=改行相当）を防ぐため、"%" 自体を明示的に弾く。
    $errors[] = 'メールアドレスに無効な文字が含まれています。';
  }

  // 電話番号（必須）
  $e = validateTel($input['tel'], '電話番号');
  if ($e !== '') $errors[] = $e;

  // 携帯番号（任意・入力があればバリデーション）
  if ($input['mobile'] !== '') {
    $e = validateTel($input['mobile'], '携帯番号');
    if ($e !== '') $errors[] = $e;
  }

  // 業態 / 業種（必須）
  if ($input['industry'] === '') {
    $errors[] = '業態 / 業種は必須です。';
  }

  // ホームページURL（必須・http:// または https:// のみ許容）
  if ($input['website'] === '') {
    $errors[] = 'ホームページURLは必須です。';
  } elseif (!filter_var($input['website'], FILTER_VALIDATE_URL)) {
    $errors[] = 'ホームページURLの形式が正しくありません。';
  } elseif (!preg_match('/\Ahttps?:\/\//i', $input['website'])) {
    $errors[] = 'ホームページURLは http:// または https:// で始まる形式で入力してください。';
  }

  // プライバシーポリシー同意（必須）
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
 */
function checkRateLimit(string $ip): bool
{
  $safeIp       = preg_replace('/[^a-zA-Z0-9\.\:\-]/', '_', $ip);
  $rateLimitDir = rtrim(LOG_DIR, '/\\') . DIRECTORY_SEPARATOR . 'rate-limit';
  $file         = $rateLimitDir . DIRECTORY_SEPARATOR . 'rl_' . md5($safeIp) . '.json';
  $now          = time();

  ensureLogDirectory($rateLimitDir, LOG_DIR_PERMISSION);

  $fp = @fopen($file, 'c+');
  if ($fp === false) {
    error_log("[bornstem-contact][SECURITY-ALERT] レートリミットファイルのオープン失敗（fail-open発生）: {$file}");
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

  $history  = array_values(array_filter($history, function (int $ts) use ($now): bool {
    return ($now - $ts) < RATE_LIMIT_SECONDS;
  }));
  $allowed  = count($history) < RATE_LIMIT_COUNT;

  if ($allowed) {
    $history[] = $now;
  }

  ftruncate($fp, 0);
  rewind($fp);
  fwrite($fp, json_encode($history));
  flock($fp, LOCK_UN);
  fclose($fp);

  if (stripos(PHP_OS, 'WIN') !== 0 && file_exists($file)) {
    if (@chmod($file, LOG_FILE_PERMISSION) === false) {
      error_log("[bornstem-contact] レートリミットファイル権限設定失敗: {$file}");
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

  // BOM 除去（UTF-8 BOM: 0xEF 0xBB 0xBF）
  if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
    $raw = substr($raw, 3);
  }

  // プレースホルダー置換
  foreach ($vars as $key => $value) {
    $raw = str_replace('{{' . $key . '}}', $value, $raw);
  }

  // Subject行の抽出
  // ([^\r\n]+) で改行を含まないよう限定する（s フラグを付けると改行を含んで貪欲マッチしてしまうバグを防ぐ）
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
  assertNoHeaderInjection($to, 'to');
  assertNoHeaderInjection($toName, 'toName');

  $encodedSubject  = mb_encode_mimeheader($subject, 'UTF-8', 'B');
  $encodedToName   = mb_encode_mimeheader($toName, 'UTF-8', 'B');
  $encodedFromName = mb_encode_mimeheader(FROM_NAME, 'UTF-8', 'B');

  // folding 部分（"\r\n\t" / "\r\n "）を除去してからインジェクション検査
  assertNoHeaderInjection(preg_replace('/\r\n[\t ]/', '', $encodedSubject), 'subject-encoded');
  assertNoHeaderInjection(preg_replace('/\r\n[\t ]/', '', $encodedToName), 'toName-encoded');

  $headers  = 'From: ' . $encodedFromName . ' <' . FROM_EMAIL . '>' . "\r\n";
  $headers .= 'Return-Path: ' . FROM_EMAIL . "\r\n";
  $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
  $headers .= 'Content-Transfer-Encoding: base64' . "\r\n";

  $encodedBody = chunk_split(base64_encode($body));

  return mail(
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

// ハニーポット検証
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
  if ($isAjax) {
    respond(false, implode(' ', $errors));
  } else {
    header('Location: ./index.php?error=1', true, 303);
    exit;
  }
}

// -----------------------------------------------------------------------
// メール本文用変数の組み立て
// -----------------------------------------------------------------------

// 郵便番号: 表示用にハイフン区切り形式（123-4567）へ整形
// バリデーション（\A\d{3}-?\d{4}\z）を通過した値はハイフン除去後必ず7桁になるため、
// else 節（sanitizeForMailBody）は実際には到達しない（デッドコード）。
// バリデーション変更時の安全網として残しておく。
$rawZip     = str_replace('-', '', $input['zipcode']);
$zipcodeFormatted = strlen($rawZip) === 7
  ? substr($rawZip, 0, 3) . '-' . substr($rawZip, 3)
  : sanitizeForMailBody($input['zipcode']);

// 担当者が未入力の場合は「（未入力）」を表示
$staffLastname      = $input['staff_lastname'] !== '' ? $input['staff_lastname'] : '（未入力）';
$staffFirstname     = $input['staff_firstname'] !== '' ? $input['staff_firstname'] : '（未入力）';
$staffLastnameKana  = $input['staff_lastname_kana'] !== '' ? $input['staff_lastname_kana'] : '（未入力）';
$staffFirstnameKana = $input['staff_firstname_kana'] !== '' ? $input['staff_firstname_kana'] : '（未入力）';
$mobile             = $input['mobile'] !== '' ? $input['mobile'] : '（未入力）';
$building           = $input['building'] !== '' ? $input['building'] : '（未入力）';

$vars = [
  'company'              => sanitizeForMailBody($input['company']),
  'company_kana'         => sanitizeForMailBody($input['company_kana']),
  'rep_lastname'         => sanitizeForMailBody($input['rep_lastname']),
  'rep_firstname'        => sanitizeForMailBody($input['rep_firstname']),
  'rep_lastname_kana'    => sanitizeForMailBody($input['rep_lastname_kana']),
  'rep_firstname_kana'   => sanitizeForMailBody($input['rep_firstname_kana']),
  'staff_lastname'       => sanitizeForMailBody($staffLastname),
  'staff_firstname'      => sanitizeForMailBody($staffFirstname),
  'staff_lastname_kana'  => sanitizeForMailBody($staffLastnameKana),
  'staff_firstname_kana' => sanitizeForMailBody($staffFirstnameKana),
  'zipcode'              => $zipcodeFormatted,
  'prefecture'           => sanitizeForMailBody($input['prefecture']),
  'city'                 => sanitizeForMailBody($input['city']),
  'street'               => sanitizeForMailBody($input['street']),
  'building'             => sanitizeForMailBody($building),
  'email'                => sanitizeForMailBody($input['email']),
  'tel'                  => formatTelDisplay($input['tel']),
  'mobile'               => formatTelDisplay($mobile),
  'industry'             => sanitizeForMailBody($input['industry']),
  'website'              => sanitizeForMailBody($input['website']),
  'timestamp'            => $now,
  'ip'                   => $ip,
  'user_agent'           => sanitizeForMailBody($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'),
];

$templateDir = __DIR__ . '/templates/';

// 管理者宛メール（ADMIN_EMAILS 配列の全アドレスに送信）
$adminMail    = buildMailFromTemplate(
  $templateDir . 'admin-mail.txt',
  $vars,
  ADMIN_SUBJECT
);
// $adminSentCount: 送信成功件数（1件以上で受付成功扱い）
// $adminFailCount: 送信失敗件数（0より大きければ error_log に痕跡を残す）
$adminSentCount = 0;
$adminFailCount = 0;
if (!is_array(ADMIN_EMAILS) || count(ADMIN_EMAILS) === 0) {
  error_log('[bornstem-contact][CONFIG-ERROR] ADMIN_EMAILS が配列でないか空です');
  // 全件失敗扱いで進む（$adminSentCount === 0 で既存ロジックがエラー応答する）
}
foreach (ADMIN_EMAILS as $adminEmail) {
  $sent = sendMail($adminEmail, ADMIN_NAME, $adminMail['subject'], $adminMail['body']);
  if ($sent) {
    $adminSentCount++;
  } else {
    $adminFailCount++;
    error_log("[bornstem-contact][MAIL-FAIL] 管理者宛メール送信失敗: {$adminEmail}");
  }
}
// 1件以上成功していれば受付成功とみなす（全件失敗のみユーザーにエラーを返す）
$adminAllFailed = ($adminSentCount === 0);

// 自動返信メール（宛先は入力者のメールアドレス、表示名は代表者名）
$repName = $input['rep_lastname'] . ' ' . $input['rep_firstname'];
$autoreplyMail = buildMailFromTemplate(
  $templateDir . 'autoreply-mail.txt',
  $vars,
  AUTOREPLY_SUBJECT
);
$autoreplySuccess = sendMail($input['email'], $repName, $autoreplyMail['subject'], $autoreplyMail['body']);

// ログ記録
// 全件失敗: result=error / 部分失敗: result=partial / 全件成功: result=success
if ($adminAllFailed) {
  $resultLabel = 'error';
} elseif ($adminFailCount > 0) {
  $resultLabel = 'partial';
} else {
  $resultLabel = 'success';
}
$logEntry = [
  'timestamp'  => date('c'),
  'result'     => $resultLabel,
  'ip'         => $ip,
  'company'    => $input['company'],
  'rep_name'   => $input['rep_lastname'] . ' ' . $input['rep_firstname'],
  'email'      => $input['email'],
];
if ($adminFailCount > 0) {
  $logEntry['admin_mail_error'] = "管理者宛メール送信失敗（{$adminFailCount}件）、成功（{$adminSentCount}件）";
  $logEntry['admin_mail_detail'] = 'mail() returned false（詳細は error_log を参照）';
}
if (!$autoreplySuccess) {
  $logEntry['autoreply_error']        = '自動返信メール送信失敗';
  $logEntry['autoreply_error_detail'] = 'mail() returned false';
}
writeLog($logEntry);

// 全アドレス失敗時のみユーザーにエラーを返す
// 1件成功・1件失敗の場合はユーザーには受付成功を伝え、error_log・ログに痕跡を残す
if ($adminAllFailed) {
  if ($isAjax) {
    respond(false, 'メールの送信に失敗しました。お手数ですが、直接ご連絡ください。');
  } else {
    header('Location: ./index.php?error=1', true, 303);
    exit;
  }
}

// CSRF トークンを回転させる（完全送信成功時のみ）
unset($_SESSION['csrf_token']);

// 自動返信の成否に関わらず受付は完了しているためユーザーには受付完了を伝える。
// 自動返信失敗は上記 writeLog に記録されるため、運用側でログを確認する。
$autoreplyNote = $autoreplySuccess
  ? 'お問い合わせを受け付けました。確認メールをお送りしましたのでご確認ください。'
  : 'お問い合わせを受け付けました。確認メールの送信に時間がかかる場合がございますが、内容は確かに受付けております。';

if ($isAjax) {
  respond(true, $autoreplyNote);
} else {
  header('Location: ./thanks.html', true, 303);
  exit;
}
