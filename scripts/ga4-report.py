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

with open(cred_path) as f:
    cred = json.load(f)
with open(token_path) as f:
    token = json.load(f)

# トークンリフレッシュ
data = urllib.parse.urlencode({
    'client_id': cred['installed']['client_id'],
    'client_secret': cred['installed']['client_secret'],
    'refresh_token': token['normal']['refresh_token'],
    'grant_type': 'refresh_token'
}).encode()
try:
    req = urllib.request.Request('https://oauth2.googleapis.com/token', data=data, method='POST')
    with urllib.request.urlopen(req) as res:
        access_token = json.loads(res.read())['access_token']
except Exception as e:
    print(f'AUTH_ERROR: {e}', file=sys.stderr)
    sys.exit(1)

property_id = '320411221'
headers = {'Authorization': 'Bearer ' + access_token, 'Content-Type': 'application/json'}
url = f'https://analyticsdata.googleapis.com/v1beta/properties/{property_id}:runReport'

def run(body):
    try:
        req = urllib.request.Request(url, data=json.dumps(body).encode(), headers=headers)
        with urllib.request.urlopen(req) as res:
            return json.loads(res.read())
    except urllib.error.HTTPError as e:
        print(f'GA4_API_ERROR: {e.code} {e.reason}', file=sys.stderr)
        return {}
    except Exception as e:
        print(f'GA4_API_ERROR: {e}', file=sys.stderr)
        return {}

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

# 3c. 広告経由 x トップページ着地（過去7日）
r_top_ad = run({
    'dimensions': [{'name': 'landingPage'}],
    'metrics': [
        {'name': 'sessions'},
        {'name': 'bounceRate'},
        {'name': 'averageSessionDuration'}
    ],
    'dateRanges': [{'startDate': '7daysAgo', 'endDate': 'today'}],
    'dimensionFilter': {
        'andGroup': {'expressions': [
            {'filter': {'fieldName': 'landingPage', 'stringFilter': {'matchType': 'EXACT', 'value': '/'}}},
            {'orGroup': {'expressions': [
                {'filter': {'fieldName': 'sessionDefaultChannelGrouping', 'stringFilter': {'matchType': 'EXACT', 'value': 'Paid Social'}}},
                {'filter': {'fieldName': 'sessionDefaultChannelGrouping', 'stringFilter': {'matchType': 'EXACT', 'value': 'Paid Search'}}},
            ]}}
        ]}
    }
})

# 3d. Instagram 自然流入（プロフィールリンク等、過去7日）
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

# TOP広告（広告経由・トップページ着地、過去7日）
top_ad_m = r_top_ad.get('rows', [{}])[0].get('metricValues', [{'value':'0'}]*3) if r_top_ad.get('rows') else [{'value':'0'}]*3
top_ad_sessions = int(top_ad_m[0]['value'])
top_ad_bounce = float(top_ad_m[1]['value']) * 100
top_ad_dur = float(top_ad_m[2]['value'])
print(f'TOP_AD_SESSIONS_7D: {top_ad_sessions}')
print(f'TOP_AD_BOUNCE_7D: {top_ad_bounce:.1f}')
print(f'TOP_AD_DURATION_7D: {top_ad_dur:.0f}')

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

