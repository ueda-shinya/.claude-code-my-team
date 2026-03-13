# Google系 MCPサーバー導入手順書

作成日: 2026-03-13
作成者: カナタ（エージェントビルダー）

---

## 1. 選定結果サマリー

| 優先度 | サービス | 推奨MCPサーバー | ランタイム | 備考 |
|--------|----------|----------------|-----------|------|
| 1 | Google カレンダー | `@modelcontextprotocol/server-google-calendar` | Node.js (npx) | 公式/コミュニティで最も安定 |
| 2 | Gmail | `@modelcontextprotocol/server-gmail` | Node.js (npx) | カレンダーと同じOAuth認証を共有可能 |
| 3 | GA4 | `@modelcontextprotocol/server-google-analytics` またはコミュニティ製 | Node.js (npx) | GA4 Data API v1 を使用 |

### 選定の考え方

Google系MCPサーバーは複数の選択肢がありますが、以下の基準で選定しています：

1. **公式 MCP サーバーリポジトリ**（github.com/modelcontextprotocol/servers）にあるものを最優先
2. なければ **Anthropic 公式** のものを優先
3. それもなければ **コミュニティ製で Star 数が多い**ものを採用

**重要**: MCPサーバーのエコシステムは急速に変化しています。
インストール前に必ず以下で最新の公式推奨を確認してください：

- https://github.com/modelcontextprotocol/servers
- https://modelcontextprotocol.io/docs/servers

---

## 2. 前提条件の確認

以下がインストール済みであることを確認してください。

```bash
# Node.js (v18以上推奨)
node --version

# npm
npm --version

# npx
which npx
```

もし Node.js が未インストールの場合：

```bash
brew install node
```

---

## 3. Google Cloud Console での OAuth 設定（共通・最初に1回だけ）

3つのサービスすべてで Google OAuth 2.0 認証が必要です。
1つのプロジェクト・1つの OAuth クライアントで3サービス分をまかなえます。

### Step 3-1: GCP プロジェクト作成

1. https://console.cloud.google.com/ にアクセス
2. 上部のプロジェクトセレクタ → 「新しいプロジェクト」
3. プロジェクト名: `claude-mcp-integration`（任意）
4. 「作成」をクリック

### Step 3-2: 必要な API を有効化

Google Cloud Console の「API とサービス」→「ライブラリ」で以下を検索して有効化：

| API | 用途 |
|-----|------|
| Google Calendar API | カレンダーの読み書き |
| Gmail API | メールの読み取り・下書き作成 |
| Google Analytics Data API | GA4 データ取得 |

### Step 3-3: OAuth 同意画面の設定

1. 「API とサービス」→「OAuth 同意画面」
2. User Type: 「外部」を選択（個人アカウントの場合）
3. アプリ名: `Claude MCP`（任意）
4. ユーザーサポートメール: シンヤさんのメールアドレス
5. スコープの追加:
   - `https://www.googleapis.com/auth/calendar`（カレンダー読み書き）
   - `https://www.googleapis.com/auth/calendar.readonly`（カレンダー読み取り）
   - `https://www.googleapis.com/auth/gmail.readonly`（メール読み取り）
   - `https://www.googleapis.com/auth/gmail.compose`（下書き作成）
   - `https://www.googleapis.com/auth/analytics.readonly`（GA4 読み取り）
6. テストユーザーにシンヤさんのGoogleアカウントを追加
7. 保存

### Step 3-4: OAuth クライアント ID の作成

1. 「API とサービス」→「認証情報」→「認証情報を作成」→「OAuth クライアント ID」
2. アプリケーションの種類: 「デスクトップ アプリ」
3. 名前: `Claude Code MCP`
4. 「作成」をクリック
5. **クライアント ID** と **クライアントシークレット** をメモ
6. JSON をダウンロード → `/Users/uedashinya/.claude/google-oauth-credentials.json` として保存

```bash
# ダウンロードした認証情報を配置
mv ~/Downloads/client_secret_*.json /Users/uedashinya/.claude/google-oauth-credentials.json
```

**注意**: このファイルには秘密情報が含まれます。Gitにコミットしないでください。
`.gitignore` に以下を追加することを推奨します：

```
google-oauth-credentials.json
google-oauth-token*.json
*.credentials.json
```

---

## 4. settings.json の設定

`/Users/uedashinya/.claude/settings.json` に以下の形式で追加します。

**注意**: 以下はテンプレートです。実際のパッケージ名・引数は
インストールしたMCPサーバーのREADMEに従って調整してください。

