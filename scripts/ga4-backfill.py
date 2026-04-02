"""
GA4 日次レポート バックフィルスクリプト
NOTION_GA4_DB_ID の全データをクリアして、2026-03-01 ～ 今日の日次レポートを再投入する。

実行:
  python ~/.claude/scripts/ga4-backfill.py
"""

import json, urllib.request, urllib.parse, urllib.error, os, sys, time, ssl
from datetime import date, timedelta

sys.stdout.reconfigure(encoding='utf-8')

# LP CTAクリック集計開始日（これより前のデータは0件扱い）
LP_CTA_START_DATE = '2026-03-21'

# バックフィル対象期間
BACKFILL_FROM = date(2026, 3, 1)

# ---------------------------------------------------------------------------
# 認証情報・環境変数読み込み
# ---------------------------------------------------------------------------

cred_path  = os.path.expanduser('~/.claude/google-oauth-credentials.json')
token_path = os.path.expanduser('~/.claude/mcp-google-calendar-token.json')
env_path   = os.path.expanduser('~/.claude/.env')

with open(cred_path, encoding='utf-8') as f:
    cred = json.load(f)
with open(token_path, encoding='utf-8') as f:
    token = json.load(f)

_env = {}
with open(env_path, encoding='utf-8') as f:
    for line in f:
        line = line.strip()
        if line and not line.startswith('#') and '=' in line:
            k, v = line.split('=', 1)
            _env[k.strip()] = v.strip().strip('"').strip("'")

NOTION_API_TOKEN = _env.get('NOTION_API_TOKEN', '')
NOTION_GA4_DB_ID = _env.get('NOTION_GA4_DB_ID', '')

if not NOTION_API_TOKEN or not NOTION_GA4_DB_ID:
    print('ERROR: NOTION_API_TOKEN または NOTION_GA4_DB_ID が .env に未設定です', file=sys.stderr)
    sys.exit(1)

# SSL context（全urlopen共通）
_ctx = ssl.create_default_context()

# ---------------------------------------------------------------------------
# GA4 アクセストークン取得
# ---------------------------------------------------------------------------

data = urllib.parse.urlencode({
    'client_id':     cred['installed']['client_id'],
    'client_secret': cred['installed']['client_secret'],
    'refresh_token': token['normal']['refresh_token'],
    'grant_type':    'refresh_token'
}).encode()

try:
    req = urllib.request.Request('https://oauth2.googleapis.com/token', data=data, method='POST')
    with urllib.request.urlopen(req, context=_ctx) as res:
        access_token = json.loads(res.read())['access_token']
except Exception as e:
    print(f'GA4 認証エラー: {e}', file=sys.stderr)
    sys.exit(1)

property_id = '320411221'
ga4_headers = {
    'Authorization': 'Bearer ' + access_token,
    'Content-Type': 'application/json',
}
ga4_url = f'https://analyticsdata.googleapis.com/v1beta/properties/{property_id}:runReport'

notion_headers = {
    'Authorization': f'Bearer {NOTION_API_TOKEN}',
    'Notion-Version': '2022-06-28',
    'Content-Type': 'application/json',
}

# ---------------------------------------------------------------------------
# ユーティリティ
# ---------------------------------------------------------------------------

def run_ga4(body):
    """GA4 レポートクエリを実行する。失敗時は {} を返す。"""
    try:
        req = urllib.request.Request(
            ga4_url,
            data=json.dumps(body).encode(),
            headers=ga4_headers
        )
        with urllib.request.urlopen(req, context=_ctx) as res:
            return json.loads(res.read())
    except urllib.error.HTTPError as e:
        print(f'  GA4_API_ERROR: {e.code} {e.reason}', file=sys.stderr)
        return {}
    except Exception as e:
        print(f'  GA4_API_ERROR: {e}', file=sys.stderr)
        return {}


def notion_request(path, method='GET', body=None):
    """Notion API リクエストを送信する。失敗時は例外を再送出する。"""
    url = f'https://api.notion.com/v1{path}'
    data = json.dumps(body, ensure_ascii=False).encode('utf-8') if body is not None else None
    req = urllib.request.Request(url, data=data, headers=notion_headers, method=method)
    with urllib.request.urlopen(req, context=_ctx) as res:
        return json.loads(res.read())


