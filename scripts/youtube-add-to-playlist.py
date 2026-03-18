"""
YouTube ダイジェストの動画を「Claude厳選」プレイリストに追加する
- プレイリストが存在しない場合は自動作成
- 既に追加済みの動画はスキップ
"""
import json, urllib.request, urllib.parse, os, sys, re
sys.stdout.reconfigure(encoding='utf-8')

PLAYLIST_NAME = 'Claude厳選'
cred_path = os.path.expanduser('~/.claude/google-oauth-credentials.json')
token_path = os.path.expanduser('~/.claude/mcp-google-calendar-token.json')
digest_path = os.path.expanduser('~/.claude/youtube-digest.md')
added_path = os.path.expanduser('~/.claude/youtube-playlist-added.json')

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

# 既追加IDを読み込み
added_ids = set()
if os.path.exists(added_path):
    with open(added_path, encoding='utf-8') as f:
        added_ids = set(json.load(f))

# プレイリスト検索
playlists = api_get('https://www.googleapis.com/youtube/v3/playlists?part=snippet&mine=true&maxResults=50')
playlist_id = None
for item in playlists.get('items', []):
    if item['snippet']['title'] == PLAYLIST_NAME:
        playlist_id = item['id']
        print(f'既存プレイリスト発見: {playlist_id}')
        break

# なければ作成
if not playlist_id:
    result = api_post('https://www.googleapis.com/youtube/v3/playlists?part=snippet,status', {
        'snippet': {'title': PLAYLIST_NAME, 'description': 'Claude（アスカ）が毎朝ピックアップしたAI・WordPress動画'},
        'status': {'privacyStatus': 'private'}
    })
    playlist_id = result['id']
    print(f'プレイリスト作成: {playlist_id}')

# ダイジェストから動画IDを抽出
with open(digest_path, encoding='utf-8') as f:
    content = f.read()

video_ids = re.findall(r'youtube\.com/watch\?v=([A-Za-z0-9_-]+)', content)
print(f'ダイジェスト内の動画: {len(video_ids)}件')

added_count = 0
skipped_count = 0
new_added = []

for vid in video_ids:
    if vid in added_ids:
        skipped_count += 1
        continue
    try:
        api_post('https://www.googleapis.com/youtube/v3/playlistItems?part=snippet', {
            'snippet': {
                'playlistId': playlist_id,
                'resourceId': {'kind': 'youtube#video', 'videoId': vid}
            }
        })
        added_count += 1
        new_added.append(vid)
        print(f'追加: {vid}')
    except Exception as e:
        print(f'スキップ（エラー）: {vid} - {e}')

# 追加済みIDを更新
updated = list(added_ids) + new_added
with open(added_path, 'w', encoding='utf-8') as f:
    json.dump(updated, f)

print(f'\n完了: {added_count}件追加 / {skipped_count}件スキップ')
print(f'プレイリスト: https://www.youtube.com/playlist?list={playlist_id}')
