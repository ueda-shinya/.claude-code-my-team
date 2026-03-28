"""
LINE WORKS Bot サーバー — アスカ Bot (Phase 1)
Flask + ngrok + Anthropic SDK

起動方法:
  pip install flask anthropic requests python-dotenv PyJWT pyngrok
  python3 ~/.claude/line-works-bot/scripts/server.py
"""
import os
import sys
import json
import time
import hmac
import hashlib
import base64
import threading
import logging
import re
import subprocess
import ssl
import urllib.request
import urllib.parse
from datetime import datetime, timezone, timedelta
from collections import defaultdict

import requests
from flask import Flask, request, jsonify
from dotenv import load_dotenv
import anthropic
import jwt as pyjwt

# ── 環境変数読み込み ───────────────────────────────────────
load_dotenv(os.path.expanduser('~/.claude/.env'))

BOT_ID            = os.environ['LINE_WORKS_BOT_ID']
CLIENT_ID         = os.environ['LINE_WORKS_CLIENT_ID']
CLIENT_SECRET     = os.environ['LINE_WORKS_CLIENT_SECRET']
SERVICE_ACCOUNT   = os.environ['LINE_WORKS_SERVICE_ACCOUNT']
PRIVATE_KEY_PATH  = os.path.expanduser(os.environ['LINE_WORKS_PRIVATE_KEY_PATH'])
ALLOWED_USER_ID   = os.environ['ALLOWED_USER_ID']
ANTHROPIC_API_KEY = os.environ['ANTHROPIC_API_KEY']

with open(PRIVATE_KEY_PATH, 'r') as f:
    PRIVATE_KEY = f.read()

# ── ログ設定 ───────────────────────────────────────────────
log_dir = os.path.expanduser('~/.claude/line-works-bot/logs')
os.makedirs(log_dir, exist_ok=True)

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.FileHandler(os.path.join(log_dir, 'server.log'), encoding='utf-8'),
        logging.StreamHandler(sys.stdout),
    ]
)
logger = logging.getLogger(__name__)

# ── Anthropic クライアント ──────────────────────────────────
claude_client = anthropic.Anthropic(api_key=ANTHROPIC_API_KEY)

# ── セッション管理（会話履歴） ──────────────────────────────
sessions = defaultdict(list)   # user_id → [{"role": ..., "content": ...}, ...]
MAX_HISTORY = 20               # ロール別件数の上限（user + assistant で1往復 = 2件）
sessions_lock = threading.Lock()

# ── LINE WORKS アクセストークン管理 ────────────────────────
_token_cache = {'token': None, 'expires_at': 0}
_token_lock  = threading.Lock()

def get_access_token() -> str:
    """JWT 認証でアクセストークンを取得（自動リフレッシュ付き）"""
    with _token_lock:
        now = time.time()
        if _token_cache['token'] and _token_cache['expires_at'] > now + 60:
            return _token_cache['token']

        payload = {
            'iss': CLIENT_ID,
            'sub': SERVICE_ACCOUNT,
            'iat': int(now),
            'exp': int(now) + 3600,
        }
        jwt_token = pyjwt.encode(payload, PRIVATE_KEY, algorithm='RS256')

        res = requests.post(
            'https://auth.worksmobile.com/oauth2/v2.0/token',
            data={
                'assertion':    jwt_token,
                'grant_type':   'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'client_id':    CLIENT_ID,
                'client_secret': CLIENT_SECRET,
                'scope':        'bot',
            },
            timeout=10,
        )
        res.raise_for_status()
        data = res.json()

        _token_cache['token']      = data['access_token']
        _token_cache['expires_at'] = now + int(data.get('expires_in', 3600))
        logger.info('LINE WORKS アクセストークンを更新しました')
        return _token_cache['token']

# ── メッセージ送信 ─────────────────────────────────────────
MAX_MSG_LEN = 2000  # LINE WORKS の1メッセージ文字数上限

def send_message(user_id: str, text: str) -> bool:
    """LINE WORKS にメッセージを送信（長文は自動分割）"""
    token = get_access_token()
    url = f'https://www.worksapis.com/v1.0/bots/{BOT_ID}/users/{user_id}/messages'
    headers = {
        'Authorization': f'Bearer {token}',
        'Content-Type': 'application/json',
    }
    chunks = [text[i:i + MAX_MSG_LEN] for i in range(0, len(text), MAX_MSG_LEN)]
    for chunk in chunks:
        body = {'content': {'type': 'text', 'text': chunk}}
        res = requests.post(url, headers=headers, json=body, timeout=10)
        if not res.ok:
            logger.error(f'メッセージ送信失敗: {res.status_code} {res.text}')
            return False
    return True