def get_metric(r, row=0, idx=0):
    """GA4 レスポンスから指定行・指定メトリクスの値を取得する。"""
    rows = r.get('rows', [])
    if not rows or row >= len(rows):
        return '0'
    return rows[row]['metricValues'][idx]['value']


# ---------------------------------------------------------------------------
# Notion ブロック構築ヘルパー
# ---------------------------------------------------------------------------

def _bar(value, max_val, width=15):
    """Unicode バーチャートを生成する。"""
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


# ---------------------------------------------------------------------------
# ステップ1: 既存ページを全削除（アーカイブ）
# ---------------------------------------------------------------------------

print('ステップ1: 既存ページを全削除中...')

all_pages = []
cursor = None
while True:
    body = {'page_size': 100}
    if cursor:
        body['start_cursor'] = cursor
    result = notion_request(f'/databases/{NOTION_GA4_DB_ID}/query', method='POST', body=body)
    all_pages.extend(result.get('results', []))
    if result.get('has_more'):
        cursor = result.get('next_cursor')
    else:
        break
    time.sleep(0.3)

delete_count = 0
for page in all_pages:
    try:
        notion_request(
            f'/pages/{page["id"]}',
            method='PATCH',
            body={'archived': True}
        )
        delete_count += 1
        time.sleep(0.3)
    except Exception as e:
        print(f'  WARN: ページ削除失敗 {page["id"]}: {e}', file=sys.stderr)

print(f'DELETE: {delete_count}件削除')

# ---------------------------------------------------------------------------
# ステップ2: 各日のデータを投入
# ---------------------------------------------------------------------------

to_date   = date.today()
total_days = (to_date - BACKFILL_FROM).days + 1
error_dates = []  # エラーが発生した日付を蓄積するリスト

print(f'ステップ2: {BACKFILL_FROM} ～ {to_date} の {total_days}日分を投入中...')

