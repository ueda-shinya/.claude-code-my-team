# /morning-briefing スキル

毎日版モーニングブリーフィング（軽量版）。「おはよ」トリガーで hook から additionalContext が注入された場合に実行する。
レン考察・YouTubeダイジェスト更新は省略し、トークン消費を抑える。詳細分析は `/morning-briefing-weekly` で実施。

## トリガー条件

以下のいずれかに該当する場合にこのスキルを実行してください：

- コンテキストに「/morning-briefing スキルを実行してください」が含まれている
- ユーザーが「おはよ」「おはよう」「おはようございます」と発言した

## 運用制約（暫定）
- [20260313] Google Calendar MCP（mcp__google-calendar）はVSCode拡張機能環境で使用不可。方法B（CLIフォールバック）を優先すること（参照: troubleshooting/active/20260313_google-calendar-mcp.md）
- 障害解決時にこのセクションを削除すること

## 実行手順

以下のステップを実行してください。各ステップが失敗しても、他のステップは止めずに続行してください。

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

### ステップ 2-2: 前日打ち合わせ・訪問後の CRM 履歴チェック

前日のカレンダーから「打ち合わせ・訪問・商談系」イベントを抽出し、Notion 議事録への記録漏れを検出する。
失敗しても次のステップに進むこと。

**① 前日イベント取得 ＆ 議事録チェック（Mac・Windows両対応）：**

カレンダーのトークン取得はステップ2で使ったコードと同じ方式で実施する。YESTERDAY を前日の日付（YYYY-MM-DD形式）に置き換えて実行すること。

```bash
python3 -c "
import json, urllib.request, urllib.parse, os, sys, ssl
from datetime import datetime, timezone, timedelta
sys.stdout.reconfigure(encoding='utf-8')

jst = timezone(timedelta(hours=9))
now = datetime.now(jst)
yesterday = (now - timedelta(days=1)).strftime('%Y-%m-%d')

# --- Google Calendar: 前日イベント取得 ---
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

url = f'https://www.googleapis.com/calendar/v3/calendars/primary/events?timeMin={yesterday}T00:00:00%2B09:00&timeMax={yesterday}T23:59:59%2B09:00&singleEvents=true&orderBy=startTime&timeZone=Asia/Tokyo'
req2 = urllib.request.Request(url, headers={'Authorization': 'Bearer ' + access_token})
with urllib.request.urlopen(req2) as res:
    events = json.loads(res.read())

# 打ち合わせ・訪問系キーワード
MEETING_KEYWORDS = ['打ち合わせ', '訪問', 'MTG', 'ミーティング', '商談', '面談', '会議', 'meeting', 'visit']
meeting_events = []
for e in events.get('items', []):
    title = e.get('summary', '')
    if any(kw.lower() in title.lower() for kw in MEETING_KEYWORDS):
        start = e.get('start', {})
        t = start.get('dateTime', start.get('date', ''))[:16]
        meeting_events.append(f'{t} {title}')

if not meeting_events:
    print('MEETING_NONE')
    sys.exit(0)

# --- Notion 議事録 DB: 前日〜当日作成エントリを確認 ---
env_path = os.path.expanduser('~/.claude/.env')
notion_token = ''
minutes_db_id = ''
with open(env_path, encoding='utf-8') as f:
    for line in f:
        line = line.strip().strip('\"').strip(\"'\")
        if line.startswith('NOTION_API_TOKEN='):
            notion_token = line.split('=', 1)[1].strip('\"').strip(\"'\")
        if line.startswith('NOTION_MINUTES_DB_ID='):
            minutes_db_id = line.split('=', 1)[1].strip('\"').strip(\"'\")

ctx = ssl.create_default_context()
query = json.dumps({
    'filter': {
        'property': '日時',
        'date': {'on_or_after': yesterday}
    }
}).encode()
req3 = urllib.request.Request(
    f'https://api.notion.com/v1/databases/{minutes_db_id}/query',
    data=query,
    headers={
        'Authorization': f'Bearer {notion_token}',
        'Notion-Version': '2022-06-28',
        'Content-Type': 'application/json'
    },
    method='POST'
)
with urllib.request.urlopen(req3, context=ctx, timeout=10) as res:
    result = json.loads(res.read())

minutes_count = len(result.get('results', []))

print(f'MEETINGS: {len(meeting_events)}')
for m in meeting_events:
    print(f'  - {m}')
print(f'MINUTES_AFTER: {minutes_count}')
" 2>&1 | cat
```

**② 判定ロジック：**

- 出力が `MEETING_NONE` → 打ち合わせ系イベントなし → セクション省略
- `MEETINGS: N` かつ `MINUTES_AFTER: 0` → 記録漏れの可能性あり → 報告に含める
- `MEETINGS: N` かつ `MINUTES_AFTER: 1以上` → 記録済み → 報告に含めない（セクション省略）
- スクリプトがエラーで失敗した場合 → セクション省略

