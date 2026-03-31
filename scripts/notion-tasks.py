#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Notion 残件タスク管理スクリプト

使い方:
  notion-tasks.py --create-db <parent_page_id>   # DBを新規作成して .env に ID を書き込む
  notion-tasks.py --list                          # タスク一覧をステータス順で表示
  notion-tasks.py --add <タイトル> [--priority 高|中|低] [--category カテゴリ名] [--memo メモ]
  notion-tasks.py --update <部分タイトル> --status <ステータス>
  notion-tasks.py --add-history <部分タイトル> --text <テキスト>   # 作業履歴に追記
  notion-tasks.py --update-memo <部分タイトル> --text <テキスト>   # メモを上書き更新
  notion-tasks.py --import-from-handoff           # session-handoff.md の残件をインポート
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

# ---- 定数 ----

ENV_PATH = os.path.expanduser('~/.claude/.env')
HANDOFF_PATH = os.path.expanduser('~/.claude/session-handoff.md')
SSL_CTX = ssl.create_default_context()

# Notion rich_text プロパティの文字数上限
NOTION_RICH_TEXT_LIMIT = 2000

# ステータスの表示順（ソート用）
STATUS_ORDER = ['未着手', '進行中', '保留', '完了']
STATUS_OPTIONS = ['未着手', '進行中', '完了', '保留']
PRIORITY_OPTIONS = ['高', '中', '低']
CATEGORY_OPTIONS = ['LP制作', '開発', 'GA4', 'Bot', 'その他']


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
        # コメント行は保持
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

def notion_request(method, path, data=None, token=None):
    """Notion API へリクエストを送って JSON を返す"""
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


# ---- プロパティ変換ヘルパー ----

def get_text(props, key):
    p = props.get(key, {})
    t = p.get('type', '')
    if t == 'title':
        return ''.join(i.get('plain_text', '') for i in p.get('title', []))
    if t == 'rich_text':
        return ''.join(i.get('plain_text', '') for i in p.get('rich_text', []))
    return ''


def get_select(props, key):
    s = props.get(key, {}).get('select')
    return s['name'] if s else ''


def get_date(props, key):
    d = props.get(key, {}).get('date')
    return d['start'] if d else ''


def page_to_task(page):
    p = page['properties']
    return {
        'id': page['id'],
        'タイトル': get_text(p, 'タイトル'),
        'ステータス': get_select(p, 'ステータス'),
        '優先度': get_select(p, '優先度'),
        'カテゴリ': get_select(p, 'カテゴリ'),
        'メモ': get_text(p, 'メモ'),
        '作業履歴': get_text(p, '作業履歴'),
        '作成日': get_date(p, '作成日'),
    }


def status_sort_key(task):
    """ステータスを定義順でソートするためのキー関数"""
    s = task['ステータス']
    try:
        return STATUS_ORDER.index(s)
    except ValueError:
        return len(STATUS_ORDER)


# ---- --create-db ----

def cmd_create_db(parent_page_id, token):
    """Notion に残件タスク DB を作成し、DB ID を .env に書き込む"""
    print(f'DB を作成中... (parent_page_id: {parent_page_id})')

    body = {
        'parent': {'page_id': parent_page_id},
        'title': [{'type': 'text', 'text': {'content': '残件タスク'}}],
        'properties': {
            # title プロパティ（必須・キー名をタイトルに合わせる）
            'タイトル': {'title': {}},
            'ステータス': {
                'select': {
                    'options': [
                        {'name': '未着手', 'color': 'gray'},
                        {'name': '進行中', 'color': 'blue'},
                        {'name': '完了', 'color': 'green'},
                        {'name': '保留', 'color': 'yellow'},
                    ]
                }
            },
            '優先度': {
                'select': {
                    'options': [
                        {'name': '高', 'color': 'red'},
                        {'name': '中', 'color': 'orange'},
                        {'name': '低', 'color': 'default'},
                    ]
                }
            },
            'カテゴリ': {
                'select': {
                    'options': [
                        {'name': 'LP制作', 'color': 'purple'},
                        {'name': '開発', 'color': 'blue'},
                        {'name': 'GA4', 'color': 'green'},
                        {'name': 'Bot', 'color': 'pink'},
                        {'name': 'その他', 'color': 'default'},
                    ]
                }
            },
            'メモ': {'rich_text': {}},
            '作成日': {'date': {}},
        },
    }

    result = notion_request('POST', '/databases', body, token=token)
    db_id = result.get('id', '')
    if not db_id:
        print('[ERROR] DB ID を取得できませんでした。レスポンスを確認してください。')
        print(json.dumps(result, ensure_ascii=False, indent=2))
        sys.exit(1)

    # .env に書き込む
    update_env_key('NOTION_TASKS_DB_ID', db_id)

    print(f'DB を作成しました。')
    print(f'  DB ID: {db_id}')
    print(f'  .env の NOTION_TASKS_DB_ID を更新しました。')