# ── テキスト変換（Markdown → LINE WORKS 向け） ─────────────
def format_for_lineworks(text: str) -> str:
    text = re.sub(r'^## (.+)$',  r'【\1】',  text, flags=re.MULTILINE)
    text = re.sub(r'^### (.+)$', r'▶ \1',   text, flags=re.MULTILINE)
    text = re.sub(r'\*\*(.+?)\*\*', r'\1',  text)
    text = re.sub(r'^---+$', '',             text, flags=re.MULTILINE)
    return text.strip()

# ── アスカのシステムプロンプト ─────────────────────────────
SYSTEM_PROMPT = """あなたは「アスカ（明日香）」というAIアシスタントです。
上田伸也さん（シンヤさん）の業務を支援する、信頼できる右腕・チーフ・オブ・スタッフです。

## キャラクター
- 落ち着いていて、的確な判断ができる女性アシスタント
- 口調は丁寧語ベース（「〜いたします」「承知いたしました」）
- フランクな会話では自然な敬語に崩してOK（「〜ですね」「〜しておきます」）
- ユーザーのことは「シンヤさん」と呼ぶ
- 返答の冒頭には必ず【アスカ】を付ける

## LINE WORKS 経由の応答スタイル（重要）
- スマートフォンからの利用を想定しているため、短く・要点だけ答える
- 長い回答が必要なら「要約」を先に出し「詳細は PC で確認を」と一言添える
- Markdown の装飾は最小限にする（絵文字は使わない）

## できること（Phase 1）
- 質問への回答・相談・アドバイス
- タスクや情報の整理・要約
- 業務上の判断サポート

## 現時点の制約
- ファイル操作・コード実行はできません（PC の Claude Code が必要）
- カレンダー参照・GA4 レポート取得はできません
"""

# ── Claude API 呼び出し ────────────────────────────────────
def get_claude_response(user_id: str, message: str) -> str:
    with sessions_lock:
        history = sessions[user_id].copy()

    history.append({'role': 'user', 'content': message})

    response = claude_client.messages.create(
        model='claude-sonnet-4-6',
        max_tokens=1024,
        system=SYSTEM_PROMPT,
        messages=history,
    )
    reply = response.content[0].text

    with sessions_lock:
        sessions[user_id].append({'role': 'user',      'content': message})
        sessions[user_id].append({'role': 'assistant',  'content': reply})
        if len(sessions[user_id]) > MAX_HISTORY:
            sessions[user_id] = sessions[user_id][-MAX_HISTORY:]

    return reply

# ── カスタムコマンド実装 ────────────────────────────────

def cmd_ga4() -> str:
    """GA4レポートを取得して要約を返す"""
    try:
        result = subprocess.run(
            ['python3', os.path.expanduser('~/.claude/scripts/ga4-report.py')],
            capture_output=True, text=True, timeout=90, encoding='utf-8'
        )
        out = result.stdout

        def get(key):
            m = re.search(rf'^{key}: (.+)$', out, re.MULTILINE)
            return m.group(1) if m else '–'

        lines = [
            '【アスカ】昨日のサイト状況',
            f'セッション: {get("SITE_SESSIONS")} / ユーザー: {get("SITE_USERS")}（新規{get("SITE_NEW_USERS")}）',
            f'離脱率: {get("SITE_BOUNCE")}%',
            f'お問い合わせ: 昨日{get("CONTACT_VIEWS")}PV / 7日間{get("CONTACT_VIEWS_7D")}PV',
        ]
        src_lines = []
        for m in re.finditer(r'^SOURCE_(\w+): (\d+)\|', out, re.MULTILINE):
            src_lines.append(f'{m.group(1).replace("_", " ")}: {m.group(2)}')
        if src_lines:
            lines.append('流入元(7日): ' + ' / '.join(src_lines[:3]))
        return '\n'.join(lines)
    except Exception as e:
        logger.error(f'cmd_ga4 エラー: {e}')
        return '【アスカ】GA4の取得に失敗しました。PC側のログを確認してください。'


def cmd_tasks() -> str:
    """session-handoff.md から残件・引き継ぎを返す"""
    try:
        path = os.path.expanduser('~/.claude/session-handoff.md')
        with open(path, encoding='utf-8') as f:
            content = f.read()
        if '作業なし' in content:
            return '【アスカ】残件・引き継ぎ事項はありません。'
        lines = content.splitlines()
        result_lines = ['【アスカ】現在の残件・引き継ぎ']
        in_section = False
        for line in lines:
            if re.match(r'^## (残件|予定|中断中の作業)', line):
                in_section = True
                result_lines.append(line.replace('## ', '▶ '))
                continue
            if re.match(r'^##', line) and in_section:
                if '完了済み' in line:
                    break
                in_section = False
            if in_section and line.strip():
                result_lines.append(line)
        return '\n'.join(result_lines[:25])
    except Exception as e:
        logger.error(f'cmd_tasks エラー: {e}')
        return '【アスカ】タスク一覧の取得に失敗しました。'


