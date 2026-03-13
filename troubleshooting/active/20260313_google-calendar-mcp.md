# トラブル: Google カレンダー MCP が再起動後に接続されない

## メタ情報
- 開始日: 2026-03-13
- ステータス: 対応中
- 関連システム: Claude Code MCP / Google Calendar API / OAuth2

## 症状
Claude Code を再起動すると、Google カレンダー MCP が接続されない。
Mac 側で発生中。認証エラーにより読み込めない状態。

## 作業ログ
<!-- 新しいものが上 -->

### [2026-03-13 --:--] 初期調査・既存情報の整理
- 操作: 既存の障害ログ（`~/.claude/reports/google-calendar-mcp-status.md`）から情報を収集
- 結果: 以下の事実を確認
  - 使用パッケージ: `@cocal/google-calendar-mcp`（非公式）
  - トークン `expiry_date`: 1773378569651（2026-03-13 14:09:29 頃、期限切れの可能性）
  - `GOOGLE_OAUTH_TOKEN` 環境変数が settings.json に未設定
  - `mcp-needs-auth-cache.json` が Google Calendar の認証が必要と記録
- 分類: 調査
- タグ: OAuth, token, MCP, 設定確認

## 試行済みアクション一覧
| # | 日時 | 分類 | タグ | 結果 |
|---|------|------|------|------|
| 1 | 2026-03-13 | 調査 | OAuth, token, MCP, 設定確認 | 3つの仮説を特定 |

## 仮説リスト
- [ ] 仮説: パッケージが非標準（`@cocal/google-calendar-mcp`）で、トークンファイルのパスや命名規則が異なる（根拠: 手順書では `@modelcontextprotocol/server-google-calendar` を想定していた）
- [ ] 仮説: トークンが期限切れ（根拠: `expiry_date` が 2026-03-13 14:09 頃で、refresh token による自動更新が機能していない可能性）
- [ ] 仮説: `GOOGLE_OAUTH_TOKEN` 環境変数が未設定のため、パッケージがトークンファイルを見つけられない（根拠: Gmail や GA4 の設定にはこの変数が設定されているが、カレンダーには設定されていない）

## 未実施の対応案
以下は既存障害ログから引き継いだ対応案。優先度順に記載。

### 対応案 B（推奨・低リスク）: settings.json に GOOGLE_OAUTH_TOKEN を追加
```json
"env": {
  "GOOGLE_OAUTH_CREDENTIALS": "<path>/google-oauth-credentials.json",
  "GOOGLE_OAUTH_TOKEN": "<path>/mcp-google-calendar-token.json"
}
```
理由: 最も変更が小さく、他のMCP（Gmail, GA4）と設定を揃えられる。

### 対応案 A（中リスク）: トークンを削除して再認証
```bash
rm ~/.claude/mcp-google-calendar-token.json
# Claude Code を再起動 → OAuth フローが走る
```
理由: トークン期限切れの場合、これで解決する可能性がある。

### 対応案 C（高リスク）: パッケージを公式推奨に切り替える
`@cocal/google-calendar-mcp` → `@modelcontextprotocol/server-google-calendar`
理由: 根本解決になるが、設定変更が大きい。

## 解決策
（解決したら記載）
