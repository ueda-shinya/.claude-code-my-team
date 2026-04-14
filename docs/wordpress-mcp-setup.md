# WordPress MCP 導入手順書

**対象環境:** Windows (PC_PLATFORM=win) / Claude Code  
**対象パッケージ:** `WordPress/mcp-adapter` + `Automattic/mcp-wordpress-remote`  
**認証方式:** Application Password（MCP 専用編集者アカウント）  
**作成日:** 2026-04-14  
**ステータス:** 手順書のみ（実サイト未導入）

---

## 目次

1. [前提条件](#1-前提条件)
2. [WordPress 側作業](#2-wordpress-側作業)
3. [Claude Code 側設定](#3-claude-code-側設定)
4. [疎通確認](#4-疎通確認)
5. [トラブルシューティング](#5-トラブルシューティング)
6. [セキュリティ注意事項](#6-セキュリティ注意事項)

---

## 1. 前提条件

### 1.1 WordPress 環境

| 項目 | 要件 |
|------|------|
| WordPress バージョン | 5.6 以上（Application Password が標準搭載） |
| 通信プロトコル | **HTTPS 必須・HTTP 禁止**（HTTP では Application Password が機能しない。後述 6.1 参照） |
| REST API | 有効（デフォルト有効。無効化プラグインが入っていないこと） |
| パーマリンク設定 | 「投稿名」など `/wp-json/` が動作する設定であること |

### 1.2 ローカル（Windows）環境

| 項目 | 要件 |
|------|------|
| Node.js | 18 以上（LTS 推奨）。`node -v` で確認 |
| npm | 8 以上（Node.js に付属） |
| Claude Code | 最新版。`claude --version` で確認 |

### 1.3 確認コマンド（PowerShell または Git Bash）

```bash
node -v
npm -v
claude --version
```

---

## 2. WordPress 側作業

### 2.1 MCP 専用ユーザーの作成

**なぜ専用アカウントを作るのか:**  
既存の管理者アカウントに Application Password を発行すると、MCP 経由でそのアカウントの全権限が行使される。最小権限の原則に従い、MCP 操作に必要な最低限の権限を持つ専用アカウントを用意する。

**手順:**

1. WordPress 管理画面 → 「ユーザー」→「新規ユーザーを追加」
2. 以下の設定で作成する:

   | 項目 | 設定値 |
   |------|--------|
   | ユーザー名 | `mcp-editor`（任意だが識別しやすい名前） |
   | メールアドレス | 管理者が受け取れるアドレス |
   | 権限グループ | **編集者**（Contributor や Author は投稿のみ。Adminは不要） |

> **注意:** 管理者権限は与えない。編集者権限で投稿・編集・公開・メディアアップロードが可能。

### 2.2 Application Password の発行

**手順:**

1. 管理画面 → 「ユーザー」→「全ユーザー」→ `mcp-editor` を選択
2. ユーザー編集画面の下部にある「Application Passwords」セクションへスクロール
3. 「新しいアプリケーションパスワードの名前」に `Claude MCP` と入力
4. 「新しいアプリケーションパスワードを追加」をクリック
5. **表示されたパスワードをその場でコピーする**（再表示不可）

> **パスワードの形式:** `xxxx xxxx xxxx xxxx xxxx xxxx`（スペース入り 24 文字）  
> **保存先:** この後 `.env` ファイルに記録する。絶対にコードや手順書に直接書かない。

### 2.3 REST API の動作確認

ブラウザまたは curl で以下の URL にアクセスし、JSON が返ることを確認する:

```
https://your-site.example.com/wp-json/wp/v2/
```

`{"name":"サイト名", ...}` のような JSON が返れば正常。

---

## 3. Claude Code 側設定

### 3.1 環境変数ファイルの準備

認証情報を環境変数で管理する。**コードや設定ファイルに直接記述しない。**

`C:\Users\ueda-\.claude\.env` に以下を追記（既存の他の変数は消さないこと）:

```env
# WordPress MCP 設定
WP_SITE_URL=https://your-site.example.com
WP_USERNAME=mcp-editor
WP_APP_PASSWORD=xxxx xxxx xxxx xxxx xxxx xxxx
```

> **注意:**
> - `WP_SITE_URL` は末尾スラッシュなし、HTTPS で指定する
> - `WP_APP_PASSWORD` はスペース入りのままでも動作する（パーセントエンコードは不要）
> - `.env` ファイルは `.gitignore` 対象であること（既存の設定で対応済み）

### 3.2 MCP サーバーのインストール

Git Bash または PowerShell を開き以下を実行:

```bash
npm install -g @automattic/mcp-wordpress-remote
```

インストール確認:

```bash
npx mcp-wordpress-remote --version
```

### 3.3 Claude Code への MCP 設定（settings.json）

Claude Code の設定ファイルに MCP サーバーを登録する。

**設定ファイルの場所（Claude Code 専用）:**

```
C:\Users\ueda-\.claude\settings.json
```

または、プロジェクトスコープで管理する場合は `.mcp.json`（プロジェクトルートに配置）。

> **確認方法:** Claude Code で `claude mcp list` を実行し、登録状況を確認する。

**設定内容（settings.json に追記）:**

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "npx",
      "args": [
        "-y",
        "@automattic/mcp-wordpress-remote"
      ],
      "env": {
        "WP_SITE_URL": "${WP_SITE_URL}",
        "WP_USERNAME": "${WP_USERNAME}",
        "WP_APP_PASSWORD": "${WP_APP_PASSWORD}"
      }
    }
  }
}
```

> **重要:** `env` ブロックの `${変数名}` は **OS 環境変数**を参照する。`~/.claude/.env` を自動で読み込む仕組みではないため、Claude Code 起動前に環境変数をシェルに設定しておく必要がある（後述の 3.4 を参照）。値を直接書かない。

**既存の `mcpServers` が存在する場合:**  
`"wordpress": { ... }` のブロックのみ追加し、他のサーバー設定を壊さないこと。

### 3.4 Claude Code の起動手順（環境変数の読み込み）

`~/.claude/.env` に定義した変数を OS 環境変数としてシェルに読み込んでから Claude Code を起動する。

**Windows PowerShell（永続化する場合）:**

```powershell
# 永続化（ログオン後も有効）
setx WP_SITE_URL "https://your-site.example.com"
setx WP_USERNAME "mcp-editor"
setx WP_APP_PASSWORD "xxxx xxxx xxxx xxxx xxxx xxxx"

# setx 後は PowerShell を再起動してから claude を起動
claude
```

**Windows PowerShell（セッション限定の場合）:**

```powershell
# このウィンドウを閉じると消える
$env:WP_SITE_URL="https://your-site.example.com"
$env:WP_USERNAME="mcp-editor"
$env:WP_APP_PASSWORD="xxxx xxxx xxxx xxxx xxxx xxxx"
claude
```

**Git Bash（.env ファイルから一括読み込み）:**

```bash
# set -a で自動 export を有効にしてから source する
set -a; source ~/.claude/.env; set +a
claude
```

> **注意:** `source ~/.claude/.env` 単体だと `export` 宣言がない変数は子プロセスに引き継がれない。必ず `set -a` / `set +a` を前後に付けること。

---

## 4. 疎通確認

### 4.1 MCP サーバーの認識確認

```bash
claude mcp list
```

`wordpress` が一覧に表示されることを確認。

### 4.2 接続テスト

Claude Code のプロンプトで以下を入力して確認:

```
WordPressの最新5件の投稿タイトルを取得して
```

正常な場合: 投稿タイトルの一覧が返る  
失敗の場合: エラーメッセージが返る（[トラブルシューティング](#5-トラブルシューティング) を参照）

### 4.3 書き込みテスト（任意）

テスト用の下書き投稿を作成して確認:

```
「MCPテスト投稿」というタイトルで下書き投稿を作成して
```

確認後、WordPress 管理画面から当該下書きを手動削除する。

---

## 5. トラブルシューティング

### 5.1 接続できない / タイムアウト

**確認事項:**

1. `WP_SITE_URL` が HTTPS になっているか
2. URL の末尾にスラッシュが入っていないか（`https://example.com/` → NG）
3. WordPress の REST API が有効か（`/wp-json/wp/v2/` にアクセスして確認）
4. ファイアウォール・セキュリティプラグインが REST API をブロックしていないか

```bash
# REST API の疎通確認（Git Bash）
curl -s https://your-site.example.com/wp-json/wp/v2/ | head -c 200
```

### 5.2 401 Unauthorized

**原因と対処:**

| 原因 | 対処 |
|------|------|
| Application Password が誤っている | WordPress でパスワードを再発行し `.env` を更新 |
| ユーザー名が誤っている | `WP_USERNAME` をユーザー名（メールではなく） に設定 |
| Application Password 機能が無効 | プラグインやコードで無効化されていないか確認 |
| HTTPS でない（HTTP） | HTTPS に変更。HTTP での接続は禁止。Application Password は HTTP では機能しない（6.1 参照） |

### 5.3 403 Forbidden

**原因と対処:**

| 原因 | 対処 |
|------|------|
| 権限不足 | `mcp-editor` アカウントの権限グループを確認（編集者であること） |
| セキュリティプラグインがブロック | Wordfence 等の設定で REST API を許可する |
| IP 制限 | サーバー側の IP 制限設定を確認 |

### 5.4 `npx mcp-wordpress-remote` が見つからない

```bash
# グローバルインストールの確認
npm list -g @automattic/mcp-wordpress-remote

# 見つからない場合は再インストール
npm install -g @automattic/mcp-wordpress-remote

# Node.js の PATH が通っているか確認
where node
where npx
```

### 5.5 環境変数が読み込まれない

```bash
# .env の内容確認（パスワードが出力されるので注意）
# Git Bash（set -a で export を有効にしてから source する）
set -a; source ~/.claude/.env; set +a
echo $WP_SITE_URL

# 変数が空の場合、.env の記述形式を確認（等号前後にスペースを入れない）
# NG: WP_SITE_URL = https://...
# OK: WP_SITE_URL=https://...
```

> **補足:** `source ~/.claude/.env` 単体では `export` 宣言のない変数が子プロセス（Claude Code）に引き継がれない。`set -a` / `set +a` を使うこと。

### 5.6 Windows 特有の問題

| 問題 | 対処 |
|------|------|
| パスの区切り文字エラー | 設定ファイルのパスはバックスラッシュでなくスラッシュを使用 |
| `npx` が認識されない | PowerShell を管理者権限で実行し `npm install -g @automattic/mcp-wordpress-remote` を再実行 |
| 環境変数が反映されない | Claude Code を完全に終了してから再起動 |

---

## 6. セキュリティ注意事項

### 6.1 HTTPS の厳守（最重要）

**HTTP での接続は禁止する。**

Application Password は HTTP 通信では平文で送信される。中間者攻撃により認証情報が盗まれるリスクがある。  
**必ず HTTPS（SSL/TLS）が有効なサイトのみに接続すること。**

> **CVE-2025-11749 教訓:**  
> 2025年に報告された WordPress 関連の脆弱性事例では、不適切な認証情報の管理と権限過多の組み合わせが被害を拡大させた。Application Password の適切な権限スコープと HTTPS の組み合わせが基本的な防衛ライン。

### 6.2 権限の最小化

- MCP 専用アカウントは**編集者権限のみ**（管理者権限は与えない）
- 使用しない機能が必要な権限を要求する場合は、その機能の利用を見直す
- 定期的にアクセスログを確認し、意図しない操作がないか監視する

### 6.3 認証情報の管理

| すること | してはいけないこと |
|---------|------------------|
| `.env` ファイルで管理 | コードへの直接記述 |
| `.gitignore` に `.env` を含める | `settings.json` への直接記述 |
| Application Password の定期的な再発行 | 不要になった Password の放置 |
| 利用者を限定した権限グループ設定 | 複数のシステムで同じ Password を使い回す |

### 6.4 Application Password のスコープ

WordPress 6.2 以降では Application Password に**スコープ制限**が設定できる（プラグインによっては対応）。可能であれば、MCP 用パスワードを REST API のみに制限することを推奨する。

### 6.5 不要になった際の対処

MCP の利用を停止する場合は、必ず WordPress 管理画面から該当の Application Password を**失効（Revoke）**させること。

```
管理画面 → ユーザー → mcp-editor → Application Passwords → 該当パスワードの「削除」
```

### 6.6 ログ・監視

- WordPress の REST API アクセスログを定期確認する（`WooCommerce`、`Wordfence` 等で可視化可能）
- 異常なアクセスパターン（深夜の大量リクエスト等）を検知したら即座に Application Password を失効させる

---

## 付録: 設定ファイルの完成形イメージ

### .env（記述例・値はダミー）

```env
# WordPress MCP 設定
WP_SITE_URL=https://your-site.example.com
WP_USERNAME=mcp-editor
WP_APP_PASSWORD=AbCd EfGh IjKl MnOp QrSt UvWx
```

### settings.json（mcpServers 部分のみ）

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote"],
      "env": {
        "WP_SITE_URL": "${WP_SITE_URL}",
        "WP_USERNAME": "${WP_USERNAME}",
        "WP_APP_PASSWORD": "${WP_APP_PASSWORD}"
      }
    }
  }
}
```

> **再掲:** `${変数名}` は OS 環境変数を参照する。Claude Code 起動前に 3.4 の手順で環境変数を設定しておくこと。

---

*本手順書は実サイトへの導入前の参照用です。導入の際は必ずシンヤさんの承認を得てから実施してください。*
