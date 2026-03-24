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

- git pull で新しいコミットが取り込まれた場合（"Already up to date." 以外）→ 以下のコマンドでコミット一覧を取得し、報告の「リポジトリ更新」セクションに含める
  ```bash
  cd ~/.claude && git log ORIG_HEAD..HEAD --oneline 2>&1
  ```
- git pull が "Already up to date." の場合 → 結果を記憶するが報告には含めない
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

リフレッシュ自体も失敗した場合（HTTP 400 等）は、リフレッシュトークンが期限切れの可能性が高い（約4.8日で失効）。
「カレンダーの取得に失敗しました。リフレッシュトークンが期限切れの可能性があります。`python3 ~/.claude/scripts/youtube-oauth.py` でブラウザ再認証してください」と報告し、他のステップは継続すること。
（参照: troubleshooting/active/20260321_oauth-refresh-token-expired.md）

月曜日の場合は、今週分（月曜から日曜まで）の予定も追加で取得する。

### ステップ 3: YouTube動画ダイジェスト更新

`~/.claude/youtube-digest.md` の最終更新日時を確認し、**24時間以上経過している場合（またはファイルが存在しない場合）のみ**、以下を実行してください。

**検索キーワード（各最大3件）：**
- `Claude Code`
- `ChatGPT 最新`
- `Gemini AI 最新`
- `NotebookLM`
- `Genspark`
- `AI ツール 最新`
- `WordPress`
- `ワードプレス`

**ロジック：**
- 72時間以内にアップロードされた動画のみ対象
- 2段階API（search → videos/statistics）で実再生数を取得し、再生数の多い順にソート
- 既出動画除外：`~/.claude/youtube-seen-ids.json` に記録済みの動画IDはスキップ
- 各クエリで上位3件を採用

**① 24時間チェック＆動画取得（Mac・Windows両対応）：**

```bash
python3 -c "
import json, urllib.request, urllib.parse, os, sys, re
from datetime import datetime, timezone, timedelta
sys.stdout.reconfigure(encoding='utf-8')

digest_path = os.path.expanduser('~/.claude/youtube-digest.md')
seen_path = os.path.expanduser('~/.claude/youtube-seen-ids.json')
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

# 既出動画IDを読み込み
seen_ids = set()
if os.path.exists(seen_path):
    with open(seen_path, encoding='utf-8') as f:
        seen_ids = set(json.load(f))

published_after = (now - timedelta(hours=72)).strftime('%Y-%m-%dT%H:%M:%S+09:00')

queries = [
    ('Claude Code', 'AI関連'),
    ('ChatGPT 最新', 'AI関連'),
    ('Gemini AI 最新', 'AI関連'),
    ('NotebookLM', 'AI関連'),
    ('Genspark', 'AI関連'),
    ('AI ツール 最新', 'AI関連'),
    ('WordPress', 'WordPress関連'),
    ('ワードプレス', 'WordPress関連'),
]

results = []
new_ids = []

for query, category in queries:
    # ステップ1: search で候補動画IDを取得（多めに取得して絞り込む）
    params = urllib.parse.urlencode({
        'part': 'snippet',
        'q': query,
        'type': 'video',
        'publishedAfter': published_after,
        'order': 'viewCount',
        'maxResults': 10,
        'relevanceLanguage': 'ja',
        'regionCode': 'JP',
        'key': api_key
    })
    url = 'https://www.googleapis.com/youtube/v3/search?' + params
    req = urllib.request.Request(url)
    with urllib.request.urlopen(req) as res:
        data = json.loads(res.read())

    # 既出除外
    candidate_ids = [
        item['id']['videoId']
        for item in data.get('items', [])
        if item['id'].get('videoId') and item['id']['videoId'] not in seen_ids
    ]
    if not candidate_ids:
        continue

    # ステップ2: videos/statistics で実再生数を取得
    ids_param = ','.join(candidate_ids)
    stats_params = urllib.parse.urlencode({
        'part': 'snippet,statistics',
        'id': ids_param,
        'key': api_key
    })
    stats_url = 'https://www.googleapis.com/youtube/v3/videos?' + stats_params
    req2 = urllib.request.Request(stats_url)
    with urllib.request.urlopen(req2) as res2:
        stats_data = json.loads(res2.read())

    # 日本語動画のみ・再生数でソートして上位3件
    videos = []
    for item in stats_data.get('items', []):
        snippet = item['snippet']
        lang = snippet.get('defaultAudioLanguage') or snippet.get('defaultLanguage') or ''
        # 言語が未設定 or ja の動画のみ採用（英語・韓国語等を除外）
        if lang and not lang.startswith('ja'):
            continue
        vid = item['id']
        view_count = int(item.get('statistics', {}).get('viewCount', 0))
        title = snippet['title']
        channel = snippet['channelTitle']
        desc = snippet['description'][:100].replace('\n', ' ')
        videos.append({'id': vid, 'views': view_count, 'title': title, 'channel': channel, 'desc': desc, 'query': query, 'category': category})

    videos.sort(key=lambda x: x['views'], reverse=True)
    top3 = videos[:3]
    results.extend(top3)
    new_ids.extend([v['id'] for v in top3])

# 既出IDを更新（新規採用分を追加）
updated_seen = list(seen_ids) + new_ids
with open(seen_path, 'w', encoding='utf-8') as f:
    json.dump(updated_seen, f)

# ファイル書き出し
lines = ['# YouTube ダイジェスト', f'最終更新: {now.isoformat()}', '']

for category in ['AI関連', 'WordPress関連']:
    cat_results = [r for r in results if r['category'] == category]
    if not cat_results:
        continue
    lines.append(f'## {category}')
    for r in cat_results:
        views_str = f'{r[\"views\"]:,}'
        lines.append(f'- [{r[\"title\"]}](https://www.youtube.com/watch?v={r[\"id\"]})')
        lines.append(f'  - チャンネル：{r[\"channel\"]} / 再生数：{views_str}')
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
cd ~/.claude && git add youtube-digest.md youtube-seen-ids.json && git commit -m "chore: YouTubeダイジェスト更新" && git push 2>&1
```

