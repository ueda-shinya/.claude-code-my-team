# OU Basic Guard - 要件定義

## 概要

wp-admin・wp-login.php に PHP レベルの Basic 認証をかける WordPress 通常プラグイン。
IDやパスワードを忘れてロックアウトされるリスクに対し、FTP不要・プラグイン有効のままで認証を無効化できる仕組みを持つ。

---

## 背景・課題

### 旧構成（mu-plugin）の問題点
- mu-plugin は WordPress 管理画面から無効化できない
- IDやパスワードを忘れた場合、FTPでディレクトリをリネームする必要があった
- .htaccess ベース認証を使っていたため、サーバー環境依存の問題が発生しやすかった

### 解決方針
- 通常プラグインに移行し、管理画面から設定できるようにする
- PHP レベルの認証に統一（.htaccess 非依存）
- 緊急時はキーファイルを置くだけで認証をスキップできる仕組みを設ける

---

## 機能要件

### 1. Basic 認証

| 項目 | 仕様 |
|---|---|
| 実装方式 | PHP レベル（`init` フック 優先度0） |
| 保護対象 | wp-login.php / /wp-admin/ のいずれか、または両方（設定可） |
| 認証方式 | ユーザー名 + パスワード（bcrypt ハッシュ保存） |
| 除外対象 | `wp-admin/admin-ajax.php`（WordPress標準AJAX処理への影響防止） |

### 2. 緊急無効化（キーファイル方式）

- `wp-content/ou-basic-guard-disable.key` が存在する場合、認証を完全スキップ
- ファイルの中身は問わない（ダミーテキストでOK）
- プラグイン自体は有効のまま認証だけ無効化できる
- キーファイルを削除すれば認証が再開される

**選定理由：**
ディレクトリリネーム方式だと「FTPが使える環境」が前提になる。キーファイル方式であれば、FTPでファイルを1つ置くだけで済み、プラグインの有効/無効状態に影響しない。

### 3. ローカル環境の自動スキップ

以下のいずれかに該当する場合は認証をスキップ（チェック順に評価）：

1. `WP_ENVIRONMENT_TYPE` 定数が `'local'` または `'development'`
2. `WP_LOCAL_DEV` 定数が `true`
3. HTTP_HOST が `localhost` / `127.0.0.1` / `.local` / `.test` / `.localhost` で終わる

> ※ HTTP_HOST はクライアント制御可能な値のため補助的な判定。WP_ENVIRONMENT_TYPE / WP_LOCAL_DEV が優先。

### 4. PHP-FPM 環境対応

PHP-FPM 環境では `$_SERVER['PHP_AUTH_USER']` が取れない場合がある。
フォールバックとして `HTTP_AUTHORIZATION` / `REDIRECT_HTTP_AUTHORIZATION` から取得する。

.htaccess への追記例（サーバー互換性）：
```
SetEnvIf Authorization "(.+)" HTTP_AUTHORIZATION=$1
```

### 5. .htaccess 変更前の自動バックアップ

- 有効化時、および `.htaccess` を変更する操作の直前に自動バックアップを取得する
- バックアップファイル名：`.htaccess-ou-basic-guard-backup`（拡張子なし。Apache の `.ht*` ブロック対象となり Web 非公開。このプラグインによるバックアップと識別できるファイル名）
- バックアップファイルが既に存在する場合はスキップ（上書きしない）
- バックアップ対象：ルートの `.htaccess`（`wp-admin/.htaccess` は対象外）

### 6. 旧 mu-plugin からの移行サポート

- 有効化時に .htaccess の旧ブロック（`# BEGIN OU_BASIC_GUARD`）を検知
- admin_notices で通知
- 設定画面から手動で削除できる

---

## 管理画面要件

**場所：** 設定 > Basic Guard

### 現在の状態テーブル

| 項目 | 表示内容 |
|---|---|
| 認証状態 | 有効 / 無効 / 設定不完全 / スキップ中（キーファイルあり）/ スキップ中（ローカル環境） |
| キーファイルの状態 | 存在する（認証スキップ中）/ 存在しない（通常動作）+ パス表示 |
| ローカル環境判定 | ローカル環境と判定 / 本番環境 |
| 緊急無効化の方法 | キーファイルのパスと手順を表示 |

### 設定フォーム

- Basic 認証の有効化チェックボックス
- 保護対象（ラジオ：wp-login.php のみ / /wp-admin/ のみ / 両方）
- ユーザー名（テキスト）
- パスワード（平文不保存・bcrypt で保存）

---

## セキュリティ要件

| 項目 | 対応 |
|---|---|
| CSRF 対策 | wp_nonce_field / check_admin_referer |
| XSS 対策 | esc_html / esc_attr / esc_url |
| タイミング攻撃対策 | hash_equals() によるユーザー名比較 |
| パスワード保存 | bcrypt（PASSWORD_BCRYPT）。WP標準の phpass より強度が高いため意図的に使用 |
| パスインジェクション対策 | SCRIPT_NAME のみ使用（PHP_SELF フォールバックなし） |
| base64 デコード | strict モード（不正な文字列は即座に拒否） |
| HTTPS | HTTP 環境では管理画面に警告表示 |

---

## 非機能要件

- PHP 8.0 以上
- WordPress 5.9 以上
- .htaccess 非依存（Apache / Nginx どちらでも動作）
- アンインストール時に全オプションを削除（uninstall.php）

---

## バージョン履歴

| バージョン | 変更内容 |
|---|---|
| v3.1.0 | .htaccess 変更前の自動バックアップ機能を追加 |
| v3.0.0 | 通常プラグインに移行。キーファイル方式・ローカルスキップ・admin-ajax.php除外・セキュリティ強化 |
| v2.0.0 | （欠番・要件確認前に誤実装したため破棄） |
| v1.x | mu-plugin 版（.htaccess ベース） |
