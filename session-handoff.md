# セッション引き継ぎ

## 再起動前の状況
Google カレンダー MCP、Windows（VSCode拡張機能）での動作確認済み。

## 再起動後にやること
Mac 側の MCP 設定を行う。
- `~/.claude/.mcp.json` をプロジェクトルートに配置するだけで解決するはず（Windowsと同じ対処）

## 関連情報
- 障害ログ: `~/.claude/troubleshooting/active/20260313_google-calendar-mcp.md`（Mac対応完了後に resolved/ へ移動）
- 恒久対策（任意）: GCP の OAuth 同意画面をテスト→本番モードに変更
  プロジェクト: `claude-mcp-integration-490103`
