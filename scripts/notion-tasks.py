#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Notion 案件管理スクリプト

使い方:
  notion-tasks.py --create-db <PARENT_PAGE_ID>
      新スキーマでDBを作成して .env に NOTION_TASKS_DB_ID を書き込む
      旧IDは NOTION_TASKS_DB_ID_OLD として退避

  notion-tasks.py --list [--filter-status S] [--filter-type T]
      [--filter-env E] [--filter-client C] [--filter-priority P]
      案件一覧をステータス順で表示

  notion-tasks.py --add タイトル
      [--type 種別] [--priority 優先度] [--status ステータス]
      [--category カテゴリ] [--env 環境] [--client クライアント]
      [--assignee 担当] [--memo メモ] [--start-date YYYY-MM-DD]
      案件を1件追加する

  notion-tasks.py --update 部分タイトル
      [--status S] [--priority P] [--assignee A] [--blocker テキスト]
      案件のプロパティを更新する

  notion-tasks.py --show 部分タイトル
      全プロパティ + ページ本文（作業履歴）を表示

  notion-tasks.py --add-block 部分タイトル --text テキスト
      [--env 環境] [--assignee 担当]
      ページ本文末尾に作業ブロックを追記する

  notion-tasks.py --alerts
      優先度別の閾値を超えた案件を一覧表示（朝のブリーフィング用）

  notion-tasks.py --migrate
      NOTION_TASKS_DB_ID_OLD の全件を新DBに移行する
"""

import json
import os
import re
import ssl
import sys
import argparse
import tempfile
import urllib.request
import urllib.error
from datetime import datetime, timezone, timedelta

# Windows環境での文字化け対策
sys.stdout.reconfigure(encoding='utf-8')
sys.stderr.reconfigure(encoding='utf-8')

# Notion DBプロパティ名定数
from notion_schema import TasksDB

# ---- 定数 ----

ENV_PATH = os.path.expanduser('~/.claude/.env')
SSL_CTX = ssl.create_default_context()

# Notion rich_text プロパティの文字数上限
NOTION_RICH_TEXT_LIMIT = 2000

# 日本時間タイムゾーン
JST = timezone(timedelta(hours=9))

# 案件種別
TYPE_OPTIONS = ['実装', '環境構築', '運用改善', '調査・相談', '手作業', '議題・検討']
TYPE_DEFAULT = '実装'

# ステータス
STATUS_OPTIONS = ['未着手', '次にやる', '進行中', 'レビュー待ち', 'シンヤ確認待ち', '保留', '完了', '取下げ']
STATUS_DEFAULT = '未着手'
STATUS_ORDER = ['次にやる', '進行中', 'レビュー待ち', 'シンヤ確認待ち', '未着手', '保留', '完了', '取下げ']
# アラート除外ステータス
STATUS_ALERT_EXCLUDE = {'保留', '完了', '取下げ'}

# 優先度
PRIORITY_OPTIONS = ['P1-即時', 'P2-今週中', 'P3-今月中', 'P4-いつかやる', 'P5-アイデア']
PRIORITY_DEFAULT = 'P3-今月中'
# 優先度別アラート閾値（日数）
PRIORITY_ALERT_DAYS = {
    'P1-即時': 1,
    'P2-今週中': 3,
    'P3-今月中': 7,
    'P4-いつかやる': 30,
    # P5-アイデア はアラートなし
}

# カテゴリ
CATEGORY_OPTIONS = ['LP制作', '開発', 'GA4', 'Bot', 'マーケ', 'クライアント', 'チーム運用', 'その他']
CATEGORY_DEFAULT = 'その他'

# 対象環境
ENV_OPTIONS = ['Windows', 'Mac', 'クロスプラットフォーム', '環境不問']
ENV_DEFAULT = '環境不問'

# クライアント
CLIENT_OPTIONS = ['officeueda', 'inada-ryota', 'us-saijo', '（内部）']

# 担当
ASSIGNEE_OPTIONS = ['Asuka', 'Shu', 'Sakura', 'シンヤ', 'その他']
ASSIGNEE_DEFAULT = 'Asuka'

# 旧ステータス→新ステータスのマッピング（--migrate用）
OLD_STATUS_MAP = {
    '未着手': '未着手',
    '進行中': '進行中',
    '完了': '完了',
    '保留': '保留',
}

# 旧優先度→新優先度のマッピング（--migrate用）
OLD_PRIORITY_MAP = {
    '高': 'P2-今週中',
    '中': 'P3-今月中',
    '低': 'P4-いつかやる',
}


# ---- .env 読み込み・書き込み ----

def load_env():
    """~/.claude/.env を読み込んで dict で返す"""
    if not os.path.exists(ENV_PATH):
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
    """
    if not os.path.exists(ENV_PATH):
        print(f'[ERROR] .env が見つかりません: {ENV_PATH}')
        sys.exit(1)

    with open(ENV_PATH, encoding='utf-8') as f:
        lines = f.readlines()

    new_line = f'{key}={value}\n'
    found = False
    new_lines = []
    for line in lines:
        stripped = line.strip()
        # コメント行・空行は保持
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
        # 末尾に改行を確保してから追加
        if new_lines and not new_lines[-1].endswith('\n'):
            new_lines.append('\n')
        new_lines.append(new_line)

    # アトミック書き込み
    dir_name = os.path.dirname(ENV_PATH)
    with tempfile.NamedTemporaryFile(
        mode='w', encoding='utf-8',
        dir=dir_name, delete=False, suffix='.tmp'
    ) as tmp:
        tmp.writelines(new_lines)
        tmp_path = tmp.name
    os.replace(tmp_path, ENV_PATH)


