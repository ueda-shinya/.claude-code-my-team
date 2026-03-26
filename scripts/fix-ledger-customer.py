#!/usr/bin/env python3
"""見積・請求台帳: 識別記号から顧客リレーションを埋める"""

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
CRM_DB    = env['NOTION_CRM_DB_ID']
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
# Step 1: CRM から 識別記号 → page_id マップ作成
# =============================================
print('=== Step1: CRM 識別記号マップ作成 ===')
shiki_map = {}  # 識別記号 → page_id
cursor = None
while True:
    body = {'page_size': 100}
    if cursor:
        body['start_cursor'] = cursor
    res = notion_req('POST', f'/databases/{CRM_DB}/query', body)
    for page in res.get('results', []):
        props = page['properties']
        # 識別記号フィールド（richtext型）
        shiki_prop = props.get('識別記号', {})
        shiki_arr  = shiki_prop.get('rich_text', [])
        shiki = ''.join(t.get('plain_text','') for t in shiki_arr).strip()
        if shiki:
            shiki_map[shiki] = page['id']
    if not res.get('has_more'):
        break
    cursor = res.get('next_cursor')
    time.sleep(0.3)

print(f'  識別記号マップ: {len(shiki_map)} 件')
for k, v in sorted(shiki_map.items())[:10]:
    print(f'    {k} → {v[:8]}...')

# =============================================
# Step 2: 台帳の顧客が空のレコードを取得
# =============================================
print('\n=== Step2: 台帳レコード取得（顧客空のもの） ===')
pages = []
cursor = None
while True:
    body = {'page_size': 100}
    if cursor:
        body['start_cursor'] = cursor
    res = notion_req('POST', f'/databases/{LEDGER_DB}/query', body)
    for page in res.get('results', []):
        props = page['properties']
        # 顧客リレーションが空かチェック
        kokyaku = props.get('顧客', {}).get('relation', [])
        if not kokyaku:
            pages.append(page)
    if not res.get('has_more'):
        break
    cursor = res.get('next_cursor')
    time.sleep(0.3)

print(f'  顧客空レコード: {len(pages)} 件')

# =============================================
# Step 3: 管理番号から識別記号を抽出して紐付け
# =============================================
print('\n=== Step3: 識別記号で顧客紐付け ===')

def extract_shikibetsu(bangou):
    """管理番号から識別記号を抽出: 20220824A-1 → A"""
    m = re.search(r'\d{8}([A-Z]{1,3})-\d+', bangou)
    return m.group(1) if m else None

ok = err = skip_zzz = skip_notfound = 0

for i, page in enumerate(pages):
    props   = page['properties']
    page_id = page['id']

    # 管理番号取得
    bangou_prop = props.get('管理番号') or props.get('番号') or {}
    title_arr   = bangou_prop.get('title', [])
    bangou      = ''.join(t.get('plain_text','') for t in title_arr)

    shiki = extract_shikibetsu(bangou)

    if not shiki:
        skip_notfound += 1
        print(f'  スキップ（識別記号抽出失敗）: {bangou}')
        continue

    # ZZZ は空白のまま
    if shiki == 'ZZZ':
        skip_zzz += 1
        continue

    crm_id = shiki_map.get(shiki)
    if not crm_id:
        skip_notfound += 1
        print(f'  スキップ（CRM未登録）: {bangou} 識別記号={shiki}')
        continue

    try:
        notion_req('PATCH', f'/pages/{page_id}', {
            'properties': {'顧客': {'relation': [{'id': crm_id}]}}
        }, timeout=30)
        ok += 1
        if ok % 10 == 0:
            print(f'  [{ok}件更新済み]...')
        time.sleep(0.35)
    except Exception as e:
        err += 1
        print(f'  エラー [{bangou}]: {e}')
        time.sleep(0.5)

print(f'\n完了: 紐付け {ok} / ZZZスキップ {skip_zzz} / 未発見 {skip_notfound} / エラー {err}')
