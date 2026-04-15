#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Claude Code レーダー — Notion DB 管理スクリプト

使い方:
  notion-radar.py --create-db
      「Claude Code レーダー」DBを作成して .env に NOTION_RADAR_DB_ID を書き込む
      親ページは .env の NOTION_ASUKA_PAGE_ID を使用

  notion-radar.py --add
      --title タイトル
      --date YYYY-MM-DD          （省略時: 当日）
      --category カテゴリ         スキル / MCP / Tips / ドキュメント更新 / その他
      --summary 要約
      --url URL
      --source 情報源             GitHub / Reddit / 公式docs / X / その他
      --riku-check リク検証       ✅信頼 / ⚠️要注意 / ❌怪しい
      --kanata-verdict カナタ判定  導入推奨 / 中立 / 非推奨
      --kanata-reason カナタ判定理由
      --recommend 1-5            おすすめ度（数値）
      ※ URL が既に Notion DB に登録済みの場合はスキップ（exit 0）

  notion-radar.py --list [--filter-status ステータス]
      レーダー項目を一覧表示（デフォルト: 実施可否=未確認 のみ）

  notion-radar.py --seen-check <url>
      既知URLかチェック。
        既知: stdout に "SEEN" を出力 + exit 0
        未知: stdout に "NEW" を出力 + exit 1
      stdout と exit code どちらでも判定可能。

  notion-radar.py --seen-add <url> --title タイトル
      既知URLとして登録（sha256ハッシュ + タイムスタンプ付き）