```json
{
  "mcpServers": {
    "google-calendar": {
      "command": "npx",
      "args": [
        "-y",
        "@modelcontextprotocol/server-google-calendar"
      ],
      "env": {
        "GOOGLE_OAUTH_CREDENTIALS": "/Users/uedashinya/.claude/google-oauth-credentials.json",
        "GOOGLE_OAUTH_TOKEN": "/Users/uedashinya/.claude/google-oauth-token-calendar.json"
      }
    },
    "gmail": {
      "command": "npx",
      "args": [
        "-y",
        "@modelcontextprotocol/server-gmail"
      ],
      "env": {
        "GOOGLE_OAUTH_CREDENTIALS": "/Users/uedashinya/.claude/google-oauth-credentials.json",
        "GOOGLE_OAUTH_TOKEN": "/Users/uedashinya/.claude/google-oauth-token-gmail.json"
      }
    },
    "google-analytics": {
      "command": "npx",
      "args": [
        "-y",
        "@modelcontextprotocol/server-google-analytics"
      ],
      "env": {
        "GOOGLE_OAUTH_CREDENTIALS": "/Users/uedashinya/.claude/google-oauth-credentials.json",
        "GOOGLE_OAUTH_TOKEN": "/Users/uedashinya/.claude/google-oauth-token-ga4.json",
        "GA4_PROPERTY_ID": "properties/XXXXXXXXX"
      }
    }
  }
}
```

---

## 5. 初回認証フロー（シンヤさんが行う作業）

各MCPサーバーを初めて使うとき、以下の手順でOAuth認証を完了させます。

1. Claude Code を起動する
2. カレンダー関連の質問をする（例:「今日の予定を教えて」）
3. ターミナルにOAuth認証用のURLが表示される
4. そのURLをブラウザで開く
5. Googleアカウントでログイン → アクセスを許可
6. 認証コードをターミナルに貼り付け（または自動コールバック）
7. トークンファイルが自動生成される

**Google カレンダー → Gmail → GA4 の順で1回ずつ行います。**

2回目以降はトークンが保存されているため認証不要です。

---

## 6. GA4 プロパティIDの確認方法

1. https://analytics.google.com/ にアクセス
2. 左下の歯車アイコン（管理）をクリック
3. プロパティ列の「プロパティの設定」をクリック
4. 右上に表示される「プロパティ ID」をメモ（数字のみ）
5. settings.json の `GA4_PROPERTY_ID` に `properties/数字` の形式で設定

---

## 7. 導入の推奨手順（実行順序）

### Phase 1: 準備（約15分）
```
[ ] Node.js / npm / npx のインストール確認
[ ] Google Cloud Console でプロジェクト作成
[ ] 3つの API を有効化
[ ] OAuth 同意画面の設定
[ ] OAuth クライアント ID の作成・JSONダウンロード
[ ] 認証情報JSONを ~/.claude/ に配置
[ ] .gitignore に認証ファイルを追加
```

### Phase 2: MCPサーバー調査・選定（約10分）
```
[ ] npm search / GitHub で最新のMCPサーバーを検索
[ ] 各パッケージのREADMEを確認して設定値を特定
[ ] settings.json を作成
```

### Phase 3: カレンダーの導入・動作確認（約5分）
```
[ ] settings.json にカレンダーの設定のみ追加
[ ] Claude Code を再起動
[ ] 「今日の予定を教えて」で動作確認
[ ] OAuth認証を完了
```

### Phase 4: Gmail の導入・動作確認（約5分）
```
[ ] settings.json に Gmail の設定を追加
[ ] Claude Code を再起動
[ ] 「未読メールを確認して」で動作確認
[ ] OAuth認証を完了
```

### Phase 5: GA4 の導入・動作確認（約10分）
```
[ ] GA4 のプロパティIDを確認
[ ] settings.json に GA4 の設定を追加
[ ] Claude Code を再起動
[ ] 「先月のPV数を教えて」で動作確認
[ ] OAuth認証を完了
```

---

## 8. トラブルシューティング

### MCPサーバーが起動しない
```bash
npx @modelcontextprotocol/server-google-calendar
```

### OAuth認証でエラーが出る
- 「redirect_uri_mismatch」→ OAuth クライアントのタイプが「デスクトップ アプリ」か確認
- 「access_denied」→ テストユーザーに自分のアカウントが追加されているか確認
- 「invalid_scope」→ 必要なAPIが有効化されているか確認

### トークンの期限切れ
```bash
rm /Users/uedashinya/.claude/google-oauth-token-*.json
# Claude Code を再起動 → 再認証が求められる
```

---

## 9. セキュリティに関する注意事項

1. `google-oauth-credentials.json` と `google-oauth-token-*.json` は絶対にGitにコミットしない
2. 必要なスコープだけを許可する
3. OAuth同意画面が「テスト」モードの場合、テストユーザーに登録したアカウントしか使えない

---

## 10. 重要な補足

本手順書のMCPサーバーのパッケージ名は2026年3月時点の推定に基づくテンプレートです。
**実際のインストール前に以下を必ず確認してください**：

- 公式リポジトリ: https://github.com/modelcontextprotocol/servers
- MCP公式サイト: https://modelcontextprotocol.io
- npm検索: `npm search @modelcontextprotocol google`

---

以上
