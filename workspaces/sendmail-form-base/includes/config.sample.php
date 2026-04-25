<?php
// このファイルをコピーして config.php を作成してください
// config.php は .gitignore で除外されます

// 管理者宛メール設定
define('ADMIN_EMAIL', 'admin@example.com');           // 問い合わせ通知の宛先
define('ADMIN_NAME', '管理者');

// 送信元設定（サーバーのドメインと一致するアドレスを推奨）
define('FROM_EMAIL', 'noreply@example.com');
define('FROM_NAME', 'サイト名 お問い合わせフォーム');

// 自動返信メール設定
define('AUTOREPLY_SUBJECT', 'お問い合わせを受け付けました');
define('ADMIN_SUBJECT', '【サイト名】お問い合わせが届きました');

// サイト情報
define('SITE_NAME', 'サイト名');
define('SITE_URL', 'https://example.com');

// プライバシーポリシーURL（contact.php から参照）
define('PRIVACY_POLICY_URL', '/privacy');

// レートリミット（同一IPからの送信制限）
define('RATE_LIMIT_COUNT', 3);     // 許容送信回数
define('RATE_LIMIT_SECONDS', 600); // 制限時間（秒）：デフォルト10分

// ログ保存ディレクトリ（末尾スラッシュは rtrim で正規化するため不要だが、
// 既存設定との互換性のため末尾スラッシュがあっても動作する）
define('LOG_DIR', __DIR__ . '/../logs/');

// ログのパーミッション設定（Linux/Mac のみ有効。Windows は NTFS ACL 管理のため無効）
define('LOG_DIR_PERMISSION', 0700);  // ディレクトリ：所有者のみ rwx
define('LOG_FILE_PERMISSION', 0600); // ファイル：所有者のみ rw
