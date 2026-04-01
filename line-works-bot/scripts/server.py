"""
LINE WORKS Bot サーバー — アスカ Bot (Phase 1)
Flask + ngrok + Anthropic SDK + APScheduler (Chatwork 定期同期)

起動方法:
  pip install flask anthropic requests python-dotenv PyJWT pyngrok apscheduler
  X:\\Python310\\python.exe ~/.claude/line-works-bot/scripts/server.py
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
from apscheduler.schedulers.background import BackgroundScheduler

# ── 環境変数読み込み ───────────────────────────────────────
load_dotenv(os.path.expanduser('~/.claude/.env'))

BOT_ID            = os.environ['LINE_WORKS_ASUKA_BOT_ID']
CLIENT_ID         = os.environ['LINE_WORKS_CLIENT_ID']
CLIENT_SECRET     = os.environ['LINE_WORKS_CLIENT_SECRET']
SERVICE_ACCOUNT   = os.environ['LINE_WORKS_SERVICE_ACCOUNT']
PRIVATE_KEY_PATH  = os.path.expanduser(os.environ['LINE_WORKS_PRIVATE_KEY_PATH'])
ALLOWED_USER_ID   = os.environ['ALLOWED_USER_ID']
ANTHROPIC_API_KEY = os.environ['ANTHROPIC_API_KEY']
BOT_SECRET        = os.environ.get('LINE_WORKS_ASUKA_BOT_SECRET', '')
MIO_BOT_ID        = os.environ.get('LINE_WORKS_MIO_BOT_ID', '')
MIO_BOT_SECRET    = os.environ.get('LINE_WORKS_MIO_BOT_SECRET', '')
BOT_CHAIN_MAX_TURNS = 5  # Bot-to-Bot 会話の最大ターン数

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

def send_message(user_id: str, text: str, channel_id: str = None, bot_id: str = None) -> bool:
    """LINE WORKS にメッセージを送信（長文は自動分割）
    channel_id が指定された場合はグループチャットへ送信、
    None の場合は DM（user 宛て）に送信する。
    bot_id が指定された場合はそのBotで送信、未指定の場合はアスカBot（BOT_ID）で送信する。
    """
    token = get_access_token()
    effective_bot_id = bot_id or BOT_ID
    if channel_id:
        # グループチャット向けエンドポイント
        url = f'https://www.worksapis.com/v1.0/bots/{effective_bot_id}/channels/{channel_id}/messages'
        logger.info(f'グループ送信先: channel_id={channel_id}')
    else:
        # DM 向けエンドポイント
        url = f'https://www.worksapis.com/v1.0/bots/{effective_bot_id}/users/{user_id}/messages'
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

# ── エージェントルーティング ──────────────────────────────
def parse_agent_routing(text: str) -> tuple[str, str]:
    """
    メッセージ先頭のエージェント名を検出してルーティングを決定する。
    戻り値: (agent, actual_text)
      - agent: 'mio' / 'asuka' / 'default'
      - actual_text: エージェント名プレフィックスを除いたメッセージ本文
    """
    # ミオへのルーティング判定
    m = re.match(r'^ミオ[、,：:　\s]\s*(.+)', text, re.DOTALL)
    if m:
        actual = m.group(1).strip()
        logger.info(f'ルーティング: mio / 実際のメッセージ: {actual[:40]}')
        return 'mio', actual

    # アスカへのルーティング判定
    m = re.match(r'^アスカ[、,：:　\s]\s*(.+)', text, re.DOTALL)
    if m:
        actual = m.group(1).strip()
        logger.info(f'ルーティング: asuka / 実際のメッセージ: {actual[:40]}')
        return 'asuka', actual

    # 宛先なし
    logger.info(f'ルーティング: default / メッセージ: {text[:40]}')
    return 'default', text


def detect_handoff(reply: str) -> tuple[str, str] | None:
    """
    返答末尾のBot引継ぎパターンを検出する。
    戻り値: (next_agent, message) または None
    """
    lines = reply.strip().splitlines()
    tail = '\n'.join(lines[-5:]) if len(lines) >= 5 else reply

    # パターン①: ミオ、〇〇（従来形式）
    m = re.search(r'ミオ[、,：:　\s]\s*(.+)', tail)
    if m:
        return 'mio', m.group(1).strip()
    # パターン②: ミオに〇〇を（動詞）形式
    m = re.search(r'ミオに(.{5,60})(?:を調べ|をリサーチ|を依頼|を調査|してもら)', tail)
    if m:
        return 'mio', m.group(1).strip() + 'を調べてください'

    # パターン①: アスカ、〇〇（従来形式）
    m = re.search(r'アスカ[、,：:　\s]\s*(.+)', tail)
    if m:
        return 'asuka', m.group(1).strip()
    # パターン②: アスカに〇〇を（動詞）形式
    m = re.search(r'アスカに(.{5,60})(?:を依頼|してもら|をお願い)', tail)
    if m:
        return 'asuka', m.group(1).strip()

    return None


def _run_bot_chain(user_id: str, initial_reply: str, initial_agent: str, channel_id: str):
    """Bot-to-Bot 会話チェーンを実行する（グループチャット専用・最大BOT_CHAIN_MAX_TURNSターン）"""
    current_reply = initial_reply
    current_agent = initial_agent

    for turn in range(BOT_CHAIN_MAX_TURNS):
        handoff = detect_handoff(current_reply)
        if not handoff:
            break
        next_agent, next_msg = handoff
        if next_agent == current_agent:
            logger.info(f'Bot引継ぎ: 自分自身({current_agent})への引継ぎを無視')
            break

        logger.info(f'Bot引継ぎ [{turn + 1}/{BOT_CHAIN_MAX_TURNS}]: {current_agent} → {next_agent}: {next_msg[:40]}')

        if next_agent == 'mio':
            next_reply = get_mio_response(user_id, next_msg)
            next_bot_id = MIO_BOT_ID
        else:
            next_reply = get_claude_response(user_id, next_msg)
            next_bot_id = BOT_ID

        next_reply = format_for_lineworks(next_reply)
        send_message(user_id, next_reply, channel_id=channel_id, bot_id=next_bot_id)
        logger.info(f'Bot引継ぎ送信 [{next_agent}]: {next_reply[:80]}')

        current_reply = next_reply
        current_agent = next_agent


# ── アスカのシステムプロンプト ─────────────────────────────
SYSTEM_PROMPT = """あなたは「アスカ（明日香）」というAIアシスタントです。
上田伸也さん（シンヤさん）の業務を支援する、信頼できる右腕・チーフ・オブ・スタッフです。

