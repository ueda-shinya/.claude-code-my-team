"""
Chatwork 全ルーム未確認メッセージ取得・解析・連携スクリプト

処理フロー:
  1. chatwork-state.json を読み込んで各ルームの最終確認済みIDを取得
  2. Chatwork API で全ルーム一覧を取得
  3. 各ルームで最終確認済みID以降のメッセージを取得
  4. 新着メッセージを Claude API で解析
  5. タスク → Notion 案件リストに追加
  6. スケジュール → Google Calendar に追加
  7. 優先度高 → LINE WORKS で通知
  8. chatwork-state.json を更新

単体実行:
  X:\\Python310\\python.exe ~/.claude/scripts/chatwork-sync.py
  X:\\Python310\\python.exe ~/.claude/scripts/chatwork-sync.py --dry-run
"""

import os
import sys
import json
import ssl
import logging
import argparse
import tempfile
import urllib.request
import urllib.parse
from datetime import datetime, timezone, timedelta

import requests
from dotenv import load_dotenv
import anthropic

# ── 環境変数読み込み ─────────────────────────────────────────
load_dotenv(os.path.expanduser('~/.claude/.env'))

CHATWORK_API_TOKEN = os.environ.get('CHATWORK_API_TOKEN', '')
ANTHROPIC_API_KEY  = os.environ.get('ANTHROPIC_API_KEY', '')
NOTION_API_TOKEN   = os.environ.get('NOTION_API_TOKEN', '')
NOTION_PROJECT_DB_ID = os.environ.get('NOTION_PROJECT_DB_ID', '')

# LINE WORKS 通知先（ALLOWED_USER_ID 宛に個人メッセージ送信）
LINE_WORKS_BOT_ID      = os.environ.get('LINE_WORKS_BOT_ID', '')
LINE_WORKS_CLIENT_ID   = os.environ.get('LINE_WORKS_CLIENT_ID', '')
LINE_WORKS_CLIENT_SECRET = os.environ.get('LINE_WORKS_CLIENT_SECRET', '')
LINE_WORKS_SERVICE_ACCOUNT = os.environ.get('LINE_WORKS_SERVICE_ACCOUNT', '')
LINE_WORKS_PRIVATE_KEY_PATH = os.path.expanduser(
    os.environ.get('LINE_WORKS_PRIVATE_KEY_PATH', '~/.claude/line-works-bot/private.key')
)
ALLOWED_USER_ID = os.environ.get('ALLOWED_USER_ID', '')

# 一次返信機能
CHATWORK_MY_ACCOUNT_ID = os.environ.get('CHATWORK_MY_ACCOUNT_ID', '')
_urgent_rooms_raw = os.environ.get('CHATWORK_URGENT_ROOM_IDS', '')
CHATWORK_URGENT_ROOM_IDS = set(
    x.strip() for x in _urgent_rooms_raw.split(',') if x.strip()
)

# ── 定数 ──────────────────────────────────────────────────────
CHATWORK_API_BASE    = 'https://api.chatwork.com/v2'
STATE_FILE           = os.path.expanduser('~/.claude/tmp/chatwork-state.json')
LOG_DIR              = os.path.expanduser('~/.claude/line-works-bot/logs')

# 一次返信: 受信から何時間経過したら返信するか
URGENT_REPLY_AFTER_HOURS = 1

# 一次返信メッセージ（内容は運用しながら調整）
URGENT_AUTO_REPLY_TEXT = (
    'お世話になっております。\n'
    'AIのアスカです。\n'
    'ご連絡いただいた内容を確認しております。\n'
    '担当者より折り返しご連絡いたしますので、しばらくお待ちください。'
)

# ── APIコスト管理 ──────────────────────────────────────────────
INPUT_COST_PER_MTOK  = 0.80   # claude-haiku-4-5: $0.80/MTok
OUTPUT_COST_PER_MTOK = 4.00   # claude-haiku-4-5: $4.00/MTok
COST_THRESHOLD_USD   = 0.05   # 通常運用でこの金額を超えたらアラート（約¥7.5）
COST_HISTORY_FILE    = os.path.expanduser('~/.claude/tmp/api-cost-history.json')

# ── ログ設定 ─────────────────────────────────────────────────
os.makedirs(LOG_DIR, exist_ok=True)
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.FileHandler(os.path.join(LOG_DIR, 'chatwork-sync.log'), encoding='utf-8'),
        logging.StreamHandler(sys.stdout),
    ]
)
logger = logging.getLogger(__name__)

