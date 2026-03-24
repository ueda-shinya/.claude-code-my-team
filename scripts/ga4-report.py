"""
GA4 日次レポートスクリプト
モーニングブリーフィングから呼び出す用

出力形式（stdout）:
  CONTACT_VIEWS: <件数>  ← /contact* のページビュー（昨日）
  CONTACT_VIEWS_7D: <件数>  ← /contact* のページビュー（過去7日）
"""
import json, urllib.request, urllib.parse, os, sys
from datetime import datetime, timezone, timedelta
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

# /contact* のページビュー（昨日 & 過去7日）
contact_filter = {
    'filter': {
        'fieldName': 'pagePath',
        'stringFilter': {'matchType': 'BEGINS_WITH', 'value': '/contact'}
    }
}

r_yesterday = run({
    'metrics': [{'name': 'screenPageViews'}, {'name': 'totalUsers'}],
    'dateRanges': [{'startDate': 'yesterday', 'endDate': 'yesterday'}],
    'dimensionFilter': contact_filter
})

r_7d = run({
    'metrics': [{'name': 'screenPageViews'}, {'name': 'totalUsers'}],
    'dateRanges': [{'startDate': '7daysAgo', 'endDate': 'today'}],
    'dimensionFilter': contact_filter
})

def get_metric(r, idx=0):
    rows = r.get('rows', [])
    if not rows:
        return '0'
    return rows[0]['metricValues'][idx]['value']

pv_yesterday = get_metric(r_yesterday, 0)
users_yesterday = get_metric(r_yesterday, 1)
pv_7d = get_metric(r_7d, 0)
users_7d = get_metric(r_7d, 1)

print(f'CONTACT_VIEWS: {pv_yesterday}')
print(f'CONTACT_USERS: {users_yesterday}')
print(f'CONTACT_VIEWS_7D: {pv_7d}')
print(f'CONTACT_USERS_7D: {users_7d}')
