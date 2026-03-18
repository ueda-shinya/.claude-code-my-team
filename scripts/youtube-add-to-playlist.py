"""
YouTube ダイジェストの動画を「Claude厳選」プレイリストに同期する
- プレイリストが存在しない場合は自動作成
- ダイジェストにない動画はプレイリストから削除（視聴済み扱い）
- ダイジェストにある動画で未追加のものを追加
"""
import json, urllib.request, urllib.parse, os, sys, re
sys.stdout.reconfigure(encoding='utf-8')

PLAYLIST_NAME = 'Claude厳選'
cred_path = os.path.expanduser('~/.claude/google-oauth-credentials.json')
token_path = os.path.expanduser('~/.claude/mcp-google-calendar-token.json')
digest_path = os.path.expanduser('~/.claude/youtube-digest.md')

# トークンリフレッシュ
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

headers = {'Authorization': f'Bearer {access_token}', 'Content-Type': 'application/json'}

def api_get(url):
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req) as res:
        return json.loads(res.read())

def api_post(url, body):
    data = json.dumps(body).encode('utf-8')
    req = urllib.request.Request(url, data=data, headers=headers, method='POST')
    with urllib.request.urlopen(req) as res:
        return json.loads(res.read())

def api_delete(url):
    req = urllib.request.Request(url, headers=headers, method='DELETE')
    with urllib.request.urlopen(req) as res:
        return res.status

# プレイリスト検索
playlists = api_get('https://www.googleapis.com/youtube/v3/playlists?part=snippet&mine=true&maxResults=50')
playlist_id = None
for item in playlists.get('items', []):
    if item['snippet']['title'] == PLAYLIST_NAME:
        playlist_id = item['id']
        break

# なければ作成
if not playlist_id:
    result = api_post('https://www.googleapis.com/youtube/v3/playlists?part=snippet,status', {
        'snippet': {'title': PLAYLIST_NAME, 'description': 'Claude（アスカ）が毎朝ピックアップしたAI・WordPress動画'},
        'status': {'privacyStatus': 'private'}
    })
    playlist_id = result['id']
    print(f'プレイリスト作成: {playlist_id}')

# プレイリストの現在のアイテムを取得（videoId → playlistItemId のマップ）
current_items = {}
page_token = None
while True:
    url = f'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId={playlist_id}&maxResults=50'
    if page_token:
        url += f'&pageToken={page_token}'
    result = api_get(url)
    for item in result.get('items', []):
        vid = item['snippet']['resourceId']['videoId']
        item_id = item['id']
        current_items[vid] = item_id
    page_token = result.get('nextPageToken')
    if not page_token:
        break

# ダイジェストから動画IDを抽出
with open(digest_path, encoding='utf-8') as f:
    content = f.read()

digest_ids = re.findall(r'youtube\.com/watch\?v=([A-Za-z0-9_-]+)', content)
digest_set = set(digest_ids)

# ダイジェストにない動画を削除
deleted_count = 0
for vid, item_id in current_items.items():
    if vid not in digest_set:
        try:
            api_delete(f'https://www.googleapis.com/youtube/v3/playlistItems?id={item_id}')
            deleted_count += 1
        except Exception as e:
            print(f'削除失敗: {vid} - {e}')

# ダイジェストにある動画で未追加のものを追加
added_count = 0
for vid in digest_ids:
    if vid in current_items:
        continue
    try:
        api_post('https://www.googleapis.com/youtube/v3/playlistItems?part=snippet', {
            'snippet': {
                'playlistId': playlist_id,
                'resourceId': {'kind': 'youtube#video', 'videoId': vid}
            }
        })
        added_count += 1
    except Exception as e:
        print(f'追加失敗: {vid} - {e}')

print(f'完了: {added_count}件追加 / {deleted_count}件削除')
print(f'プレイリスト: https://www.youtube.com/playlist?list={playlist_id}')
