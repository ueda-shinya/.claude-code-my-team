# sendmail-form-base

PHPお問い合わせフォームのベーステンプレートです。
新規サイトに導入する際は「カスタマイズ手順」に沿って設定ファイルとテンプレートを書き換えるだけで使い始められます。

## ディレクトリ構成

```
sendmail-form-base/
├── index.html               ← トップページ（「お問い合わせはこちらから」ボタン）
├── contact.php              ← フォーム本体（PHPでCSRFトークンを埋め込む）
├── submit.php               ← 送信処理（バリデーション・メール送信・ログ）
├── thanks.html              ← サンクスページ（送信完了後にリダイレクト）
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
- `mb_encode_mimeheader` 関数（`php-mbstring` 拡張）
- `mail()` 関数が使えるサーバー環境（`sendmail` または `postfix` が必要）
- `logs/` ディレクトリへの書き込み権限

## メール送信方式についての注意

本テンプレートは `mail()` 関数を直接使い、件名・本文・ヘッダーをすべて自前でエンコードしています。
`mb_send_mail()` は使用していません。理由:

- サーバー側で `mb_language('Japanese')` が設定されている環境（さくらインターネット等の一部レンタルサーバー）で
  `mb_send_mail()` を呼ぶと、base64 エンコード済みの本文をさらに ISO-2022-JP に変換しようとして
  二重変換となり、受信側で文字化けします。
- 「文字化けするから `mb_send_mail` に戻そう」という判断は逆効果です。`mail()` 直叩きのまま使ってください。

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

### 5. サンクスページ

送信完了後に `thanks.html` へリダイレクトします（実装済み）。

- **JavaScript 有効環境**: `form.js` が AJAX で送信し、成功時に `window.location.replace('./thanks.html')` でリダイレクト（ブラウザ履歴を汚さない）
- **JavaScript 無効環境**: 通常の POST 送信が走り、`submit.php` が成功時に 303 See Other で `thanks.html` へリダイレクト（フォールバック対応済み）。失敗時は `contact.php?error=1` に戻りエラーメッセージを表示する
- `submit.php` は AJAX か通常 POST かを `X-Requested-With` ヘッダーで判定して応答を分岐する（JSON / リダイレクト）
- `thanks.html` のページタイトル・文言はプロジェクトに合わせて書き換えてください
- noindex/nofollow メタタグ設定済みのため、検索エンジンにインデックスされません

リダイレクト先のURLを変更する場合は `form.js` の `window.location.replace('./thanks.html')` を修正してください。

## スパム対策

以下の3層で対策しています。

| 対策 | 実装箇所 |
|---|---|
| CSRFトークン | `contact.php`（発行）/ `submit.php`（検証） |
| ハニーポット（`url_homepage` フィールド: 入力があればボット判定） | `contact.php` / `submit.php` |
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
| 電話番号 | Yes | 半角数字・ハイフンのみ / 数字部分が10〜11桁 |
| メールアドレス | Yes | メール形式 / 最大254文字 |
| 郵便番号 | Yes | 7桁（ハイフンありなし両対応）/ 最大8文字 |
| 住所 | Yes | 空でないこと（郵便番号から自動反映）/ 最大200文字 |
| プライバシーポリシー同意 | Yes | チェック必須 |

## 改版履歴

| バージョン | 日付 | 変更内容 |
|---|---|---|
| v1.6.0 | 2026-04-24 | JS無効フォールバック対応・CSS DRY化・ブラウザ履歴汚染防止。(1) `submit.php` に AJAX 判定（`X-Requested-With` / `Accept` ヘッダー）を追加。JS無効の通常POSTは成功時に 303 リダイレクト（`thanks.html`）、失敗時は `contact.php?error=1` にリダイレクト。(2) `contact.php` に `?error=1` 受け取り時のエラーメッセージ表示を追加。(3) `form.js` の fetch に `X-Requested-With: XMLHttpRequest` ヘッダーを付与。リダイレクト箇所を `window.location.href` → `window.location.replace()` に変更（戻るボタン再送信防止）。(4) `site.css` の `p-top-hero` と `p-thanks` の共通スタイルをセレクタグループ化で DRY 化（`@media` も同様に整理）。 |
| v1.5.0 | 2026-04-24 | サンクスページ追加。`thanks.html` 新規作成（noindex・FLOCSS `p-thanks` 系クラス・レスポンシブ対応）。`form.js` 送信成功時を `showFormMessage` → `window.location.href = './thanks.html'` リダイレクトに変更。`site.css` に `p-thanks` 系スタイルを追加。`submit.php` 変更なし。 |
| v1.4.0 | 2026-04-25 | 非公開ディレクトリ .htaccess に Options -Indexes を追加（CLAUDE.md「Web Project Directory Structure Rule」準拠）。ディレクトリリスティング防止の二重防御を確立。 |
| v1.3.0 | 2026-04-24 | `index.php` を `contact.php` にリネーム。トップページ `index.html` を新規追加（「お問い合わせはこちらから」ボタン → `contact.php` 遷移）。動線: `index.html` → `contact.php` → `submit.php` の3ステップに整理。 |
| v1.2.0 | 2026-04-24 | FLOCSS 準拠化 + コード最適化。(1) `form.css` 全クラスを FLOCSS プレフィックス付きに改名（`l-form-wrap` / `c-form-group` / `c-required-badge` / `c-field-error` / `c-btn--secondary` / `u-hp-field` 等）。(2) `contact.php` のクラス参照を全更新。(3) `form.js` の動的クラス生成（`showFormMessage` / `hideFormMessage`）を更新。(4) `submit.php` の `sendMail()` 内 `mb_encode_mimeheader()` 重複呼び出しを解消（先にエンコード変数を生成してインジェクション検査に使い回す）。(5) `validate()` の無効文字エラーメッセージ重複防止（フラグ方式で全フィールド走査後に1件のみ追加）。(6) README に「メール送信方式についての注意」セクション追加（`mb_send_mail` に戻してはいけない理由を明記）。 |
| v1.1.0 | 2026-04-24 | plapendual 案件の不具合知見を反映。(1) `mb_language('Japanese')` 削除 + `mb_send_mail()` → `mail()` 直叩きに切替（mb_language 設定下での ISO-2022-JP 二重変換・文字化け防止）。(2) `buildMailFromTemplate()` Subject 抽出の正規表現を `(.+)` から `([^\r\n]+)` に修正（`s` フラグ + 貪欲マッチによる Subject 吸収バグ修正）。(3) `assertNoHeaderInjection()` に URLエンコード済み改行（`%0A/%0D/%00`）チェックを追加。(4) `validate()` の全フィールドチェックにも URLエンコード改行チェックを追加。(5) MIME folding 検査（`\r\n[\t ]` 除去）を `sendMail()` 内の encoded 値の後検査に追加。 |
| v1.0.0 | 2026-04-23 | 初版リリース。PHP 7.4+ 対応。sendmail / CSRF / ハニーポット / レートリミット（10分3回・flock）/ 郵便番号→住所自動反映（zipcloud、5秒タイムアウト）/ プライバシーポリシー同意チェック / 管理者宛+自動返信2通（テンプレート分離）/ ログ記録（JSON Lines、0700/0600 パーミッション）/ `.htaccess` + OS chmod 二重防御 / CLAUDE.md「Web Project Directory Structure Rule」準拠（公開/非公開ディレクトリ分離）/ サクラレビュー Pass |