# ---- --list ----

def cmd_list(token, db_id):
    """DB 内の全タスクをステータス順で表示"""
    result = notion_request('POST', f'/databases/{db_id}/query', {}, token=token)
    pages = result.get('results', [])
    if not pages:
        print('タスクがありません。')
        return

    tasks = [page_to_task(p) for p in pages]
    tasks.sort(key=status_sort_key)

    for t in tasks:
        priority_str = f'優先度: {t["優先度"]}' if t['優先度'] else '優先度: -'
        category_str = f'  [{t["カテゴリ"]}]' if t['カテゴリ'] else ''
        memo_str = f'\n      メモ: {t["メモ"]}' if t['メモ'] else ''
        print(f'[{t["ステータス"]}] {t["タイトル"]}  ({priority_str}){category_str}{memo_str}')

    print(f'\n合計 {len(tasks)} 件')


# ---- --add ----

def cmd_add(title, priority, category, memo, token, db_id):
    """タスクを1件追加する"""
    today_iso = datetime.now(timezone(timedelta(hours=9))).date().isoformat()

    props = {
        'タイトル': {'title': [{'text': {'content': title}}]},
        'ステータス': {'select': {'name': '未着手'}},
        '優先度': {'select': {'name': priority}},
        'カテゴリ': {'select': {'name': category}},
        '作成日': {'date': {'start': today_iso}},
    }
    if memo:
        props['メモ'] = {'rich_text': [{'text': {'content': memo}}]}

    notion_request('POST', '/pages', {
        'parent': {'database_id': db_id},
        'properties': props,
    }, token=token)
    print(f'追加しました: {title}')


# ---- --update ----

def cmd_update(partial_title, new_status, token, db_id):
    """部分タイトルに一致するタスクのステータスを更新する"""
    result = notion_request('POST', f'/databases/{db_id}/query', {
        'filter': {'property': 'タイトル', 'title': {'contains': partial_title}}
    }, token=token)
    pages = result.get('results', [])

    if not pages:
        print(f'[ERROR] 「{partial_title}」に一致するタスクが見つかりません。')
        sys.exit(1)

    if len(pages) > 1:
        print(f'[ERROR] {len(pages)} 件一致しました。タイトルをより具体的に指定してください。')
        for p in pages:
            t = page_to_task(p)
            print(f'  - {t["タイトル"]} [{t["ステータス"]}]')
        sys.exit(1)

    page = pages[0]
    task = page_to_task(page)

    notion_request('PATCH', f'/pages/{page["id"]}', {
        'properties': {
            'ステータス': {'select': {'name': new_status}}
        }
    }, token=token)
    print(f'更新しました: {task["タイトル"]}  [{task["ステータス"]}] → [{new_status}]')


# ---- --add-history ----

def find_page_by_partial_title(partial_title, token, db_id):
    """
    部分タイトルで Notion DB を検索し、マッチしたページ一覧を返す。
    --update と同じ検索ロジック。
    """
    result = notion_request('POST', f'/databases/{db_id}/query', {
        'filter': {'property': 'タイトル', 'title': {'contains': partial_title}}
    }, token=token)
    return result.get('results', [])


def cmd_add_history(partial_title, text, token, db_id):
    """「作業履歴」プロパティに改行して追記する"""
    pages = find_page_by_partial_title(partial_title, token, db_id)

    if not pages:
        print(f'[ERROR] 該当タスクが見つかりません: {partial_title}')
        sys.exit(1)

    if len(pages) > 1:
        print(f'[ERROR] {len(pages)} 件一致しました。タイトルをより具体的に指定してください。')
        for p in pages:
            t = page_to_task(p)
            print(f'  - {t["タイトル"]} [{t["ステータス"]}]')
        sys.exit(1)

    page = pages[0]
    task = page_to_task(page)

    # 既存の作業履歴を取得して追記
    existing = task['作業履歴']
    if existing:
        new_history = existing + '\n' + text
    else:
        new_history = text

    # 2,000文字上限対策：超過した場合は古い行から削除してトリム
    if len(new_history) > NOTION_RICH_TEXT_LIMIT:
        print(f'[WARN] 作業履歴が {len(new_history)} 文字に達します（上限: {NOTION_RICH_TEXT_LIMIT}文字）。古い履歴を削除します。')
        lines = new_history.split('\n')
        while len('\n'.join(lines)) > NOTION_RICH_TEXT_LIMIT and len(lines) > 1:
            lines.pop(0)
        new_history = '\n'.join(lines)
        print(f'  {len(new_history)} 文字に調整しました。')

    notion_request('PATCH', f'/pages/{page["id"]}', {
        'properties': {
            '作業履歴': {'rich_text': [{'text': {'content': new_history}}]}
        }
    }, token=token)

    print(f'履歴を追記しました: {task["タイトル"]}')
    print(f'  作業履歴: {text}')