for i, offset in enumerate(range(total_days)):
    D = BACKFILL_FROM + timedelta(days=offset)
    date_str   = D.strftime('%Y-%m-%d')         # YYYY-MM-DD
    date_label = f'{D.year}/{D.month}/{D.day}'  # Notionタイトル用

    # GA4クエリ用日付
    yesterday_eq = date_str
    d7_start     = (D - timedelta(days=6)).strftime('%Y-%m-%d')
    d7_end       = date_str

    # LP CTA開始日を考慮
    lp_cta_start = max(LP_CTA_START_DATE, d7_start)

    print(f'[{i+1}/{total_days}] {date_str} ...', end=' ', flush=True)

    # --- GA4 クエリ群 ---

    lp_filter = {
        'filter': {
            'fieldName': 'pagePath',
            'stringFilter': {'matchType': 'BEGINS_WITH', 'value': '/lp-260319'}
        }
    }
    contact_filter = {
        'filter': {
            'fieldName': 'pagePath',
            'stringFilter': {'matchType': 'BEGINS_WITH', 'value': '/contact'}
        }
    }

    # 1. サイト全体概要（その日）
    r_overview = run_ga4({
        'metrics': [
            {'name': 'sessions'}, {'name': 'totalUsers'},
            {'name': 'newUsers'}, {'name': 'bounceRate'}
        ],
        'dateRanges': [{'startDate': yesterday_eq, 'endDate': yesterday_eq}],
    })
    time.sleep(0.3)

    # 2. /contact* PV（その日）
    r_contact_yd = run_ga4({
        'metrics': [{'name': 'screenPageViews'}, {'name': 'totalUsers'}],
        'dateRanges': [{'startDate': yesterday_eq, 'endDate': yesterday_eq}],
        'dimensionFilter': contact_filter
    })
    time.sleep(0.3)

    # 3. /contact* PV 7日
    r_contact_7d = run_ga4({
        'metrics': [{'name': 'screenPageViews'}, {'name': 'totalUsers'}],
        'dateRanges': [{'startDate': d7_start, 'endDate': d7_end}],
        'dimensionFilter': contact_filter
    })
    time.sleep(0.3)

    # 4. 流入元 上位5件（7日）
    r_source = run_ga4({
        'dimensions': [{'name': 'sessionDefaultChannelGrouping'}],
        'metrics': [{'name': 'sessions'}, {'name': 'newUsers'}],
        'dateRanges': [{'startDate': d7_start, 'endDate': d7_end}],
        'orderBys': [{'metric': {'metricName': 'sessions'}, 'desc': True}],
        'limit': 5
    })
    time.sleep(0.3)

    # 5. 広告詳細（7日、apexlineキャンペーン + Paid Social/Search）
    r_ads = run_ga4({
        'dimensions': [{'name': 'sessionSource'}, {'name': 'sessionMedium'}],
        'metrics': [
            {'name': 'sessions'}, {'name': 'newUsers'},
            {'name': 'bounceRate'}, {'name': 'averageSessionDuration'}
        ],
        'dateRanges': [{'startDate': d7_start, 'endDate': d7_end}],
        'dimensionFilter': {
            'andGroup': {'expressions': [
                {'orGroup': {'expressions': [
                    {'filter': {'fieldName': 'sessionDefaultChannelGrouping',
                                'stringFilter': {'matchType': 'EXACT', 'value': 'Paid Social'}}},
                    {'filter': {'fieldName': 'sessionDefaultChannelGrouping',
                                'stringFilter': {'matchType': 'EXACT', 'value': 'Paid Search'}}},
                ]}},
                {'filter': {'fieldName': 'sessionCampaignName',
                            'stringFilter': {'matchType': 'EXACT', 'value': 'apexline'}}}
            ]}
        },
        'orderBys': [{'metric': {'metricName': 'sessions'}, 'desc': True}],
        'limit': 10
    })
    time.sleep(0.3)

    # 6. TOP広告経由LP着地（7日、landingPage BEGINS_WITH '/lp-260319' + Paid Social/Search）
    r_top_ad = run_ga4({
        'metrics': [
            {'name': 'sessions'},
            {'name': 'bounceRate'},
            {'name': 'averageSessionDuration'}
        ],
        'dateRanges': [{'startDate': d7_start, 'endDate': d7_end}],
        'dimensionFilter': {
            'andGroup': {'expressions': [
                {'filter': {'fieldName': 'landingPage',
                            'stringFilter': {'matchType': 'BEGINS_WITH', 'value': '/lp-260319'}}},
                {'orGroup': {'expressions': [
                    {'filter': {'fieldName': 'sessionDefaultChannelGrouping',
                                'stringFilter': {'matchType': 'EXACT', 'value': 'Paid Social'}}},
                    {'filter': {'fieldName': 'sessionDefaultChannelGrouping',
                                'stringFilter': {'matchType': 'EXACT', 'value': 'Paid Search'}}},
                ]}}
            ]}
        }
    })
    time.sleep(0.3)

    # 7. 人気ページ Top5（その日）
    r_pages = run_ga4({
        'dimensions': [{'name': 'pagePath'}],
        'metrics': [{'name': 'screenPageViews'}, {'name': 'totalUsers'}],
        'dateRanges': [{'startDate': yesterday_eq, 'endDate': yesterday_eq}],
        'orderBys': [{'metric': {'metricName': 'screenPageViews'}, 'desc': True}],
        'limit': 5
    })
    time.sleep(0.3)

    # 8. LP概要 7日（pagePath BEGINS_WITH '/lp-260319'）
    r_lp = run_ga4({
        'metrics': [
            {'name': 'sessions'}, {'name': 'bounceRate'},
            {'name': 'averageSessionDuration'}
        ],
        'dateRanges': [{'startDate': d7_start, 'endDate': d7_end}],
        'dimensionFilter': lp_filter
    })
    time.sleep(0.3)

    # 9. LP CTA clicks（LP_CTA_START_DATE以降のみ。それより前の日付は0件扱い）
    if D >= date.fromisoformat(LP_CTA_START_DATE):
        r_lp_cta = run_ga4({
            'metrics': [{'name': 'eventCount'}],
            'dateRanges': [{'startDate': lp_cta_start, 'endDate': yesterday_eq}],
            'dimensionFilter': {
                'andGroup': {'expressions': [
                    lp_filter,
                    {'filter': {'fieldName': 'eventName',
                                'stringFilter': {'matchType': 'EXACT', 'value': 'cta_click'}}}
                ]}
            }
        })
        time.sleep(0.3)
    else:
        r_lp_cta = {}

    # 10. LP デバイス別 7日（LP filter × deviceCategory）
    r_lp_device = run_ga4({
        'dimensions': [{'name': 'deviceCategory'}],
        'metrics': [{'name': 'sessions'}, {'name': 'bounceRate'}],
        'dateRanges': [{'startDate': d7_start, 'endDate': d7_end}],
        'dimensionFilter': lp_filter,
        'orderBys': [{'metric': {'metricName': 'sessions'}, 'desc': True}]
    })
    time.sleep(0.3)

    # --- メトリクス計算 ---

    m = (r_overview.get('rows', [{}])[0].get('metricValues', [{'value': '0'}] * 4)
         if r_overview.get('rows') else [{'value': '0'}] * 4)
    bounce = float(m[3]['value']) * 100

    lp_m = (r_lp.get('rows', [{}])[0].get('metricValues', [{'value': '0'}] * 3)
            if r_lp.get('rows') else [{'value': '0'}] * 3)
    lp_bounce = float(lp_m[1]['value']) * 100
    lp_dur    = float(lp_m[2]['value'])

    top_ad_m = (r_top_ad.get('rows', [{}])[0].get('metricValues', [{'value': '0'}] * 3)
                if r_top_ad.get('rows') else [{'value': '0'}] * 3)
    top_ad_sessions = int(top_ad_m[0]['value'])
    top_ad_bounce   = float(top_ad_m[1]['value']) * 100
    top_ad_dur      = float(top_ad_m[2]['value'])

    lp_cta_cnt = int(get_metric(r_lp_cta, 0, 0))

    # LP モバイル離脱率
    lp_mobile_bounce = None
    for _row in r_lp_device.get('rows', []):
        if _row['dimensionValues'][0]['value'] == 'mobile':
            lp_mobile_bounce = float(_row['metricValues'][1]['value']) * 100

    # 流入元テキスト（上位5件）
    source_parts = []
    for _row in r_source.get('rows', []):
        _ch = _row['dimensionValues'][0]['value']
        _s  = _row['metricValues'][0]['value']
        source_parts.append(f'{_ch}: {_s}')
    source_text = ' / '.join(source_parts)

    # --- Notion プロパティ ---
    props = {
        '日付':              {'title': [{'text': {'content': date_str}}]},
        'セッション':        {'number': int(m[0]['value'])},
        'ユーザー':          {'number': int(m[1]['value'])},
        '新規ユーザー':      {'number': int(m[2]['value'])},
        '離脱率%':           {'number': round(bounce, 1)},
        '問い合わせPV':      {'number': int(get_metric(r_contact_yd, 0, 0))},
        'LPセッション':      {'number': int(lp_m[0]['value'])},
        'LP離脱率%':         {'number': round(lp_bounce, 1)},
        'LP平均滞在秒':      {'number': round(lp_dur, 0)},
        'LPCTAクリック':     {'number': lp_cta_cnt},
        'TOP広告セッション': {'number': top_ad_sessions},
        'TOP広告離脱率%':    {'number': round(top_ad_bounce, 1)},
        'TOP広告滞在秒':     {'number': round(top_ad_dur, 0)},
        '流入元':            {'rich_text': [{'text': {'content': source_text}}]},
    }
    if lp_mobile_bounce is not None:
        props['LPモバイル離脱率%'] = {'number': round(lp_mobile_bounce, 1)}

    # --- Notion ブロック構築 ---
    blocks = []

    # サイト概要（その日）
    blocks.append(_h2('📊 サイト概要（日次）'))
    blocks.append(_callout(
        f'セッション: {int(m[0]["value"])}　ユーザー: {int(m[1]["value"])}（新規 {int(m[2]["value"])}）\n'
        f'離脱率: {bounce:.1f}%  {_bounce_icon(bounce)}',
        emoji='📊', color='blue_background'
    ))

    # 流入元（7日）
    source_rows = r_source.get('rows', [])
    if source_rows:
        blocks.append(_divider())
        blocks.append(_h2('📈 流入元（7日）'))
        max_s = max(int(r['metricValues'][0]['value']) for r in source_rows)
        for _row in source_rows:
            _ch = _row['dimensionValues'][0]['value']
            _s  = int(_row['metricValues'][0]['value'])
            _n  = int(_row['metricValues'][1]['value'])
            _b  = _bar(_s, max_s, 16)
            blocks.append(_para(f'{_ch:<22} {_b}  {_s} セッション（新規 {_n}）'))

    # LP状況（7日）
    lp_sessions = int(lp_m[0]['value'])
    dur_min = int(lp_dur // 60)
    dur_sec = int(lp_dur % 60)
    dur_str = f'{dur_min}分{dur_sec}秒' if dur_min > 0 else f'{int(lp_dur)}秒'

    lp_alerts = []
    if lp_bounce > 60:
        lp_alerts.append(f'離脱率高め（{lp_bounce:.1f}%）')
    if lp_mobile_bounce is not None and lp_mobile_bounce > 70:
        lp_alerts.append(f'モバイル離脱率高め（{lp_mobile_bounce:.1f}%）')
    if lp_cta_cnt == 0 and lp_sessions >= 5:
        lp_alerts.append('CTAクリックがゼロ')
    if lp_sessions == 0:
        lp_alerts.append('LPへのアクセスなし')

    lp_color = 'red_background' if lp_alerts else ('yellow_background' if lp_bounce > 50 else 'green_background')
    lp_emoji = '🔴' if lp_alerts else ('🟡' if lp_bounce > 50 else '🟢')

    lp_content = (
        f'セッション: {lp_sessions}\n'
        f'離脱率: {lp_bounce:.1f}%  {_bounce_icon(lp_bounce)}\n'
        f'平均滞在: {dur_str}  {_dur_icon(lp_dur)}\n'
        f'CTAクリック: {lp_cta_cnt}  {"🟢" if lp_cta_cnt > 0 else "🔴"}'
    )
    if lp_mobile_bounce is not None:
        lp_content += f'\nモバイル離脱率: {lp_mobile_bounce:.1f}%  {_bounce_icon(lp_mobile_bounce)}'

    blocks.append(_divider())
    blocks.append(_h2('🏠 LP状況（lp-260319 / 7日）'))
    blocks.append(_callout(lp_content, emoji=lp_emoji, color=lp_color))
    if lp_alerts:
        blocks.append(_callout('⚠️ ' + ' / '.join(lp_alerts), emoji='⚠️', color='yellow_background'))

    # お問い合わせ
    contact_yd_pv = int(get_metric(r_contact_yd, 0, 0))
    contact_yd_u  = int(get_metric(r_contact_yd, 0, 1))
    contact_7d_pv = int(get_metric(r_contact_7d, 0, 0))
    contact_7d_u  = int(get_metric(r_contact_7d, 0, 1))
    contact_emoji = '🟢' if contact_yd_pv > 0 else '⬜'
    blocks.append(_divider())
    blocks.append(_h2('📞 お問い合わせ'))
    blocks.append(_callout(
        f'当日: {contact_yd_pv} PV / {contact_yd_u} ユーザー\n'
        f'過去7日: {contact_7d_pv} PV / {contact_7d_u} ユーザー',
        emoji=contact_emoji, color='gray_background'
    ))

    # 人気ページ Top5（その日）
    if r_pages.get('rows'):
        blocks.append(_divider())
        blocks.append(_h2('📄 人気ページ Top5（日次）'))
        for _idx, _row in enumerate(r_pages['rows'], 1):
            _path = _row['dimensionValues'][0]['value']
            _pv   = _row['metricValues'][0]['value']
            _u    = _row['metricValues'][1]['value']
            blocks.append(_bullet(f'{_path}  →  {_pv} PV / {_u} ユーザー'))

    # --- Notion ページ作成 ---
    try:
        # レスポンスからページIDを直接取得（再クエリ不要）
        page_res = notion_request(
            '/pages',
            method='POST',
            body={
                'parent': {'database_id': NOTION_GA4_DB_ID},
                'properties': props,
                'children': blocks[:100],  # 最初の100ブロックは作成時に含める
            }
        )
        page_id = page_res.get('id')

        # ブロックが100件超の場合は追加投入
        # （通常はそこまで多くならないが念のため）
        if len(blocks) > 100 and page_id:
            for chunk_start in range(100, len(blocks), 100):
                chunk = blocks[chunk_start:chunk_start + 100]
                notion_request(
                    f'/blocks/{page_id}/children',
                    method='PATCH',
                    body={'children': chunk}
                )
                time.sleep(0.3)

        print('OK')
    except Exception as e:
        print(f'FAIL ({e})', file=sys.stderr)
        error_dates.append(date_str)

    time.sleep(0.3)

if error_dates:
    print(f'DONE (errors on: {", ".join(error_dates)})')
else:
    print('DONE')
