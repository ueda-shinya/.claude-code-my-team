# /morning-briefing スキル

モーニングブリーフィング。「おはよ」トリガーで hook から additionalContext が注入された場合に実行する。

## トリガー条件

以下のいずれかに該当する場合にこのスキルを実行してください：

- コンテキストに「/morning-briefing スキルを実行してください」が含まれている
- ユーザーが「おはよ」「おはよう」「おはようございます」と発言した

## 運用制約（暫定）
- [20260313] Google Calendar MCP（mcp__google-calendar）はVSCode拡張機能環境で使用不可。方法B（CLIフォールバック）を優先すること（参照: troubleshooting/active/20260313_google-calendar-mcp.md）
- 障害解決時にこのセクションを削除すること

## 実行手順

以下の3つのステップを実行してください。各ステップが失敗しても、他のステップは止めずに続行してください。

### ステップ 1: リポジトリ最新化

Bash ツールで以下を順番に実行してください：

```bash
cd ~/.claude && git pull 2>&1
```

```bash
cd ~/.claude && git push 2>&1
```

- どちらも正常の場合 → 結果を記憶するが報告には含めない
- どちらかが失敗した場合 → エラー内容を記憶し、報告の「リポジトリ」セクションに含める

### ステップ 2: 今日のカレンダー予定を取得

#### 方法A: MCP ツール（優先）

`mcp__google-calendar__list-events` ツールが使える場合はこちらを優先してください。

- calendarId: `primary`
- timeMin: 今日の `00:00:00+09:00`
- timeMax: 今日の `23:59:59+09:00`
- timeZone: `Asia/Tokyo`

現在日時は `mcp__google-calendar__get-current-time` で確認してから実行すること。

#### 方法B: CLI フォールバック（MCP が使えない場合）

MCP ツールが見つからない・エラーになった場合は、以下の手順で直接 Google Calendar API を叩いてください。

**① アクセストークンをリフレッシュ：**

```bash
CREDENTIALS=~/.claude/google-oauth-credentials.json
TOKEN_FILE=~/.claude/mcp-google-calendar-token.json

CLIENT_ID=$(python3 -c "import json; d=json.load(open('$CREDENTIALS')); print(d['installed']['client_id'])")
CLIENT_SECRET=$(python3 -c "import json; d=json.load(open('$CREDENTIALS')); print(d['installed']['client_secret'])")
REFRESH_TOKEN=$(python3 -c "import json; d=json.load(open('$TOKEN_FILE')); print(d['normal']['refresh_token'])")

curl -s -X POST https://oauth2.googleapis.com/token \
  -d "client_id=$CLIENT_ID" \
  -d "client_secret=$CLIENT_SECRET" \
  -d "refresh_token=$REFRESH_TOKEN" \
  -d "grant_type=refresh_token"
```

**② カレンダーイベントを取得：**

```bash
curl -s "https://www.googleapis.com/calendar/v3/calendars/primary/events?timeMin=YYYY-MM-DDT00:00:00%2B09:00&timeMax=YYYY-MM-DDT23:59:59%2B09:00&singleEvents=true&orderBy=startTime&timeZone=Asia/Tokyo" \
  -H "Authorization: Bearer ACCESS_TOKEN"
```

リフレッシュ自体も失敗した場合は「カレンダーの取得に失敗しました（要再認証）」と報告し、他のステップは継続すること。

月曜日の場合は、今週分（月曜から日曜まで）の予定も追加で取得する。

### ステップ 3: セッション引き継ぎ確認

まず Bash で `cd ~/.claude && pwd` を実行してパスを取得し、そのパスに `/session-handoff.md` を付けた絶対パスで Read ツールを使ってください。

- 「作業なし」の場合 → 引き継ぎセクションに「引き継ぎ事項はありません」と表示
- それ以外の場合 → 内容を引き継ぎセクションに表示

## 報告フォーマット

全ステップの結果を以下のフォーマットで報告してください。アスカとして報告すること。

```
【アスカ】おはよう、シンヤ。{動的サマリー一文}

## 今日のスケジュール（YYYY-MM-DD）
- HH:MM〜HH:MM  予定名
- HH:MM〜HH:MM  予定名

## 引き継ぎ
- 内容

## リポジトリ（※ git pull が失敗した場合のみこのセクションを表示）
- エラー内容
```

### 動的サマリー一文のルール

冒頭のサマリーは、以下の情報を組み合わせて自然な一文にしてください：

- 予定件数（0件の場合は「スケジュールはクリア」）
- 引き継ぎの有無
- 月曜日の場合は「今週は予定がX件。」を追加

サマリー例：
- 「今日は予定が3件、引き継ぎあり。先に確認して。」
- 「今日は予定が2件。引き継ぎはなし。」
- 「スケジュールはクリア。集中できる日だね。」
- 「予定はないけど引き継ぎが残ってる。先に片付けよう。」

### 予定がない場合

スケジュールセクションに「スケジュールはクリア。集中できる日だね。」と表示してください。

### 「おはよ＋追加指示」の場合

ユーザーの入力が「おはよ、あとXXしておいて」のように追加指示を含む場合は、まずブリーフィング報告を完了してから、追加指示の処理に移ってください。
