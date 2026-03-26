#!/usr/bin/env python3
"""見積・請求台帳 Notion スキーマ更新 + レコード一括修正"""

import json, urllib.request, ssl, os, sys, time, re
sys.stdout.reconfigure(encoding='utf-8')

ENV_PATH = os.path.expanduser('~/.claude/.env')
def load_env():
    env = {}
    with open(ENV_PATH, encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                k, v = line.split('=', 1)
                env[k.strip()] = v.strip()
    return env

env = load_env()
TOKEN     = env['NOTION_API_TOKEN']
LEDGER_DB = env['NOTION_LEDGER_DB_ID']
HEADERS   = {
    'Authorization': f'Bearer {TOKEN}',
    'Notion-Version': '2022-06-28',
    'Content-Type': 'application/json',
}
ctx = ssl.create_default_context()

def notion_req(method, path, body=None, timeout=60):
    url = f'https://api.notion.com/v1{path}'
    data = json.dumps(body).encode() if body else None
    req = urllib.request.Request(url, data=data, headers=HEADERS, method=method)
    with urllib.request.urlopen(req, context=ctx, timeout=timeout) as res:
        return json.loads(res.read())

# =============================================
# Step 1: DB スキーマ更新
# =============================================
print('=== Step1: DBスキーマ更新 ===')
patch_body = {
    'properties': {
        # 番号 → 管理番号 に名前変更
        '番号': {
            'name': '管理番号',
            'title': {}
        },
        # ステータス 追加
        'ステータス': {
            'select': {
                'options': [
                    {'name': '見積り',   'color': 'gray'},
                    {'name': '着手中',   'color': 'blue'},
                    {'name': '納品済み', 'color': 'yellow'},
                    {'name': '請求済み', 'color': 'orange'},
                    {'name': '入金済み', 'color': 'green'},
                    {'name': 'キャンセル', 'color': 'red'},
                ]
            }
        }
    }
}
try:
    res = notion_req('PATCH', f'/databases/{LEDGER_DB}', patch_body, timeout=120)
    print('  スキーマ更新完了')
except Exception as e:
    print(f'  スキーマ更新エラー: {e}')
    # タイムアウトでも実際には完了している場合があるため続行
    print('  タイムアウトの可能性あり。続行します。')

time.sleep(2)

# =============================================
# Step 2: 全レコード取得
# =============================================
print('\n=== Step2: 全レコード取得 ===')
pages = []
cursor = None
while True:
    body = {'page_size': 100}
    if cursor:
        body['start_cursor'] = cursor
    res = notion_req('POST', f'/databases/{LEDGER_DB}/query', body, timeout=60)
    pages.extend(res.get('results', []))
    if not res.get('has_more'):
        break
    cursor = res.get('next_cursor')
    time.sleep(0.3)

print(f'  取得件数: {len(pages)}')

# =============================================
# Step 3: 各レコードの 管理番号 修正 + ステータス判定
# =============================================
def determine_status(props):
    """ステータスを Notion フィールドから判定"""
    # メモ に 'キャンセル' → キャンセル
    memo_text = ''
    for field in ['メモ']:
        rt = props.get(field, {}).get('rich_text', [])
        memo_text += ''.join(t.get('plain_text','') for t in rt)
    if 'キャンセル' in memo_text:
        return 'キャンセル'

    # 入金状況
    nyukin_select = props.get('入金状況', {}).get('select')
    nyukin = nyukin_select.get('name') if nyukin_select else None

    # 種別
    shubetsu_select = props.get('種別', {}).get('select')
    shubetsu = shubetsu_select.get('name') if shubetsu_select else None

    # 入金日
    nyukinbi = props.get('入金日', {}).get('date')

    # 納品日
    nōhinbi = props.get('納品日', {}).get('date')

    if nyukin == '入金済み' or nyukinbi or shubetsu == '領収書':
        return '入金済み'
    if shubetsu == '請求書':
        return '請求済み'
    if shubetsu == '見積書':
        return '見積り'
    if nōhinbi:
        return '納品済み'
    return None  # 空白

print('\n=== Step3: レコード一括更新 ===')
ok = err = skip = 0

for i, page in enumerate(pages):
    props = page['properties']
    page_id = page['id']

    # 管理番号（旧: 番号）取得 - どちらの名前でも対応
    bangou_prop = props.get('管理番号') or props.get('番号') or {}
    title_arr = bangou_prop.get('title', [])
    current_bangou = ''.join(t.get('plain_text','') for t in title_arr)

    # () を除去: 20220824(A)-1 → 20220824A-1
    new_bangou = re.sub(r'\(([A-Z]{1,3})\)', r'\1', current_bangou)

    # ステータス判定
    status = determine_status(props)

    # 更新が不要なら skip
    changed = (new_bangou != current_bangou) or (status is not None)
    if not changed:
        skip += 1
        continue

    update_props = {}
    if new_bangou != current_bangou:
        update_props['管理番号'] = {'title': [{'text': {'content': new_bangou}}]}
    if status:
        update_props['ステータス'] = {'select': {'name': status}}

    try:
        notion_req('PATCH', f'/pages/{page_id}', {'properties': update_props}, timeout=30)
        ok += 1
        if (ok) % 20 == 0:
            print(f'  [{i+1}/{len(pages)}] 更新済み...')
        time.sleep(0.35)
    except Exception as e:
        err += 1
        print(f'  エラー [{current_bangou}]: {e}')
        time.sleep(0.5)

print(f'\n完了: 更新 {ok} / スキップ {skip} / エラー {err}')