---

### ステップ 3: GA4 サイト状況確認

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
- `TOPPAGE_SESSIONS_YD` / `TOPPAGE_USERS_YD` / `TOPPAGE_BOUNCE_YD` / `TOPPAGE_AVG_DURATION_YD`：トップページ（/）昨日
- `LP_SESSIONS_YD` / `LP_USERS_YD` / `LP_BOUNCE_YD` / `LP_AVG_DURATION_YD`：LP（/lp-260319）昨日
- `LP_SESSIONS_7D`：LP セッション数（過去7日）
- `LP_BOUNCE_7D`：LP 直帰率（過去7日）
- `LP_AVG_DURATION_7D`：LP 平均滞在時間・秒（過去7日）
- `LP_CTA_CLICKS_7D`：LP CTA クリック数（過去7日）
- `LP_CTA_LABEL_<label>`：LP CTAボタン別クリック数（LP_CTA_START_DATE以降）
- `LP_MOBILE_BOUNCE_7D`：LP モバイル直帰率（過去7日）

**アスカ異常値判定ルール（レン考察の代わりに以下をすべてチェック）：**

レンへの委譲は行わない。アスカ自身が以下のルールで異常値を判定する：

- 広告の直帰率が 80% 超 → `[広告名]の直帰率がX%です。レンへの分析依頼を検討してください。`（AD_* の bounceRate を使用）
- 前週比セッション 50% 以下（※前週同曜日データ未取得のため現在は判定不可。SITE_SESSIONSが著しく低い場合のみ主観でフラグ）→ `昨日のセッションが少ない（X件）。先週と比べて確認を。`
- お問い合わせが週0件（`CONTACT_VIEWS_7D: 0`）→ `過去7日のお問い合わせが0件です。レンへの相談を検討してください。`
- LP の CTA クリックが 0 かつ `LP_SESSIONS_7D` が 5 以上 → `LPのCTAクリックがゼロ（セッションXあり）。レンへの分析依頼を検討してください。`（LP_CTA_CLICKS_7D・LP_SESSIONS_7D を使用）

異常なしの場合 → 「特記事項なし」と表示

取得後、以下の評価ロジックを適用して各アラートリストを作成する：

【GA4定義の注意】バウンス率 = エンゲージドでなかったセッションの割合（100% - エンゲージメント率）。
「10秒超継続」だけでもエンゲージドセッションになるため、単純に「すぐ帰った人」ではない。
流入元・デバイス・コンテンツのゴールによって文脈が異なるため、数値単体で判断しない。

**トップページ評価（`TOPPAGE_ALERT` リスト）：**
- `TOPPAGE_AVG_DURATION_YD` が 30秒未満 → 「トップページの平均滞在が短い（X秒）。訴求・導線を確認。」
- `TOPPAGE_BOUNCE_YD` が 80% 超 → 「トップページの直帰率が高い（X%）。ファーストビューを確認。」
- `TOPPAGE_SESSIONS_YD` が 0 → 「昨日トップページへのアクセスがありませんでした。」

**LP評価（`LP_ALERT_DAILY` リスト）：**
- `LP_BOUNCE_YD` が 80% 超 → 「LP直帰率が高い（X%）。広告クリエイティブとLP訴求のズレを確認。」
- `LP_AVG_DURATION_YD` が 30秒未満 → 「LP平均滞在が短い（X秒）。ファーストビューの見直しを推奨。」
- `LP_SESSIONS_YD` が 0 → 「昨日LPへのアクセスがありませんでした。」

失敗した場合 → GA4 セクションを省略して次のステップへ

### ステップ 4.8: 定期タスクのリマインダーチェック

今日の日付から「毎月第2日曜日」かどうかを判定する。

```bash
python3 -c "
from datetime import date
import sys
today = date.today()
# 今月の第2日曜日を計算
first_day = today.replace(day=1)
# 第1日曜日
weekday = first_day.weekday()  # 0=月曜
days_to_sunday = (6 - weekday) % 7
first_sunday = first_day.day + days_to_sunday
second_sunday = first_sunday + 7
print('TODAY:', today.day)
print('SECOND_SUNDAY:', second_sunday)
print('IS_SECOND_SUNDAY:', str(today.day == second_sunday))
" 2>&1
```

- `IS_SECOND_SUNDAY: True` の場合 → 報告に `## 定期タスク` セクションを追加する
- `IS_SECOND_SUNDAY: False` の場合 → スキップ

### ステップ 5: セッション引き継ぎ確認

まず Bash で `cd ~/.claude && pwd` を実行してパスを取得し、そのパスに `/session-handoff.md` を付けた絶対パスで Read ツールを使ってください。

- 「作業なし」の場合 → 引き継ぎセクションに「引き継ぎ事項はありません」と表示
- それ以外の場合 → 内容を引き継ぎセクションに表示

