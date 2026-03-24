"""
YouTube OAuth 再認証スクリプト
既存の Google OAuth トークンに youtube スコープを追加する
"""
import json, urllib.request, urllib.parse, os, sys, http.server, threading, webbrowser
sys.stdout.reconfigure(encoding='utf-8')

cred_path = os.path.expanduser('~/.claude/google-oauth-credentials.json')
token_path = os.path.expanduser('~/.claude/mcp-google-calendar-token.json')

cred = json.load(open(cred_path))
installed = cred['installed']

SCOPES = [
    'https://www.googleapis.com/auth/analytics.readonly',
    'https://www.googleapis.com/auth/analytics.edit',
    'https://www.googleapis.com/auth/calendar.events',
    'https://www.googleapis.com/auth/calendar.readonly',
    'https://www.googleapis.com/auth/youtube',
]

auth_code = None

class Handler(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        global auth_code
        parsed = urllib.parse.urlparse(self.path)
        params = urllib.parse.parse_qs(parsed.query)
        if 'code' in params:
            auth_code = params['code'][0]
            self.send_response(200)
            self.end_headers()
            self.wfile.write('認証完了！このタブを閉じてください。'.encode('utf-8'))
        else:
            self.send_response(400)
            self.end_headers()
    def log_message(self, *args):
        pass

server = http.server.HTTPServer(('localhost', 8080), Handler)
thread = threading.Thread(target=server.handle_request)
thread.start()

params = urllib.parse.urlencode({
    'client_id': installed['client_id'],
    'redirect_uri': 'http://localhost:8080',
    'response_type': 'code',
    'scope': ' '.join(SCOPES),
    'access_type': 'offline',
    'prompt': 'consent',
})
auth_url = f"{installed['auth_uri']}?{params}"

print('以下のURLをブラウザで開いてください：')
print(auth_url)
print()
webbrowser.open(auth_url)
print('ブラウザで認証を完了してください...')

thread.join(timeout=120)

if not auth_code:
    print('ERROR: 認証タイムアウト')
    sys.exit(1)

# コードをトークンに交換
data = urllib.parse.urlencode({
    'client_id': installed['client_id'],
    'client_secret': installed['client_secret'],
    'code': auth_code,
    'grant_type': 'authorization_code',
    'redirect_uri': 'http://localhost:8080',
}).encode()

req = urllib.request.Request(installed['token_uri'], data=data, method='POST')
with urllib.request.urlopen(req) as res:
    token_data = json.loads(res.read())

# 既存トークンファイルを更新
existing = json.load(open(token_path)) if os.path.exists(token_path) else {}
existing['normal'] = {
    'access_token': token_data['access_token'],
    'refresh_token': token_data.get('refresh_token', existing.get('normal', {}).get('refresh_token', '')),
    'scope': token_data.get('scope', ' '.join(SCOPES)),
    'token_type': token_data['token_type'],
    'expiry_date': 0,
}

with open(token_path, 'w') as f:
    json.dump(existing, f, indent=2)

print('トークンを更新しました！')
print('スコープ:', token_data.get('scope', ''))
