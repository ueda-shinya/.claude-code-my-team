# sendmail-form-base

PHPお問い合わせフォームのベーステンプレートです。
新規サイトに導入する際は「カスタマイズ手順」に沿って設定ファイルとテンプレートを書き換えるだけで使い始められます。

## ディレクトリ構成

```
sendmail-form-base/
├── index.php                ← フォーム本体（PHPでCSRFトークンを埋め込む）
├── submit.php               ← 送信処理（バリデーション・メール送信・ログ）
├── assets/                  ← 公開前提（静的配信のみ）
│   ├── css/
│   │   └── form.css         ← ベーススタイル（使い回し先で上書き前提）
│   ├── js/
│   │   └── form.js          ← 郵便番号検索・バリデーション・非同期送信
│   └── images/              ← 画像置き場（空ディレクトリ保持用 .gitkeep 同梱）
├── includes/                ← 非公開PHP（require専用・URL直叩き拒否）
│   ├── config.sample.php    ← 設定サンプル → config.php にコピーして使う
│   ├── session-init.php     ← セッション初期化（Cookie属性設定）
│   └── .htaccess            ← Webからのアクセスを全面拒否（Apache用）
├── templates/               ← 非公開データ（URL直叩き拒否）
│   ├── admin-mail.txt       ← 管理者宛メール本文テンプレート
│   ├── autoreply-mail.txt   ← 自動返信メール本文テンプレート
│   └── .htaccess            ← Webからのアクセスを全面拒否（Apache用）
├── logs/
│   ├── .gitkeep             ← ログディレクトリ（*.log は .gitignore 対象）
│   ├── .htaccess            ← Webからのアクセスを全面拒否（Apache用）
│   ├── index.html           ← ディレクトリリスティング対策（空ファイル）
│   └── rate-limit/          ← レートリミット用JSONファイル置き場（自動生成）
└── .gitignore
```

## 動作要件

- PHP 7.4 以上
- `mb_send_mail` / `mb_language` 関数（`php-mbstring` 拡張）
- `sendmail` または `postfix` が利用可能なサーバー環境
- `logs/` ディレクトリへの書き込み権限

## カスタマイズ手順

### 1. config.php を作成する

```bash
cp includes/config.sample.php includes/config.php
```

`includes/config.php` を開いて以下を書き換えてください。

| 定数 | 説明 |
|---|---|
| `ADMIN_EMAIL` | 問い合わせ通知の受信アドレス |
| `ADMIN_NAME` | 管理者名（メール表示名） |
| `FROM_EMAIL` | 送信元アドレス（サーバードメインと一致推奨） |
| `FROM_NAME` | 送信元表示名 |
| `AUTOREPLY_SUBJECT` | 自動返信メールの件名 |
| `ADMIN_SUBJECT` | 管理者宛メールの件名 |
| `SITE_NAME` | サイト名（メール本文に挿入） |
| `SITE_URL` | サイトURL（自動返信メールのフッターに挿入） |
| `PRIVACY_POLICY_URL` | プライバシーポリシーページのURL |
| `RATE_LIMIT_COUNT` | 同一IPの許容送信回数（デフォルト: 3回） |
| `RATE_LIMIT_SECONDS` | レートリミットの時間窓（デフォルト: 600秒=10分） |
| `LOG_DIR_PERMISSION` | ログディレクトリのパーミッション（デフォルト: 0700） |
| `LOG_FILE_PERMISSION` | ログファイルのパーミッション（デフォルト: 0600） |

### 2. メールテンプレートを編集する

`templates/admin-mail.txt` と `templates/autoreply-mail.txt` を編集してください。

**利用可能なプレースホルダー:**

| プレースホルダー | 置換内容 |
|---|---|
| `{{name}}` | 送信者のお名前 |
| `{{tel}}` | 電話番号 |
| `{{email}}` | メールアドレス |
| `{{zip}}` | 郵便番号 |
| `{{address}}` | 住所 |
| `{{ip}}` | 送信元IPアドレス |
| `{{datetime}}` | 送信日時 |
| `{{site_name}}` | `SITE_NAME` の値 |
| `{{site_url}}` | `SITE_URL` の値 |

**件名の定義方法:**

テンプレートの1行目に `Subject: 件名` と書くと、その値がメールの件名になります（`config.php` の件名定数より優先されます）。

```
Subject: 【サイト名】お問い合わせが届きました

本文はここから...
```

