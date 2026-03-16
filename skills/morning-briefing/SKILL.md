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

以下の4つのステップを実行してください。各ステップが失敗しても、他のステップは止めずに続行してください。

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

**① トークンリフレッシュ＆カレンダー取得（Mac・Windows両対応）：**

`os.path.expanduser` を使うことでMac・Windows両方で動作します。以下のpython3スクリプトを1つのBashコマンドとして実行してください：

```bash
python3 -c "
import json, urllib.request, urllib.parse, os, sys
sys.stdout.reconfigure(encoding='utf-8')

cred_path = os.path.expanduser('~/.claude/google-oauth-credentials.json')
token_path = os.path.expanduser('~/.claude/mcp-google-calendar-token.json')

cred = json.load(open(cred_path))
token = json.load(open(token_path))

data = urllib.parse.urlencode({
    'client_id': cred['installed']['client_id'],
    'client_secret': cred['installed']['client_secret'],
    'refresh_token': token['normal']['refresh_token'],
    'grant_type': 'refresh_token'
}).encode()

req = urllib.request.Request('https://oauth2.googleapis.com/token', data=data, method='POST')
with urllib.request.urlopen(req) as res:
    access_token = json.loads(res.read())['access_token']

# DATE_FROM・DATE_TO は実行前に今日の日付（YYYY-MM-DD形式）に置き換えること
url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events?timeMin=YYYY-MM-DDT00:00:00%2B09:00&timeMax=YYYY-MM-DDT23:59:59%2B09:00&singleEvents=true&orderBy=startTime&timeZone=Asia/Tokyo'
req2 = urllib.request.Request(url, headers={'Authorization': 'Bearer ' + access_token})
with urllib.request.urlopen(req2) as res:
    events = json.loads(res.read())

items = events.get('items', [])
print(f'件数: {len(items)}')
for e in items:
    start = e.get('start', {})
    t = start.get('dateTime', start.get('date', ''))
    print(t[:16], e.get('summary', '（タイトルなし）'))
" 2>&1 | cat
```

リフレッシュ自体も失敗した場合は「カレンダーの取得に失敗しました（要再認証）」と報告し、他のステップは継続すること。

月曜日の場合は、今週分（月曜から日曜まで）の予定も追加で取得する。

### ステップ 3: YouTube動画ダイジェスト更新

`~/.claude/youtube-digest.md` の最終更新日時を確認し、**24時間以上経過している場合（またはファイルが存在しない場合）のみ**、以下を実行してください。

**① 24時間チェック＆動画取得（Mac・Windows両対応）：**

```bash
python3 -c "
import json, urllib.request, urllib.parse, os, sys, re
from datetime import datetime, timezone, timedelta
sys.stdout.reconfigure(encoding='utf-8')

digest_path = os.path.expanduser('~/.claude/youtube-digest.md')
jst = timezone(timedelta(hours=9))
now = datetime.now(jst)

# 24時間チェック
if os.path.exists(digest_path):
    with open(digest_path, encoding='utf-8') as f:
        content = f.read()
    m = re.search(r'最終更新: (.+)', content)
    if m:
        last_updated = datetime.fromisoformat(m.group(1))
        if (now - last_updated).total_seconds() < 86400:
            print('SKIP: 最終更新から24時間未経過')
            sys.exit(0)

# APIキー読み込み
env_path = os.path.expanduser('~/.claude/.env')
api_key = ''
with open(env_path) as f:
    for line in f:
        if line.startswith('YOUTUBE_API_KEY='):
            api_key = line.strip().split('=', 1)[1]

published_after = (now - timedelta(hours=24)).strftime('%Y-%m-%dT%H:%M:%S+09:00')

results = []
for query in ['AI 人工知能 最新', 'WordPress 最新']:
    params = urllib.parse.urlencode({
        'part': 'snippet',
        'q': query,
        'type': 'video',
        'publishedAfter': published_after,
        'order': 'viewCount',
        'maxResults': 5,
        'relevanceLanguage': 'ja',
        'key': api_key
    })
    url = 'https://www.googleapis.com/youtube/v3/search?' + params
    req = urllib.request.Request(url)
    with urllib.request.urlopen(req) as res:
        data = json.loads(res.read())
    for item in data.get('items', []):
        vid = item['id'].get('videoId', '')
        title = item['snippet']['title']
        channel = item['snippet']['channelTitle']
        desc = item['snippet']['description'][:100].replace('\n', ' ')
        results.append({'query': query, 'id': vid, 'title': title, 'channel': channel, 'desc': desc})

# ファイル書き出し
lines = [f'# YouTube ダイジェスト', f'最終更新: {now.isoformat()}', '']
for q in ['AI 人工知能 最新', 'WordPress 最新']:
    label = 'AI関連' if 'AI' in q else 'WordPress関連'
    lines.append(f'## {label}')
    for r in [x for x in results if x['query'] == q]:
        lines.append(f'- [{r[\"title\"]}](https://www.youtube.com/watch?v={r[\"id\"]})')
        lines.append(f'  - チャンネル：{r[\"channel\"]}')
        lines.append(f'  - 概要：{r[\"desc\"]}')
    lines.append('')

with open(digest_path, 'w', encoding='utf-8') as f:
    f.write('\n'.join(lines))

print(f'UPDATED: {len(results)}件取得')
" 2>&1 | cat
```

**② 更新された場合はGitコミット：**

出力が `UPDATED:` で始まる場合のみ以下を実行してください：

```bash
cd ~/.claude && git add youtube-digest.md && git commit -m "chore: YouTubeダイジェスト更新" && git push 2>&1
```

- `SKIP:` の場合 → コミット不要。既存ファイルの内容を Read ツールで読み込む
- 取得失敗の場合 → 「YouTube動画の取得に失敗しました」と報告し、次のステップへ

### ステップ 4: セッション引き継ぎ確認

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

## YouTube ダイジェスト（最終更新: YYYY-MM-DD HH:MM）
### AI関連
- [動画タイトル](URL)
  - チャンネル：チャンネル名
  - 概要：説明文

### WordPress関連
- [動画タイトル](URL)
  - チャンネル：チャンネル名
  - 概要：説明文

## リポジトリ（※ git pull が失敗した場合のみこのセクションを表示）
- エラー内容
```

YouTubeダイジェストは `~/.claude/youtube-digest.md` の内容をそのまま転記すること。SKIPの場合も既存ファイルから読み込んで表示する。取得失敗かつファイルも存在しない場合はセクションを省略する。

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
