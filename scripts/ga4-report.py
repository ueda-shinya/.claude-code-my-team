"""
GA4 日次レポートスクリプト
モーニングブリーフィングから呼び出す用

出力形式（stdout）:
  SITE_SESSIONS: <件数>          ← サイト全体セッション（昨日）
  SITE_USERS: <件数>             ← サイト全体ユーザー（昨日）
  SITE_NEW_USERS: <件数>         ← 新規ユーザー（昨日）
  SITE_BOUNCE: <率>              ← 離脱率（昨日）
  CONTACT_VIEWS: <件数>          ← /contact* PV（昨日）
  CONTACT_USERS: <件数>          ← /contact* ユーザー（昨日）
  CONTACT_VIEWS_7D: <件数>       ← /contact* PV（過去7日）
  CONTACT_USERS_7D: <件数>       ← /contact* ユーザー（過去7日）
  SOURCE_<チャンネル>: <セッション>|<新規>  ← 流入元別（過去7日、上位5件）
  TOP_PAGE_<n>: <path>|<PV>|<ユーザー>    ← 人気ページ Top5（昨日）
  LP_SESSIONS_7D: <件数>         ← LP セッション（過去7日）
  LP_BOUNCE_7D: <率>             ← LP 離脱率（過去7日）
  LP_AVG_DURATION_7D: <秒>       ← LP 平均滞在時間（過去7日）
  LP_CTA_CLICKS_7D: <件数>       ← LP CTA クリック数（LP_CTA_START_DATE以降・テスト除外）
  LP_MOBILE_BOUNCE_7D: <率>      ← LP モバイル離脱率（過去7日）
  LP_DAILY_<YYYYMMDD>_<チャンネル>: <セッション>  ← LP 日別流入元（過去7日）
"""

# テストクリック除外: この日付以降のCTAクリックのみ集計
LP_CTA_START_DATE = '2026-03-21'

import json, urllib.request, urllib.parse, os, sys
sys.stdout.reconfigure(encoding='utf-8')

cred_path = os.path.expanduser('~/.claude/google-oauth-credentials.json')
token_path = os.path.expanduser('~/.claude/mcp-google-calendar-token.json')

cred = json.load(open(cred_path))
token = json.load(open(token_path))

# トークンリフレッシュ
data = urllib.parse.urlencode({
    'client_id': cred['installed']['client_id'],
    'client_secret': cred['installed']['client_secret'],
    'refresh_token': token['normal']['refresh_token'],
    'grant_type': 'refresh_token'
}).encode()
req = urllib.request.Request('https://oauth2.googleapis.com/token', data=data, method='POST')
with urllib.request.urlopen(req) as res:
    access_token = json.loads(res.read())['access_token']

property_id = '320411221'
headers = {'Authorization': 'Bearer ' + access_token, 'Content-Type': 'application/json'}
url = f'https://analyticsdata.googleapis.com/v1beta/properties/{property_id}:runReport'

def run(body):
    req = urllib.request.Request(url, data=json.dumps(body).encode(), headers=headers)
    with urllib.request.urlopen(req) as res:
        return json.loads(res.read())

def get_metric(r, row=0, idx=0):
    rows = r.get('rows', [])
    if not rows or row >= len(rows):
        return '0'
    return rows[row]['metricValues'][idx]['value']

# 1. サイト全体の概要（昨日）
r_overview = run({
    'metrics': [
        {'name': 'sessions'}, {'name': 'totalUsers'},
        {'name': 'newUsers'}, {'name': 'bounceRate'}
    ],
    'dateRanges': [{'startDate': 'yesterday', 'endDate': 'yesterday'}],
})

# 2. /contact* のページビュー（昨日 & 過去7日）
contact_filter = {
    'filter': {
        'fieldName': 'pagePath',
        'stringFilter': {'matchType': 'BEGINS_WITH', 'value': '/contact'}
    }
}
r_contact_yd = run({
    'metrics': [{'name': 'screenPageViews'}, {'name': 'totalUsers'}],
    'dateRanges': [{'startDate': 'yesterday', 'endDate': 'yesterday'}],
    'dimensionFilter': contact_filter
})
r_contact_7d = run({
    'metrics': [{'name': 'screenPageViews'}, {'name': 'totalUsers'}],
    'dateRanges': [{'startDate': '7daysAgo', 'endDate': 'today'}],
    'dimensionFilter': contact_filter
})