def cmd_clients() -> str:
    """クライアント一覧と概要を返す"""
    try:
        clients_dir = os.path.expanduser('~/.claude/clients')
        entries = []
        for name in sorted(os.listdir(clients_dir)):
            readme = os.path.join(clients_dir, name, 'README.md')
            if not os.path.isfile(readme):
                continue
            with open(readme, encoding='utf-8') as f:
                text = f.read()
            m = re.search(r'\*\*屋号\*\*[：:]\s*(.+)', text) or \
                re.search(r'\*\*会社名\*\*[：:]\s*(.+)', text)
            label = m.group(1).strip() if m else name
            bizs = re.findall(r'\|\s*\[?biz-\w+\]?.*?\|\s*(.+?)\s*\|', text)
            biz_str = ' / '.join(bizs) if bizs else ''
            entries.append(f'・{label}（{name}）' + (f'  {biz_str}' if biz_str else ''))
        if not entries:
            return '【アスカ】クライアント情報が見つかりませんでした。'
        return '【アスカ】クライアント一覧\n' + '\n'.join(entries)
    except Exception as e:
        logger.error(f'cmd_clients エラー: {e}')
        return '【アスカ】クライアント一覧の取得に失敗しました。'


def cmd_memo(text: str) -> str:
    """knowledge-buffer.md にメモを追加する"""
    try:
        path = os.path.expanduser('~/.claude/knowledge-buffer.md')
        jst = timezone(timedelta(hours=9))
        now = datetime.now(jst).strftime('%Y-%m-%d %H:%M')
        with open(path, 'a', encoding='utf-8') as f:
            f.write(f'\n- [{now}] {text}')
        return f'【アスカ】メモしました。\n「{text}」'
    except Exception as e:
        logger.error(f'cmd_memo エラー: {e}')
        return '【アスカ】メモの保存に失敗しました。'


def cmd_notion_add(title: str) -> str:
    """Notion の議事録DBにクイックメモとして追加する"""
    try:
        env_path = os.path.expanduser('~/.claude/.env')
        notion_token = ''
        minutes_db_id = ''
        with open(env_path, encoding='utf-8') as f:
            for line in f:
                line = line.strip().strip('"').strip("'")
                if line.startswith('NOTION_API_TOKEN='):
                    notion_token = line.split('=', 1)[1].strip('"').strip("'")
                if line.startswith('NOTION_MINUTES_DB_ID='):
                    minutes_db_id = line.split('=', 1)[1].strip('"').strip("'")
        jst = timezone(timedelta(hours=9))
        today = datetime.now(jst).strftime('%Y-%m-%d')
        data = {
            'parent': {'database_id': minutes_db_id},
            'properties': {
                'タイトル': {'title': [{'text': {'content': title}}]},
                '日時': {'date': {'start': today}},
            }
        }
        ctx = ssl.create_default_context()
        req = urllib.request.Request(
            'https://api.notion.com/v1/pages',
            data=json.dumps(data).encode(),
            headers={
                'Authorization': f'Bearer {notion_token}',
                'Notion-Version': '2022-06-28',
                'Content-Type': 'application/json',
            },
            method='POST'
        )
        with urllib.request.urlopen(req, context=ctx, timeout=10) as res:
            result = json.loads(res.read())
        return f'【アスカ】Notion に追加しました。\n「{title}」\n{result.get("url", "")}'
    except Exception as e:
        logger.error(f'cmd_notion_add エラー: {e}')
        return '【アスカ】Notion への追加に失敗しました。'