# ── Anthropic クライアント ────────────────────────────────────
claude_client = anthropic.Anthropic(api_key=ANTHROPIC_API_KEY)

# ── LINE WORKS アクセストークン ──────────────────────────────
_lw_token_cache = {'token': None, 'expires_at': 0}

def get_lw_access_token() -> str:
    """LINE WORKS アクセストークンを取得（JWT認証）"""
    import time
    import jwt as pyjwt

    now = time.time()
    if _lw_token_cache['token'] and _lw_token_cache['expires_at'] > now + 60:
        return _lw_token_cache['token']

    try:
        with open(LINE_WORKS_PRIVATE_KEY_PATH, 'r') as f:
            private_key = f.read()

        payload = {
            'iss': LINE_WORKS_CLIENT_ID,
            'sub': LINE_WORKS_SERVICE_ACCOUNT,
            'iat': int(now),
            'exp': int(now) + 3600,
        }
        jwt_token = pyjwt.encode(payload, private_key, algorithm='RS256')

        res = requests.post(
            'https://auth.worksmobile.com/oauth2/v2.0/token',
            data={
                'assertion':     jwt_token,
                'grant_type':    'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'client_id':     LINE_WORKS_CLIENT_ID,
                'client_secret': LINE_WORKS_CLIENT_SECRET,
                'scope':         'bot',
            },
            timeout=10,
        )
        res.raise_for_status()
        data = res.json()
        _lw_token_cache['token']      = data['access_token']
        _lw_token_cache['expires_at'] = now + int(data.get('expires_in', 3600))
        return _lw_token_cache['token']
    except Exception as e:
        logger.error(f'LINE WORKS トークン取得失敗: {e}')
        raise


def send_line_works_message(text: str, dry_run: bool = False) -> bool:
    """LINE WORKS にメッセージを送信（ALLOWED_USER_ID 宛）"""
    if dry_run:
        logger.info(f'[DRY-RUN] LINE WORKS 送信スキップ:\n{text}')
        return True
    try:
        token = get_lw_access_token()
        url = f'https://www.worksapis.com/v1.0/bots/{LINE_WORKS_BOT_ID}/users/{ALLOWED_USER_ID}/messages'
        headers = {
            'Authorization': f'Bearer {token}',
            'Content-Type':  'application/json',
        }
        body = {'content': {'type': 'text', 'text': text}}
        res = requests.post(url, headers=headers, json=body, timeout=10)
        if not res.ok:
            logger.error(f'LINE WORKS 送信失敗: {res.status_code} {res.text}')
            return False
        logger.info('LINE WORKS 通知送信完了')
        return True
    except Exception as e:
        logger.error(f'send_line_works_message エラー: {e}')
        return False


# ── 状態管理 ─────────────────────────────────────────────────

def load_state() -> dict:
    """chatwork-state.json を読み込む（なければ空の初期値を返す）"""
    if os.path.exists(STATE_FILE):
        try:
            with open(STATE_FILE, encoding='utf-8') as f:
                return json.load(f)
        except Exception as e:
            logger.warning(f'状態ファイルの読み込み失敗（初期化します）: {e}')
    return {'rooms': {}}


def save_state(state: dict, dry_run: bool = False):
    """chatwork-state.json をアトミック書き込みで保存する"""
    if dry_run:
        logger.info('[DRY-RUN] 状態ファイルの保存をスキップ')
        return
    os.makedirs(os.path.dirname(STATE_FILE), exist_ok=True)
    fd, tmp_path = tempfile.mkstemp(dir=os.path.dirname(STATE_FILE), suffix='.tmp')
    try:
        with os.fdopen(fd, 'w', encoding='utf-8') as f:
            json.dump(state, f, ensure_ascii=False, indent=2)
        os.replace(tmp_path, STATE_FILE)
    except Exception:
        os.unlink(tmp_path)
        raise
    logger.info(f'状態ファイルを保存しました: {STATE_FILE}')


def calc_check_interval(last_updated_at_str: str) -> int:
    """
    最終更新日から動的にチェック間隔を決定する
    未記録（初回） → 4時間
    7日以内        → 4時間
    7〜14日        → 12時間
    14日以上       → 24時間
    """
    if not last_updated_at_str:
        return 4
    try:
        jst = timezone(timedelta(hours=9))
        last_updated = datetime.fromisoformat(last_updated_at_str)
        if last_updated.tzinfo is None:
            last_updated = last_updated.replace(tzinfo=jst)
        now = datetime.now(jst)
        diff_days = (now - last_updated).days
        if diff_days < 7:
            return 4
        elif diff_days < 14:
            return 12
        else:
            return 24
    except Exception:
        return 4