# 3. 流入元（過去7日、チャンネルグループ別、上位5件）
r_source = run({
    'dimensions': [{'name': 'sessionDefaultChannelGrouping'}],
    'metrics': [{'name': 'sessions'}, {'name': 'newUsers'}],
    'dateRanges': [{'startDate': '7daysAgo', 'endDate': 'today'}],
    'orderBys': [{'metric': {'metricName': 'sessions'}, 'desc': True}],
    'limit': 5
})

# 3b. 広告別詳細（Paid Social / Paid Search のソース・媒体別、過去7日）
r_ads = run({
    'dimensions': [{'name': 'sessionSource'}, {'name': 'sessionMedium'}],
    'metrics': [
        {'name': 'sessions'}, {'name': 'newUsers'},
        {'name': 'bounceRate'}, {'name': 'averageSessionDuration'}
    ],
    'dateRanges': [{'startDate': '7daysAgo', 'endDate': 'today'}],
    'dimensionFilter': {
        'orGroup': {'expressions': [
            {'filter': {'fieldName': 'sessionDefaultChannelGrouping', 'stringFilter': {'matchType': 'EXACT', 'value': 'Paid Social'}}},
            {'filter': {'fieldName': 'sessionDefaultChannelGrouping', 'stringFilter': {'matchType': 'EXACT', 'value': 'Paid Search'}}},
        ]}
    },
    'orderBys': [{'metric': {'metricName': 'sessions'}, 'desc': True}],
    'limit': 10
})

# 3c. Instagram 自然流入（プロフィールリンク等、過去7日）
r_ig_organic = run({
    'dimensions': [{'name': 'sessionSource'}, {'name': 'sessionMedium'}],
    'metrics': [
        {'name': 'sessions'}, {'name': 'newUsers'},
        {'name': 'bounceRate'}, {'name': 'averageSessionDuration'}
    ],
    'dateRanges': [{'startDate': '7daysAgo', 'endDate': 'today'}],
    'dimensionFilter': {
        'orGroup': {'expressions': [
            {'filter': {'fieldName': 'sessionSource', 'stringFilter': {'matchType': 'CONTAINS', 'value': 'instagram'}}},
            {'filter': {'fieldName': 'sessionSource', 'stringFilter': {'matchType': 'CONTAINS', 'value': 'l.instagram'}}},
        ]}
    },
    'orderBys': [{'metric': {'metricName': 'sessions'}, 'desc': True}],
    'limit': 10
})

# 4. 人気ページ Top5（昨日）
r_pages = run({
    'dimensions': [{'name': 'pagePath'}],
    'metrics': [{'name': 'screenPageViews'}, {'name': 'totalUsers'}],
    'dateRanges': [{'startDate': 'yesterday', 'endDate': 'yesterday'}],
    'orderBys': [{'metric': {'metricName': 'screenPageViews'}, 'desc': True}],
    'limit': 5
})

lp_filter = {
    'filter': {
        'fieldName': 'pagePath',
        'stringFilter': {'matchType': 'BEGINS_WITH', 'value': '/lp-260319'}
    }
}

# 5. LP 概要（過去7日）
r_lp = run({
    'metrics': [
        {'name': 'sessions'}, {'name': 'bounceRate'},
        {'name': 'averageSessionDuration'}
    ],
    'dateRanges': [{'startDate': '7daysAgo', 'endDate': 'today'}],
    'dimensionFilter': lp_filter
})

# 6. LP CTA クリック（LP_CTA_START_DATE以降・テスト除外）
r_lp_cta = run({
    'metrics': [{'name': 'eventCount'}],
    'dateRanges': [{'startDate': LP_CTA_START_DATE, 'endDate': 'today'}],
    'dimensionFilter': {
        'andGroup': {'expressions': [
            lp_filter,
            {'filter': {'fieldName': 'eventName', 'stringFilter': {'matchType': 'EXACT', 'value': 'cta_click'}}}
        ]}
    }
})

# 7. LP デバイス別（モバイル離脱率、過去7日）
r_lp_device = run({
    'dimensions': [{'name': 'deviceCategory'}],
    'metrics': [{'name': 'sessions'}, {'name': 'bounceRate'}],
    'dateRanges': [{'startDate': '7daysAgo', 'endDate': 'today'}],
    'dimensionFilter': lp_filter,
    'orderBys': [{'metric': {'metricName': 'sessions'}, 'desc': True}]
})