# ---- --update-memo ----

def cmd_update_memo(partial_title, text, token, db_id):
    """「メモ」プロパティを上書き更新する"""
    pages = find_page_by_partial_title(partial_title, token, db_id)

    if not pages:
        print(f'[ERROR] 該当タスクが見つかりません: {partial_title}')
        sys.exit(1)

    if len(pages) > 1:
        print(f'[ERROR] {len(pages)} 件一致しました。タイトルをより具体的に指定してください。')
        for p in pages:
            t = page_to_task(p)
            print(f'  - {t["タイトル"]} [{t["ステータス"]}]')
        sys.exit(1)

    page = pages[0]
    task = page_to_task(page)

    notion_request('PATCH', f'/pages/{page["id"]}', {
        'properties': {
            'メモ': {'rich_text': [{'text': {'content': text}}]}
        }
    }, token=token)

    print(f'メモを更新しました: {task["タイトル"]}')
    print(f'  メモ: {text}')


# ---- --import-from-handoff ----

def strip_markdown_bold(text):
    """**太字** マークダウンを除去する"""
    return re.sub(r'\*\*(.+?)\*\*', r'\1', text)


def parse_handoff_tasks():
    """
    session-handoff.md の「## 残件」セクションをパースして
    タスクリストを返す。

    戻り値: list of dict
      - title: str
      - memo: str（サブ箇条書きを連結）
    """
    if not os.path.exists(HANDOFF_PATH):
        print(f'[ERROR] session-handoff.md が見つかりません: {HANDOFF_PATH}')
        sys.exit(1)

    with open(HANDOFF_PATH, encoding='utf-8') as f:
        content = f.read()

    # ## 残件 セクションを切り出す
    # 次の ## が来るまで（または EOF まで）
    match = re.search(r'^## 残件\s*\n(.*?)(?=^##|\Z)', content, re.MULTILINE | re.DOTALL)
    if not match:
        print('[WARN] session-handoff.md に「## 残件」セクションが見つかりません。')
        return []

    section = match.group(1)
    lines = section.splitlines()

    tasks = []
    current_task = None

    for line in lines:
        # トップレベルの箇条書き（`- ` で始まる行、ただし先頭スペースなし）
        top_match = re.match(r'^- (.+)$', line)
        if top_match:
            # 前のタスクを確定
            if current_task:
                tasks.append(current_task)
            raw_title = top_match.group(1).strip()
            title = strip_markdown_bold(raw_title).strip()
            # インラインのサブ補足（最初の ` - ` 以降は後続行処理で拾う）
            current_task = {'title': title, 'memo': ''}
            continue

        # サブ箇条書き（先頭にスペース + `- `）
        sub_match = re.match(r'^[ \t]+- (.+)$', line)
        if sub_match and current_task:
            raw_sub = sub_match.group(1).strip()
            sub = strip_markdown_bold(raw_sub)
            if current_task['memo']:
                current_task['memo'] += ' / ' + sub
            else:
                current_task['memo'] = sub

    # 最後のタスクを確定
    if current_task:
        tasks.append(current_task)

    return tasks


def fetch_existing_titles(token, db_id):
    """DB 内の既存タスクタイトル一覧を返す（重複チェック用）"""
    result = notion_request('POST', f'/databases/{db_id}/query', {}, token=token)
    pages = result.get('results', [])
    return set(page_to_task(p)['タイトル'] for p in pages)


