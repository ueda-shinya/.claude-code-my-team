# Google カレンダー MCP 障害ログ

最終更新: 2026-03-13

---

## 現状サマリー

**ステータス: 未解決**

Google カレンダー MCP が認証エラーで読み込めない状態。
Mac 側で発生中。Windows 側での再現・対応を検討中。

---

## 確認済みの設定

### settings.json の MCP 設定（Mac）

```json
"google-calendar": {
  "command": "npx",
  "args": ["-y", "@cocal/google-calendar-mcp"],
  "env": {
    "GOOGLE_OAUTH_CREDENTIALS": "/Users/uedashinya/.claude/google-oauth-credentials.json"
  }
}
```

**補足**: `GOOGLE_OAUTH_TOKEN` の環境変数は設定されていない。
トークンファイルは `mcp-google-calendar-token.json` として `~/.claude/` 直下に存在。

### 認証情報ファイルの状態（Mac）

| ファイル | 状態 |
|---|---|
| `~/.claude/google-oauth-credentials.json` | 存在する |
| `~/.claude/mcp-google-calendar-token.json` | 存在する（詳細は下記） |

### トークンの内容（一部）

```json
{
  "scope": "https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/calendar.readonly",
  "token_type": "Bearer",
  "expiry_date": 1773378569651  // ← 2026-03-13 14:09:29（期限切れの可能性あり）
}
```

### mcp-needs-auth-cache.json

```json
{
  "claude.ai Gmail": { "timestamp": 1773389365209 },
  "claude.ai Google Calendar": { "timestamp": 1773389363902 }
}
```

→ Claude が Google Calendar の認証が必要と判断している。

---

## 問題の仮説

1. **パッケージが非標準** : `@cocal/google-calendar-mcp` を使用中。
   - 手順書では `@modelcontextprotocol/server-google-calendar` を想定していた
   - `@cocal` パッケージの仕様（トークンファイルのパスや命名）が異なる可能性

2. **トークン期限切れ** : `expiry_date` が 2026-03-13 14:09 頃で、すでに期限切れの可能性がある。

3. **`GOOGLE_OAUTH_TOKEN` 環境変数が未設定** : Gmail や GA4 の設定と違い、カレンダーの設定には `GOOGLE_OAUTH_TOKEN` パスが指定されていない。パッケージ側がデフォルトパスを期待している可能性。

---

## 試すべき対応（未実施）

### 対応案 A: トークンを削除して再認証

```bash
rm ~/.claude/mcp-google-calendar-token.json
# Claude Code を再起動 → カレンダー操作を実行 → OAuth フローが走る
```

### 対応案 B: settings.json に GOOGLE_OAUTH_TOKEN を追加

```json
"google-calendar": {
  "command": "npx",
  "args": ["-y", "@cocal/google-calendar-mcp"],
  "env": {
    "GOOGLE_OAUTH_CREDENTIALS": "/Users/uedashinya/.claude/google-oauth-credentials.json",
    "GOOGLE_OAUTH_TOKEN": "/Users/uedashinya/.claude/mcp-google-calendar-token.json"
  }
}
```

### 対応案 C: パッケージを公式推奨に切り替える

```json
"google-calendar": {
  "command": "npx",
  "args": ["-y", "@modelcontextprotocol/server-google-calendar"],
  "env": {
    "GOOGLE_OAUTH_CREDENTIALS": "/Users/uedashinya/.claude/google-oauth-credentials.json",
    "GOOGLE_OAUTH_TOKEN": "/Users/uedashinya/.claude/google-oauth-token-calendar.json"
  }
}
```

---

## Windows 側での対応について

Mac 側の認証情報ファイルを Windows に持ち込む場合の注意：

- `google-oauth-credentials.json` : GCP コンソールでダウンロードした OAuth クライアント情報。同じファイルを使い回せる。
- `mcp-google-calendar-token.json` : Mac でのアクセストークン。Windows では**パスが異なる**ため再認証が必要。

Windows 側の設定では、パスを Windows 形式に変更すること：

```json
"GOOGLE_OAUTH_CREDENTIALS": "C:\\Users\\<ユーザー名>\\.claude\\google-oauth-credentials.json"
```

---

## 参考リンク

- 手順書（Mac）: `~/.claude/reports/mcp-setup.md`
- 使用中パッケージ: https://www.npmjs.com/package/@cocal/google-calendar-mcp
- 公式 MCP リポジトリ: https://github.com/modelcontextprotocol/servers
