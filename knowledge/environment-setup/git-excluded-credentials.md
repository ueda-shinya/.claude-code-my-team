# Git管理外の認証ファイル一覧

## 概要

セキュリティ上の理由で `.gitignore` に登録されており、Git同期されないファイルがある。
新しいPCで環境構築する際は、これらを手動でコピーする必要がある。

## 対象ファイル一覧

| ファイル | 用途 | 手動コピー必要 |
|---|---|---|
| `~/.claude/.env` | APIキー（GEMINI_API_KEY等） | 必要 |
| `~/.claude/google-oauth-credentials.json` | Google OAuth クライアントID・シークレット | 必要 |
| `~/.claude/mcp-google-calendar-token.json` | Google Calendar 用リフレッシュトークン | 必要（※） |
| `~/.claude/settings.json` | Claude Code のMCP設定等 | 必要 |
| `~/.claude/.credentials.json` | その他認証情報 | 必要 |

※ `mcp-google-calendar-token.json` はコピーする代わりに、新しいPCで `npx @cocal/google-calendar-mcp auth` を実行して再認証する方法もある。

## コピー手段

以下のいずれかで安全にコピーする（メール添付は非推奨）:
- USBメモリ経由
- OneDrive / Google Drive 等のクラウドストレージ（コピー後は削除）
- セキュアなファイル転送ツール

## 注意事項

- **これらのファイルを GitHub に上げてはいけない。** 不正利用のリスクがある
- `.gitignore` からこれらを除外しないこと
- GCPがテストモードの場合、`refresh_token` は7日で失効する。再認証が必要になったらトラブルシューティング記録 `troubleshooting/active/20260313_google-calendar-mcp.md` を参照

## `.env` に必要なキー一覧

新しいPCで `.env` を手動コピーした後、以下のキーが揃っているか確認すること。

| キー | 用途 |
|---|---|
| `GEMINI_API_KEY` | Gemini 画像生成 |
| `YOUTUBE_API_KEY` | YouTube ダイジェスト |
| `ANTHROPIC_API_KEY` | メール自動化・LINE WORKS Bot |
| `LINE_WORKS_BOT_ID` 等 | LINE WORKS Bot 認証 |
| `MAIL_HOST` 等 | メール自動化（Xserver IMAP/SMTP） |
| `NOTION_API_TOKEN` | Notion API 共通 |
| `NOTION_CRM_DB_ID` | Notion 顧客リスト DB |
| `NOTION_PROJECT_DB_ID` | Notion 案件リスト DB |
| `NOTION_MINUTES_DB_ID` | Notion 議事録 DB |
| `NOTION_LEDGER_DB_ID` | Notion 見積・請求台帳 DB |
| `NOTION_GA4_DB_ID` | Notion GA4 日次レポート DB（`32fb7112-f5f8-8108-aa71-dc5865243404`） |

### 注意：`.env` 各行は必ず改行で終わること

2026-03-27 に発生した事例：`NOTION_LEDGER_DB_ID` の行末に改行がなく `NOTION_GA4_DB_ID` と連結されてしまい、ブリーフィングの Notion 書き込みが `SKIP` になった。`.env` に追記する際は `echo 'KEY=VALUE' >> ~/.claude/.env` ではなくエディタで開いて追記するのが安全。

## 発生した事例

### 2026-03-16: Windows環境でGoogle Calendar CLIフォールバック失敗

- **状況:** Windows の Claude Code 環境で `google-oauth-credentials.json` と `mcp-google-calendar-token.json` が存在せず、Google Calendar CLI フォールバックが失敗した
- **原因:** `.gitignore` で除外されているため Git 同期されない（セキュリティ上正しい設定）
- **対処:** 手動コピーまたは再認証で解決