## キャラクター
- 落ち着いていて、的確な判断ができる女性アシスタント
- 口調は丁寧語ベース（「〜いたします」「承知いたしました」）
- フランクな会話では自然な敬語に崩してOK（「〜ですね」「〜しておきます」）
- ユーザーのことは「シンヤさん」と呼ぶ

## LINE WORKS 経由の応答スタイル（重要）
- スマートフォンからの利用を想定しているため、短く・要点だけ答える
- 長い回答が必要なら「要約」を先に出し「詳細は PC で確認を」と一言添える
- Markdown の装飾は最小限にする（絵文字は使わない）

## できること（Phase 1）
- 質問への回答・相談・アドバイス
- タスクや情報の整理・要約
- 業務上の判断サポート
- Google カレンダーの予定確認（サーバーがリアルタイム取得してプロンプトに注入する）

## カレンダーデータについて（重要）
- このプロンプトに「カレンダー情報」セクションが含まれている場合、それはサーバーが Google Calendar API でリアルタイム取得した実際のデータです
- そのデータをそのまま回答に使用してください
- 「カレンダーにアクセスできない」などとは絶対に言わないでください

## Bot間引継ぎ（グループチャット）
グループチャットでミオ（リサーチ担当）に調査を依頼したい場合は、返答の末尾に以下の**いずれかの形式**で書いてください。サーバーが自動的にミオに引き継ぎます（最大5ターン）。

形式A（推奨）: 「ミオ、〇〇を調べてください」
形式B: 「ミオに〇〇を調べてもらいます」「ミオに〇〇を依頼します」

**重要**: 「ミオに伝えておきます」「ミオに相談します」のような曖昧な表現は使わないこと。必ず「調べ」「依頼」「リサーチ」「調査」「してもら」のいずれかの動詞を含めること。

## 現時点の制約
- ファイル操作・コード実行はできません（PC の Claude Code が必要）
"""

# ── ミオのシステムプロンプト ───────────────────────────────
MIO_SYSTEM_PROMPT = """あなたは「ミオ」というAIリサーチャーです。
上田伸也さん（シンヤさん）の業務リサーチを専門に担当します。

## キャラクター
- 丁寧で正確な情報収集・整理が得意
- 「〜です」「〜ます」ベースの標準的な敬語

## LINE WORKS 経由の応答スタイル
- スマートフォンからの利用を想定。短く・要点だけ答える
- 情報が多い場合は「要約→詳細は PC で確認を」という構成にする

## できること
- Web上の情報収集・調査・まとめ
- 競合・業界・製品情報のリサーチ
- 調べた内容の整理・比較

## Bot間引継ぎ（グループチャット）
グループチャットでアスカ（メインアシスタント）に処理を依頼したい場合は、返答の末尾に以下の**いずれかの形式**で書いてください。サーバーが自動的にアスカに引き継ぎます（最大5ターン）。

形式A（推奨）: 「アスカ、〇〇してください」
形式B: 「アスカに〇〇を依頼します」「アスカに〇〇をお願いします」「アスカに〇〇してもらいます」