# ---- Notion API 共通 ----

def notion_request(method, path, data=None, token=None, allow_404=False):
    """
    Notion API へリクエストを送って JSON を返す。
    allow_404=True の場合、404 は None を返す（sys.exit しない）。
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
        print(f'[ERROR] HTTP {e.code}: {msg}')
        sys.exit(1)
    except urllib.error.URLError as e:
        print(f'[ERROR] 接続エラー: {e.reason}')
        sys.exit(1)


def search_db_by_title(db_name, token):
    """
    Notion Search API で同名のDBを検索する。
    見つかった場合は最初のDBの id を返す。見つからなければ None を返す。
    """
    body = {
        'filter': {'property': 'object', 'value': 'database'},
        'query': db_name,
        'page_size': 100,
    }
    result = notion_request('POST', '/search', body, token=token)
    if not result:
        return None
    for db in result.get('results', []):
        # タイトルが完全一致するDBを探す
        titles = db.get('title', [])
        title_text = ''.join(t.get('plain_text', '') for t in titles)
        if title_text == db_name:
            return db.get('id', '')
    return None


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


def get_multi_select(props, key):
    """multi_select プロパティの name リストを取得"""
    items = props.get(key, {}).get('multi_select', [])
    return [i['name'] for i in items]


def get_date(props, key):
    """date プロパティの start を取得"""
    d = props.get(key, {}).get('date')
    return d['start'] if d else ''


def get_last_edited_time(page):
    """Notion ページのトップレベル last_edited_time を取得（システムプロパティ）"""
    return page.get('last_edited_time', '')


def get_created_time(page):
    """Notion ページのトップレベル created_time を取得（システムプロパティ）"""
    return page.get('created_time', '')


def page_to_item(page):
    """Notion ページを案件 dict に変換"""
    p = page['properties']
    return {
        'id': page['id'],
        TasksDB.TITLE:      get_text(p, TasksDB.TITLE),
        TasksDB.TYPE:       get_select(p, TasksDB.TYPE),
        TasksDB.STATUS:     get_select(p, TasksDB.STATUS),
        TasksDB.PRIORITY:   get_select(p, TasksDB.PRIORITY),
        TasksDB.CATEGORY:   get_select(p, TasksDB.CATEGORY),
        TasksDB.ENV:        get_multi_select(p, TasksDB.ENV),
        TasksDB.CLIENT:     get_select(p, TasksDB.CLIENT),
        TasksDB.ASSIGNEE:   get_select(p, TasksDB.ASSIGNEE),
        TasksDB.BLOCKER:    get_text(p, TasksDB.BLOCKER),
        TasksDB.MEMO:       get_text(p, TasksDB.MEMO),
        TasksDB.START_DATE: get_date(p, TasksDB.START_DATE),
        '最終編集日時': get_last_edited_time(page),
        '作成日時': get_created_time(page),
    }


def status_sort_key(item):
    """ステータスを定義順でソートするためのキー関数"""
    s = item[TasksDB.STATUS]
    try:
        return STATUS_ORDER.index(s)
    except ValueError:
        return len(STATUS_ORDER)


def rich_text_prop(text):
    """rich_text プロパティ用の値を生成"""
    # Notion は1ブロックあたり2000文字上限
    content = text[:NOTION_RICH_TEXT_LIMIT] if text else ''
    return {'rich_text': [{'text': {'content': content}}]}


def find_page_by_partial_title(partial_title, token, db_id):
    """
    部分タイトルで Notion DB を検索し、マッチしたページ一覧を返す。
    """
    result = notion_request('POST', f'/databases/{db_id}/query', {
        'filter': {'property': TasksDB.TITLE, 'title': {'contains': partial_title}}
    }, token=token)
    return result.get('results', [])


def resolve_single_page(partial_title, token, db_id):
    """
    部分タイトルで1件に絞り込む。
    0件・複数件はエラーで終了。
    """
    pages = find_page_by_partial_title(partial_title, token, db_id)

    if not pages:
        print(f'[ERROR] 「{partial_title}」に一致する案件が見つかりません。')
        sys.exit(1)

    if len(pages) > 1:
        print(f'[ERROR] {len(pages)} 件一致しました。タイトルをより具体的に指定してください。')
        for p in pages:
            t = page_to_item(p)
            print(f'  - {t[TasksDB.TITLE]} [{t[TasksDB.STATUS]}]')
        sys.exit(1)

    return pages[0]


# ---- --create-db ----

def cmd_create_db(parent_page_id, token, env, force=False, reuse=False):
    """新スキーマで Notion DB を作成し、.env を更新する"""

    # ---- チェック1: .envにDB IDがある場合 ----
    existing_id = env.get('NOTION_TASKS_DB_ID', '')
    if existing_id and not force:
        result = notion_request('GET', f'/databases/{existing_id}', token=token, allow_404=True)
        if result is not None:
            # DBが存在する → 作成を中断
            db_title = ''.join(
                t.get('plain_text', '') for t in result.get('title', [])
            )
            print(f'[INFO] 既に存在します: {db_title} ({existing_id})')
            print('  再作成する場合は --force を指定してください。')
            sys.exit(1)
        # 404 → IDが古い/削除済み。チェック2へ進む

    # ---- チェック2: 同名DBを検索 ----
    if not force:
        found_id = search_db_by_title('案件管理', token)
        if found_id:
            print(f'[INFO] 同名のDBが見つかりました: 案件管理 ({found_id})')
            print('  このDBを使用する場合は --reuse を指定してください。')
            print('  新規作成する場合は --force を指定してください。')
            if reuse:
                update_env_key('NOTION_TASKS_DB_ID', found_id)
                print(f'  .env の NOTION_TASKS_DB_ID を更新しました: {found_id}')
            sys.exit(1)

    # --reuse のみ指定（--force なし）でDBが見つからなかった場合は通常作成
    print(f'案件管理DBを作成中... (parent_page_id: {parent_page_id})')

    body = {
        'parent': {'page_id': parent_page_id},
        'title': [{'type': 'text', 'text': {'content': '案件管理'}}],
        'properties': {
            TasksDB.TITLE: {'title': {}},
            TasksDB.TYPE: {
                'select': {
                    'options': [
                        {'name': '実装', 'color': 'blue'},
                        {'name': '環境構築', 'color': 'purple'},
                        {'name': '運用改善', 'color': 'green'},
                        {'name': '調査・相談', 'color': 'yellow'},
                        {'name': '手作業', 'color': 'orange'},
                        {'name': '議題・検討', 'color': 'pink'},
                    ]
                }
            },
            TasksDB.STATUS: {
                'select': {
                    'options': [
                        {'name': '未着手', 'color': 'gray'},
                        {'name': '次にやる', 'color': 'blue'},
                        {'name': '進行中', 'color': 'green'},
                        {'name': 'レビュー待ち', 'color': 'yellow'},
                        {'name': 'シンヤ確認待ち', 'color': 'orange'},
                        {'name': '保留', 'color': 'red'},
                        {'name': '完了', 'color': 'default'},
                        {'name': '取下げ', 'color': 'default'},
                    ]
                }
            },
            TasksDB.PRIORITY: {
                'select': {
                    'options': [
                        {'name': 'P1-即時', 'color': 'red'},
                        {'name': 'P2-今週中', 'color': 'orange'},
                        {'name': 'P3-今月中', 'color': 'yellow'},
                        {'name': 'P4-いつかやる', 'color': 'blue'},
                        {'name': 'P5-アイデア', 'color': 'gray'},
                    ]
                }
            },
            TasksDB.CATEGORY: {
                'select': {
                    'options': [
                        {'name': 'LP制作', 'color': 'purple'},
                        {'name': '開発', 'color': 'blue'},
                        {'name': 'GA4', 'color': 'green'},
                        {'name': 'Bot', 'color': 'pink'},
                        {'name': 'マーケ', 'color': 'orange'},
                        {'name': 'クライアント', 'color': 'yellow'},
                        {'name': 'チーム運用', 'color': 'red'},
                        {'name': 'その他', 'color': 'default'},
                    ]
                }
            },
            TasksDB.ENV: {
                'multi_select': {
                    'options': [
                        {'name': 'Windows', 'color': 'blue'},
                        {'name': 'Mac', 'color': 'gray'},
                        {'name': 'クロスプラットフォーム', 'color': 'green'},
                        {'name': '環境不問', 'color': 'default'},
                    ]
                }
            },
            TasksDB.CLIENT: {
                'select': {
                    'options': [
                        {'name': 'officeueda', 'color': 'blue'},
                        {'name': 'inada-ryota', 'color': 'green'},
                        {'name': 'us-saijo', 'color': 'orange'},
                        {'name': '（内部）', 'color': 'gray'},
                    ]
                }
            },
            TasksDB.ASSIGNEE: {
                'select': {
                    'options': [
                        {'name': 'Asuka', 'color': 'pink'},
                        {'name': 'Shu', 'color': 'blue'},
                        {'name': 'Sakura', 'color': 'green'},
                        {'name': 'シンヤ', 'color': 'orange'},
                        {'name': 'その他', 'color': 'gray'},
                    ]
                }
            },
            TasksDB.BLOCKER:    {'rich_text': {}},
            TasksDB.MEMO:       {'rich_text': {}},
            TasksDB.START_DATE: {'date': {}},
            # last_edited_time / created_time は Notion 組み込みのため指定不要
        },
    }

    result = notion_request('POST', '/databases', body, token=token)
    db_id = result.get('id', '')
    if not db_id:
        print('[ERROR] DB ID を取得できませんでした。レスポンスを確認してください。')
        print(json.dumps(result, ensure_ascii=False, indent=2))
        sys.exit(1)

    # 旧 ID を退避
    old_db_id = env.get('NOTION_TASKS_DB_ID', '')
    if old_db_id:
        update_env_key('NOTION_TASKS_DB_ID_OLD', old_db_id)
        print(f'  旧 DB ID を NOTION_TASKS_DB_ID_OLD に退避しました: {old_db_id}')

    # 新 ID を書き込む
    update_env_key('NOTION_TASKS_DB_ID', db_id)

    print(f'案件管理DBを作成しました。')
    print(f'  DB ID: {db_id}')
    print(f'  .env の NOTION_TASKS_DB_ID を更新しました。')


# ---- --list ----

def parse_since(since_str):
    """
    '--since' オプション値をパースして cutoff の datetime（JST aware）を返す。
    書式: "Nd"（N日前）のみサポート。パース失敗時は stderr にエラーを出力して sys.exit(1)。
    例: "30d" → datetime.now(JST) - timedelta(days=30)
    """
    if since_str is None:
        return None
    m = re.match(r'^(\d+)d$', since_str.strip())
    if not m:
        print(f'[ERROR] --since の書式が不正です: {since_str!r}（例: 30d）', file=sys.stderr)
        sys.exit(1)
    days = int(m.group(1))
    if days <= 0:
        print(f'[ERROR] --since の日数は正の整数を指定してください: {since_str!r}', file=sys.stderr)
        sys.exit(1)
    return datetime.now(JST) - timedelta(days=days)


def cmd_list(token, db_id, filter_status=None, filter_type=None,
             filter_env=None, filter_client=None, filter_priority=None,
             since=None):
    """案件一覧をステータス順で表示

    since: '--since' オプションの文字列（例: "30d"）。
           指定時は最終編集日時が cutoff 以降の案件のみ表示する。
    """
    # フィルター条件を構築
    filters = []
    if filter_status:
        filters.append({'property': TasksDB.STATUS, 'select': {'equals': filter_status}})
    if filter_type:
        filters.append({'property': TasksDB.TYPE, 'select': {'equals': filter_type}})
    if filter_client:
        filters.append({'property': TasksDB.CLIENT, 'select': {'equals': filter_client}})
    if filter_priority:
        filters.append({'property': TasksDB.PRIORITY, 'select': {'equals': filter_priority}})
    if filter_env:
        filters.append({'property': TasksDB.ENV, 'multi_select': {'contains': filter_env}})

    body = {}
    if len(filters) == 1:
        body['filter'] = filters[0]
    elif len(filters) > 1:
        body['filter'] = {'and': filters}

    pages = notion_query_all(db_id, token, filter_body=body)

    if not pages:
        print('案件がありません。')
        return

    items = [page_to_item(p) for p in pages]
    items.sort(key=status_sort_key)

    # --since フィルタ（Pythonレベルの後処理。last_edited_time ベース）
    cutoff = parse_since(since)
    if cutoff is not None:
        filtered = []
        for item in items:
            last_edited_str = item.get('最終編集日時', '')
            if not last_edited_str:
                # 日時が取れない場合はフィルタ対象外として除外
                continue
            try:
                last_edited_utc = datetime.fromisoformat(last_edited_str.replace('Z', '+00:00'))
                last_edited_jst = last_edited_utc.astimezone(JST)
            except ValueError:
                continue
            if last_edited_jst >= cutoff:
                filtered.append(item)
        items = filtered

    if not items:
        print('案件がありません。')
        return

    for item in items:
        priority_str = item[TasksDB.PRIORITY] if item[TasksDB.PRIORITY] else '-'
        env_str = ','.join(item[TasksDB.ENV]) if item[TasksDB.ENV] else '-'
        client_str = f'  [{item[TasksDB.CLIENT]}]' if item[TasksDB.CLIENT] else ''
        assignee_str = f'  担当:{item[TasksDB.ASSIGNEE]}' if item[TasksDB.ASSIGNEE] else ''
        type_str = f'({item[TasksDB.TYPE]})' if item[TasksDB.TYPE] else ''
        memo_str = f'\n      メモ: {item[TasksDB.MEMO]}' if item[TasksDB.MEMO] else ''
        blocker_str = f'\n      ブロッカー: {item[TasksDB.BLOCKER]}' if item[TasksDB.BLOCKER] else ''
        print(
            f'[{item[TasksDB.STATUS]}] {item[TasksDB.TITLE]}  '
            f'{type_str} {priority_str} / {env_str}'
            f'{client_str}{assignee_str}'
            f'{memo_str}{blocker_str}'
        )

    print(f'\n合計 {len(items)} 件')


# ---- --add ----

def cmd_add(title, item_type, priority, status, category, env_list,
            client, assignee, memo, start_date, token, db_id):
    """案件を1件追加する"""
    if not start_date:
        start_date = datetime.now(JST).date().isoformat()

    props = {
        TasksDB.TITLE:      {'title': [{'text': {'content': title}}]},
        TasksDB.TYPE:       {'select': {'name': item_type}},
        TasksDB.STATUS:     {'select': {'name': status}},
        TasksDB.PRIORITY:   {'select': {'name': priority}},
        TasksDB.CATEGORY:   {'select': {'name': category}},
        TasksDB.START_DATE: {'date': {'start': start_date}},
    }

    if env_list:
        props[TasksDB.ENV] = {
            'multi_select': [{'name': e} for e in env_list]
        }

    if client:
        props[TasksDB.CLIENT] = {'select': {'name': client}}

    if assignee:
        props[TasksDB.ASSIGNEE] = {'select': {'name': assignee}}

    if memo:
        props[TasksDB.MEMO] = rich_text_prop(memo)

    notion_request('POST', '/pages', {
        'parent': {'database_id': db_id},
        'properties': props,
    }, token=token)
    print(f'追加しました: {title}')
    print(f'  種別: {item_type} / ステータス: {status} / 優先度: {priority}')
    print(f'  カテゴリ: {category} / 担当: {assignee} / 開始日: {start_date}')


# ---- --update ----

def cmd_update(partial_title, new_status, new_priority, new_assignee, new_blocker, token, db_id):
    """部分タイトルに一致する案件のプロパティを更新する"""
    page = resolve_single_page(partial_title, token, db_id)
    item = page_to_item(page)

    props = {}
    changes = []

    if new_status:
        props[TasksDB.STATUS] = {'select': {'name': new_status}}
        changes.append(f'ステータス: [{item[TasksDB.STATUS]}] → [{new_status}]')

    if new_priority:
        props[TasksDB.PRIORITY] = {'select': {'name': new_priority}}
        changes.append(f'優先度: [{item[TasksDB.PRIORITY]}] → [{new_priority}]')

    if new_assignee:
        props[TasksDB.ASSIGNEE] = {'select': {'name': new_assignee}}
        changes.append(f'担当: [{item[TasksDB.ASSIGNEE]}] → [{new_assignee}]')

    if new_blocker is not None:
        props[TasksDB.BLOCKER] = rich_text_prop(new_blocker)
        changes.append(f'ブロッカー: 更新')

    if not props:
        print('[ERROR] 更新するプロパティを --status / --priority / --assignee / --blocker で指定してください。')
        sys.exit(1)

    notion_request('PATCH', f'/pages/{page["id"]}', {'properties': props}, token=token)
    print(f'更新しました: {item[TasksDB.TITLE]}')
    for c in changes:
        print(f'  {c}')


# ---- --show ----

def cmd_show(partial_title, token, db_id):
    """全プロパティ + ページ本文を表示する"""
    page = resolve_single_page(partial_title, token, db_id)
    item = page_to_item(page)

    print(f'== {item[TasksDB.TITLE]} ==')
    for key, label in [
        (TasksDB.TYPE,     '種別'),
        (TasksDB.STATUS,   'ステータス'),
        (TasksDB.PRIORITY, '優先度'),
        (TasksDB.CATEGORY, 'カテゴリ'),
        (TasksDB.ASSIGNEE, '担当'),
        (TasksDB.BLOCKER,  'ブロッカー'),
        (TasksDB.START_DATE, '開始日'),
    ]:
        print(f'  {label:12}: {item[key] if item[key] else "-"}')
    # 対象環境はリスト結合の個別処理
    print(f'  {"対象環境":12}: {", ".join(item[TasksDB.ENV]) if item[TasksDB.ENV] else "-"}')
    print(f'  {"クライアント":12}: {item[TasksDB.CLIENT] if item[TasksDB.CLIENT] else "-"}')
    # 内部dictキー（定数化対象外）
    print(f'  {"最終編集":12}: {item["最終編集日時"]}')
    print(f'  {"作成日時":12}: {item["作成日時"]}')
    if item[TasksDB.MEMO]:
        print(f'\n[メモ]')
        print(item[TasksDB.MEMO])

    # ページ本文（作業履歴ブロック）を取得（ページネーション対応）
    blocks = []
    cursor = None
    while True:
        path = f'/blocks/{page["id"]}/children?page_size=100'
        if cursor:
            path += f'&start_cursor={cursor}'
        blocks_result = notion_request('GET', path, token=token)
        blocks.extend(blocks_result.get('results', []))
        if blocks_result.get('has_more'):
            cursor = blocks_result['next_cursor']
        else:
            break

    if blocks:
        print(f'\n[作業履歴]')
        for block in blocks:
            block_type = block.get('type', '')
            if block_type == 'paragraph':
                texts = block.get('paragraph', {}).get('rich_text', [])
                text = ''.join(t.get('plain_text', '') for t in texts)
                if text:
                    print(text)
            elif block_type == 'heading_3':
                texts = block.get('heading_3', {}).get('rich_text', [])
                text = ''.join(t.get('plain_text', '') for t in texts)
                if text:
                    print(f'### {text}')
            elif block_type == 'bulleted_list_item':
                texts = block.get('bulleted_list_item', {}).get('rich_text', [])
                text = ''.join(t.get('plain_text', '') for t in texts)
                if text:
                    print(f'- {text}')
    else:
        print('\n[作業履歴] なし')


# ---- --add-block ----

def cmd_add_block(partial_title, text, env_label, assignee, token, db_id):
    """ページ本文末尾に作業ブロックを追記する"""
    page = resolve_single_page(partial_title, token, db_id)
    item = page_to_item(page)

    today = datetime.now(JST).strftime('%Y-%m-%d')
    env_label = env_label or '環境不問'
    assignee = assignee or ASSIGNEE_DEFAULT

    heading_content = f'[{today}] {env_label} / {assignee}'

    # ブロック構造:
    #   heading_3: "### [YYYY-MM-DD] <環境> / <担当>"
    #   bulleted_list_item: "- <テキスト>"
    blocks = [
        {
            'object': 'block',
            'type': 'heading_3',
            'heading_3': {
                'rich_text': [
                    {'type': 'text', 'text': {'content': heading_content[:NOTION_RICH_TEXT_LIMIT]}}
                ]
            }
        },
        {
            'object': 'block',
            'type': 'bulleted_list_item',
            'bulleted_list_item': {
                'rich_text': [
                    {'type': 'text', 'text': {'content': text[:NOTION_RICH_TEXT_LIMIT]}}
                ]
            }
        }
    ]

    notion_request('PATCH', f'/blocks/{page["id"]}/children', {'children': blocks}, token=token)
    print(f'ブロックを追記しました: {item[TasksDB.TITLE]}')
    print(f'  [{today}] {env_label} / {assignee}')
    print(f'  - {text}')


# ---- --alerts ----

def cmd_alerts(token, db_id):
    """優先度別の閾値を超えた案件を一覧表示する"""
    pages = notion_query_all(db_id, token)
    now_jst = datetime.now(JST)

    alerts = []
    for page in pages:
        item = page_to_item(page)

        # 除外ステータスをスキップ
        if item[TasksDB.STATUS] in STATUS_ALERT_EXCLUDE:
            continue

        priority = item[TasksDB.PRIORITY]
        if priority not in PRIORITY_ALERT_DAYS:
            continue  # P5-アイデア などはスキップ

        threshold_days = PRIORITY_ALERT_DAYS[priority]
        last_edited_str = item['最終編集日時']

        if not last_edited_str:
            continue

        # ISO8601 形式 "2026-04-10T12:34:56.000Z" をパース
        try:
            last_edited_utc = datetime.fromisoformat(last_edited_str.replace('Z', '+00:00'))
            last_edited_jst = last_edited_utc.astimezone(JST)
        except ValueError:
            continue

        # JST 日付ベースで比較（23時間59分でも0日になる問題を防ぐ）
        elapsed_days = (now_jst.date() - last_edited_jst.date()).days

        if elapsed_days >= threshold_days:
            alerts.append((priority, elapsed_days, item[TasksDB.TITLE], item[TasksDB.STATUS]))

    if not alerts:
        print('アラート対象の案件はありません。')
        return

    # 優先度順→経過日数の降順でソート
    priority_order = {p: i for i, p in enumerate(PRIORITY_OPTIONS)}
    alerts.sort(key=lambda x: (priority_order.get(x[0], 99), -x[1]))

    print(f'=== アラート: {len(alerts)} 件 ===')
    for priority, elapsed_days, title, status in alerts:
        print(f'[{priority}] {title} — {elapsed_days}日更新なし  ({status})')


# ---- --migrate ----

def cmd_migrate(token, db_id, old_db_id):
    """旧DBの全件を新DBに移行する"""
    if not old_db_id:
        print('[ERROR] .env に NOTION_TASKS_DB_ID_OLD が設定されていません。')
        print('  --create-db を先に実行してください。')
        sys.exit(1)

    print(f'旧DB ({old_db_id}) から移行中...')
    pages = notion_query_all(old_db_id, token)

    if not pages:
        print('旧DBにデータがありません。')
        return

    print(f'  {len(pages)} 件を移行します。')
    today_iso = datetime.now(JST).date().isoformat()
    migrated = 0
    skipped = 0

    for page in pages:
        p = page['properties']

        # 旧プロパティを取得（旧DBのプロパティ名は定数化しない）
        # 理由: このブロックは --migrate コマンド限定の旧DB互換コード。新DB（NOTION_TASKS_DB_ID）に
        # 全データ移行完了後は cmd_migrate 関数ごと削除予定のため、notion_schema.py への統合は行わない。
        old_title = get_text(p, 'タイトル')
        old_status = get_select(p, 'ステータス')
        old_priority = get_select(p, '優先度')
        old_category = get_select(p, 'カテゴリ')
        old_memo = get_text(p, 'メモ')
        # '作業履歴' は旧DB固有のプロパティ（現行TasksDBにはなく、ページ本文として移行済み）
        old_history = get_text(p, '作業履歴')
        # '作成日' は旧DB固有のプロパティ（現行TasksDBは Notion 組み込みの created_time を使用）
        old_created = get_date(p, '作成日')

        if not old_title:
            print(f'  スキップ（タイトルなし）')
            skipped += 1
            continue

        # ステータス・優先度をマッピング
        new_status = OLD_STATUS_MAP.get(old_status, '未着手')
        new_priority = OLD_PRIORITY_MAP.get(old_priority, 'P3-今月中')
        new_category = old_category if old_category in CATEGORY_OPTIONS else 'その他'
        start_date = old_created if old_created else today_iso

        props = {
            TasksDB.TITLE:      {'title': [{'text': {'content': old_title}}]},
            TasksDB.TYPE:       {'select': {'name': TYPE_DEFAULT}},
            TasksDB.STATUS:     {'select': {'name': new_status}},
            TasksDB.PRIORITY:   {'select': {'name': new_priority}},
            TasksDB.CATEGORY:   {'select': {'name': new_category}},
            TasksDB.ENV:        {'multi_select': [{'name': ENV_DEFAULT}]},
            TasksDB.ASSIGNEE:   {'select': {'name': ASSIGNEE_DEFAULT}},
            TasksDB.START_DATE: {'date': {'start': start_date}},
        }

        if old_memo:
            props[TasksDB.MEMO] = rich_text_prop(old_memo)

        # 新DBにページを作成
        result = notion_request('POST', '/pages', {
            'parent': {'database_id': db_id},
            'properties': props,
        }, token=token)
        new_page_id = result.get('id', '')

        # 旧「作業履歴」プロパティをページ本文に移動
        if old_history and new_page_id:
            # 段落ブロックとして追記
            lines = old_history.split('\n')
            blocks = []
            for line in lines:
                if line.strip():
                    blocks.append({
                        'object': 'block',
                        'type': 'paragraph',
                        'paragraph': {
                            'rich_text': [
                                {'type': 'text', 'text': {'content': line[:2000]}}
                            ]
                        }
                    })
                    # Notion API は一度に100ブロックまで
                    if len(blocks) >= 90:
                        notion_request('PATCH', f'/blocks/{new_page_id}/children',
                                       {'children': blocks}, token=token)
                        blocks = []
            if blocks:
                notion_request('PATCH', f'/blocks/{new_page_id}/children',
                               {'children': blocks}, token=token)

        print(f'  移行: {old_title}  [{old_status}→{new_status}] [{old_priority}→{new_priority}]')
        migrated += 1

    print(f'\n移行完了: {migrated} 件移行、{skipped} 件スキップ')


# ---- エントリーポイント ----

def parse_env_list(env_str):
    """
    "Windows,Mac" → ['Windows', 'Mac'] に変換する。
    空文字列は空リストを返す。
    """
    if not env_str:
        return []
    return [e.strip() for e in env_str.split(',') if e.strip()]


def main():
    parser = argparse.ArgumentParser(
        description='Notion 案件管理スクリプト',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__
    )

    # --- コマンド群 ---
    parser.add_argument('--create-db', metavar='PARENT_PAGE_ID',
                        help='新スキーマでDBを作成して .env に書き込む')
    idempotent_group = parser.add_mutually_exclusive_group()
    idempotent_group.add_argument('--force', action='store_true',
                        help='--create-db: 既存DBがあっても強制的に新規作成する')
    idempotent_group.add_argument('--reuse', action='store_true',
                        help='--create-db: 同名DBが見つかった場合にそのIDを .env に設定する（新規作成しない）')
    parser.add_argument('--list', action='store_true',
                        help='案件一覧をステータス順で表示')
    parser.add_argument('--add', metavar='タイトル',
                        help='案件を追加する')
    parser.add_argument('--update', metavar='部分タイトル',
                        help='案件のプロパティを更新する')
    parser.add_argument('--show', metavar='部分タイトル',
                        help='全プロパティ + 作業履歴を表示する')
    parser.add_argument('--add-block', metavar='部分タイトル',
                        help='ページ本文末尾に作業ブロックを追記する')
    parser.add_argument('--alerts', action='store_true',
                        help='優先度別の閾値を超えた案件を一覧表示する')
    parser.add_argument('--migrate', action='store_true',
                        help='旧DBの全件を新DBに移行する')

    # --- --add 用オプション ---
    parser.add_argument('--type', dest='item_type', default=TYPE_DEFAULT,
                        choices=TYPE_OPTIONS,
                        help=f'種別（デフォルト: {TYPE_DEFAULT}）')
    parser.add_argument('--priority', default=PRIORITY_DEFAULT,
                        choices=PRIORITY_OPTIONS,
                        help=f'優先度（デフォルト: {PRIORITY_DEFAULT}）')
    parser.add_argument('--status', choices=STATUS_OPTIONS,
                        metavar='ステータス',
                        help=f'ステータス（--add デフォルト: {STATUS_DEFAULT}）選択肢: {"|".join(STATUS_OPTIONS)}')
    parser.add_argument('--category', default=CATEGORY_DEFAULT,
                        choices=CATEGORY_OPTIONS,
                        metavar='カテゴリ',
                        help=f'カテゴリ（デフォルト: {CATEGORY_DEFAULT}）選択肢: {"|".join(CATEGORY_OPTIONS)}')
    parser.add_argument('--env', dest='env_str', default='',
                        help='対象環境（カンマ区切り: "Windows,Mac"）')
    parser.add_argument('--client', default='',
                        help='クライアント')
    parser.add_argument('--assignee', default=None,
                        help=f'担当（--add デフォルト: {ASSIGNEE_DEFAULT}、--update は未指定時スキップ）')
    parser.add_argument('--memo', default='',
                        help='メモ（補足情報）')
    parser.add_argument('--start-date', dest='start_date', default='',
                        help='開始日（YYYY-MM-DD、省略時は今日）')

    # --- --update 用追加オプション ---
    parser.add_argument('--blocker', default=None,
                        help='ブロッカーテキスト（--update と併用）')

    # --- --add-block 用オプション ---
    parser.add_argument('--text', metavar='テキスト',
                        help='追記するテキスト（--add-block と併用）')

    # --- --list 用フィルターオプション ---
    parser.add_argument('--filter-status', metavar='ステータス',
                        help='ステータスでフィルター')
    parser.add_argument('--filter-type', metavar='種別',
                        help='種別でフィルター')
    parser.add_argument('--filter-env', metavar='環境',
                        help='対象環境でフィルター（1つ指定）')
    parser.add_argument('--filter-client', metavar='クライアント',
                        help='クライアントでフィルター')
    parser.add_argument('--filter-priority', metavar='優先度',
                        help='優先度でフィルター')
    parser.add_argument('--since', metavar='期間',
                        help='最終編集日時が指定期間以降の案件のみ表示（例: 30d = 直近30日）')

    args = parser.parse_args()

    # コマンドが何も指定されていない場合はヘルプ表示
    commands = [args.create_db, args.list, args.add, args.update,
                args.show, args.add_block, args.alerts, args.migrate]
    if not any(commands):
        parser.print_help()
        sys.exit(0)

    # .env 読み込み
    env = load_env()
    token = env.get('NOTION_API_TOKEN', '')
    if not token:
        print('[ERROR] .env に NOTION_API_TOKEN が設定されていません。')
        sys.exit(1)

    # --create-db は DB ID 不要
    if args.create_db:
        cmd_create_db(args.create_db, token, env, force=args.force, reuse=args.reuse)
        return

    # それ以外は DB ID が必要
    db_id = env.get('NOTION_TASKS_DB_ID', '')
    if not db_id:
        print('[ERROR] .env に NOTION_TASKS_DB_ID が設定されていません。')
        print('  先に --create-db <PARENT_PAGE_ID> を実行してください。')
        sys.exit(1)

    if args.list:
        cmd_list(
            token, db_id,
            filter_status=args.filter_status,
            filter_type=args.filter_type,
            filter_env=args.filter_env,
            filter_client=args.filter_client,
            filter_priority=args.filter_priority,
            since=args.since,
        )

    elif args.add:
        env_list = parse_env_list(args.env_str)
        add_status = args.status if args.status else STATUS_DEFAULT
        add_assignee = args.assignee or ASSIGNEE_DEFAULT
        cmd_add(
            title=args.add,
            item_type=args.item_type,
            priority=args.priority,
            status=add_status,
            category=args.category,
            env_list=env_list,
            client=args.client,
            assignee=add_assignee,
            memo=args.memo,
            start_date=args.start_date,
            token=token,
            db_id=db_id,
        )

    elif args.update:
        # --update は少なくとも1つの更新オプションが必要
        if not any([args.status, args.priority, args.assignee, args.blocker is not None]):
            print('[ERROR] --update を使う場合は --status / --priority / --assignee / --blocker のいずれかを指定してください。')
            sys.exit(1)
        # バリデーション
        if args.status and args.status not in STATUS_OPTIONS:
            print(f'[ERROR] ステータスは次のいずれかを指定してください: {", ".join(STATUS_OPTIONS)}')
            sys.exit(1)
        if args.priority and args.priority not in PRIORITY_OPTIONS:
            print(f'[ERROR] 優先度は次のいずれかを指定してください: {", ".join(PRIORITY_OPTIONS)}')
            sys.exit(1)
        if args.assignee and args.assignee not in ASSIGNEE_OPTIONS:
            print(f'[ERROR] 担当は次のいずれかを指定してください: {", ".join(ASSIGNEE_OPTIONS)}')
            sys.exit(1)
        cmd_update(
            partial_title=args.update,
            new_status=args.status,
            new_priority=args.priority,
            new_assignee=args.assignee,  # None のまま渡す（未指定時はスキップされる）
            new_blocker=args.blocker,
            token=token,
            db_id=db_id,
        )

    elif args.show:
        cmd_show(args.show, token, db_id)

    elif args.add_block:
        if not args.text:
            print('[ERROR] --add-block を使う場合は --text も指定してください。')
            sys.exit(1)
        cmd_add_block(
            partial_title=args.add_block,
            text=args.text,
            env_label=args.env_str or None,
            assignee=args.assignee,
            token=token,
            db_id=db_id,
        )

    elif args.alerts:
        cmd_alerts(token, db_id)

    elif args.migrate:
        old_db_id = env.get('NOTION_TASKS_DB_ID_OLD', '')
        cmd_migrate(token, db_id, old_db_id)


if __name__ == '__main__':
    main()