### 3. ディレクトリのパーミッションを設定する（本番サーバー）

`logs/` ディレクトリへの書き込み権限を付与してください。

```bash
chmod 700 logs/
```

> **パーミッション自動設定について**
> `writeLog()` 実行時に `logs/` および `logs/rate-limit/` のパーミッションが自動的に `0700`（所有者のみアクセス可）に再設定されます。ログファイル本体は `0600` に設定されます。755 のままでも初回リクエスト時に自動で 700 に引き締められますが、初期値として 700 を推奨します。これはLinux環境のみ有効です。Windowsローカル環境では NTFS ACL により管理されるため、chmod はスキップされます。

### 4. プライバシーポリシーのURLを設定する

`config.php` の `PRIVACY_POLICY_URL` を実際のURLに書き換えてください。

```php
define('PRIVACY_POLICY_URL', '/privacy');
```

### 5. サンクスページを追加する場合（任意）

現在の実装は送信完了メッセージをフォーム上に表示します。
サンクスページへのリダイレクトに変更する場合は `submit.php` の `respond()` 関数を修正し、`form.js` のリダイレクト処理を追加してください。

## スパム対策

以下の3層で対策しています。

| 対策 | 実装箇所 |
|---|---|
| CSRFトークン | `index.php`（発行）/ `submit.php`（検証） |
| ハニーポット（`url_homepage` フィールド: 入力があればボット判定） | `index.php` / `submit.php` |
| レートリミット（同一IP・10分間に3回まで） | `submit.php` / `logs/rate-limit/` 配下にファイル保存 |

## ログ

`logs/contact-YYYYMM.log` にJSON Lines形式で記録されます。

```json
{"timestamp":"2024-01-15T10:30:00+09:00","result":"success","ip":"192.168.1.1","name":"山田太郎","email":"yamada@example.com"}
{"timestamp":"2024-01-15T10:35:00+09:00","result":"error","ip":"192.168.1.2","name":"鈴木花子","email":"suzuki@example.com","error":"管理者宛メール送信失敗","error_detail":"..."}
```

## セキュリティ上の注意

- `includes/config.php` は `.gitignore` で除外されています。Gitリポジトリにコミットしないでください。
- `includes/` および `templates/` ディレクトリには `.htaccess` を同梱しており、URL直叩きを拒否しています（Apache 2.4 / 2.2 両対応）。
- `logs/` ディレクトリも同様に `logs/.htaccess` により外部アクセスを拒否しています。
- **Nginx をご利用の場合** は `.htaccess` が読み込まれないため、サーバー設定ファイルに以下を追加してください。
  ```nginx
  location ~ /(includes|templates|logs)/ {
      deny all;
  }
  ```
- **CDN背後（Cloudflare 等）で運用する場合** は `getClientIp()` が `REMOTE_ADDR`（CDN のIPアドレス）を返します。実際の送信者IPを取得するには `submit.php` の `getClientIp()` を `$_SERVER['HTTP_CF_CONNECTING_IP']` に切り替えてください。
- `FROM_EMAIL` はサーバーのドメインと一致するアドレスを使用してください（SPFレコードの関係でメールが迷惑メール判定される場合があります）。

## 入力フィールド一覧

| フィールド | 必須 | バリデーション |
|---|---|---|
| お名前 | Yes | 空でないこと / 最大100文字 |
| 電話番号 | Yes | 数字部分が10〜13桁 |
| メールアドレス | Yes | メール形式 / 最大254文字 |
| 郵便番号 | Yes | 7桁（ハイフンありなし両対応）/ 最大8文字 |
| 住所 | Yes | 空でないこと（郵便番号から自動反映）/ 最大200文字 |
| プライバシーポリシー同意 | Yes | チェック必須 |

## 改版履歴

| バージョン | 日付 | 変更内容 |
|---|---|---|
| v1.0.0 | 2026-04-23 | 初版リリース。PHP 7.4+ 対応。sendmail / CSRF / ハニーポット / レートリミット（10分3回・flock）/ 郵便番号→住所自動反映（zipcloud、5秒タイムアウト）/ プライバシーポリシー同意チェック / 管理者宛+自動返信2通（テンプレート分離）/ ログ記録（JSON Lines、0700/0600 パーミッション）/ `.htaccess` + OS chmod 二重防御 / CLAUDE.md「Web Project Directory Structure Rule」準拠（公開/非公開ディレクトリ分離）/ サクラレビュー Pass |