- `SKIP:` の場合 → コミット不要。既存ファイルの内容を Read ツールで読み込む
- 取得失敗の場合 → 「YouTube動画の取得に失敗しました」と報告し、次のステップへ

**③ 「Claude厳選」プレイリストへ追加：**

ダイジェストが `UPDATED:` の場合も `SKIP:` の場合も、毎回以下を実行してください：

```bash
python3 ~/.claude/scripts/youtube-add-to-playlist.py 2>&1
```

- 正常完了（`完了: X件追加`）→ 結果を記憶するが報告には含めない
- 失敗した場合 → 無視して次のステップへ（プレイリスト追加はオプション扱い）

### ステップ 4: GA4 サイト状況確認

以下を実行してください：

```bash
python3 ~/.claude/scripts/ga4-report.py 2>&1 | cat
```

出力から以下の値を取得して記憶する：
- `SITE_SESSIONS` / `SITE_USERS` / `SITE_NEW_USERS` / `SITE_BOUNCE`：サイト全体（昨日）
- `CONTACT_VIEWS` / `CONTACT_USERS`：昨日の `/contact*` PV・ユーザー数
- `CONTACT_VIEWS_7D` / `CONTACT_USERS_7D`：過去7日の `/contact*` PV・ユーザー数
- `SOURCE_<チャンネル>: <セッション>|<新規>`：流入元別（過去7日、上位5件）
- `TOP_PAGE_<n>: <path>|<PV>|<ユーザー>`：人気ページ Top5（昨日）
- `LP_SESSIONS_7D`：LP セッション数（過去7日）
- `LP_BOUNCE_7D`：LP 離脱率（過去7日）
- `LP_AVG_DURATION_7D`：LP 平均滞在時間・秒（過去7日）
- `LP_CTA_CLICKS_7D`：LP CTA クリック数（過去7日）
- `LP_MOBILE_BOUNCE_7D`：LP モバイル離脱率（過去7日）

取得後、以下の改善提案ロジックを適用して `LP_ALERT` リストを作成する：
- `LP_BOUNCE_7D` が 60% 超 → 「離脱率が高め（X%）。FV の訴求またはモバイル表示を要確認」
- `LP_MOBILE_BOUNCE_7D` が 70% 超 → 「モバイル離脱率が高め（X%）。表示速度・FVデザインを確認」
- `LP_AVG_DURATION_7D` が 30秒未満 → 「平均滞在時間が短い（X秒）。コンテンツが読まれていない可能性あり」
- `LP_CTA_CLICKS_7D` が 0 かつ `LP_SESSIONS_7D` が 5 以上 → 「CTAクリックがゼロ。ボタンの視認性・訴求を要確認」
- `LP_SESSIONS_7D` が 0 → 「LP へのアクセスなし。広告・SNS等の集客状況を確認」

失敗した場合 → GA4 セクションを省略して次のステップへ

### ステップ 5: セッション引き継ぎ確認

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

## サイト状況（昨日）
- 全体：Xセッション / Xユーザー（新規X） / 離脱率X%
- お問い合わせ：X PV / Xユーザー（過去7日：X PV / Xユーザー）

## 流入元（過去7日）
Organic Search: X  /  Direct: X  /  Paid Social: X  /  Paid Search: X

## 広告（過去7日）
- Instagram（ig/paid）：Xセッション / 新規X / 離脱率X% / 平均滞在X秒
- Google（google/cpc）：Xセッション / 新規X / 離脱率X% / 平均滞在X秒
※ 広告がない場合はこのセクションを省略

## 人気ページ Top5（昨日）
- /path  X PV / Xユーザー

## LP（lp-260319）状況（過去7日）
- セッション：X / 離脱率：X% / 平均滞在：X秒 / CTAクリック：X回
- モバイル離脱率：X%
⚠️ 改善提案：（LP_ALERT があれば箇条書き。なければこの行を省略）

## YouTube ダイジェスト（最終更新: YYYY-MM-DD HH:MM）
### AI関連
- [動画タイトル](URL)
  - チャンネル：チャンネル名
  - 概要：説明文

### WordPress関連
- [動画タイトル](URL)
  - チャンネル：チャンネル名
  - 概要：説明文

## リポジトリ更新（※ 新しいコミットがあった場合のみ表示）
前回から X 件の変更を確認しました。
- コミットメッセージ一覧（件数と内容のみ、詳細不要）

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