def should_check_room(room_state: dict) -> bool:
    """
    次回チェック時刻を過ぎているか判定する
    last_checked_at + check_interval_hours <= now なら True
    """
    last_checked_at = room_state.get('last_checked_at')
    if not last_checked_at:
        return True
    interval = room_state.get('check_interval_hours', 1)
    try:
        jst = timezone(timedelta(hours=9))
        last = datetime.fromisoformat(last_checked_at)
        if last.tzinfo is None:
            last = last.replace(tzinfo=jst)
        next_check = last + timedelta(hours=interval)
        return datetime.now(jst) >= next_check
    except Exception:
        return True


# ── Chatwork API ─────────────────────────────────────────────

def cw_get(path: str, params: dict = None) -> dict | list | None:
    """Chatwork API GET リクエスト"""
    url = CHATWORK_API_BASE + path
    if params:
        url += '?' + urllib.parse.urlencode(params)
    try:
        req = urllib.request.Request(
            url,
            headers={'X-ChatWorkToken': CHATWORK_API_TOKEN},
        )
        ctx = ssl.create_default_context()
        with urllib.request.urlopen(req, context=ctx, timeout=10) as res:
            return json.loads(res.read())
    except Exception as e:
        logger.error(f'Chatwork API エラー [{path}]: {e}')
        return None


def get_rooms() -> list:
    """全ルーム一覧を取得"""
    result = cw_get('/rooms')
    return result if isinstance(result, list) else []


