# トラブル: Google OAuth リフレッシュトークン期限切れによるカレンダー取得失敗

## メタ情報
- 開始日: 2026-03-21
- ステータス: 解決済み
- 関連システム: Google Calendar API / OAuth2
- 関連インシデント: 20260313_google-calendar-mcp.md（VSCode拡張のMCP接続問題とは別件）

## 症状
モーニングブリーフィングのカレンダー取得（方法B: CLIフォールバック）で HTTP 400 エラーが発生。
リフレッシュトークンが失効しており、アクセストークンの再取得ができない状態。

## 原因
- Google OAuth のリフレッシュトークンには有効期限がある（`refresh_token_expires_in: 416414` = 約4.8日）
- GCPアプリが「テストモード」のため、リフレッシュトークンの有効期限が短い
- 数日間セッションを開かず、トークンが使われないまま失効した
- 毎日モーニングブリーフィングでカレンダーを取得していれば自動更新されるが、間が空くと失効する

## 復旧手順
1. ターミナルで以下を実行:
   ```bash
   python3 ~/.claude/scripts/youtube-oauth.py
   ```
2. ブラウザが開くので Google アカウントで再認証する
3. 認証完了後、トークンファイル `~/.claude/mcp-google-calendar-token.json` が更新される
4. このスクリプトは Calendar・YouTube・Analytics の全スコープをまとめて再取得する

## 再発防止のポイント
- 毎日モーニングブリーフィングを実行していればトークンは自動更新される（約4.8日以内に使えばOK）
- 4日以上セッションを開かない場合は、再開時にトークン期限切れを想定しておく
- 恒久対策: GCPコンソールでアプリを「テスト」→「本番」に変更すれば、リフレッシュトークンの有効期限制限がなくなる

## 関連ファイル
- トークンファイル: `~/.claude/mcp-google-calendar-token.json`
- 認証スクリプト: `~/.claude/scripts/youtube-oauth.py`
- OAuth認証情報: `~/.claude/google-oauth-credentials.json`
- 影響スキル: `~/.claude/skills/morning-briefing/SKILL.md`（方法Bのフォールバック）

## 作業ログ

### [2026-03-21] 原因特定・復旧完了
- 操作: カレンダー取得時に HTTP 400 エラーを確認。リフレッシュトークンの失効が原因と判明
- 結果: `python3 ~/.claude/scripts/youtube-oauth.py` でブラウザ再認証を実施し復旧
- 判明事項: `refresh_token_expires_in` は約4.8日（416414秒）
- 分類: 復旧
- タグ: OAuth, refresh_token, 期限切れ, 復旧

## 試行済みアクション一覧
| # | 日時 | 分類 | タグ | 結果 |
|---|------|------|------|------|
| 1 | 2026-03-21 | 復旧 | OAuth, refresh_token | ブラウザ再認証で復旧完了 |