# --- Notion 書き込み ---
try:
    import ssl as _ssl
    from datetime import datetime, timezone, timedelta

    _env_path = os.path.expanduser('~/.claude/.env')
    _env = {}
    with open(_env_path, encoding='utf-8') as _f:
        for _line in _f:
            _line = _line.strip()
            if _line and not _line.startswith('#') and '=' in _line:
                _k, _v = _line.split('=', 1)
                _env[_k.strip()] = _v.strip().strip('"').strip("'")

    _notion_token = _env.get('NOTION_API_TOKEN', '')
    _ga4_db_id    = _env.get('NOTION_GA4_DB_ID', '')

    if _notion_token and _ga4_db_id:
        _jst = timezone(timedelta(hours=9))
        _today = datetime.now(_jst).strftime('%Y-%m-%d')

        _ctx = _ssl.create_default_context()
        _headers = {
            'Authorization': f'Bearer {_notion_token}',
            'Notion-Version': '2022-06-28',
            'Content-Type': 'application/json',
        }

        # 既存レコード確認（同日の重複を避ける）
        _check_body = json.dumps({
            'filter': {'property': '日付', 'title': {'equals': _today}}
        }).encode()
        _check_req = urllib.request.Request(
            f'https://api.notion.com/v1/databases/{_ga4_db_id}/query',
            data=_check_body, headers=_headers, method='POST'
        )
        with urllib.request.urlopen(_check_req, context=_ctx) as _res:
            _existing = json.loads(_res.read()).get('results', [])

        # DBスキーマに不足プロパティを自動追加（既存プロパティには触れない）
        _db_req = urllib.request.Request(
            f'https://api.notion.com/v1/databases/{_ga4_db_id}',
            headers=_headers, method='GET'
        )
        with urllib.request.urlopen(_db_req, context=_ctx) as _res:
            _db_info = json.loads(_res.read())
        _existing_props = set(_db_info.get('properties', {}).keys())

        _new_props = {}
        for _prop_name in ['TOP広告セッション', 'TOP広告離脱率%', 'TOP広告滞在秒']:
            if _prop_name not in _existing_props:
                _new_props[_prop_name] = {'number': {'format': 'number'}}

        if _new_props:
            try:
                _patch_req = urllib.request.Request(
                    f'https://api.notion.com/v1/databases/{_ga4_db_id}',
                    data=json.dumps({'properties': _new_props}, ensure_ascii=False).encode('utf-8'),
                    headers=_headers, method='PATCH'
                )
                with urllib.request.urlopen(_patch_req, context=_ctx) as _res:
                    _res.read()
                print(f'NOTION_DB_SCHEMA: added {list(_new_props.keys())}')
            except Exception as _e:
                print(f'NOTION_DB_SCHEMA: WARN - {_e}', file=sys.stderr)
                # スキーマ追加失敗してもデータ書き込みは試行する

        # 流入元テキスト（上位5件）
        _sources = []
        for _row in r_source.get('rows', []):
            _ch = _row['dimensionValues'][0]['value']
            _s  = _row['metricValues'][0]['value']
            _sources.append(f'{_ch}: {_s}')
        _source_text = ' / '.join(_sources)

        _props = {
            '日付':            {'title': [{'text': {'content': _today}}]},
            'セッション':      {'number': int(m[0]['value'])},
            'ユーザー':        {'number': int(m[1]['value'])},
            '新規ユーザー':    {'number': int(m[2]['value'])},
            '離脱率%':         {'number': round(bounce, 1)},
            '問い合わせPV':    {'number': int(get_metric(r_contact_yd, 0, 0))},
            'LPセッション':    {'number': int(lp_m[0]['value'])},
            'LP離脱率%':       {'number': round(lp_bounce, 1)},
            'LP平均滞在秒':    {'number': round(lp_dur, 0)},
            'LPCTAクリック':   {'number': int(get_metric(r_lp_cta, 0, 0))},
            'TOP広告セッション': {'number': top_ad_sessions},
            'TOP広告離脱率%':   {'number': round(top_ad_bounce, 1)},
            'TOP広告滞在秒':    {'number': round(top_ad_dur, 0)},
            '流入元':          {'rich_text': [{'text': {'content': _source_text}}]},
        }

        # LP モバイル離脱率
        _lp_mobile_bounce = None
        for _row in r_lp_device.get('rows', []):
            if _row['dimensionValues'][0]['value'] == 'mobile':
                _lp_mobile_bounce = float(_row['metricValues'][1]['value']) * 100
                _props['LPモバイル離脱率%'] = {'number': round(_lp_mobile_bounce, 1)}

        if _existing:
            _page_id = _existing[0]['id']
            _req = urllib.request.Request(
                f'https://api.notion.com/v1/pages/{_page_id}',
                data=json.dumps({'properties': _props}, ensure_ascii=False).encode('utf-8'),
                headers=_headers, method='PATCH'
            )
        else:
            _req = urllib.request.Request(
                'https://api.notion.com/v1/pages',
                data=json.dumps({
                    'parent': {'database_id': _ga4_db_id},
                    'properties': _props
                }, ensure_ascii=False).encode('utf-8'),
                headers=_headers, method='POST'
            )
        with urllib.request.urlopen(_req, context=_ctx) as _res:
            _page_id = json.loads(_res.read())['id'] if not _existing else _page_id

        # --- ページ本文ブロック構築 ---

        def _bar(value, max_val, width=15):
            """Unicode バーチャート"""
            if max_val <= 0:
                return '░' * width
            filled = min(int(value / max_val * width), width)
            return '█' * filled + '░' * (width - filled)

        def _bounce_icon(rate):
            return '🟢' if rate < 50 else ('🟡' if rate < 70 else '🔴')

        def _dur_icon(sec):
            return '🔴' if sec < 30 else ('🟡' if sec < 90 else '🟢')

        def _txt(content):
            return {'type': 'text', 'text': {'content': content}}

        def _h2(text):
            return {'object': 'block', 'type': 'heading_2',
                    'heading_2': {'rich_text': [_txt(text)]}}

        def _callout(content, emoji='📊', color='gray_background'):
            return {'object': 'block', 'type': 'callout',
                    'callout': {'icon': {'type': 'emoji', 'emoji': emoji},
                                'color': color,
                                'rich_text': [_txt(content)]}}

        def _para(content):
            return {'object': 'block', 'type': 'paragraph',
                    'paragraph': {'rich_text': [_txt(content)]}}

        def _bullet(content):
            return {'object': 'block', 'type': 'bulleted_list_item',
                    'bulleted_list_item': {'rich_text': [_txt(content)]}}

        def _divider():
            return {'object': 'block', 'type': 'divider', 'divider': {}}

        _blocks = []

        # --- サイト概要（昨日） ---
        _site_sessions = int(m[0]['value'])
        _site_users    = int(m[1]['value'])
        _site_new      = int(m[2]['value'])
        _site_bounce_icon = _bounce_icon(bounce)
        _blocks.append(_h2('📊 サイト概要（昨日）'))
        _blocks.append(_callout(
            f'セッション: {_site_sessions}　ユーザー: {_site_users}（新規 {_site_new}）\n'
            f'離脱率: {bounce:.1f}%  {_site_bounce_icon}',
            emoji='📊', color='blue_background'
        ))

        # --- 流入元（過去7日）バーチャート ---
        _source_rows = r_source.get('rows', [])
        if _source_rows:
            _blocks.append(_divider())
            _blocks.append(_h2('📈 流入元（過去7日）'))
            _max_s = max(int(r['metricValues'][0]['value']) for r in _source_rows)
            for _row in _source_rows:
                _ch = _row['dimensionValues'][0]['value']
                _s  = int(_row['metricValues'][0]['value'])
                _n  = int(_row['metricValues'][1]['value'])
                _b  = _bar(_s, _max_s, 16)
                _blocks.append(_para(f'{_ch:<22} {_b}  {_s} セッション（新規 {_n}）'))

        # --- LP 状況（過去7日） ---
        _lp_sessions = int(lp_m[0]['value'])
        _lp_cta_cnt  = int(get_metric(r_lp_cta, 0, 0))
        _dur_min = int(lp_dur // 60)
        _dur_sec = int(lp_dur % 60)
        _dur_str = f'{_dur_min}分{_dur_sec}秒' if _dur_min > 0 else f'{int(lp_dur)}秒'

        _lp_alerts = []
        if lp_bounce > 60:
            _lp_alerts.append(f'離脱率高め（{lp_bounce:.1f}%）')
        if _lp_mobile_bounce and _lp_mobile_bounce > 70:
            _lp_alerts.append(f'モバイル離脱率高め（{_lp_mobile_bounce:.1f}%）')
        if _lp_cta_cnt == 0 and _lp_sessions >= 5:
            _lp_alerts.append('CTAクリックがゼロ')
        if _lp_sessions == 0:
            _lp_alerts.append('LPへのアクセスなし')

        _lp_color = 'red_background' if _lp_alerts else ('yellow_background' if lp_bounce > 50 else 'green_background')
        _lp_emoji = '🔴' if _lp_alerts else ('🟡' if lp_bounce > 50 else '🟢')

        _lp_content = (
            f'セッション: {_lp_sessions}\n'
            f'離脱率: {lp_bounce:.1f}%  {_bounce_icon(lp_bounce)}\n'
            f'平均滞在: {_dur_str}  {_dur_icon(lp_dur)}\n'
            f'CTAクリック: {_lp_cta_cnt}  {"🟢" if _lp_cta_cnt > 0 else "🔴"}'
        )
        if _lp_mobile_bounce is not None:
            _lp_content += f'\nモバイル離脱率: {_lp_mobile_bounce:.1f}%  {_bounce_icon(_lp_mobile_bounce)}'

        _blocks.append(_divider())
        _blocks.append(_h2('🏠 LP状況（lp-260319 / 過去7日）'))
        _blocks.append(_callout(_lp_content, emoji=_lp_emoji, color=_lp_color))
        if _lp_alerts:
            _blocks.append(_callout('⚠️ ' + ' / '.join(_lp_alerts), emoji='⚠️', color='yellow_background'))

        # --- お問い合わせ ---
        _contact_yd_pv = int(get_metric(r_contact_yd, 0, 0))
        _contact_yd_u  = int(get_metric(r_contact_yd, 0, 1))
        _contact_7d_pv = int(get_metric(r_contact_7d, 0, 0))
        _contact_7d_u  = int(get_metric(r_contact_7d, 0, 1))
        _contact_emoji = '🟢' if _contact_yd_pv > 0 else '⬜'
        _blocks.append(_divider())
        _blocks.append(_h2('📞 お問い合わせ'))
        _blocks.append(_callout(
            f'昨日: {_contact_yd_pv} PV / {_contact_yd_u} ユーザー\n'
            f'過去7日: {_contact_7d_pv} PV / {_contact_7d_u} ユーザー',
            emoji=_contact_emoji, color='gray_background'
        ))

        # --- 人気ページ Top5（昨日） ---
        if r_pages.get('rows'):
            _blocks.append(_divider())
            _blocks.append(_h2('📄 人気ページ Top5（昨日）'))
            for _i, _row in enumerate(r_pages['rows'], 1):
                _path = _row['dimensionValues'][0]['value']
                _pv   = _row['metricValues'][0]['value']
                _u    = _row['metricValues'][1]['value']
                _blocks.append(_bullet(f'{_path}  →  {_pv} PV / {_u} ユーザー'))

        # 既存ブロックをクリアして新しいブロックを追加
        _list_req = urllib.request.Request(
            f'https://api.notion.com/v1/blocks/{_page_id}/children?page_size=100',
            headers=_headers, method='GET'
        )
        with urllib.request.urlopen(_list_req, context=_ctx) as _res:
            _children = json.loads(_res.read()).get('results', [])
        _delete_errors = 0
        for _blk in _children:
            try:
                _del_req = urllib.request.Request(
                    f'https://api.notion.com/v1/blocks/{_blk["id"]}',
                    headers=_headers, method='DELETE'
                )
                urllib.request.urlopen(_del_req, context=_ctx).read()
            except Exception as _e:
                _delete_errors += 1
                print(f'NOTION_BLOCK_DELETE_WARN: {_blk["id"]} - {_e}', file=sys.stderr)
        if _delete_errors:
            print(f'NOTION_BLOCK_DELETE: {_delete_errors} errors', file=sys.stderr)

        # ブロック追加（100件ずつ）
        for _i in range(0, len(_blocks), 100):
            _chunk = _blocks[_i:_i+100]
            _append_req = urllib.request.Request(
                f'https://api.notion.com/v1/blocks/{_page_id}/children',
                data=json.dumps({'children': _chunk}, ensure_ascii=False).encode('utf-8'),
                headers=_headers, method='PATCH'
            )
            with urllib.request.urlopen(_append_req, context=_ctx) as _res:
                _res.read()

        print('NOTION_GA4_PUSH: OK')
    else:
        print('NOTION_GA4_PUSH: SKIP (token or db_id not set)')
except Exception as _e:
    print(f'NOTION_GA4_PUSH: ERROR ({_e})')