def send_chatwork_message(room_id: int, body: str, dry_run: bool = False) -> bool:
    """Chatwork のルームにメッセージを送信する"""
    if dry_run:
        logger.info(f'[DRY-RUN] Chatwork 送信スキップ (room={room_id}): {body[:50]}...')
        return True
    url = f'{CHATWORK_API_BASE}/rooms/{room_id}/messages'
    try:
        data = urllib.parse.urlencode({'body': body, 'self_unread': 0}).encode()
        req = urllib.request.Request(
            url,
            data=data,
            headers={
                'X-ChatWorkToken': CHATWORK_API_TOKEN,
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            method='POST',
        )
        ctx = ssl.create_default_context()
        with urllib.request.urlopen(req, context=ctx, timeout=10) as res:
            json.loads(res.read())
        logger.info(f'Chatwork 一次返信送信完了 (room={room_id})')
        return True
    except Exception as e:
        logger.error(f'send_chatwork_message エラー (room={room_id}): {e}')
        return False


def get_messages(room_id: int, force: int = 0) -> list:
    """
    ルームのメッセージ一覧を取得
    force=0: 未読メッセージのみ（サーバー側管理）
    force=1: 最新100件を強制取得
    """
    result = cw_get(f'/rooms/{room_id}/messages', {'force': force})
    return result if isinstance(result, list) else []


# ── Claude API 解析 ───────────────────────────────────────────

ANALYZE_SYSTEM_PROMPT = """以下のChatworkメッセージを解析してください。

以下のJSON形式で返してください。必ずJSONのみを返し、前後に説明文を付けないこと：
{
  "has_task": true/false,
  "task_summary": "タスクの要約（has_taskがtrueの場合）",
  "related_project": "関連する案件名（推測）",
  "has_schedule": true/false,
  "schedule_summary": "スケジュール内容",
  "schedule_datetime": "YYYY-MM-DDTHH:MM:SS（推測できる場合、不明な場合はnull）",
  "is_high_priority": true/false,
  "priority_reason": "優先度高と判断した理由（is_high_priorityがtrueの場合）"
}

is_high_priority の判定基準（以下のいずれかに該当する場合のみ true）：
- 今日中または明日中に返答・対応が必要とわかるもの
- 金額・契約・キャンセル・解約に関わる内容
- To:（名指し）で送られてきたメッセージ
- クレーム・トラブル・緊急対応が必要な内容
上記に該当しない場合は false とすること。"""


def analyze_message(room_name: str, account_name: str, send_time: str, message_body: str) -> dict | None:
    """Claude API でメッセージを解析してJSONを返す

    戻り値:
        {
            'result': dict,         # 解析結果JSON
            'input_tokens': int,    # 入力トークン数
            'output_tokens': int,   # 出力トークン数
        }
        解析失敗時は None
    """
    try:
        response = claude_client.messages.create(
            model='claude-haiku-4-5-20251001',
            max_tokens=512,
            system=ANALYZE_SYSTEM_PROMPT,
            messages=[{
                'role': 'user',
                'content': f'ルーム名：{room_name}\n送信者：{account_name}\n送信日時：{send_time}\nメッセージ本文：\n<message>\n{message_body[:3000]}\n</message>',
            }],
        )
        text = response.content[0].text.strip()

        # JSONブロックの抽出（```json ... ``` 形式にも対応）
        if '```' in text:
            import re
            m = re.search(r'```(?:json)?\s*([\s\S]+?)\s*```', text)
            if m:
                text = m.group(1)

        return {
            'result':        json.loads(text),
            'input_tokens':  response.usage.input_tokens,
            'output_tokens': response.usage.output_tokens,
        }
    except json.JSONDecodeError as e:
        logger.warning(f'Claude レスポンスのJSON解析失敗: {e} / レスポンス: {text[:200]}')
        return None
    except Exception as e:
        logger.error(f'Claude API 呼び出しエラー: {e}')
        return None


# ── Notion 案件リスト連携 ────────────────────────────────────

def notion_request(method: str, path: str, data: dict = None) -> dict | None:
    """Notion API リクエスト"""
    url = 'https://api.notion.com/v1' + path
    headers = {
        'Authorization':  f'Bearer {NOTION_API_TOKEN}',
        'Notion-Version': '2022-06-28',
        'Content-Type':   'application/json',
    }
    ctx = ssl.create_default_context()
    try:
        body = json.dumps(data).encode() if data else None
        req = urllib.request.Request(url, data=body, headers=headers, method=method)
        with urllib.request.urlopen(req, context=ctx, timeout=10) as res:
            return json.loads(res.read())
    except Exception as e:
        logger.error(f'Notion API エラー [{method} {path}]: {e}')
        return None


def find_notion_project(project_name: str) -> str | None:
    """Notion 案件DBで案件名を検索して page_id を返す（なければ None）"""
    data = {
        'filter': {
            'property': '案件名',
            'title': {'contains': project_name},
        }
    }
    result = notion_request('POST', f'/databases/{NOTION_PROJECT_DB_ID}/query', data)
    if result and result.get('results'):
        return result['results'][0]['id']
    return None


def add_or_update_notion_project(task_summary: str, related_project: str, dry_run: bool = False) -> bool:
    """Notion 案件リストにタスクを追加（既存案件があればメモを更新、なければ新規作成）"""
    if dry_run:
        logger.info(f'[DRY-RUN] Notion 書き込みスキップ: 案件={related_project} / タスク={task_summary}')
        return True

    jst = timezone(timedelta(hours=9))
    now_str = datetime.now(jst).isoformat()

    page_id = find_notion_project(related_project) if related_project else None

    if page_id:
        # 既存案件の概要を更新（DBプロパティ名: 概要 / type: rich_text）
        data = {
            'properties': {
                '概要': {
                    'rich_text': [{'text': {'content': task_summary[:2000]}}]
                },
            }
        }
        result = notion_request('PATCH', f'/pages/{page_id}', data)
        if result:
            logger.info(f'Notion 案件更新完了: {related_project}')
            return True
        return False
    else:
        # 新規作成（DBプロパティ名: 案件名 / type: title、概要 / type: rich_text）
        project_title = related_project or 'Chatwork タスク'
        data = {
            'parent': {'database_id': NOTION_PROJECT_DB_ID},
            'properties': {
                '案件名': {'title': [{'text': {'content': project_title}}]},
                '概要':   {'rich_text': [{'text': {'content': task_summary[:2000]}}]},
            }
        }
        result = notion_request('POST', '/pages', data)
        if result:
            logger.info(f'Notion 案件新規作成完了: {project_title}')
            return True
        return False


# ── Google Calendar 連携 ─────────────────────────────────────

def add_calendar_event(summary: str, start_iso: str, dry_run: bool = False) -> bool:
    """Google Calendar にイベントを追加（server.py の実装を参考）"""
    if dry_run:
        logger.info(f'[DRY-RUN] カレンダー追加スキップ: {summary} / {start_iso}')
        return True
    try:
        from datetime import datetime as dt
        cred_path  = os.path.expanduser('~/.claude/google-oauth-credentials.json')
        token_path = os.path.expanduser('~/.claude/mcp-google-calendar-token.json')
        with open(cred_path, encoding='utf-8') as f:
            cred = json.load(f)
        with open(token_path, encoding='utf-8') as f:
            token = json.load(f)

        # アクセストークン取得
        data = urllib.parse.urlencode({
            'client_id':     cred['installed']['client_id'],
            'client_secret': cred['installed']['client_secret'],
            'refresh_token': token['normal']['refresh_token'],
            'grant_type':    'refresh_token',
        }).encode()
        req = urllib.request.Request(
            'https://oauth2.googleapis.com/token', data=data, method='POST'
        )
        ctx = ssl.create_default_context()
        with urllib.request.urlopen(req, context=ctx, timeout=10) as res:
            access_token = json.loads(res.read())['access_token']

        # イベント追加（1時間枠）
        start = dt.fromisoformat(start_iso)
        end   = start + timedelta(hours=1)
        end_iso = end.strftime('%Y-%m-%dT%H:%M:%S+09:00')
        if '+' not in start_iso and 'Z' not in start_iso:
            start_iso = start.strftime('%Y-%m-%dT%H:%M:%S+09:00')

        event_data = json.dumps({
            'summary': summary,
            'start': {'dateTime': start_iso, 'timeZone': 'Asia/Tokyo'},
            'end':   {'dateTime': end_iso,   'timeZone': 'Asia/Tokyo'},
        }).encode()
        req2 = urllib.request.Request(
            'https://www.googleapis.com/calendar/v3/calendars/primary/events',
            data=event_data,
            headers={
                'Authorization': f'Bearer {access_token}',
                'Content-Type':  'application/json',
            },
            method='POST'
        )
        with urllib.request.urlopen(req2, context=ctx, timeout=10) as res:
            json.loads(res.read())

        logger.info(f'カレンダー追加完了: {summary} / {start_iso}')
        return True
    except Exception as e:
        logger.error(f'add_calendar_event エラー: {e}')
        return False


# ── 一次返信処理 ─────────────────────────────────────────────

def check_urgent_rooms(state: dict, rooms_state: dict, dry_run: bool = False, test_mode: bool = False):
    """
    一次返信対象ルームをチェックし、自分宛メッセージに1時間以上未返信なら自動返信する。
    - 判定: last_incoming_at > last_outgoing_at（または未返信）かつ経過1時間以上
    - 自動返信は1回のみ（urgent_auto_reply_sent フラグで管理）
    - LINE WORKS に未返信サマリーを送信
    """
    if not CHATWORK_MY_ACCOUNT_ID:
        logger.warning('CHATWORK_MY_ACCOUNT_ID が未設定のため一次返信チェックをスキップ')
        return
    if not CHATWORK_URGENT_ROOM_IDS:
        return

    jst = timezone(timedelta(hours=9))
    now = datetime.now(jst)
    pending_rooms = []

    for room_id in CHATWORK_URGENT_ROOM_IDS:
        room_state = rooms_state.get(room_id, {})
        room_name = room_state.get('room_name', f'room_{room_id}')

        last_incoming_str = room_state.get('last_incoming_at', '')
        last_outgoing_str = room_state.get('last_outgoing_at', '')
        auto_reply_sent   = room_state.get('urgent_auto_reply_sent', False)

        if not last_incoming_str:
            continue  # まだ受信記録なし

        try:
            last_incoming = datetime.fromisoformat(last_incoming_str)
            if last_incoming.tzinfo is None:
                last_incoming = last_incoming.replace(tzinfo=jst)
        except Exception:
            continue

        # 自分が返信済みかチェック（last_outgoing >= last_incoming なら返信済み）
        if last_outgoing_str:
            try:
                last_outgoing = datetime.fromisoformat(last_outgoing_str)
                if last_outgoing.tzinfo is None:
                    last_outgoing = last_outgoing.replace(tzinfo=jst)
                if last_outgoing >= last_incoming:
                    continue  # 返信済みのためスキップ
            except Exception:
                pass

        elapsed_hours = (now - last_incoming).total_seconds() / 3600

        # 1時間以上未返信 → 自動返信（まだ送っていない場合のみ）
        if elapsed_hours >= URGENT_REPLY_AFTER_HOURS and not auto_reply_sent:
            sender = room_state.get('last_incoming_sender', '不明')
            logger.info(f'一次返信実行: {room_name} / {sender} / 経過{elapsed_hours:.1f}h')
            ok = send_chatwork_message(int(room_id), URGENT_AUTO_REPLY_TEXT, dry_run=dry_run)
            if ok:
                room_state['urgent_auto_reply_sent'] = True
                room_state['urgent_auto_replied_at'] = now.isoformat()
                rooms_state[room_id] = room_state

        # 1時間以上未返信（自動返信済みでも未解決）なら通知リストに追加
        if elapsed_hours >= URGENT_REPLY_AFTER_HOURS:
            pending_rooms.append({
                'room_name':   room_name,
                'sender':      room_state.get('last_incoming_sender', '不明'),
                'excerpt':     room_state.get('last_incoming_excerpt', ''),
                'elapsed':     elapsed_hours,
                'auto_replied': room_state.get('urgent_auto_reply_sent', False),
            })

    # LINE WORKS リマインド（未返信ルームがある場合）
    if pending_rooms:
        lines = ['【Chatwork 未返信リスト】']
        for item in pending_rooms:
            replied_str = '一次返信済み' if item['auto_replied'] else '未返信'
            lines.append(
                f'・{item["room_name"]} | {item["sender"]} '
                f'({item["elapsed"]:.0f}時間経過・{replied_str})\n'
                f'  {item["excerpt"][:50]}'
            )
        send_line_works_message('\n'.join(lines), dry_run=dry_run)


# ── APIコスト履歴管理 ─────────────────────────────────────────

def append_cost_history(script: str, cost_usd: float, input_tokens: int, output_tokens: int, analyzed_count: int):
    """APIコスト履歴を COST_HISTORY_FILE に追記（最新500件を保持）"""
    os.makedirs(os.path.dirname(COST_HISTORY_FILE), exist_ok=True)
    try:
        if os.path.exists(COST_HISTORY_FILE):
            with open(COST_HISTORY_FILE, encoding='utf-8') as f:
                history = json.load(f)
        else:
            history = []
    except Exception:
        history = []

    jst = timezone(timedelta(hours=9))
    history.append({
        'timestamp':      datetime.now(jst).isoformat(),
        'script':         script,
        'cost_usd':       round(cost_usd, 6),
        'input_tokens':   input_tokens,
        'output_tokens':  output_tokens,
        'analyzed_count': analyzed_count,
    })
    history = history[-500:]  # 最新500件のみ保持

    fd, tmp = tempfile.mkstemp(dir=os.path.dirname(COST_HISTORY_FILE), suffix='.tmp')
    try:
        with os.fdopen(fd, 'w', encoding='utf-8') as f:
            json.dump(history, f, ensure_ascii=False, indent=2)
        os.replace(tmp, COST_HISTORY_FILE)
    except Exception as e:
        logger.warning(f'コスト履歴の保存失敗: {e}')
        try:
            os.unlink(tmp)
        except Exception:
            pass


# ── メイン処理 ───────────────────────────────────────────────

def run_sync(dry_run: bool = False, since_dt=None, test_mode: bool = False):
    """Chatwork 同期メイン処理"""
    logger.info(f'=== chatwork-sync 開始 {"[DRY-RUN]" if dry_run else ""} ===')
    if since_dt:
        logger.info(f'since フィルタ: {since_dt.isoformat()} 以降のみ処理')

    # トークン集計用カウンター
    total_input_tokens  = 0
    total_output_tokens = 0
    analyzed_count      = 0

    # 必須環境変数チェック
    if not CHATWORK_API_TOKEN:
        logger.error('CHATWORK_API_TOKEN が設定されていません')
        return
    if not ANTHROPIC_API_KEY:
        logger.error('ANTHROPIC_API_KEY が設定されていません')
        return

    jst = timezone(timedelta(hours=9))
    now = datetime.now(jst)
    now_str = now.isoformat()

    # 状態ファイル読み込み
    state = load_state()
    rooms_state = state.setdefault('rooms', {})

    # ルーム一覧取得
    rooms = get_rooms()
    if not rooms:
        logger.warning('Chatwork ルーム一覧の取得に失敗しました（または0件）')
        return
    logger.info(f'ルーム数: {len(rooms)}')

    for room in rooms:
        room_id   = str(room.get('room_id', ''))
        room_name = room.get('name', f'room_{room_id}')

        if not room_id:
            continue

        # チェック間隔の判定
        room_state = rooms_state.get(room_id, {})

        # check_interval_hours を動的に再計算・更新
        last_updated = room_state.get('last_updated_at', '')
        new_interval = calc_check_interval(last_updated)
        # 一次返信対象ルームは最大1時間間隔でチェック
        if room_id in CHATWORK_URGENT_ROOM_IDS:
            new_interval = min(new_interval, 1)
        room_state['check_interval_hours'] = new_interval

        if not should_check_room(room_state):
            logger.info(f'スキップ（次回チェック時刻前）: {room_name}')
            continue

        logger.info(f'チェック開始: {room_name} (room_id={room_id})')

        # メッセージ取得（--since 指定時は force=1 で全件取得、通常は force=0 で未読のみ）
        fetch_force = 1 if since_dt else 0
        messages = get_messages(int(room_id), force=fetch_force)
        if not messages:
            # 未読なし → last_checked_at のみ更新
            room_state['room_name']      = room_name
            room_state['last_checked_at'] = now_str
            rooms_state[room_id]         = room_state
            continue

        logger.info(f'  新着メッセージ: {len(messages)} 件')

        last_message_id = room_state.get('last_message_id', 0)
        new_last_id     = last_message_id
        since_skip_count = 0  # --since フィルタでスキップした件数

        for msg in messages:
            msg_id      = msg.get('message_id', '')
            account     = msg.get('account', {})
            account_name = account.get('name', '不明')
            body        = msg.get('body', '')
            send_time_ts = msg.get('send_time', 0)

            # 送信時刻をフォーマット
            try:
                send_dt  = datetime.fromtimestamp(send_time_ts, tz=jst)
                send_time = f'{send_dt.year}-{send_dt.month:02d}-{send_dt.day:02d} {send_dt.hour:02d}:{send_dt.minute:02d}'
            except Exception:
                send_time = str(send_time_ts)

            # メッセージIDの更新（since フィルタ対象でも既読扱いにするため先に更新）
            try:
                if int(msg_id) > new_last_id:
                    new_last_id = int(msg_id)
            except Exception:
                pass

            # 一次返信対象ルームの送受信状況を記録（全メッセージ対象・解析前）
            if room_id in CHATWORK_URGENT_ROOM_IDS and CHATWORK_MY_ACCOUNT_ID:
                sender_account_id = str(account.get('account_id', ''))
                if sender_account_id == CHATWORK_MY_ACCOUNT_ID:
                    # 自分の送信 → 返信済みとして記録・自動返信フラグをリセット
                    room_state['last_outgoing_at'] = send_dt.isoformat()
                    room_state['urgent_auto_reply_sent'] = False
                else:
                    # 相手からの受信 → 最新着信を更新
                    prev = room_state.get('last_incoming_at', '')
                    cur  = send_dt.isoformat()
                    if not prev or cur > prev:
                        room_state['last_incoming_at']      = cur
                        room_state['last_incoming_msg_id']  = msg_id
                        room_state['last_incoming_sender']  = account_name
                        room_state['last_incoming_excerpt'] = body[:100]
                        room_state['urgent_auto_reply_sent'] = False

            # --since フィルタ: 指定日時より前のメッセージはスキップ（last_message_id は更新済み）
            if since_dt is not None:
                try:
                    send_dt_check = datetime.fromtimestamp(send_time_ts, tz=jst)
                    if send_dt_check < since_dt:
                        since_skip_count += 1
                        continue
                except Exception:
                    pass

            # 本文が短すぎる・システムメッセージはスキップ
            if len(body.strip()) < 5:
                continue

            logger.info(f'  解析中: [{account_name}] {body[:50]}...')

            # Claude で解析
            ret = analyze_message(room_name, account_name, send_time, body)
            if ret is None:
                logger.warning(f'  解析失敗: message_id={msg_id}')
                continue
            analysis             = ret['result']
            total_input_tokens  += ret['input_tokens']
            total_output_tokens += ret['output_tokens']
            analyzed_count      += 1

            logger.info(
                f'  解析結果: has_task={analysis.get("has_task")}, '
                f'has_schedule={analysis.get("has_schedule")}, '
                f'is_high_priority={analysis.get("is_high_priority")}'
            )

            # タスク → Notion
            if analysis.get('has_task'):
                task_summary    = analysis.get('task_summary', body[:200])
                related_project = analysis.get('related_project', '')
                add_or_update_notion_project(task_summary, related_project, dry_run=dry_run)

            # スケジュール → Google Calendar
            if analysis.get('has_schedule'):
                schedule_dt = analysis.get('schedule_datetime')
                if schedule_dt:
                    summary_text = analysis.get('schedule_summary', body[:100])
                    add_calendar_event(summary_text, schedule_dt, dry_run=dry_run)
                else:
                    logger.info('  スケジュール日時が不明なためカレンダー追加をスキップ')

            # 優先度高 → LINE WORKS 通知
            if analysis.get('is_high_priority'):
                body_excerpt = body[:100] + ('...' if len(body) > 100 else '')
                notify_text = (
                    f'【Chatwork 要確認】\n'
                    f'{room_name}｜{account_name}\n'
                    f'「{body_excerpt}」\n\n'
                    f'https://www.chatwork.com/#!rid{room_id}-{msg_id}'
                )
                send_line_works_message(notify_text, dry_run=dry_run)

        # --since スキップ件数のログ出力
        if since_dt is not None and since_skip_count > 0:
            logger.info(f'  スキップ（since以前）: {since_skip_count}件')

        # 状態を更新
        room_state['room_name']       = room_name
        room_state['last_message_id'] = new_last_id
        room_state['last_checked_at'] = now_str
        # last_updated_at: メッセージがあったルームは now で更新
        if messages:
            room_state['last_updated_at'] = now_str
        rooms_state[room_id] = room_state

    # 一次返信チェック（指定ルームの未返信メッセージを処理）
    if CHATWORK_URGENT_ROOM_IDS and CHATWORK_MY_ACCOUNT_ID:
        check_urgent_rooms(state, rooms_state, dry_run=dry_run, test_mode=test_mode)

    # 状態ファイル保存
    state['rooms'] = rooms_state
    save_state(state, dry_run=dry_run)

    # コスト概算（claude-haiku-4-5-20251001 料金）
    input_cost     = total_input_tokens  / 1_000_000 * INPUT_COST_PER_MTOK
    output_cost    = total_output_tokens / 1_000_000 * OUTPUT_COST_PER_MTOK
    total_cost_usd = input_cost + output_cost
    total_cost_jpy = total_cost_usd * 150  # 概算レート

    # 履歴に記録（テスト・通常運用共通）
    append_cost_history('chatwork-sync', total_cost_usd, total_input_tokens, total_output_tokens, analyzed_count)

    if test_mode:
        # テスト/テスト運用中: 毎回コストを報告
        logger.info('=== API使用コスト概算（テストモード） ===')
        logger.info(f'解析メッセージ数: {analyzed_count} 件')
        logger.info(f'入力トークン: {total_input_tokens:,}')
        logger.info(f'出力トークン: {total_output_tokens:,}')
        logger.info(f'推定コスト: ${total_cost_usd:.4f}（約¥{total_cost_jpy:.1f}）')
    else:
        # 通常運用中: 閾値超え時のみアラート
        if total_cost_usd > COST_THRESHOLD_USD:
            # 要因を推測
            if analyzed_count > 20:
                cause = f'解析メッセージ数が多い（{analyzed_count}件）（推測）'
            elif analyzed_count > 0:
                cause = f'1件あたりのトークンが多い（平均 {(total_input_tokens + total_output_tokens) // analyzed_count:,}トークン/件）（推測）'
            else:
                cause = '解析件数0件にもかかわらずコスト発生（確認推奨）'
            logger.warning('=== [COST ALERT] APIコストが閾値を超えました ===')
            logger.warning(f'  推定コスト: ${total_cost_usd:.4f}（約¥{total_cost_jpy:.1f}）/ 閾値: ${COST_THRESHOLD_USD}')
            logger.warning(f'  解析メッセージ数: {analyzed_count}件 / 入力: {total_input_tokens:,} / 出力: {total_output_tokens:,}')
            logger.warning(f'  要因: {cause}')
        else:
            logger.info(f'APIコスト: ${total_cost_usd:.4f}（閾値内・履歴に記録済み）')

    logger.info('=== chatwork-sync 完了 ===')


# ── エントリーポイント ────────────────────────────────────────

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='Chatwork 同期スクリプト')
    parser.add_argument(
        '--dry-run',
        action='store_true',
        help='実際の書き込み（Notion/Calendar/LINEWORKS）をスキップして動作確認',
    )
    parser.add_argument(
        '--since',
        type=str,
        default=None,
        help='この日時以降のメッセージのみ処理（例: 2026-03-28T12:00:00）',
    )
    parser.add_argument(
        '--test-mode',
        action='store_true',
        help='テスト/テスト運用モード: 毎回APIコストを報告する（通常運用は閾値超え時のみ）',
    )
    args = parser.parse_args()

    # --since の解析（JSTとして扱う）
    since_dt = None
    if args.since:
        jst = timezone(timedelta(hours=9))
        try:
            since_dt = datetime.fromisoformat(args.since)
            if since_dt.tzinfo is None:
                since_dt = since_dt.replace(tzinfo=jst)
            logger.info(f'--since フィルタ有効: {since_dt.isoformat()} 以降のメッセージのみ処理')
        except ValueError as e:
            logger.error(f'--since の日時フォーマットが不正です: {e}')
            sys.exit(1)

    run_sync(dry_run=args.dry_run, since_dt=since_dt, test_mode=args.test_mode)