# 8. LP 日別流入元（過去7日、ソース/媒体別）
r_lp_daily = run({
    'dimensions': [{'name': 'date'}, {'name': 'sessionSource'}, {'name': 'sessionMedium'}],
    'metrics': [{'name': 'sessions'}],
    'dateRanges': [{'startDate': '7daysAgo', 'endDate': 'today'}],
    'dimensionFilter': lp_filter,
    'orderBys': [{'dimension': {'dimensionName': 'date'}, 'desc': False}]
})

# --- 出力 ---
# サイト全体
m = r_overview.get('rows', [{}])[0].get('metricValues', [{'value':'0'}]*4) if r_overview.get('rows') else [{'value':'0'}]*4
print(f'SITE_SESSIONS: {m[0]["value"]}')
print(f'SITE_USERS: {m[1]["value"]}')
print(f'SITE_NEW_USERS: {m[2]["value"]}')
bounce = float(m[3]['value']) * 100
print(f'SITE_BOUNCE: {bounce:.1f}')

# /contact*
print(f'CONTACT_VIEWS: {get_metric(r_contact_yd, 0, 0)}')
print(f'CONTACT_USERS: {get_metric(r_contact_yd, 0, 1)}')
print(f'CONTACT_VIEWS_7D: {get_metric(r_contact_7d, 0, 0)}')
print(f'CONTACT_USERS_7D: {get_metric(r_contact_7d, 0, 1)}')

# 流入元
for row in r_source.get('rows', []):
    ch = row['dimensionValues'][0]['value'].replace(' ', '_')
    s = row['metricValues'][0]['value']
    n = row['metricValues'][1]['value']
    print(f'SOURCE_{ch}: {s}|{n}')

# 人気ページ
for i, row in enumerate(r_pages.get('rows', []), 1):
    path = row['dimensionValues'][0]['value']
    pv = row['metricValues'][0]['value']
    u = row['metricValues'][1]['value']
    print(f'TOP_PAGE_{i}: {path}|{pv}|{u}')

# LP 概要
lp_m = r_lp.get('rows', [{}])[0].get('metricValues', [{'value':'0'}]*3) if r_lp.get('rows') else [{'value':'0'}]*3
print(f'LP_SESSIONS_7D: {lp_m[0]["value"]}')
lp_bounce = float(lp_m[1]['value']) * 100
print(f'LP_BOUNCE_7D: {lp_bounce:.1f}')
lp_dur = float(lp_m[2]['value'])
print(f'LP_AVG_DURATION_7D: {lp_dur:.0f}')

# LP CTAクリック（テスト除外）
print(f'LP_CTA_CLICKS_7D: {get_metric(r_lp_cta, 0, 0)}')

# LP モバイル離脱率
for row in r_lp_device.get('rows', []):
    if row['dimensionValues'][0]['value'] == 'mobile':
        mb = float(row['metricValues'][1]['value']) * 100
        print(f'LP_MOBILE_BOUNCE_7D: {mb:.1f}')

# 広告別詳細
for row in r_ads.get('rows', []):
    src = row['dimensionValues'][0]['value']
    med = row['dimensionValues'][1]['value']
    s   = row['metricValues'][0]['value']
    n   = row['metricValues'][1]['value']
    br  = float(row['metricValues'][2]['value']) * 100
    dur = float(row['metricValues'][3]['value'])
    key = f'{src}__{med}'.replace(' ', '_')
    print(f'AD_{key}: {s}|{n}|{br:.1f}|{dur:.0f}')

# Instagram 自然流入（プロフィールリンク等）
for row in r_ig_organic.get('rows', []):
    src = row['dimensionValues'][0]['value']
    med = row['dimensionValues'][1]['value']
    s   = row['metricValues'][0]['value']
    n   = row['metricValues'][1]['value']
    br  = float(row['metricValues'][2]['value']) * 100
    dur = float(row['metricValues'][3]['value'])
    key = f'{src}__{med}'.replace(' ', '_').replace('.', '_')
    print(f'IG_{key}: {s}|{n}|{br:.1f}|{dur:.0f}')

# LP 日別流入元（ソース/媒体）
for row in r_lp_daily.get('rows', []):
    d   = row['dimensionValues'][0]['value']
    src = row['dimensionValues'][1]['value']
    med = row['dimensionValues'][2]['value']
    s   = row['metricValues'][0]['value']
    key = f'{src}__{med}'.replace(' ', '_').replace('.', '_')
    print(f'LP_DAILY_{d}_{key}: {s}')