### ステップ 6: YouTube ダイジェスト（読み込みのみ）

`~/.claude/youtube-digest.md` を Read ツールで読み込んで報告に含める。
取得・更新・Gitコミットは行わない（週次版でのみ実施）。
ファイルが存在しない場合はセクション省略。

### ステップ 7: Claude Code レーダー最新結果

`~/.claude/tmp/claude-code-radar-latest.json` を Read ツールで読み込む。

- **ファイルが存在しない** → 「本日未実行」と1行表示
- **ファイル存在するが `executed_at` の日付が当日でない** → 「本日未実行（最終実行: YYYY-MM-DD）」と1行表示
- ファイル存在＆当日実行済みの場合、JSON から以下を取得して報告に含める：
  - `executed_at`（最終実行日時）
  - `registered`（新規登録件数）
  - `star5_count`（⭐5 件数）
  - `star4plus_entries`（⭐4 以上のエントリ配列。各要素は category / title / verdict_reason 等を含む）
- **`registered == 0`** の場合は「本日の新着はありません」と1行だけ表示
- **`star5_count ≥ 1`** の場合は見出しに `🔥` を付けて強調
- `star4plus_entries` から上位最大3件を「- [カテゴリ] タイトル — 理由」の形式でバレット表示
- 「詳細は Notion『Claude Code レーダー』DB で確認してください」と案内を末尾に1行追加

## 報告フォーマット

全ステップの結果を以下のフォーマットで報告してください。アスカとして報告すること。

```
【アスカ】おはよう、シンヤ。{動的サマリー一文}

## 今日のスケジュール（YYYY-MM-DD）
- HH:MM〜HH:MM  予定名
- HH:MM〜HH:MM  予定名

## 引き継ぎ
- 内容

## 履歴更新を忘れずに（※ 前日の打ち合わせ・訪問後に議事録未登録の場合のみ表示）
昨日の以下の予定後、議事録への記録がまだのようです：
- HH:MM 予定名
→ `python3 ~/.claude/scripts/notion-projects.py minutes-add` で記録してください

## サイト状況（昨日）
- 全体：Xセッション / Xユーザー（新規X） / 離脱率X%
- お問い合わせ：X PV / Xユーザー（過去7日：X PV / Xユーザー）

## 流入元（過去7日）
Organic Search: X  /  Direct: X  /  Paid Social: X  /  Paid Search: X

## Instagram（過去7日）
- 広告（ig/paid）：Xセッション / 新規X / 離脱率X% / 平均滞在X秒
- プロフィール等（organic/referral）：Xセッション / 新規X / 離脱率X% / 平均滞在X秒
※ データがない行は省略。Instagram流入が一切ない場合はセクションごと省略

## Google広告（過去7日）
- google/cpc：Xセッション / 新規X / 離脱率X% / 平均滞在X秒
※ データがない場合はセクションごと省略

## トップページ（/）昨日
- セッション：X / ユーザー：X / 離脱率：X% / 平均滞在：X秒
評価：（TOPPAGE_ALERTがあれば箇条書き。なければ「特記事項なし」）

## LP（lp-260319）昨日
- セッション：X / ユーザー：X / 離脱率：X% / 平均滞在：X秒
- CTA クリック（累計）：X回
  - fv: X / cta_mid: X / service: X / works: X / line: X / fixed_cta: X
  ※ データがあるlabelのみ表示。全0件の場合は内訳省略
- モバイル離脱率（7D）：X%
評価：（LP_ALERT_DAILYがあれば箇条書き。なければ「特記事項なし」）

## 人気ページ Top5（昨日）
- /path  X PV / Xユーザー

アスカ判定：（異常値があれば記載。なければ「特記事項なし」）

## YouTube ダイジェスト（最終更新: YYYY-MM-DD HH:MM）
（youtube-digest.md の内容をそのまま転記。ファイルが存在しない場合はセクション省略）

## Claude Code レーダー（最終実行: YYYY-MM-DD HH:MM）※ star5_count≥1 なら 🔥 を付与
- 新規 X 件 / ⭐5 X 件
- [カテゴリ] タイトル — 理由（⭐4以上を上位最大3件）
- 詳細は Notion「Claude Code レーダー」DB で確認
※ ファイル未存在 or executed_at が当日でない場合は「本日未実行」1行のみ。registered==0 の場合は「本日の新着はありません」1行のみ

## 定期タスク（※ 第2日曜日のみ表示）
今日は **月次ルール棚卸し** の日です。
`/rule-review` で実行できます（リナ＋Gemini＋全員参加。所要時間：15〜20分）

## リポジトリ更新（※ 新しいコミットがあった場合のみ表示）
前回から X 件の変更を確認しました。
- コミットメッセージ一覧（件数と内容のみ、詳細不要）

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