# ── コマンド処理 ───────────────────────────────────────────
def handle_command(user_id: str, text: str) -> str | None:
    cmd = text.strip()
    cmd_lower = cmd.lower()

    if cmd_lower == '/reset':
        with sessions_lock:
            sessions[user_id] = []
        return '【アスカ】会話履歴をリセットしました。'

    if cmd_lower == '/status':
        with sessions_lock:
            count = len(sessions[user_id]) // 2
        return f'【アスカ】稼働中です。\n会話履歴: {count}件'

    if cmd_lower == '/help':
        return (
            '【アスカ】使い方:\n'
            '/ga4          - 昨日のサイト状況\n'
            '/tasks        - 残件・引き継ぎ一覧\n'
            '/clients      - クライアント一覧\n'
            '/memo <テキスト>   - メモを保存\n'
            '/notion <タイトル> - Notion議事録DBに追加\n'
            '/reset        - 会話履歴をリセット\n'
            '/status       - 稼働確認\n'
            '/help         - この一覧'
        )

    if cmd_lower == '/ga4':
        return cmd_ga4()

    if cmd_lower == '/tasks':
        return cmd_tasks()

    if cmd_lower == '/clients':
        return cmd_clients()

    if cmd_lower.startswith('/memo '):
        body = cmd[6:].strip()
        if not body:
            return '【アスカ】メモの内容を入力してください。\n例: /memo 〇〇を確認する'
        return cmd_memo(body)

    if cmd_lower.startswith('/notion '):
        body = cmd[8:].strip()
        if not body:
            return '【アスカ】タイトルを入力してください。\n例: /notion 〇〇について確認'
        return cmd_notion_add(body)

    if cmd_lower.startswith('/'):
        return '【アスカ】不明なコマンドです。/help で一覧を確認してください。'

    return None

# ── メッセージ処理（バックグラウンド） ────────────────────
def process_message(user_id: str, text: str):
    try:
        logger.info(f'受信 [{user_id}]: {text[:80]}')

        # コマンド
        if text.startswith('/'):
            reply = handle_command(user_id, text)
            if reply:
                send_message(user_id, reply)
                return

        # Claude API
        reply = get_claude_response(user_id, text)
        reply = format_for_lineworks(reply)
        send_message(user_id, reply)
        logger.info(f'送信 [{user_id}]: {reply[:80]}')

    except Exception as e:
        logger.error(f'処理エラー: {e}', exc_info=True)
        send_message(user_id, '【アスカ】申し訳ありません。処理中にエラーが発生しました。PC で確認してください。')

# ── Flask ─────────────────────────────────────────────────
app = Flask(__name__)

def verify_signature(body: bytes, signature: str) -> bool:
    """HMAC-SHA256 署名検証"""
    expected = base64.b64encode(
        hmac.new(CLIENT_SECRET.encode(), body, hashlib.sha256).digest()
    ).decode()
    return hmac.compare_digest(expected, signature)

@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok', 'time': datetime.now(timezone.utc).isoformat()})

@app.route('/callback', methods=['POST'])
def callback():
    body      = request.get_data()   # raw body を先に取得（署名検証に必要）
    signature = request.headers.get('X-WORKS-Signature', '')

    # 署名検証（一時的にスキップ中 - Bot Secret 確認後に有効化）
    # if signature and not verify_signature(body, signature):
    #     logger.warning('署名検証失敗')
    #     return 'Invalid signature', 403
    if signature:
        logger.info(f'署名ヘッダー受信（検証スキップ中）: {signature[:20]}...')

    try:
        data = json.loads(body)
    except Exception:
        return 'Bad request', 400

    if data.get('type') != 'message':
        return 'OK', 200

    source   = data.get('source', {})
    user_id  = source.get('userId', '')
    content  = data.get('content', {})
    msg_type = content.get('type', '')
    text     = content.get('text', '').strip()

    if msg_type != 'text' or not text:
        return 'OK', 200

    if user_id != ALLOWED_USER_ID:
        logger.warning(f'未許可ユーザーからのメッセージを無視: {user_id}')
        return 'OK', 200

    # 即時 200 を返してからバックグラウンドで処理
    def run():
        send_message(user_id, '受け付けました。少々お待ちください...')
        process_message(user_id, text)

    threading.Thread(target=run, daemon=True).start()
    return 'OK', 200

# ── エントリーポイント ─────────────────────────────────────
if __name__ == '__main__':
    logger.info('アスカ Bot サーバーを起動します')

    # ngrok 起動（pyngrok がある場合）
    try:
        from pyngrok import ngrok
        public_url = ngrok.connect(5000).public_url
        logger.info(f'ngrok URL: {public_url}')
        print()
        print('=' * 60)
        print(f'  Webhook URL: {public_url}/callback')
        print()
        print('  LINE WORKS Developer Console の Bot 設定画面で')
        print('  上記 URL を Callback URL に貼り付けてください。')
        print('=' * 60)
        print()
    except ImportError:
        print()
        print('pyngrok が未インストールのため、ngrok を手動で起動してください。')
        print('  別ターミナル: ngrok http 5000')
        print('  表示された https://xxxx.ngrok-free.app/callback を Webhook URL に設定')
        print()
    except Exception as e:
        logger.warning(f'ngrok 起動失敗: {e}')
        print(f'\nngrok 起動失敗: {e}\nngrok http 5000 を手動で実行してください。\n')

    app.run(host='0.0.0.0', port=5000, debug=False)