"""

import argparse
import hashlib
import json
import os
import pathlib
import ssl
import sys
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timezone, timedelta

# Windows環境での文字化け対策
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

# ---- 定数 ----

ENV_PATH = pathlib.Path.home() / '.claude' / '.env'
SEEN_JSON_PATH = pathlib.Path.home() / '.claude' / 'knowledge' / 'claude-code-radar-seen.json'
SSL_CTX = ssl.create_default_context()

NOTION_RICH_TEXT_LIMIT = 2000
# seen JSON の保持期間（日数）
SEEN_RETENTION_DAYS = 90

# .env のパーミッション（APIトークン含有のため最低限 owner read/write のみ）
ENV_MIN_MODE = 0o600

# seen.json ロック: 最大リトライ回数・待機秒数
SEEN_LOCK_MAX_RETRIES = 50
SEEN_LOCK_RETRY_INTERVAL = 0.1  # 秒

JST = timezone(timedelta(hours=9))

# カテゴリ
CATEGORY_OPTIONS = ['スキル', 'MCP', 'Tips', 'ドキュメント更新', 'その他']
CATEGORY_DEFAULT = 'その他'

# 情報源
SOURCE_OPTIONS = ['GitHub', 'Reddit', '公式docs', 'X', 'その他']
SOURCE_DEFAULT = 'その他'

# リク検証
RIKU_CHECK_OPTIONS = ['✅信頼', '⚠️要注意', '❌怪しい']

# カナタ判定
KANATA_VERDICT_OPTIONS = ['導入推奨', '中立', '非推奨']

# おすすめ度
RECOMMEND_OPTIONS = ['⭐5', '⭐4', '⭐3', '⭐2', '⭐1']

# 実施可否
STATUS_OPTIONS = ['未確認', '導入', '却下', '保留']
STATUS_DEFAULT = '未確認'


# ---- バリデーションヘルパー ----

def validate_url(url_val):
    """
    URLバリデーション。
    - scheme が http / https のみ許可
    - netloc が空でないこと
    - 制御文字（\r \n \t）を含まないこと
    問題があれば (False, エラーメッセージ) を返す。正常なら (True, '') を返す。
    """
    if any(c in url_val for c in ('\r', '\n', '\t')):
        return False, f'URL に制御文字（\\r \\n \\t）が含まれています: {url_val!r}'
    parsed = urllib.parse.urlparse(url_val)
    if parsed.scheme not in ('http', 'https'):
        return False, f'URL は http:// または https:// で始まる必要があります: {url_val}'
    if not parsed.netloc:
        return False, f'URL のホスト名が空です: {url_val}'
    return True, ''


def validate_whitelist(value, options, arg_name):
    """
    ホワイトリスト検証。options に含まれなければエラーメッセージを返す。
    含まれれば '' を返す。
    """
    if value not in options:
        return f'[ERROR] {arg_name} の値が不正です: {value}\n  有効な値: {", ".join(options)}'
    return ''


def validate_date(date_str):
    """
    YYYY-MM-DD フォーマット検証。
    正常なら (True, '') を返す。異常なら (False, エラーメッセージ) を返す。
    """
    try:
        datetime.strptime(date_str, '%Y-%m-%d')
        return True, ''
    except ValueError:
        return False, f'[ERROR] --date は YYYY-MM-DD 形式で指定してください: {date_str}'


# ---- .env 読み込み・書き込み ----

def load_env():
    """~/.claude/.env を読み込んで dict で返す"""
    if not ENV_PATH.exists():
        print(f'[ERROR] .env が見つかりません: {ENV_PATH}')
        sys.exit(1)
    env = {}
    with open(ENV_PATH, encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                k, v = line.split('=', 1)
                v = v.strip()
                if (v.startswith('"') and v.endswith('"')) or \
                   (v.startswith("'") and v.endswith("'")):
                    v = v[1:-1]
                env[k.strip()] = v
    return env


def update_env_key(key, value):
    """
    .env の指定キーを上書きする。
    既存行があれば置換、なければ末尾に追加する。
    アトミック書き込みで安全性を確保し、元ファイルの
    パーミッションを引き継ぐ（最低 0o600 を保証）。
    """
    if not ENV_PATH.exists():
        print(f'[ERROR] .env が見つかりません: {ENV_PATH}')
        sys.exit(1)

    # 元ファイルの mode を取得（最低 0o600 を保証）
    try:
        orig_stat = os.stat(ENV_PATH)
        orig_mode = orig_stat.st_mode & 0o777
        target_mode = orig_mode if (orig_mode & 0o600) == 0o600 else ENV_MIN_MODE
    except OSError:
        target_mode = ENV_MIN_MODE

    with open(ENV_PATH, encoding='utf-8') as f:
        lines = f.readlines()

    new_line = f'{key}={value}\n'
    found = False
    new_lines = []
    for line in lines:
        stripped = line.strip()
        if stripped.startswith('#') or '=' not in stripped:
            new_lines.append(line)
            continue
        k = stripped.split('=', 1)[0].strip()
        if k == key:
            new_lines.append(new_line)
            found = True
        else:
            new_lines.append(line)

    if not found:
        if new_lines and not new_lines[-1].endswith('\n'):
            new_lines.append('\n')
        new_lines.append(new_line)

    # アトミック書き込み（一時ファイル経由で上書き）
    dir_name = ENV_PATH.parent
    with tempfile.NamedTemporaryFile(
        mode='w', encoding='utf-8',
        dir=str(dir_name), delete=False, suffix='.tmp'
    ) as tmp:
        tmp.writelines(new_lines)
        tmp_path = tmp.name

    # パーミッション設定（os.chmod は Windows では限定動作だが害なし）
    try:
        os.chmod(tmp_path, target_mode)
    except OSError:
        pass

    os.replace(tmp_path, str(ENV_PATH))


# ---- Notion API 共通 ----

def notion_request(method, path, data=None, token=None, allow_404=False):
    """
    Notion API へリクエストを送って JSON を返す。
    allow_404=True の場合、404 は None を返す。
    エラーログは [ERROR] HTTP {code} {method} {path}: {msg} 形式。
    429 (レートリミット) は特別メッセージを付加する。
    """
    url = f'https://api.notion.com/v1{path}'
    body = json.dumps(data, ensure_ascii=False).encode('utf-8') if data is not None else None
    headers = {
        'Authorization': f'Bearer {token}',
        'Notion-Version': '2022-06-28',
        'Content-Type': 'application/json',
    }
    req = urllib.request.Request(url, data=body, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, context=SSL_CTX, timeout=30) as res:
            return json.loads(res.read().decode('utf-8'))
    except urllib.error.HTTPError as e:
        if allow_404 and e.code == 404:
            return None
        try:
            err = json.loads(e.read().decode('utf-8'))
            msg = err.get('message', '詳細不明')
        except Exception:
            msg = 'レスポンス解析不可'
        extra = ' (レートリミット超過)' if e.code == 429 else ''
        print(f'[ERROR] HTTP {e.code} {method} {path}: {msg}{extra}')
        sys.exit(1)
    except urllib.error.URLError as e:
        print(f'[ERROR] 接続エラー: {e.reason}')
        sys.exit(1)


def notion_query_all(db_id, token, filter_body=None):
    """ページネーションを考慮して全件取得する"""
    pages = []
    body = filter_body.copy() if filter_body else {}
    body['page_size'] = 100

    while True:
        result = notion_request('POST', f'/databases/{db_id}/query', body, token=token)
        pages.extend(result.get('results', []))
        if result.get('has_more'):
            body['start_cursor'] = result['next_cursor']
        else:
            break
    return pages


# ---- プロパティ変換ヘルパー ----

def get_text(props, key):
    """title または rich_text プロパティのテキストを取得"""
    p = props.get(key, {})
    t = p.get('type', '')
    if t == 'title':
        return ''.join(i.get('plain_text', '') for i in p.get('title', []))
    if t == 'rich_text':
        return ''.join(i.get('plain_text', '') for i in p.get('rich_text', []))
    return ''


def get_select(props, key):
    """select プロパティの name を取得"""
    s = props.get(key, {}).get('select')
    return s['name'] if s else ''


def get_date(props, key):
    """date プロパティの start を取得"""
    d = props.get(key, {}).get('date')
    return d['start'] if d else ''


def get_url(props, key):
    """url プロパティの値を取得"""
    return props.get(key, {}).get('url') or ''


def rich_text_prop(text):
    """rich_text プロパティ用の値を生成（2000文字上限）"""
    content = text[:NOTION_RICH_TEXT_LIMIT] if text else ''
    return {'rich_text': [{'text': {'content': content}}]}


def recommend_num_to_str(num):
    """1-5 の数値を ⭐N 形式に変換（範囲検証済みのため直アクセス）"""
    mapping = {1: '⭐1', 2: '⭐2', 3: '⭐3', 4: '⭐4', 5: '⭐5'}
    return mapping[int(num)]


def page_to_item(page):
    """Notion ページをレーダー項目 dict に変換"""
    p = page['properties']
    return {
        'id': page['id'],
        'タイトル': get_text(p, 'タイトル'),
        '投稿日時': get_date(p, '投稿日時'),
        'カテゴリ': get_select(p, 'カテゴリ'),
        '要約': get_text(p, '要約'),
        'リンク': get_url(p, 'リンク'),
        '情報源': get_select(p, '情報源'),
        'リク検証': get_select(p, 'リク検証'),
        'カナタ判定': get_select(p, 'カナタ判定'),
        'カナタ判定理由': get_text(p, 'カナタ判定理由'),
        'おすすめ度': get_select(p, 'おすすめ度'),
        '実施可否': get_select(p, '実施可否'),
    }


# ---- Notion URL 重複チェック ----

def check_notion_url_duplicate(url, token, db_id):
    """
    指定 URL が既に Notion DB に登録されているかクエリで確認する。
    登録済みなら True を返す。

    seen.json での事前フィルタが主（高速・ローカル）、Notion 側は保険。
    並行実行時の TOCTOU（Time-Of-Check-Time-Of-Use）競合で seen.json チェックをすり
    抜けた場合でも、Notion 側クエリで二重登録を防ぐ。
    """
    filter_body = {
        'filter': {
            'property': 'リンク',
            'url': {'equals': url}
        }
    }
    pages = notion_query_all(db_id, token, filter_body=filter_body)
    return len(pages) > 0


# ---- --create-db ----

def cmd_create_db(token, env):
    """Claude Code レーダー DB を作成し .env に NOTION_RADAR_DB_ID を追記する"""
    parent_page_id = env.get('NOTION_ASUKA_PAGE_ID', '')
    if not parent_page_id:
        print('[ERROR] .env に NOTION_ASUKA_PAGE_ID が設定されていません。')
        sys.exit(1)

    # 既存DBチェック
    existing_id = env.get('NOTION_RADAR_DB_ID', '')
    if existing_id:
        result = notion_request('GET', f'/databases/{existing_id}', token=token, allow_404=True)
        if result is not None:
            db_title = ''.join(t.get('plain_text', '') for t in result.get('title', []))
            print(f'[INFO] 既に存在します: {db_title} ({existing_id})')
            print('  再作成する場合は .env の NOTION_RADAR_DB_ID を削除してから実行してください。')
            sys.exit(0)

    print(f'Claude Code レーダー DB を作成中... (parent: {parent_page_id})')

    body = {
        'parent': {'page_id': parent_page_id},
        'title': [{'type': 'text', 'text': {'content': 'Claude Code レーダー'}}],
        'properties': {
            'タイトル': {'title': {}},
            '投稿日時': {'date': {}},
            'カテゴリ': {
                'select': {
                    'options': [
                        {'name': 'スキル',         'color': 'blue'},
                        {'name': 'MCP',             'color': 'purple'},
                        {'name': 'Tips',            'color': 'green'},
                        {'name': 'ドキュメント更新', 'color': 'yellow'},
                        {'name': 'その他',          'color': 'default'},
                    ]
                }
            },
            '要約': {'rich_text': {}},
            'リンク': {'url': {}},
            '情報源': {
                'select': {
                    'options': [
                        {'name': 'GitHub',   'color': 'gray'},
                        {'name': 'Reddit',   'color': 'orange'},
                        {'name': '公式docs', 'color': 'blue'},
                        {'name': 'X',        'color': 'default'},
                        {'name': 'その他',   'color': 'default'},
                    ]
                }
            },
            'リク検証': {
                'select': {
                    'options': [
                        {'name': '✅信頼',   'color': 'green'},
                        {'name': '⚠️要注意', 'color': 'yellow'},
                        {'name': '❌怪しい', 'color': 'red'},
                    ]
                }
            },
            'カナタ判定': {
                'select': {
                    'options': [
                        {'name': '導入推奨', 'color': 'green'},
                        {'name': '中立',     'color': 'gray'},
                        {'name': '非推奨',   'color': 'red'},
                    ]
                }
            },
            'カナタ判定理由': {'rich_text': {}},
            'おすすめ度': {
                'select': {
                    'options': [
                        {'name': '⭐5', 'color': 'yellow'},
                        {'name': '⭐4', 'color': 'green'},
                        {'name': '⭐3', 'color': 'blue'},
                        {'name': '⭐2', 'color': 'gray'},
                        {'name': '⭐1', 'color': 'default'},
                    ]
                }
            },
            '実施可否': {
                'select': {
                    'options': [
                        {'name': '未確認', 'color': 'gray'},
                        {'name': '導入',   'color': 'green'},
                        {'name': '却下',   'color': 'red'},
                        {'name': '保留',   'color': 'yellow'},
                    ]
                }
            },
        },
    }

    result = notion_request('POST', '/databases', body, token=token)
    db_id = result.get('id', '')
    if not db_id:
        print('[ERROR] DB ID を取得できませんでした。')
        print(json.dumps(result, ensure_ascii=False, indent=2))
        sys.exit(1)

    update_env_key('NOTION_RADAR_DB_ID', db_id)

    print('Claude Code レーダー DB を作成しました。')
    print(f'  DB ID: {db_id}')
    print('  .env の NOTION_RADAR_DB_ID を更新しました。')
    print()
    print('[Mac側への反映メモ]')
    print(f'  Mac の ~/.claude/.env に以下を追記してください（次回 git pull 後に手動で）:')
    print(f'  NOTION_RADAR_DB_ID={db_id}')


# ---- --list ----

def cmd_list(token, db_id, filter_status=None):
    """レーダー項目を一覧表示（デフォルト: 実施可否=未確認）"""
    target_status = filter_status if filter_status else STATUS_DEFAULT

    filter_body = {
        'filter': {
            'property': '実施可否',
            'select': {'equals': target_status}
        }
    }

    pages = notion_query_all(db_id, token, filter_body=filter_body)

    if not pages:
        print(f'[実施可否: {target_status}] の項目はありません。')
        return

    items = [page_to_item(p) for p in pages]
    # 投稿日時の降順でソート（新しい順）
    items.sort(key=lambda x: x['投稿日時'] or '', reverse=True)

    print(f'=== Claude Code レーダー [{target_status}] {len(items)} 件 ===')
    for item in items:
        date_str = item['投稿日時'] or '-'
        category_str = f'[{item["カテゴリ"]}]' if item['カテゴリ'] else ''
        source_str = item['情報源'] or '-'
        riku_str = item['リク検証'] or '-'
        kanata_str = item['カナタ判定'] or '-'
        recommend_str = item['おすすめ度'] or '-'
        summary_short = item['要約'][:60] + '...' if len(item['要約']) > 60 else item['要約']
        print(
            f'{date_str}  {category_str} {item["タイトル"]}\n'
            f'  情報源: {source_str}  リク検証: {riku_str}  カナタ: {kanata_str}  '
            f'おすすめ度: {recommend_str}\n'
            f'  {summary_short}'
        )
        if item['リンク']:
            print(f'  URL: {item["リンク"]}')
        print()

    print(f'合計 {len(items)} 件')


# ---- --add ----

def cmd_add(args, token, db_id):
    """レーダー項目を1件登録する"""
    if not args.title:
        print('[ERROR] --title は必須です。')
        sys.exit(1)

    # --date フォーマット検証
    if args.date:
        ok, err = validate_date(args.date)
        if not ok:
            print(err)
            sys.exit(1)
    date_str = args.date or datetime.now(JST).date().isoformat()

    # --category ホワイトリスト検証
    category = args.category or CATEGORY_DEFAULT
    if args.category:
        err = validate_whitelist(args.category, CATEGORY_OPTIONS, '--category')
        if err:
            print(err)
            sys.exit(1)

    # --source ホワイトリスト検証
    source = args.source or SOURCE_DEFAULT
    if args.source:
        err = validate_whitelist(args.source, SOURCE_OPTIONS, '--source')
        if err:
            print(err)
            sys.exit(1)

    status = STATUS_DEFAULT  # 登録時は常に「未確認」

    # URL バリデーション + Notion 重複チェック
    url_val = None
    if args.url:
        url_val = args.url.strip()
        ok, err = validate_url(url_val)
        if not ok:
            print(f'[ERROR] {err}')
            sys.exit(1)

        # Notion DB 内の重複確認
        if check_notion_url_duplicate(url_val, token, db_id):
            print(f'[SKIP] 既にNotion登録済み: {url_val}')
            sys.exit(0)

    props = {
        'タイトル': {'title': [{'text': {'content': args.title}}]},
        '投稿日時': {'date': {'start': date_str}},
        'カテゴリ': {'select': {'name': category}},
        '情報源': {'select': {'name': source}},
        '実施可否': {'select': {'name': status}},
    }

    if args.summary:
        props['要約'] = rich_text_prop(args.summary)

    if url_val:
        props['リンク'] = {'url': url_val}

    if args.riku_check:
        err = validate_whitelist(args.riku_check, RIKU_CHECK_OPTIONS, '--riku-check')
        if err:
            print(err)
            sys.exit(1)
        props['リク検証'] = {'select': {'name': args.riku_check}}

    if args.kanata_verdict:
        err = validate_whitelist(args.kanata_verdict, KANATA_VERDICT_OPTIONS, '--kanata-verdict')
        if err:
            print(err)
            sys.exit(1)
        props['カナタ判定'] = {'select': {'name': args.kanata_verdict}}

    if args.kanata_reason:
        props['カナタ判定理由'] = rich_text_prop(args.kanata_reason)

    if args.recommend:
        try:
            rec_num = int(args.recommend)
            if not 1 <= rec_num <= 5:
                raise ValueError()
        except ValueError:
            print('[ERROR] --recommend は 1〜5 の整数で指定してください。')
            sys.exit(1)
        props['おすすめ度'] = {'select': {'name': recommend_num_to_str(rec_num)}}

    notion_request('POST', '/pages', {
        'parent': {'database_id': db_id},
        'properties': props,
    }, token=token)

    print(f'登録しました: {args.title}')
    print(f'  日付: {date_str}  カテゴリ: {category}  情報源: {source}')
    print(f'  実施可否: {status}（登録時デフォルト）')


# ---- seen JSON 管理 ----

def _url_hash(url):
    """URL の sha256 ハッシュを返す"""
    return hashlib.sha256(url.strip().encode('utf-8')).hexdigest()


def _acquire_seen_lock():
    """
    seen.json のロックファイルを O_CREAT|O_EXCL で取得する。
    最大 SEEN_LOCK_MAX_RETRIES 回リトライ（間隔 SEEN_LOCK_RETRY_INTERVAL 秒）。
    FileExistsError 時にロックファイルの mtime が 60秒以上古ければ stale とみなして
    unlink → 再試行する（プロセスクラッシュ等でロックが残った場合のリカバリ）。
    取得できなければ sys.exit(1)。
    ロックファイルのパスを返す。
    """
    lock_path = SEEN_JSON_PATH.with_suffix('.lock')
    for _ in range(SEEN_LOCK_MAX_RETRIES):
        try:
            fd = os.open(str(lock_path), os.O_CREAT | os.O_EXCL | os.O_WRONLY)
            # デバッグ用: PID と作成時刻を書き込む
            os.write(fd, f'{os.getpid()}\n{datetime.now().isoformat()}'.encode())
            os.close(fd)
            return lock_path
        except FileExistsError:
            # stale ロック判定: mtime が 60秒以上古ければ削除して再試行
            try:
                age = time.time() - lock_path.stat().st_mtime
                if age > 60:
                    lock_path.unlink(missing_ok=True)
                    continue
            except FileNotFoundError:
                continue
            time.sleep(SEEN_LOCK_RETRY_INTERVAL)
    print(f'[ERROR] seen.json のロック取得タイムアウト: {lock_path}')
    sys.exit(1)


def _release_seen_lock(lock_path):
    """ロックファイルを削除する"""
    try:
        lock_path.unlink()
    except FileNotFoundError:
        pass


def _load_seen():
    """
    seen JSON を読み込んで返す。
    ファイルが無ければ空で初期化する。
    JSON 破損時は corrupt ファイルに退避して空初期化する。
    """
    if not SEEN_JSON_PATH.exists():
        SEEN_JSON_PATH.parent.mkdir(parents=True, exist_ok=True)
        return {'seen': []}
    try:
        with open(SEEN_JSON_PATH, encoding='utf-8') as f:
            data = json.load(f)
        if 'seen' not in data or not isinstance(data['seen'], list):
            data = {'seen': []}
        return data
    except json.JSONDecodeError:
        # 破損ファイルを退避
        timestamp = datetime.now(JST).strftime('%Y%m%d-%H%M%S')
        corrupt_path = SEEN_JSON_PATH.parent / f'claude-code-radar-seen.json.corrupt.{timestamp}'
        try:
            SEEN_JSON_PATH.rename(corrupt_path)
            print(f'[WARNING] seen.json が破損していたため退避しました: {corrupt_path}')
        except OSError as e:
            print(f'[WARNING] seen.json の退避に失敗しました: {e}')
        return {'seen': []}
    except IOError as e:
        print(f'[WARNING] seen.json の読み込みに失敗しました: {e}')
        return {'seen': []}


def _save_seen(data):
    """seen JSON を安全に書き込む（アトミック書き込み）"""
    dir_name = SEEN_JSON_PATH.parent
    dir_name.mkdir(parents=True, exist_ok=True)
    with tempfile.NamedTemporaryFile(
        mode='w', encoding='utf-8',
        dir=str(dir_name), delete=False, suffix='.tmp'
    ) as tmp:
        json.dump(data, tmp, ensure_ascii=False, indent=2)
        tmp_path = tmp.name
    os.replace(tmp_path, str(SEEN_JSON_PATH))


def _purge_old_entries(data):
    """90日超のエントリを自動削除する"""
    cutoff = datetime.now(timezone.utc) - timedelta(days=SEEN_RETENTION_DAYS)
    before_count = len(data['seen'])
    data['seen'] = [
        entry for entry in data['seen']
        if _parse_iso(entry.get('first_seen', '')) > cutoff
    ]
    removed = before_count - len(data['seen'])
    return data, removed


def _parse_iso(s):
    """ISO8601文字列を aware datetime に変換（パース失敗時は epoch を返す）"""
    try:
        return datetime.fromisoformat(s.replace('Z', '+00:00'))
    except (ValueError, AttributeError):
        return datetime(1970, 1, 1, tzinfo=timezone.utc)


def cmd_seen_check(url):
    """
    URLが既知かチェックする。
      既知: stdout に "SEEN" を出力 + exit 0
      未知: stdout に "NEW" を出力 + exit 1
    スキル側は stdout 文字列・exit code どちらでも判定可能。
    """
    data = _load_seen()
    target_hash = _url_hash(url)
    for entry in data['seen']:
        if entry.get('hash') == target_hash or entry.get('url') == url.strip():
            print('SEEN')
            sys.exit(0)
    print('NEW')
    sys.exit(1)


def cmd_seen_add(url, title):
    """URLを既知として登録する（排他制御付き）"""
    if not url:
        print('[ERROR] URL を指定してください。')
        sys.exit(1)
    url = url.strip()

    lock_path = _acquire_seen_lock()
    try:
        data = _load_seen()
        # 90日超のエントリを事前に削除
        data, removed = _purge_old_entries(data)
        if removed > 0:
            print(f'[INFO] 期限切れ {removed} 件を削除しました。')

        target_hash = _url_hash(url)
        # 重複チェック
        for entry in data['seen']:
            if entry.get('hash') == target_hash:
                print(f'[INFO] 既に登録済みです: {url}')
                sys.exit(0)

        now_iso = datetime.now(timezone.utc).isoformat()
        data['seen'].append({
            'url': url,
            'title': title or '',
            'hash': target_hash,
            'first_seen': now_iso,
        })
        _save_seen(data)
        # ロック保持中に件数を確定させてから出力（書き込み完了後の正確な値）
        seen_count = len(data['seen'])
    finally:
        _release_seen_lock(lock_path)

    print(f'登録しました: {url}')
    if title:
        print(f'  タイトル: {title}')
    print(f'  登録日時: {now_iso}')
    print(f'  現在の既知件数: {seen_count}')


# ---- main ----

def main():
    parser = argparse.ArgumentParser(
        description='Claude Code レーダー — Notion DB 管理スクリプト'
    )

    # コマンドグループ
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument('--create-db', action='store_true', help='Notion DB を作成する')
    group.add_argument('--add', action='store_true', help='レーダー項目を1件登録する')
    group.add_argument('--list', action='store_true', help='項目一覧を表示する')
    group.add_argument(
        '--seen-check', metavar='URL',
        help=(
            'URLが既知かチェックする。'
            '既知: stdout に "SEEN" 出力 + exit 0 / '
            '未知: stdout に "NEW" 出力 + exit 1。'
            'stdout・exit code どちらでも判定可能。'
        )
    )
    group.add_argument('--seen-add', metavar='URL', help='URLを既知として登録する')

    # --add オプション
    parser.add_argument('--title',          help='タイトル（--add 必須）')
    parser.add_argument(
        '--date',
        help='投稿日時 YYYY-MM-DD（省略時: 当日）'
    )
    parser.add_argument(
        '--category',
        help=f'カテゴリ: {", ".join(CATEGORY_OPTIONS)}'
    )
    parser.add_argument('--summary',        help='要約テキスト')
    parser.add_argument('--url',            help='情報元URL（http/https のみ）')
    parser.add_argument(
        '--source',
        help=f'情報源: {", ".join(SOURCE_OPTIONS)}'
    )
    parser.add_argument('--riku-check',     dest='riku_check',
                        help=f'リク検証: {", ".join(RIKU_CHECK_OPTIONS)}')
    parser.add_argument('--kanata-verdict', dest='kanata_verdict',
                        help=f'カナタ判定: {", ".join(KANATA_VERDICT_OPTIONS)}')
    parser.add_argument('--kanata-reason',  dest='kanata_reason', help='カナタ判定理由')
    parser.add_argument('--recommend',      help='おすすめ度 1〜5')

    # --list オプション
    parser.add_argument('--filter-status',  dest='filter_status',
                        help=f'実施可否フィルタ: {", ".join(STATUS_OPTIONS)}')

    # --seen-add オプション
    parser.add_argument('--title-seen',     dest='title_seen',
                        help='--seen-add 時のタイトル（--title も使用可）')

    args = parser.parse_args()

    # seen 系コマンドは Notion 不要
    if args.seen_check:
        cmd_seen_check(args.seen_check)
        return
    if args.seen_add:
        title_for_seen = args.title_seen or args.title or ''
        cmd_seen_add(args.seen_add, title_for_seen)
        return

    # Notion 系コマンド
    env = load_env()
    token = env.get('NOTION_API_TOKEN', '')
    if not token:
        print('[ERROR] .env に NOTION_API_TOKEN が設定されていません。')
        sys.exit(1)

    if args.create_db:
        cmd_create_db(token, env)
        return

    # --list / --add は DB ID が必要
    db_id = env.get('NOTION_RADAR_DB_ID', '')
    if not db_id:
        print('[ERROR] .env に NOTION_RADAR_DB_ID が設定されていません。')
        print('  先に --create-db を実行してください。')
        sys.exit(1)

    if args.list:
        cmd_list(token, db_id, filter_status=args.filter_status)
    elif args.add:
        cmd_add(args, token, db_id)


if __name__ == '__main__':
    main()
