# Google カレンダー MCP 障害ログ

最終更新: 2026-03-13 19:31

---

## 現状サマリー

**ステータス: Windows 側は暫定解決 / Mac 側は未対応 / 恒久対策は未実施**

- Windows: 認証フロー実行済み、MCP は動作可能な状態
- Mac: トークン期限切れにより認証エラー中（再認証が必要）
- 両環境共通: GCP がテストモードのため、refresh_token が7日で期限切れになる（恒久対策が必要）

---

## ソースコード解析で判明した事実（2026-03-13）

`@cocal/google-calendar-mcp` v2.6.1 のソースコード（index.js）を解析し、以下を確定。

### トークンファイルのパス解決順序

1. 環境変数 `GOOGLE_CALENDAR_MCP_TOKEN_PATH` が設定されていれば、そのパスを使用
2. 未設定の場合: `$XDG_CONFIG_HOME/google-calendar-mcp/tokens.json`
3. `$XDG_CONFIG_HOME` も未設定の場合: `~/.config/google-calendar-mcp/tokens.json`
4. レガシー: カレントディレクトリの `.gcp-saved-tokens.json`

### 環境変数の正しい仕様

| 環境変数 | 用途 | 備考 |
|----------|------|------|
| `GOOGLE_OAUTH_CREDENTIALS` | OAuth クライアント情報ファイルのパス | 必須 |
| `GOOGLE_CALENDAR_MCP_TOKEN_PATH` | トークンファイルの保存先 | 任意（デフォルトは `~/.config/google-calendar-mcp/tokens.json`） |
| `GOOGLE_ACCOUNT_MODE` | アカウントID | マルチアカウント時に使用 |
| `GOOGLE_OAUTH_TOKEN` | **このパッケージでは使用しない** | 別パッケージ用の変数 |

### トークン自動リフレッシュの仕様

- `refresh_token` が存在すれば、期限切れ時に自動リフレッシュを試みる
- ただし GCP テストモードでは `refresh_token` 自体が7日で期限切れになる
- その場合 `invalid_grant` エラーが発生し、再認証が必要になる

---

## 確認済みの設定

### settings.json（Windows） - 2026-03-13 確認

```json
"google-calendar": {
  "command": "npx",
  "args": ["-y", "@cocal/google-calendar-mcp"],
  "env": {
    "GOOGLE_OAUTH_CREDENTIALS": "C:\\Users\\ueda-\\.claude\\google-oauth-credentials.json",
    "GOOGLE_CALENDAR_MCP_TOKEN_PATH": "C:\\Users\\ueda-\\.claude\\mcp-google-calendar-token.json"
  }
}
```

設定は正しい。`GOOGLE_CALENDAR_MCP_TOKEN_PATH` がソースコードで実装されていることを確認済み。

### settings.json（Mac）

```json
"google-calendar": {
  "command": "npx",
  "args": ["-y", "@cocal/google-calendar-mcp"],
  "env": {
    "GOOGLE_OAUTH_CREDENTIALS": "/Users/uedashinya/.claude/google-oauth-credentials.json"
  }
}
```

Mac 側は `GOOGLE_CALENDAR_MCP_TOKEN_PATH` が未設定。
デフォルトの `~/.config/google-calendar-mcp/tokens.json` を使う、
またはレガシーパス `.gcp-saved-tokens.json` にフォールバックする。

### 認証情報ファイルの状態

| ファイル | Windows | Mac |
|----------|---------|-----|
| `google-oauth-credentials.json` | 存在する | 存在する |
| `mcp-google-calendar-token.json` | 存在する（2026-03-13 認証済み） | 存在する（期限切れ） |

### Windows 側トークンの内容（2026-03-13 19:31 取得）

```json
{
  "normal": {
    "access_token": "ya29...(省略)",
    "refresh_token": "1//0eVo2XON...(省略)",
    "scope": "https://www.googleapis.com/auth/calendar",
    "token_type": "Bearer",
    "refresh_token_expires_in": 604799,
    "expiry_date": 1773397896749
  }
}
```

- access_token 有効期限: 2026-03-13 19:31（約1時間、自動リフレッシュ対象）
- refresh_token 有効期限: 7日間（テストモード）
- refresh_token 期限切れ予定: 2026-03-20 頃

---

## 仮説の検証結果

| 仮説 | 結果 | 根拠 |
|------|------|------|
| パッケージが非標準で問題がある | 棄却 | v2.6.1 で活発にメンテされている。仕様を正しく理解すれば動作する。 |
| トークンが期限切れ | 確定（Mac） | GCP テストモードで refresh_token が7日で期限切れ。Mac 側の expiry_date と一致。 |
| `GOOGLE_OAUTH_TOKEN` 未設定が原因 | 棄却 | このパッケージは `GOOGLE_OAUTH_TOKEN` を使わない。正しくは `GOOGLE_CALENDAR_MCP_TOKEN_PATH`。 |

---

## 対応履歴

| 日時 | 対応内容 | 結果 |
|------|----------|------|
| 2026-03-13 19:31 | Windows で `npx @cocal/google-calendar-mcp auth` を実行 | 認証成功。トークン取得。 |

---

## 残タスク

### 1. Mac 側の再認証（短期対策）

Mac 側で以下を実行する:

```bash
export GOOGLE_OAUTH_CREDENTIALS="/Users/uedashinya/.claude/google-oauth-credentials.json"
npx -y @cocal/google-calendar-mcp auth
```

### 2. GCP テストモード -> 本番モードへの変更（恒久対策）

現在 GCP の OAuth 同意画面が「テスト」モードのため、refresh_token が7日で期限切れになる。

対応手順:
1. [Google Cloud Console](https://console.cloud.google.com) にアクセス
2. プロジェクト `claude-mcp-integration-490103` を選択
3. 「APIとサービス」 > 「OAuth 同意画面」
4. 「アプリを公開」をクリック
5. 確認画面で「確認」を押す

注意:
- 本番モードにすると、Google の審査プロセスが必要になる場合がある
- ただし、自分のアカウントのみで使用する場合は「内部」タイプにすれば審査不要
- 組織アカウント（Google Workspace）でない場合は「内部」タイプは選択できない
- その場合は定期的な再認証を運用でカバーするか、スクリプト化を検討

### 3. Mac 側 settings.json に GOOGLE_CALENDAR_MCP_TOKEN_PATH を追加（推奨）

Mac 側の設定にトークンパスの環境変数を明示的に追加する:

```json
"env": {
  "GOOGLE_OAUTH_CREDENTIALS": "/Users/uedashinya/.claude/google-oauth-credentials.json",
  "GOOGLE_CALENDAR_MCP_TOKEN_PATH": "/Users/uedashinya/.claude/mcp-google-calendar-token.json"
}
```

---

## 参考リンク

- トラブルシューティング詳細ログ: `~/.claude/troubleshooting/active/20260313_google-calendar-mcp.md`
- 手順書（Mac）: `~/.claude/reports/mcp-setup.md`
- 使用中パッケージ: https://www.npmjs.com/package/@cocal/google-calendar-mcp
- GCP プロジェクト: https://console.cloud.google.com/apis/credentials?project=claude-mcp-integration-490103
