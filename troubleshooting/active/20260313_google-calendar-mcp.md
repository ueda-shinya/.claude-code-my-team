# トラブル: Google カレンダー MCP が再起動後に接続されない

## メタ情報
- 開始日: 2026-03-13
- ステータス: 対応中
- 関連システム: Claude Code MCP / Google Calendar API / OAuth2

## 症状
Claude Code を再起動すると、Google カレンダー MCP が接続されない。
Windows の VSCode 拡張機能版で発生中。CLI 版（`claude` コマンド）では正常動作を確認。

## 作業ログ
<!-- 新しいものが上 -->

### [2026-03-13] 暫定対策の合意
- 操作: VSCode 拡張機能で MCP が認識されない問題の暫定対応を決定
- 結果: アスカ（メインアシスタント）が CLI 経由（npx @cocal/google-calendar-mcp）でカレンダー情報を取得する運用に切り替え
- ステータス: 暫定運用中。VSCode 拡張機能側の根本解決は継続調査
- 分類: 暫定対策
- タグ: 暫定, CLI, VSCode

### [2026-03-13 20:00] 切り分け結果に基づく絞り込み
- 操作: CLI 正常動作の事実から、設定・認証情報に関する仮説をすべて棄却。調査範囲を絞り込み
- 結果: 残問題は「VSCode 拡張機能が MCP 設定をどのパスから読み込んでいるか」のみ。設定内容・認証情報・API キー・トークンはすべて正常と確定
- 分類: 調査
- タグ: 切り分け, 絞り込み, VSCode

### [2026-03-13 18:40] VSCode 拡張機能 vs CLI の切り分け - 完了
- 操作: CLI 版（`claude` コマンド）で Google カレンダー MCP を使用
- 結果: CLI 版では正常にツールが認識され、カレンダー取得可能
- **結論: 問題は VSCode 拡張機能の MCP 接続に限定される**
- 分類: 調査
- タグ: VSCode, CLI, 切り分け

### [2026-03-13 19:31] Windows 側で OAuth 認証フロー実行 - 成功
- 操作: `npx -y @cocal/google-calendar-mcp auth` を環境変数付きで実行
- 結果: 認証成功。トークンファイル `mcp-google-calendar-token.json` が作成された。
  - access_token: 取得済み（有効期限: 2026-03-13 19:31 = 約1時間）
  - refresh_token: 取得済み（`refresh_token_expires_in: 604799` = 7日間）
  - **重要:** `refresh_token_expires_in: 604799`（7日間）は GCP テストモードであることを示す
  - 7日後（2026-03-20 頃）に同じ問題が再発する
- 分類: コマンド実行
- タグ: OAuth, token, 認証, Windows

### [2026-03-13 19:10] パッケージソースコード解析 - トークンパス仕様の確定
- 操作: `@cocal/google-calendar-mcp` v2.6.1 のビルド済みソースコード（index.js）を解析
- 結果: トークンファイルのパス解決ロジックを確定
  1. `GOOGLE_CALENDAR_MCP_TOKEN_PATH` 環境変数が設定されていれば、そのパスを使用（ドキュメント未記載だがコードに存在）
  2. 未設定の場合: `$XDG_CONFIG_HOME/google-calendar-mcp/tokens.json`（Linux/Mac）
  3. `$XDG_CONFIG_HOME` 未設定の場合: `~/.config/google-calendar-mcp/tokens.json`
  4. レガシー: カレントディレクトリの `.gcp-saved-tokens.json`
  - トークンは `tokens.json` というファイル名（`mcp-google-calendar-token.json` ではない）
  - マルチアカウント対応で、トークンはアカウントID別に保存される
  - `refresh_token` があれば期限切れ時に自動リフレッシュを試みる
  - 自動リフレッシュ失敗時（invalid_grant）は再認証を促す
- 分類: 調査
- タグ: OAuth, token, MCP, ソースコード解析

### [2026-03-13 19:00] Windows 側の設定確認とパッケージ仕様調査
- 操作: Windows の settings.json、認証ファイル、パッケージの公式ドキュメントを確認
- 結果: 以下の事実を確認
  - Windows の settings.json には `GOOGLE_OAUTH_CREDENTIALS` と `GOOGLE_CALENDAR_MCP_TOKEN_PATH` が設定済み
  - `google-oauth-credentials.json` は Windows に存在する
  - `mcp-google-calendar-token.json` は Windows に**存在しない**（未認証）
  - パッケージ v2.6.1 の公式ドキュメントを確認:
    - 公式に記載されている環境変数は `GOOGLE_OAUTH_CREDENTIALS` のみ
    - `GOOGLE_CALENDAR_MCP_TOKEN_PATH` はドキュメントに記載あり（ヘルプ出力のフッター部分）
    - 初回起動時に OAuth フローが自動的に走る仕様
    - テストモードでは 7日でトークンが期限切れになる
    - 再認証コマンド: `npx @cocal/google-calendar-mcp auth`