**重要**: 「アスカに伝えておきます」のような曖昧な表現は使わないこと。必ず「依頼」「してもら」「お願い」のいずれかの動詞を含めること。
"""

# ── カレンダー取得 ─────────────────────────────────────────
def fetch_calendar(date_from: str, date_to: str) -> str:
    """Google Calendar から指定期間のイベントを取得して文字列で返す"""
    try:
        cred_path = os.path.expanduser('~/.claude/google-oauth-credentials.json')
        token_path = os.path.expanduser('~/.claude/mcp-google-calendar-token.json')
        with open(cred_path, encoding='utf-8') as f:
            cred = json.load(f)
        with open(token_path, encoding='utf-8') as f:
            token = json.load(f)

        data = urllib.parse.urlencode({
            'client_id':     cred['installed']['client_id'],
            'client_secret': cred['installed']['client_secret'],
            'refresh_token': token['normal']['refresh_token'],
            'grant_type':    'refresh_token',
        }).encode()
        req = urllib.request.Request('https://oauth2.googleapis.com/token', data=data, method='POST')
        with urllib.request.urlopen(req, timeout=10) as res:
            access_token = json.loads(res.read())['access_token']

        url = (
            'https://www.googleapis.com/calendar/v3/calendars/primary/events'
            f'?timeMin={urllib.parse.quote(date_from + "T00:00:00+09:00")}'
            f'&timeMax={urllib.parse.quote(date_to   + "T23:59:59+09:00")}'
            '&singleEvents=true&orderBy=startTime&timeZone=Asia/Tokyo'
        )
        req2 = urllib.request.Request(url, headers={'Authorization': f'Bearer {access_token}'})
        with urllib.request.urlopen(req2, timeout=10) as res:
            items = json.loads(res.read()).get('items', [])

        if not items:
            return '予定なし'
        lines = []
        for e in items:
            start = e.get('start', {})
            t = start.get('dateTime', start.get('date', ''))[:16]
            lines.append(f'- {t} {e.get("summary", "（タイトルなし）")}')
        return '\n'.join(lines)
    except Exception as e:
        logger.error(f'fetch_calendar エラー: {e}')
        return None


def add_calendar_event(summary: str, start_iso: str, end_iso: str) -> str | None:
    """Google Calendar にイベントを追加して結果メッセージを返す"""
    try:
        cred_path = os.path.expanduser('~/.claude/google-oauth-credentials.json')
        token_path = os.path.expanduser('~/.claude/mcp-google-calendar-token.json')
        with open(cred_path, encoding='utf-8') as f:
            cred = json.load(f)
        with open(token_path, encoding='utf-8') as f:
            token = json.load(f)

        data = urllib.parse.urlencode({
            'client_id':     cred['installed']['client_id'],
            'client_secret': cred['installed']['client_secret'],
            'refresh_token': token['normal']['refresh_token'],
            'grant_type':    'refresh_token',
        }).encode()
        req = urllib.request.Request('https://oauth2.googleapis.com/token', data=data, method='POST')
        with urllib.request.urlopen(req, timeout=10) as res:
            access_token = json.loads(res.read())['access_token']

        event_data = json.dumps({
            'summary': summary,
            'start': {'dateTime': start_iso, 'timeZone': 'Asia/Tokyo'},
            'end':   {'dateTime': end_iso,   'timeZone': 'Asia/Tokyo'},
        }).encode()
        req2 = urllib.request.Request(
            'https://www.googleapis.com/calendar/v3/calendars/primary/events',
            data=event_data,
            headers={'Authorization': f'Bearer {access_token}', 'Content-Type': 'application/json'},
            method='POST'
        )
        with urllib.request.urlopen(req2, timeout=10) as res:
            json.loads(res.read())

        dt = datetime.fromisoformat(start_iso)
        label = f'{dt.month}月{dt.day}日 {dt.hour}:{dt.minute:02d}'
        return f'カレンダーに追加しました。\n{summary}\n{label}（1時間）'
    except Exception as e:
        logger.error(f'add_calendar_event エラー: {e}')
        return None


def parse_add_event(text: str):
    """
    「明日14時に田中さんとMTG追加して」をパースして (summary, start_iso, end_iso) を返す。
    解析できない場合は None を返す。
    """
    jst = timezone(timedelta(hours=9))
    today = datetime.now(jst)

    # 基準日の特定
    if '明後日' in text:
        base = today + timedelta(days=2)
    elif '明日' in text:
        base = today + timedelta(days=1)
    elif '今日' in text or '本日' in text:
        base = today
    else:
        m = re.search(r'(\d{1,2})月(\d{1,2})日', text)
        if m:
            month, day = int(m.group(1)), int(m.group(2))
            base = today.replace(month=month, day=day, tzinfo=jst)
            if base.date() < today.date():
                base = base.replace(year=today.year + 1)
        else:
            return None

    # 時刻の特定（例: 14:30 / 14時30分 / 14時）
    m = re.search(r'(\d{1,2})[時:](\d{2})(?:分)?', text)
    if m:
        hour, minute = int(m.group(1)), int(m.group(2))
    else:
        m = re.search(r'(\d{1,2})時', text)
        if m:
            hour, minute = int(m.group(1)), 0
        else:
            return None  # 時刻が特定できなければスキップ

    start = base.replace(hour=hour, minute=minute, second=0, microsecond=0)
    end   = start + timedelta(hours=1)

    # タイトル抽出: 日時・操作キーワードを除いた残り
    title = text
    for pat in [r'\d{1,2}月\d{1,2}日', r'明後日', r'明日', r'今日', r'本日',
                r'\d{1,2}[時:]\d{2}(?:分)?', r'\d{1,2}時',
                r'カレンダー[にへ]?', r'予定[をに]?', r'追加して', r'を入れて',
                r'に入れて', r'登録して', r'入れといて', r'追加']:
        title = re.sub(pat, '', title)
    title = re.sub(r'[\s　]+', ' ', title).strip().strip('のをにでへ ')

    if not title:
        title = '予定'

    start_iso = start.strftime('%Y-%m-%dT%H:%M:%S+09:00')
    end_iso   = end.strftime('%Y-%m-%dT%H:%M:%S+09:00')
    return title, start_iso, end_iso


def detect_schedule_intent(text: str):
    """
    予定・スケジュール系のキーワードを検出して取得対象日付を返す。
    戻り値: (date_from, date_to, label) or None
    """
    KEYWORDS = ['予定', 'スケジュール', '何時', '何かある', 'アポ', '打ち合わせ', 'mtg', '会議']
    if not any(kw in text.lower() for kw in KEYWORDS):
        return None

    jst = timezone(timedelta(hours=9))
    today = datetime.now(jst)

    if '来週' in text:
        # 来週月〜日
        days_to_monday = (7 - today.weekday()) % 7 or 7
        monday = today + timedelta(days=days_to_monday)
        sunday = monday + timedelta(days=6)
        return monday.strftime('%Y-%m-%d'), sunday.strftime('%Y-%m-%d'), '来週'
    if '今週' in text:
        monday = today - timedelta(days=today.weekday())
        sunday = monday + timedelta(days=6)
        return monday.strftime('%Y-%m-%d'), sunday.strftime('%Y-%m-%d'), '今週'
    if '明後日' in text:
        d = today + timedelta(days=2)
        ds = d.strftime('%Y-%m-%d')
        return ds, ds, f'{d.month}月{d.day}日'
    if '明日' in text:
        d = today + timedelta(days=1)
        ds = d.strftime('%Y-%m-%d')
        return ds, ds, '明日'
    # デフォルト：今日
    ds = today.strftime('%Y-%m-%d')
    return ds, ds, '今日'


# ── Claude API 呼び出し ────────────────────────────────────
def get_claude_response(user_id: str, message: str) -> str:
    with sessions_lock:
        history = sessions[user_id].copy()

    # カレンダー情報を自動注入
    system = SYSTEM_PROMPT
    intent = detect_schedule_intent(message)
    if intent:
        date_from, date_to, label = intent
        logger.info(f'カレンダー取得開始: {label} ({date_from}〜{date_to})')
        cal_text = fetch_calendar(date_from, date_to)
        if cal_text is not None:
            jst = timezone(timedelta(hours=9))
            now_str = datetime.now(jst).strftime('%Y-%m-%d %H:%M')
            system += f'\n\n## {label}のカレンダー情報（{date_from}〜{date_to}）\n{cal_text}\n\n現在時刻: {now_str}'
            logger.info(f'カレンダー注入完了: {cal_text[:50]}')

    history.append({'role': 'user', 'content': message})

    response = claude_client.messages.create(
        model='claude-sonnet-4-6',
        max_tokens=1024,
        system=system,
        messages=history,
    )
    reply = response.content[0].text

    with sessions_lock:
        sessions[user_id].append({'role': 'user',      'content': message})
        sessions[user_id].append({'role': 'assistant',  'content': reply})
        if len(sessions[user_id]) > MAX_HISTORY:
            sessions[user_id] = sessions[user_id][-MAX_HISTORY:]

    return reply

# ── ミオ用 Claude API 呼び出し ────────────────────────────
def get_mio_response(user_id: str, message: str) -> str:
    """ミオ専用セッションで Claude API を呼び出す（カレンダー注入なし）"""
    session_key = f'{user_id}_mio'

    with sessions_lock:
        history = sessions[session_key].copy()

    history.append({'role': 'user', 'content': message})

    response = claude_client.messages.create(
        model='claude-sonnet-4-6',
        max_tokens=1024,
        system=MIO_SYSTEM_PROMPT,
        messages=history,
    )
    reply = response.content[0].text

    with sessions_lock:
        sessions[session_key].append({'role': 'user',      'content': message})
        sessions[session_key].append({'role': 'assistant',  'content': reply})
        if len(sessions[session_key]) > MAX_HISTORY:
            sessions[session_key] = sessions[session_key][-MAX_HISTORY:]

    return reply


# ── カスタムコマンド実装 ────────────────────────────────

GA4_CACHE_PATH = os.path.expanduser('~/.claude/tmp/ga4-cache.txt')

def _parse_ga4_output(out: str) -> str:
    """ga4-report.py の stdout を LINE WORKS 向けサマリーに変換"""
    def get(key):
        m = re.search(rf'^{key}: (.+)$', out, re.MULTILINE)
        return m.group(1) if m else '–'

    lines = [
        '昨日のサイト状況',
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


def cmd_ga4(force: bool = False) -> str:
    """GA4レポートを返す。当日キャッシュがあればそれを使い、なければ取得してキャッシュ保存"""
    jst = timezone(timedelta(hours=9))
    today_str = datetime.now(jst).strftime('%Y-%m-%d')

    # キャッシュ確認（force=False かつ当日キャッシュあり → 即返却）
    if not force and os.path.exists(GA4_CACHE_PATH):
        try:
            with open(GA4_CACHE_PATH, encoding='utf-8') as f:
                first_line = f.readline().strip()
                cached_out = f.read()
            if first_line == today_str:
                logger.info('GA4キャッシュ使用')
                return _parse_ga4_output(cached_out)
        except Exception:
            pass  # 読み込み失敗時は再取得

    # 新規取得
    try:
        logger.info('GA4取得開始（ga4-report.py実行）')
        result = subprocess.run(
            [sys.executable, os.path.expanduser('~/.claude/scripts/ga4-report.py')],
            capture_output=True, text=True, timeout=90, encoding='utf-8'
        )
        out = result.stdout
        if out.strip():
            # キャッシュ保存（1行目に日付、2行目以降に stdout）
            os.makedirs(os.path.dirname(GA4_CACHE_PATH), exist_ok=True)
            with open(GA4_CACHE_PATH, 'w', encoding='utf-8') as f:
                f.write(today_str + '\n' + out)
            logger.info('GA4キャッシュ保存完了')
        return _parse_ga4_output(out)
    except Exception as e:
        logger.error(f'cmd_ga4 エラー: {e}')
        return 'GA4の取得に失敗しました。PC側のログを確認してください。'


def cmd_tasks() -> str:
    """Notion の残件タスクDB から未着手・進行中・保留のタスクを返す"""
    try:
        # .env から Notion 認証情報を読み込む
        env_path = os.path.expanduser('~/.claude/.env')
        notion_token = ''
        tasks_db_id = ''
        with open(env_path, encoding='utf-8') as f:
            for line in f:
                line = line.strip().strip('"').strip("'")
                if line.startswith('NOTION_API_TOKEN='):
                    notion_token = line.split('=', 1)[1].strip('"').strip("'")
                if line.startswith('NOTION_TASKS_DB_ID='):
                    tasks_db_id = line.split('=', 1)[1].strip('"').strip("'")

        if not notion_token or not tasks_db_id:
            logger.error('cmd_tasks: NOTION_API_TOKEN または NOTION_TASKS_DB_ID が未設定')
            return '⚠️ Notion の設定が不足しています。.env を確認してください。'

        # Notion API: 完了以外のタスクを作成日昇順で取得
        filter_body = {
            'filter': {
                'property': 'ステータス',
                'select': {
                    'does_not_equal': '完了'
                }
            },
            'sorts': [{'property': '作成日', 'direction': 'ascending'}]
        }
        ctx = ssl.create_default_context()
        req = urllib.request.Request(
            f'https://api.notion.com/v1/databases/{tasks_db_id}/query',
            data=json.dumps(filter_body).encode(),
            headers={
                'Authorization': f'Bearer {notion_token}',
                'Notion-Version': '2022-06-28',
                'Content-Type': 'application/json',
            },
            method='POST'
        )
        with urllib.request.urlopen(req, context=ctx, timeout=10) as res:
            result = json.loads(res.read())

        pages = result.get('results', [])
        if not pages:
            return '残件タスクはありません ✅'

        lines = [f'📋 残件タスク（{len(pages)}件）', '']
        for page in pages:
            props = page.get('properties', {})

            # タスク名（title プロパティ）
            title_prop = props.get('タスク名') or props.get('名前') or {}
            title_items = title_prop.get('title', [])
            title = ''.join(t.get('plain_text', '') for t in title_items).strip() or '（タイトルなし）'

            # ステータス（select プロパティ）
            status_prop = props.get('ステータス', {})
            status_val = (status_prop.get('select') or {}).get('name', '')

            # ステータスラベル
            STATUS_LABELS = {
                '未着手': '未着手',
                '進行中': '進行中',
                '保留':   '保留',
            }
            label = STATUS_LABELS.get(status_val, status_val or '不明')

            lines.append(f'[{label}] {title}')

            # 作業履歴（rich_text プロパティ）— 最後の1行のみ表示
            history_prop = props.get('作業履歴', {})
            history_items = history_prop.get('rich_text', [])
            history_text = ''.join(t.get('plain_text', '') for t in history_items).strip()
            if history_text and label == '進行中':
                # 改行で分割して最後の空でない行を取得
                history_lines = [l.strip() for l in history_text.splitlines() if l.strip()]
                if history_lines:
                    lines.append(f'  └ 最新履歴: {history_lines[-1]}')

        lines.append('')
        lines.append('Notion で管理中 🔗')
        return '\n'.join(lines)

    except urllib.error.HTTPError as e:
        logger.error(f'cmd_tasks Notion API エラー: HTTP {e.code}')
        return 'Notion API がエラーを返しました。認証情報を確認してください。'
    except Exception as e:
        logger.error(f'cmd_tasks エラー: {e}')
        return '⚠️ Notion への接続に失敗しました。直接 Notion を確認してください。'


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
            return 'クライアント情報が見つかりませんでした。'
        return 'クライアント一覧\n' + '\n'.join(entries)
    except Exception as e:
        logger.error(f'cmd_clients エラー: {e}')
        return 'クライアント一覧の取得に失敗しました。'


def cmd_memo(text: str) -> str:
    """knowledge-buffer.md にメモを追加する"""
    try:
        path = os.path.expanduser('~/.claude/knowledge-buffer.md')
        jst = timezone(timedelta(hours=9))
        now = datetime.now(jst).strftime('%Y-%m-%d %H:%M')
        with open(path, 'a', encoding='utf-8') as f:
            f.write(f'\n- [{now}] {text}')
        return f'メモしました。\n「{text}」'
    except Exception as e:
        logger.error(f'cmd_memo エラー: {e}')
        return 'メモの保存に失敗しました。'


MAX_DECIDE_LEN = 200  # /decide コマンドの入力文字数上限

def cmd_decide(text: str) -> str:
    """session-handoff.md の設計・実装決定ログに追記して git push する"""
    if len(text) > MAX_DECIDE_LEN:
        return f'決定内容が長すぎます（{len(text)}文字）。{MAX_DECIDE_LEN}文字以内にしてください。'
    try:
        path = os.path.expanduser('~/.claude/session-handoff.md')
        jst = timezone(timedelta(hours=9))
        today = datetime.now(jst)
        date_str = f'{today.year}-{today.month:02d}-{today.day:02d}'
        entry = f'- [{date_str}] {text}（`/decide` コマンドで記録）'

        with open(path, encoding='utf-8') as f:
            content = f.read()

        # 「設計・実装決定ログ」セクションの末尾エントリを探して追記
        # セクション内の最後の「- [」行の直後に挿入する
        lines = content.splitlines(keepends=True)
        insert_idx = None
        in_section = False
        for i, line in enumerate(lines):
            if re.match(r'^## 設計・実装決定ログ', line):
                in_section = True
                continue
            if in_section and re.match(r'^##', line):
                # 次のセクションに入ったら終了
                break
            if in_section and re.match(r'^- \[', line):
                insert_idx = i  # 最後のエントリ行を更新し続ける

        if insert_idx is not None:
            # 最後のエントリ行の直後に追記
            lines.insert(insert_idx + 1, entry + '\n')
        else:
            # エントリが1件もない場合はセクション末尾（次セクション区切りの手前）に追記
            in_section = False
            inserted = False
            for i, line in enumerate(lines):
                if re.match(r'^## 設計・実装決定ログ', line):
                    in_section = True
                    continue
                if in_section and re.match(r'^##', line):
                    lines.insert(i, entry + '\n')
                    inserted = True
                    break
            if not inserted:
                # セクション自体が存在しない場合はヘッダーごと末尾に追記
                lines.append('\n## 設計・実装決定ログ\n')
                lines.append(entry + '\n')

        with open(path, 'w', encoding='utf-8') as f:
            f.writelines(lines)

        # git add → commit → push
        repo_dir = os.path.expanduser('~/.claude')
        subprocess.run(
            ['git', 'add', 'session-handoff.md'],
            cwd=repo_dir, capture_output=True, timeout=30
        )
        subprocess.run(
            ['git', 'commit', '-m', 'chore: /decide で設計決定を記録'],
            cwd=repo_dir, capture_output=True, timeout=30
        )
        push_result = subprocess.run(
            ['git', 'push'],
            cwd=repo_dir, capture_output=True, timeout=60
        )
        if push_result.returncode != 0:
            logger.warning(f'cmd_decide: git push 失敗 (returncode={push_result.returncode})')
            return f'記録しました（ローカルのみ。pushに失敗しました）。\n{entry}'
        logger.info(f'cmd_decide: 記録完了 → {entry}')
        return f'記録しました。\n{entry}'
    except Exception as e:
        logger.error(f'cmd_decide エラー: {e}')
        return '設計決定の記録に失敗しました。PC側のログを確認してください。'


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
        return f'Notion に追加しました。\n「{title}」\n{result.get("url", "")}'
    except Exception as e:
        logger.error(f'cmd_notion_add エラー: {e}')
        return 'Notion への追加に失敗しました。'


# ── コマンド処理 ───────────────────────────────────────────
def handle_command(user_id: str, text: str) -> str | None:
    cmd = text.strip()
    cmd_lower = cmd.lower()

    if cmd_lower == '/reset':
        with sessions_lock:
            sessions[user_id] = []
            sessions[f'{user_id}_mio'] = []
        return '会話履歴をリセットしました。（アスカ・ミオ両方）'

    if cmd_lower == '/status':
        with sessions_lock:
            asuka_count = len(sessions[user_id]) // 2
            mio_count = len(sessions[f'{user_id}_mio']) // 2
        return f'稼働中です。\n会話履歴: アスカ {asuka_count}件 / ミオ {mio_count}件'

    if cmd_lower == '/help':
        return (
            '使い方:\n'
            '/ga4          - 昨日のサイト状況\n'
            '/tasks        - 残件・引き継ぎ一覧\n'
            '/clients      - クライアント一覧\n'
            '/memo <テキスト>   - メモを保存\n'
            '/notion <タイトル> - Notion議事録DBに追加\n'
            '/decide <内容>    - 設計・実装決定を記録してpush\n'
            '/reset        - 会話履歴をリセット\n'
            '/status       - 稼働確認\n'
            '/help         - この一覧\n'
            '\nエージェント指定:\n'
            '「ミオ、〇〇調べて」 → ミオがリサーチ\n'
            '「アスカ、〇〇して」 → アスカが処理\n'
            '（グループでは宛先指定が必要）'
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
            return 'メモの内容を入力してください。\n例: /memo 〇〇を確認する'
        return cmd_memo(body)

    if cmd_lower.startswith('/notion '):
        body = cmd[8:].strip()
        if not body:
            return 'タイトルを入力してください。\n例: /notion 〇〇について確認'
        return cmd_notion_add(body)

    if cmd_lower == '/decide':
        return '使い方: /decide <決定内容>\n例: /decide ga4-report.pyの広告着地URLを動的検出に変更する'

    if cmd_lower.startswith('/decide '):
        body = cmd[8:].strip()
        if not body:
            return '決定内容を入力してください。\n例: /decide ga4-report.pyの広告着地URLを動的検出に変更する'
        return cmd_decide(body)

    if cmd_lower.startswith('/'):
        return '不明なコマンドです。/help で一覧を確認してください。'

    return None

# ── メッセージ処理（バックグラウンド） ────────────────────
def process_message(user_id: str, text: str, channel_id: str = None, received_bot_id: str = None):
    try:
        logger.info(f'受信 [{user_id}] channel={channel_id}: {text[:80]}')

        # エージェントルーティング判定
        agent, actual_text = parse_agent_routing(text)

        # ミオのBotに届いた場合、DM のプレフィックスなしはデフォルトでミオへ（グループは別途処理）
        is_mio_bot = MIO_BOT_ID and received_bot_id == MIO_BOT_ID
        if is_mio_bot and agent == 'default' and not channel_id:
            agent = 'mio'
            logger.info(f'ミオBot受信(DM) [{user_id}] → デフォルトルーティングをミオに変更')

        # コマンド（actual_text で判定 — グループ/DM に関わらず処理）
        if actual_text.startswith('/'):
            reply = handle_command(user_id, actual_text)
            if reply:
                send_message(user_id, reply, channel_id=channel_id, bot_id=received_bot_id)
                return

        # グループチャットで宛先指定なし → コンテンツから担当Botを判定
        if agent == 'default' and channel_id:
            MIO_TRIGGER_KEYWORDS = [
                '調べて', 'リサーチ', '調査', '比較', '情報収集', '検索',
                '教えて', '調べたい', '探して', 'について', 'とは', '違い',
                'まとめて', '何か', 'どんな',
            ]
            if any(kw in actual_text for kw in MIO_TRIGGER_KEYWORDS):
                agent = 'mio'
                logger.info(f'コンテンツルーティング → ミオ [{user_id}]: {actual_text[:40]}')
            else:
                agent = 'asuka'
                logger.info(f'コンテンツルーティング → アスカ(デフォルト) [{user_id}]: {actual_text[:40]}')

        # グループチャットで、自分宛てでないメッセージは無視
        if channel_id:
            if agent == 'mio' and not is_mio_bot:
                logger.info(f'グループ: ミオ宛てメッセージをアスカBotは無視 [{user_id}]')
                return
            if agent == 'asuka' and is_mio_bot:
                logger.info(f'グループ: アスカ宛てメッセージをミオBotは無視 [{user_id}]')
                return

        # ミオへのルーティング
        if agent == 'mio':
            logger.info(f'ミオへルーティング [{user_id}]: {actual_text[:40]}')
            reply = get_mio_response(user_id, actual_text)
            reply = format_for_lineworks(reply)
            send_message(user_id, reply, channel_id=channel_id, bot_id=received_bot_id)
            logger.info(f'ミオ送信 [{user_id}]: {reply[:80]}')
            if channel_id and MIO_BOT_ID:
                _run_bot_chain(user_id, reply, 'mio', channel_id)
            return

        # アスカの処理（agent == 'asuka' または agent == 'default'（DM））
        # カレンダー追加意図の検出
        ADD_KEYWORDS = ['追加して', 'を入れて', 'に入れて', '登録して', '入れといて', 'カレンダーに追加']
        if any(kw in actual_text for kw in ADD_KEYWORDS):
            parsed = parse_add_event(actual_text)
            if parsed:
                title, start_iso, end_iso = parsed
                logger.info(f'カレンダー追加: {title} {start_iso}')
                result = add_calendar_event(title, start_iso, end_iso)
                if result:
                    send_message(user_id, result, channel_id=channel_id, bot_id=received_bot_id)
                    return
                send_message(user_id, 'カレンダーへの追加に失敗しました。PC側のログを確認してください。', channel_id=channel_id, bot_id=received_bot_id)
                return

        # 自然言語 GA4 検出
        GA4_KEYWORDS = ['ga4', 'アクセス解析', 'サイト状況', 'アクセス数', 'サイト分析',
                        'アクセスレポート', 'ga4レポート', 'サイトレポート']
        if any(kw in actual_text.lower() for kw in GA4_KEYWORDS):
            logger.info(f'GA4自然言語検出: {actual_text[:40]}')
            force = any(kw in actual_text for kw in ['最新', '今すぐ', '今取得', '再取得', '最新版'])
            send_message(user_id, cmd_ga4(force=force), channel_id=channel_id, bot_id=received_bot_id)
            return

        # Claude API（アスカ）
        reply = get_claude_response(user_id, actual_text)
        reply = format_for_lineworks(reply)
        send_message(user_id, reply, channel_id=channel_id, bot_id=received_bot_id)
        logger.info(f'送信 [{user_id}]: {reply[:80]}')
        if channel_id and MIO_BOT_ID:
            _run_bot_chain(user_id, reply, 'asuka', channel_id)

    except Exception as e:
        logger.error(f'処理エラー: {e}', exc_info=True)
        if MIO_BOT_ID and received_bot_id == MIO_BOT_ID:
            err_msg = '申し訳ありません。処理中にエラーが発生しました。PC で確認してください。'
        else:
            err_msg = '申し訳ありません。処理中にエラーが発生しました。PC で確認してください。'
        send_message(user_id, err_msg, channel_id=channel_id, bot_id=received_bot_id)

# ── Flask ─────────────────────────────────────────────────
app = Flask(__name__)

def verify_signature(body: bytes, signature: str) -> str | None:
    """HMAC-SHA256 署名検証。成功したBot種別を返す（'asuka' / 'mio'）。失敗時は None"""
    secrets = [(BOT_SECRET, 'asuka'), (MIO_BOT_SECRET, 'mio')]
    for secret, label in secrets:
        if not secret:
            continue
        expected = base64.b64encode(
            hmac.new(secret.encode(), body, hashlib.sha256).digest()
        ).decode()
        if hmac.compare_digest(expected, signature):
            return label
    return None

@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok', 'time': datetime.now(timezone.utc).isoformat()})

@app.route('/callback', methods=['POST'])
def callback():
    body      = request.get_data()   # raw body を先に取得（署名検証に必要）
    signature = request.headers.get('X-WORKS-Signature', '')

    # 署名検証（どちらのBotか確定）
    verified_bot = verify_signature(body, signature) if signature else None
    if not verified_bot:
        logger.warning('署名検証失敗')
        return 'Invalid signature', 403

    try:
        data = json.loads(body)
    except Exception:
        return 'Bad request', 400

    if data.get('type') != 'message':
        return 'OK', 200

    source     = data.get('source', {})
    user_id    = source.get('userId', '')
    channel_id = source.get('channelId', None)  # グループチャットの場合に存在
    # 署名検証結果からBotIDを確定（ペイロードの botId は偽装可能なため使用しない）
    received_bot_id = MIO_BOT_ID if verified_bot == 'mio' else BOT_ID
    logger.info(f'受信 bot={verified_bot} / channel={channel_id}')
    content    = data.get('content', {})
    msg_type   = content.get('type', '')
    text       = content.get('text', '').strip()

    if msg_type != 'text' or not text:
        return 'OK', 200

    if user_id != ALLOWED_USER_ID:
        logger.warning(f'未許可ユーザーからのメッセージを無視: {user_id}')
        return 'OK', 200

    # 即時 200 を返してからバックグラウンドで処理
    def run():
        process_message(user_id, text, channel_id=channel_id, received_bot_id=received_bot_id)

    threading.Thread(target=run, daemon=True).start()
    return 'OK', 200

# ── Chatwork 定期同期（APScheduler） ──────────────────────
CHATWORK_SYNC_SCRIPT = os.path.expanduser('~/.claude/scripts/chatwork-sync.py')

def run_chatwork_sync():
    """
    chatwork-sync.py を子プロセスで実行するジョブ。
    15分ごとに呼ばれるが、各ルームのチェック間隔判定は chatwork-sync.py 側で行う。
    """
    logger.info('Chatwork 定期同期ジョブ起動')
    try:
        result = subprocess.run(
            [sys.executable, CHATWORK_SYNC_SCRIPT],
            capture_output=True,
            text=True,
            timeout=300,
            encoding='utf-8',
            errors='replace',
            env={**os.environ, 'PYTHONIOENCODING': 'utf-8'},
        )
        if result.stdout:
            logger.info(f'chatwork-sync stdout:\n{result.stdout[-1000:]}')
        if result.stderr:
            logger.warning(f'chatwork-sync stderr:\n{result.stderr[-500:]}')
        if result.returncode != 0:
            logger.error(f'chatwork-sync が異常終了しました (returncode={result.returncode})')
        else:
            logger.info('Chatwork 定期同期ジョブ完了')
    except subprocess.TimeoutExpired:
        logger.error('chatwork-sync がタイムアウトしました（300秒超過）')
    except Exception as e:
        logger.error(f'Chatwork 定期同期ジョブ エラー: {e}')


# ── エントリーポイント ─────────────────────────────────────
if __name__ == '__main__':
    logger.info('アスカ Bot サーバーを起動します')

    # 起動時クリーンアップ（旧 ngrok プロセスを終了）
    subprocess.run(['taskkill', '/F', '/IM', 'ngrok.exe'], capture_output=True)

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

    # APScheduler 起動（Chatwork 15分ごと定期同期）
    scheduler = BackgroundScheduler(timezone='Asia/Tokyo')
    scheduler.add_job(
        run_chatwork_sync,
        trigger='interval',
        minutes=15,
        id='chatwork_sync',
        replace_existing=True,
        max_instances=1,
        next_run_time=datetime.now(timezone(timedelta(hours=9))),  # 起動直後に1回実行
    )
    scheduler.start()
    logger.info('APScheduler 起動完了（Chatwork 同期: 15分ごと）')

    app.run(host='127.0.0.1', port=5000, debug=False)
