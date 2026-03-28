"""
GA4 考察をNotionページに追加するスクリプト
~/.claude/tmp/ga4-analysis.txt を読み込んでNotionに追加する
"""
import json, urllib.request, os, sys, ssl
from datetime import datetime, timezone, timedelta

sys.stdout.reconfigure(encoding='utf-8')

# 考察テキスト読み込み
analysis_path = os.path.expanduser('~/.claude/tmp/ga4-analysis.txt')
if not os.path.exists(analysis_path):
    print('SKIP: ga4-analysis.txt not found')
    sys.exit(0)

with open(analysis_path, encoding='utf-8') as f:
    analysis_text = f.read().strip()

if not analysis_text:
    print('SKIP: analysis text is empty')
    sys.exit(0)

# .env 読み込み
env_path = os.path.expanduser('~/.claude/.env')
env = {}
with open(env_path, encoding='utf-8') as f:
    for line in f:
        line = line.strip()
        if line and not line.startswith('#') and '=' in line:
            k, v = line.split('=', 1)
            env[k.strip()] = v.strip().strip('"').strip("'")

notion_token = env.get('NOTION_API_TOKEN', '')
ga4_db_id    = env.get('NOTION_GA4_DB_ID', '')

if not notion_token or not ga4_db_id:
    print('SKIP: token or db_id not set')
    sys.exit(0)

jst   = timezone(timedelta(hours=9))
today = datetime.now(jst).strftime('%Y-%m-%d')

ctx = ssl.create_default_context()
headers = {
    'Authorization': f'Bearer {notion_token}',
    'Notion-Version': '2022-06-28',
    'Content-Type': 'application/json',
}

# 今日のページを取得
check_body = json.dumps({
    'filter': {'property': '日付', 'title': {'equals': today}}
}).encode()
req = urllib.request.Request(
    f'https://api.notion.com/v1/databases/{ga4_db_id}/query',
    data=check_body, headers=headers, method='POST'
)
with urllib.request.urlopen(req, context=ctx) as res:
    results = json.loads(res.read()).get('results', [])

if not results:
    print('SKIP: today page not found')
    sys.exit(0)

page_id = results[0]['id']

def txt(content):
    return {'type': 'text', 'text': {'content': content}}

# 既存の考察ブロック（divider + heading_2「考察」以降）を削除
list_req = urllib.request.Request(
    f'https://api.notion.com/v1/blocks/{page_id}/children?page_size=100',
    headers=headers, method='GET'
)
with urllib.request.urlopen(list_req, context=ctx) as res:
    children = json.loads(res.read()).get('results', [])

analysis_start_idx = None
for i, blk in enumerate(children):
    if blk.get('type') == 'heading_2':
        for rt in blk.get('heading_2', {}).get('rich_text', []):
            if '考察' in rt.get('text', {}).get('content', ''):
                analysis_start_idx = i - 1 if i > 0 else i
                break
    if analysis_start_idx is not None:
        break

if analysis_start_idx is not None:
    for blk in children[analysis_start_idx:]:
        try:
            del_req = urllib.request.Request(
                f'https://api.notion.com/v1/blocks/{blk["id"]}',
                headers=headers, method='DELETE'
            )
            urllib.request.urlopen(del_req, context=ctx).read()
        except Exception:
            pass

# 考察ブロックを追加
blocks = [
    {'object': 'block', 'type': 'divider', 'divider': {}},
    {'object': 'block', 'type': 'heading_2',
     'heading_2': {'rich_text': [txt('💡 考察（レン）')]}},
    {'object': 'block', 'type': 'callout', 'callout': {
        'icon': {'type': 'emoji', 'emoji': '📈'},
        'color': 'purple_background',
        'rich_text': [txt(analysis_text)]
    }}
]

append_req = urllib.request.Request(
    f'https://api.notion.com/v1/blocks/{page_id}/children',
    data=json.dumps({'children': blocks}, ensure_ascii=False).encode('utf-8'),
    headers=headers, method='PATCH'
)
with urllib.request.urlopen(append_req, context=ctx) as res:
    res.read()

print('OK')