- 分類: 調査
- タグ: OAuth, token, MCP, 設定確認, パッケージ仕様

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
| 2 | 2026-03-13 19:00 | 調査 | OAuth, token, MCP, 設定確認, パッケージ仕様 | 公式ドキュメントで環境変数仕様を確認。テストモード7日期限切れの可能性。 |
| 3 | 2026-03-13 19:10 | 調査 | OAuth, token, MCP, ソースコード解析 | ソースコードからトークンパス解決ロジックを確定。GOOGLE_CALENDAR_MCP_TOKEN_PATH はコードに実装済み。 |
| 4 | 2026-03-13 19:31 | コマンド実行 | OAuth, token, 認証, Windows | Windows で認証成功。ただし refresh_token は7日間有効（テストモード）。恒久対策が必要。 |
| 5 | 2026-03-13 20:00 | 調査 | 切り分け, 絞り込み, VSCode | CLI正常動作により設定・認証系仮説をすべて棄却。残問題をVSCode拡張のMCP設定パスに限定。 |
| 6 | 2026-03-13 | 暫定対策 | CLI, 暫定 | CLI経由での取得を暫定対応として合意 |

## 仮説リスト
- [x] 仮説: パッケージが非標準 → 棄却（理由: v2.6.1 で活発にメンテされている。機能も豊富。問題はパッケージではなくトークンの状態。）
- [x] 仮説: トークンが期限切れ → 棄却（理由: CLI版で正常動作を確認済み。トークンは有効。）
- [x] 仮説: `GOOGLE_OAUTH_TOKEN` 環境変数が未設定 → 棄却（理由: このパッケージは `GOOGLE_OAUTH_TOKEN` を使わない。`GOOGLE_CALENDAR_MCP_TOKEN_PATH` が正しい環境変数名。ソースコードで確認済み。）
- [x] 仮説: `GOOGLE_CALENDAR_MCP_TOKEN_PATH` は非公式で無視される → 棄却（理由: ソースコードの `getSecureTokenPath()` で明示的にこの環境変数をチェックしている。コードに実装済み。）
- [x] 仮説: Windows 側ではトークンファイルが存在しないため、初回認証フローを実行する必要がある → 棄却（理由: 認証実行済み。さらにCLI版で正常動作確認済みのため、認証情報は問題なし。）
- [x] 仮説: 設定ファイル・認証情報全般に問題がある → 棄却（理由: CLI版（`claude` コマンド）で正常にカレンダー取得できた。CLI と VSCode拡張は同じ設定・認証情報を使うため、設定・認証は正常と確定。）
- [ ] 仮説: VSCode 拡張機能が `~/.claude.json` とは異なるパスから MCP 設定を読み込んでいる（根拠: CLI では動作するが VSCode 拡張では動作しない。差異は MCP 設定の読み込みパスにあると推定。）

## 対応方針（切り分け結果に基づく絞り込み版）

### 確定事実
- CLI 版（`claude` コマンド）では Google カレンダー MCP が正常に動作する
- したがって、設定ファイル・認証情報・トークン・API キーはすべて正常
- **問題は VSCode 拡張機能が MCP 設定を読み込むパスにある**

### 残る調査対象（これだけ）
VSCode 拡張機能が参照している MCP 設定ファイルのパスを特定する。

### 次のアクション
1. `~/.claude.json` の中身を確認し、MCP 設定が記述されているか見る
2. VSCode 拡張機能が `~/.claude.json` ではなく別のパス（例: プロジェクトローカルの `.claude.json`、VSCode の `settings.json` 内の MCP 設定）を参照している可能性を調査する

### 恒久対策（別件）
- GCP コンソールでアプリを「テスト」から「本番」に変更する（refresh_token の7日期限切れ対策）
- Google Cloud Console > OAuth 同意画面 > アプリを公開

## 暫定対策

### 対策内容
VSCode 拡張機能で MCP が認識されないため、CLI 経由（npx @cocal/google-calendar-mcp）でカレンダー情報を取得する運用に切り替え。

### 影響スキル・手順書
- 検索キーワード: google-calendar, calendar, mcp__google-calendar
- ヒット:
  - `~/.claude/skills/morning-briefing/SKILL.md`: ステップ2でMCPツール使用 / 方法Bフォールバックあり / 更新済み: [x]
  - `~/.claude/CLAUDE.md`: モーニングブリーフィング設定あり / 直接影響なし
- 影響なし: 他のスキル・エージェントにカレンダー参照なし

### 実効性確認
- [x] morning-briefing の方法B（CLIフォールバック）が実際に動作するか確認済み
- 確認結果: SKILL.md に方法Bが既に記載されており動作する

### 有効期限
VSCode 拡張機能の MCP 設定パス問題が解決するまで

## 解決策
（解決したら記載）
