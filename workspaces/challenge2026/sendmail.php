<?php
session_start();

$limit_seconds = 60;
$now = time();
$rate_limited = false;
if (isset($_SESSION['last_submit_time']) && ($now - $_SESSION['last_submit_time']) < $limit_seconds) {
  $rate_limited = true;
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
  http_response_code(403);
  echo json_encode(["error" => "不正な送信が検出されました（CSRFトークン不一致）。"]);
  exit;
}

mb_language("ja");
mb_internal_encoding("UTF-8");
// $admin_email = "siryoseikyu@meimonkai.jp,info@linnoa.net";
$admin_email = "kms2wa8z@gmail.com";
$from_email = "no-reply@meimonkai.co.jp";

function sanitize($key)
{
  return htmlspecialchars(trim($_POST[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}

$name = sanitize("name");
$furigana = sanitize("furigana");
$zipcode = sanitize("postcode");
$pref = sanitize("pref");
$city = sanitize("city");
$address = sanitize("address");
$building = sanitize("building");
$tel = sanitize("tel");
$email = trim($_POST["email"] ?? '');
$gender = sanitize("gender");
$grade = sanitize("grade");
$school_type = sanitize("school_type");
$school_name = sanitize("school_name");
$contact_person = sanitize("contact_person");
$resou_status = sanitize("resou_status");
$message = sanitize("message");
$place = sanitize("place");
$nickname = trim($_POST["nickname"] ?? '');

if ($nickname !== "") {
  http_response_code(403);
  echo json_encode(["error" => "不正なアクセスが検出されました。"]);
  exit;
}

if (
  $place === '' || $name === '' || $furigana === '' || $zipcode === '' ||
  $pref === '' || $city === '' || $address === '' || $tel === '' ||
  $email === '' || $gender === '' || $grade === '' ||
  $contact_person === '' || $resou_status === '' ||
  !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
  http_response_code(400);
  echo json_encode(["error" => "入力内容に不備があります。必須項目をご確認ください。"]);
  exit;
}

if (!preg_match('/^[0-9\-]+$/', $tel)) {
  http_response_code(400);
  echo json_encode(["error" => "電話番号の形式が正しくありません。"]);
  exit;
}

// 先頭3桁で携帯・IP電話か固定電話かを判定し、桁数を検証する
$tel_digits = str_replace('-', '', $tel);
if (preg_match('/\A(090|080|070|050|0800)/', $tel_digits)) {
  // 携帯・IP電話・フリーダイヤル(0800): 11桁必須
  if (!preg_match('/\A0\d{10}\z/', $tel_digits)) {
    http_response_code(400);
    echo json_encode(["error" => "携帯電話・フリーダイヤル(0800)（090, 080, 070, 050, 0800）は11桁で入力してください。"]);
    exit;
  }
} else {
  // 固定電話: 10桁必須
  if (!preg_match('/\A0\d{9}\z/', $tel_digits)) {
    http_response_code(400);
    echo json_encode(["error" => "固定電話番号は10桁で入力してください。"]);
    exit;
  }
}

// トークン更新処理
unset($_SESSION['csrf_token']);
$new_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $new_token;

$log_dir = __DIR__ . '/logs';
$log_file = $log_dir . '/form.log';
if (!is_dir($log_dir)) {
  mkdir($log_dir, 0755, true);
}
$log_entry = sprintf(
  "[%s] IP: %s | Email: %s | Name: %s\n",
  date('Y-m-d H:i:s'),
  $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
  $email,
  $name
);
file_put_contents($log_file, $log_entry, FILE_APPEND);

$subject_user = "[名門会] チャレンジ問題へのお問い合わせありがとうございます。";
$subject_admin = "【チャレンジ問題LP】お問い合わせフォームから問い合わせがありました";

$body_admin = <<<EOT
【チャレンジ問題LP】お問い合わせフォームから問い合わせがありましたので、ご確認とご対応よろしくお願いいたします。

【ご希望の場所】 {$place}
【生徒名】 {$name}
【フリガナ】 {$furigana}
【性別】 {$gender}
【学年】 {$grade}
【学校区分】 {$school_type}
【学校名】 {$school_name}
【ご住所】 {$zipcode} {$pref} {$city} {$address} {$building}
【電話番号】 {$tel}
【メールアドレス】 {$email}
【お問い合わせされた方】 {$contact_person}
【リソー教育グループのご利用状況】 {$resou_status}
【ご質問・ご相談】
{$message}
EOT;

$body_user = <<<EOT
{$name} 様

このメールはシステムからの自動返信メールです。

この度は、名門会のチャレンジ問題についてお問い合わせいただき、誠にありがとうございます。
後ほど担当者から改めてお電話もしくはメールにてご連絡差し上げますので、しばらくお待ちください。

以下は送信いただいた内容となります。
-------------------------------------
【ご希望の教室】 {$place}
【生徒名】 {$name}
【フリガナ】 {$furigana}
【性別】 {$gender}
【学年】 {$grade}
【学校区分】 {$school_type}
【学校名】 {$school_name}
【ご住所】 {$zipcode} {$pref} {$city} {$address} {$building}
【電話番号】 {$tel}
【メールアドレス】 {$email}
【お問い合わせされた方】 {$contact_person}
【リソー教育グループのご利用状況】 {$resou_status}
【ご質問・ご相談】
{$message}
EOT;

$headers = "From: {$from_email}";
$success_user = mb_send_mail($email, $subject_user, $body_user, $headers);
$success_admin = mb_send_mail($admin_email, $subject_admin, $body_admin, $headers);

header("Content-Type: application/json");

if ($success_user && $success_admin) {
  $_SESSION['last_submit_time'] = $now;
  echo json_encode([
    "status" => "success",
    "redirect" => "https://meimonkai.co.jp/lp/challenge2026/thanks.html",
    "message" => "お問い合わせありがとうございます。確認メールを送信しました。"
  ]);
  exit;
} else {
  if ($rate_limited) {
    $wait = $limit_seconds - ($now - $_SESSION['last_submit_time']);
    http_response_code(429);
    echo json_encode(["status" => "error", "error" => "短時間に連続して送信されています。{$wait}秒後に再送信してください。"]);
    exit;
  }
  http_response_code(500);
  echo json_encode(["status" => "error", "error" => "メール送信中にエラーが発生しました。時間を置いて再度お試しください。"]);
  exit;
}
