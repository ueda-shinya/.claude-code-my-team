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

# ── 定数 ──────────────────────────────────────────────────────
CHATWORK_API_BASE = 'https://api.chatwork.com/v2'
STATE_FILE        = os.path.expanduser('~/.claude/tmp/chatwork-state.json')
LOG_DIR           = os.path.expanduser('~/.claude/line-works-bot/logs')

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
    """Claude API でメッセージを解析してJSONを返す"""
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

        return json.loads(text)
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


# ── メイン処理 ───────────────────────────────────────────────

def run_sync(dry_run: bool = False, since_dt=None):
    """Chatwork 同期メイン処理"""
    logger.info(f'=== chatwork-sync 開始 {"[DRY-RUN]" if dry_run else ""} ===')
    if since_dt:
        logger.info(f'since フィルタ: {since_dt.isoformat()} 以降のみ処理')

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
            analysis = analyze_message(room_name, account_name, send_time, body)
            if not analysis:
                logger.warning(f'  解析失敗: message_id={msg_id}')
                continue

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
                # 本文の先頭100文字
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

    # 状態ファイル保存
    state['rooms'] = rooms_state
    save_state(state, dry_run=dry_run)

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

    run_sync(dry_run=args.dry_run, since_dt=since_dt)