def cmd_import_from_handoff(token, db_id):
    """session-handoff.md の残件を Notion DB にインポートする"""
    tasks = parse_handoff_tasks()
    if not tasks:
        print('インポートするタスクがありません。')
        return

    print(f'session-handoff.md から {len(tasks)} 件のタスクをインポートします...')

    existing_titles = fetch_existing_titles(token, db_id)
    today_iso = datetime.now(timezone(timedelta(hours=9))).date().isoformat()

    added = 0
    skipped = 0

    for task in tasks:
        title = task['title']
        memo = task['memo']

        if title in existing_titles:
            print(f'  スキップ（既存）: {title}')
            skipped += 1
            continue

        props = {
            'タイトル': {'title': [{'text': {'content': title}}]},
            'ステータス': {'select': {'name': '未着手'}},
            '優先度': {'select': {'name': '中'}},
            'カテゴリ': {'select': {'name': 'その他'}},
            '作成日': {'date': {'start': today_iso}},
        }
        if memo:
            props['メモ'] = {'rich_text': [{'text': {'content': memo}}]}

        notion_request('POST', '/pages', {
            'parent': {'database_id': db_id},
            'properties': props,
        }, token=token)
        print(f'  追加: {title}')
        existing_titles.add(title)
        added += 1

    print(f'完了: {added} 件追加、{skipped} 件スキップ')


# ---- エントリーポイント ----

def main():
    sys.stdout.reconfigure(encoding='utf-8')

    parser = argparse.ArgumentParser(
        description='Notion 残件タスク管理スクリプト',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__
    )
    parser.add_argument('--create-db', metavar='PARENT_PAGE_ID',
                        help='Notion DB を新規作成して .env に ID を書き込む')
    parser.add_argument('--list', action='store_true',
                        help='タスク一覧をステータス順で表示')
    parser.add_argument('--add', metavar='タイトル',
                        help='タスクを追加する')
    parser.add_argument('--priority', default='中', choices=PRIORITY_OPTIONS,
                        help='優先度（デフォルト: 中）')
    parser.add_argument('--category', default='その他',
                        help='カテゴリ（デフォルト: その他）')
    parser.add_argument('--memo', default='',
                        help='メモ（補足情報）')
    parser.add_argument('--update', metavar='部分タイトル',
                        help='タイトルが部分一致するタスクのステータスを更新する')
    parser.add_argument('--status', metavar='ステータス',
                        help='更新後のステータス（--update と併用）')
    parser.add_argument('--add-history', metavar='部分タイトル',
                        help='「作業履歴」プロパティに改行して追記する')
    parser.add_argument('--update-memo', metavar='部分タイトル',
                        help='「メモ」プロパティを上書き更新する')
    parser.add_argument('--text', metavar='テキスト',
                        help='追記・更新するテキスト（--add-history / --update-memo と併用）')
    parser.add_argument('--import-from-handoff', action='store_true',
                        help='session-handoff.md の残件をインポートする')

    args = parser.parse_args()

    # コマンドが何も指定されていない場合はヘルプ表示
    if not any([args.create_db, args.list, args.add, args.update,
                args.add_history, args.update_memo, args.import_from_handoff]):
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
        cmd_create_db(args.create_db, token)
        return

    # それ以外は DB ID が必要
    db_id = env.get('NOTION_TASKS_DB_ID', '')
    if not db_id:
        print('[ERROR] .env に NOTION_TASKS_DB_ID が設定されていません。')
        print('  先に --create-db <parent_page_id> を実行して DB を作成してください。')
        sys.exit(1)

    if args.list:
        cmd_list(token, db_id)

    elif args.add:
        cmd_add(
            title=args.add,
            priority=args.priority,
            category=args.category,
            memo=args.memo,
            token=token,
            db_id=db_id,
        )

    elif args.update:
        if not args.status:
            print('[ERROR] --update を使う場合は --status も指定してください。')
            print(f'  使用可能なステータス: {", ".join(STATUS_OPTIONS)}')
            sys.exit(1)
        if args.status not in STATUS_OPTIONS:
            print(f'[ERROR] ステータスは {", ".join(STATUS_OPTIONS)} のいずれかを指定してください。')
            sys.exit(1)
        cmd_update(args.update, args.status, token, db_id)

    elif args.add_history:
        if not args.text:
            print('[ERROR] --add-history を使う場合は --text も指定してください。')
            sys.exit(1)
        cmd_add_history(args.add_history, args.text, token, db_id)

    elif args.update_memo:
        if not args.text:
            print('[ERROR] --update-memo を使う場合は --text も指定してください。')
            sys.exit(1)
        cmd_update_memo(args.update_memo, args.text, token, db_id)

    elif args.import_from_handoff:
        cmd_import_from_handoff(token, db_id)


if __name__ == '__main__':
    main()
