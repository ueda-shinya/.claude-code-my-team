#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Notion なぜなぜ分析 報告書管理スクリプト

使い方:
  notion-kaizen.py --create-db
      なぜなぜ分析DBを作成して .env に NOTION_KAIZEN_DB_ID を書き込む

  notion-kaizen.py --list
      報告書一覧をステータス順で表示

  notion-kaizen.py --add タイトル
      [--level HIGH|MEDIUM] [--area 領域] [--root-category 真因カテゴリ]
      [--root-summary 真因要約] [--status ステータス] [--related 関連ファイル]
      [--date YYYY-MM-DD]
      報告書を1件追加する

  notion-kaizen.py --update 部分タイトル
      [--status S] [--level L] [--area A]
      報告書のプロパティを更新する

  notion-kaizen.py --show 部分タイトル
      全プロパティ + ページ本文を表示

  notion-kaizen.py --add-block 部分タイトル --text テキスト
      ページ本文末尾にブロックを追記する（/kaizenの出力を貼る用）

  notion-kaizen.py --alerts
      30日検証期限のチェック（対策実施済み→30日超、30日検証中 を表示）
"""

import json
import os
import ssl
import sys
import argparse
import tempfile
import urllib.request
import urllib.error
from datetime import datetime, timezone, timedelta

# Windows環境での文字化け対策
sys.stdout.reconfigure(encoding='utf-8')

# ---- 定数 ----

ENV_PATH = os.path.expanduser('~/.claude/.env')
SSL_CTX = ssl.create_default_context()

# Notion rich_text プロパティの文字数上限
NOTION_RICH_TEXT_LIMIT = 2000

# 日本時間タイムゾーン
JST = timezone(timedelta(hours=9))

# 対応レベル
LEVEL_OPTIONS = ['🔴HIGH', '🟡MEDIUM']
LEVEL_DEFAULT = '🟡MEDIUM'

# 領域
AREA_OPTIONS = ['コーディング', '運用', 'コミュニケーション', '判断', '設計', 'セキュリティ']
AREA_DEFAULT = 'コーディング'

# 真因カテゴリ
ROOT_CATEGORY_OPTIONS = [
    'Design Gap',
    'Process Gap',
    'Knowledge Gap',
    'Communication Gap',
    'Tooling Gap',
    'Policy Gap',
    'Architecture Gap',
    'Scaling Assumption',
]
ROOT_CATEGORY_DEFAULT = 'Process Gap'

# ステータス
STATUS_OPTIONS = ['未実施', '対策実施済み', '30日検証中', '完了']
STATUS_DEFAULT = '未実施'
STATUS_ORDER = ['未実施', '対策実施済み', '30日検証中', '完了']


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


def get_created_time(props, key='作成日時'):
    """created_time プロパティの値を取得"""
    return props.get(key, {}).get('created_time', '')


def page_to_item(page):
    """Notion ページを報告書 dict に変換"""
    p = page['properties']
    return {
        'id': page['id'],
        'タイトル': get_text(p, 'タイトル'),
        '対応レベル': get_select(p, '対応レベル'),
        '日付': get_date(p, '日付'),
        '対策実施日': get_date(p, '対策実施日'),
        '領域': get_select(p, '領域'),
        '真因カテゴリ': get_select(p, '真因カテゴリ'),
        '真因（要約）': get_text(p, '真因（要約）'),
        'ステータス': get_select(p, 'ステータス'),
        '関連ファイル': get_text(p, '関連ファイル'),
        '作成日時': get_created_time(p),
    }


def status_sort_key(item):
    """ステータスを定義順でソートするためのキー関数"""
    s = item['ステータス']
    try:
        return STATUS_ORDER.index(s)
    except ValueError:
        return len(STATUS_ORDER)


def rich_text_prop(text):
    """rich_text プロパティ用の値を生成"""
    content = text[:NOTION_RICH_TEXT_LIMIT] if text else ''
    return {'rich_text': [{'text': {'content': content}}]}


def find_page_by_partial_title(partial_title, token, db_id):
    """部分タイトルで Notion DB を検索し、マッチしたページ一覧を返す"""
    result = notion_request('POST', f'/databases/{db_id}/query', {
        'filter': {'property': 'タイトル', 'title': {'contains': partial_title}}
    }, token=token)
    return result.get('results', [])


def resolve_single_page(partial_title, token, db_id):
    """
    部分タイトルで1件に絞り込む。
    0件・複数件はエラーで終了。
    """
    pages = find_page_by_partial_title(partial_title, token, db_id)

    if not pages:
        print(f'[ERROR] 「{partial_title}」に一致する報告書が見つかりません。')
        sys.exit(1)

    if len(pages) > 1:
        print(f'[ERROR] {len(pages)} 件一致しました。タイトルをより具体的に指定してください。')
        for p in pages:
            t = page_to_item(p)
            print(f'  - {t["タイトル"]} [{t["ステータス"]}]')
        sys.exit(1)

    return pages[0]


# ---- --create-db ----

def cmd_create_db(parent_page_id, token):
    """なぜなぜ分析DBを作成し、.env を更新する"""
    print(f'なぜなぜ分析DBを作成中... (parent_page_id: {parent_page_id})')

    body = {
        'parent': {'page_id': parent_page_id},
        'title': [{'type': 'text', 'text': {'content': 'なぜなぜ分析'}}],
        'properties': {
            'タイトル': {'title': {}},
            '対応レベル': {
                'select': {
                    'options': [
                        {'name': '🔴HIGH', 'color': 'red'},
                        {'name': '🟡MEDIUM', 'color': 'yellow'},
                    ]
                }
            },
            '日付': {'date': {}},
            '対策実施日': {'date': {}},
            '領域': {
                'select': {
                    'options': [
                        {'name': 'コーディング', 'color': 'blue'},
                        {'name': '運用', 'color': 'green'},
                        {'name': 'コミュニケーション', 'color': 'yellow'},
                        {'name': '判断', 'color': 'orange'},
                        {'name': '設計', 'color': 'purple'},
                        {'name': 'セキュリティ', 'color': 'red'},
                    ]
                }
            },
            '真因カテゴリ': {
                'select': {
                    'options': [
                        {'name': 'Design Gap', 'color': 'purple'},
                        {'name': 'Process Gap', 'color': 'blue'},
                        {'name': 'Knowledge Gap', 'color': 'green'},
                        {'name': 'Communication Gap', 'color': 'yellow'},
                        {'name': 'Tooling Gap', 'color': 'orange'},
                        {'name': 'Policy Gap', 'color': 'red'},
                        {'name': 'Architecture Gap', 'color': 'pink'},
                        {'name': 'Scaling Assumption', 'color': 'gray'},
                    ]
                }
            },
            '真因（要約）': {'rich_text': {}},
            'ステータス': {
                'select': {
                    'options': [
                        {'name': '未実施', 'color': 'gray'},
                        {'name': '対策実施済み', 'color': 'blue'},
                        {'name': '30日検証中', 'color': 'yellow'},
                        {'name': '完了', 'color': 'green'},
                    ]
                }
            },
            '関連ファイル': {'rich_text': {}},
        },
    }

    result = notion_request('POST', '/databases', body, token=token)
    db_id = result.get('id', '')
    if not db_id:
        print('[ERROR] DB ID を取得できませんでした。レスポンスを確認してください。')
        print(json.dumps(result, ensure_ascii=False, indent=2))
        sys.exit(1)

    # .env に書き込む
    update_env_key('NOTION_KAIZEN_DB_ID', db_id)

    print(f'なぜなぜ分析DBを作成しました。')
    print(f'  DB ID: {db_id}')
    print(f'  .env の NOTION_KAIZEN_DB_ID を更新しました。')


# ---- --list ----

def cmd_list(token, db_id):
    """報告書一覧をステータス順で表示"""
    pages = notion_query_all(db_id, token)

    if not pages:
        print('報告書がありません。')
        return

    items = [page_to_item(p) for p in pages]
    items.sort(key=status_sort_key)

    for item in items:
        level_str = item['対応レベル'] if item['対応レベル'] else '-'
        area_str = item['領域'] if item['領域'] else '-'
        date_str = item['日付'] if item['日付'] else '-'
        root_str = item['真因カテゴリ'] if item['真因カテゴリ'] else '-'
        summary_str = f'\n      真因: {item["真因（要約）"]}' if item['真因（要約）'] else ''
        print(
            f'[{item["ステータス"]}] {item["タイトル"]}'
            f'  {level_str} / {area_str} / {root_str}  ({date_str})'
            f'{summary_str}'
        )

    print(f'\n合計 {len(items)} 件')


# ---- --add ----

def cmd_add(title, level, area, root_category, root_summary, status, related, date_str, token, db_id):
    """報告書を1件追加する"""
    if not date_str:
        date_str = datetime.now(JST).date().isoformat()

    props = {
        'タイトル': {'title': [{'text': {'content': title}}]},
        '対応レベル': {'select': {'name': level}},
        '日付': {'date': {'start': date_str}},
        'ステータス': {'select': {'name': status}},
    }

    if area:
        props['領域'] = {'select': {'name': area}}

    if root_category:
        props['真因カテゴリ'] = {'select': {'name': root_category}}

    if root_summary:
        props['真因（要約）'] = rich_text_prop(root_summary)

    if related:
        props['関連ファイル'] = rich_text_prop(related)

    notion_request('POST', '/pages', {
        'parent': {'database_id': db_id},
        'properties': props,
    }, token=token)
    print(f'追加しました: {title}')
    print(f'  対応レベル: {level} / 領域: {area or "-"} / ステータス: {status}')
    print(f'  真因カテゴリ: {root_category or "-"} / 日付: {date_str}')


# ---- --update ----

def cmd_update(partial_title, new_status, new_level, new_area, token, db_id):
    """部分タイトルに一致する報告書のプロパティを更新する"""
    page = resolve_single_page(partial_title, token, db_id)
    item = page_to_item(page)

    props = {}
    changes = []

    if new_status:
        props['ステータス'] = {'select': {'name': new_status}}
        changes.append(f'ステータス: [{item["ステータス"]}] → [{new_status}]')
        # 「対策実施済み」に変更する際は対策実施日を当日日付で自動設定する
        if new_status == '対策実施済み':
            today_str = datetime.now(JST).date().isoformat()
            props['対策実施日'] = {'date': {'start': today_str}}
            changes.append(f'対策実施日: {today_str} (自動設定)')

    if new_level:
        props['対応レベル'] = {'select': {'name': new_level}}
        changes.append(f'対応レベル: [{item["対応レベル"]}] → [{new_level}]')

    if new_area:
        props['領域'] = {'select': {'name': new_area}}
        changes.append(f'領域: [{item["領域"]}] → [{new_area}]')

    if not props:
        print('[ERROR] 更新するプロパティを --status / --level / --area で指定してください。')
        sys.exit(1)

    notion_request('PATCH', f'/pages/{page["id"]}', {'properties': props}, token=token)
    print(f'更新しました: {item["タイトル"]}')
    for c in changes:
        print(f'  {c}')


# ---- --show ----

def cmd_show(partial_title, token, db_id):
    """全プロパティ + ページ本文を表示する"""
    page = resolve_single_page(partial_title, token, db_id)
    item = page_to_item(page)

    print(f'== {item["タイトル"]} ==')
    print(f'  対応レベル    : {item["対応レベル"] if item["対応レベル"] else "-"}')
    print(f'  日付          : {item["日付"] if item["日付"] else "-"}')
    print(f'  領域          : {item["領域"] if item["領域"] else "-"}')
    print(f'  真因カテゴリ  : {item["真因カテゴリ"] if item["真因カテゴリ"] else "-"}')
    print(f'  真因（要約）  : {item["真因（要約）"] if item["真因（要約）"] else "-"}')
    print(f'  ステータス    : {item["ステータス"] if item["ステータス"] else "-"}')
    print(f'  関連ファイル  : {item["関連ファイル"] if item["関連ファイル"] else "-"}')
    print(f'  作成日時      : {item["作成日時"]}')

    # ページ本文を取得（ページネーション対応）
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
        print(f'\n[本文]')
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
        print('\n[本文] なし')


# ---- --add-block ----

def cmd_add_block(partial_title, text, token, db_id):
    """ページ本文末尾にブロックを追記する（/kaizenの出力を貼る用）"""
    page = resolve_single_page(partial_title, token, db_id)
    item = page_to_item(page)

    today = datetime.now(JST).strftime('%Y-%m-%d')
    heading_content = f'[{today}] 追記'

    # テキストを2000文字ずつ分割してparagraphブロックのリストを生成する
    chunks = []
    remaining = text
    while remaining:
        chunks.append(remaining[:NOTION_RICH_TEXT_LIMIT])
        remaining = remaining[NOTION_RICH_TEXT_LIMIT:]

    if len(chunks) > 1:
        print(f'[INFO] テキストが長いため {len(chunks)} ブロックに分割しました')

    # ブロック構造:
    #   heading_3: "### [YYYY-MM-DD] 追記"
    #   paragraph: テキスト本文（2000文字超の場合は複数ブロック）
    blocks = [
        {
            'object': 'block',
            'type': 'heading_3',
            'heading_3': {
                'rich_text': [
                    {'type': 'text', 'text': {'content': heading_content[:NOTION_RICH_TEXT_LIMIT]}}
                ]
            }
        }
    ]
    for chunk in chunks:
        blocks.append({
            'object': 'block',
            'type': 'paragraph',
            'paragraph': {
                'rich_text': [
                    {'type': 'text', 'text': {'content': chunk}}
                ]
            }
        })

    notion_request('PATCH', f'/blocks/{page["id"]}/children', {'children': blocks}, token=token)
    print(f'ブロックを追記しました: {item["タイトル"]}')
    print(f'  [{today}] {text[:80]}{"..." if len(text) > 80 else ""}')


# ---- --alerts ----

def cmd_alerts(token, db_id):
    """30日検証期限のチェック"""
    pages = notion_query_all(db_id, token)
    now_jst = datetime.now(JST)
    today = now_jst.date()

    verification_due = []  # 対策実施済みで30日超
    in_verification = []   # 30日検証中

    for page in pages:
        item = page_to_item(page)
        status = item['ステータス']

        # 対策実施済み → 「対策実施日」から30日経過でアラート
        # 「対策実施日」が未設定の場合は「日付」にフォールバック
        if status == '対策実施済み':
            date_str = item['対策実施日'] or item['日付']
            if not date_str:
                continue
            try:
                impl_date = datetime.fromisoformat(date_str).date()
            except ValueError:
                continue
            elapsed_days = (today - impl_date).days
            if elapsed_days >= 30:
                verification_due.append((elapsed_days, item['タイトル'], item['対応レベル'], date_str))

        # 30日検証中 → 一覧表示
        elif status == '30日検証中':
            in_verification.append((item['タイトル'], item['対応レベル'], item['日付']))

    has_alert = bool(verification_due or in_verification)

    if not has_alert:
        print('アラート対象の報告書はありません。')
        return

    if verification_due:
        # 経過日数の降順でソート
        verification_due.sort(key=lambda x: -x[0])
        print(f'=== 30日検証期限到来: {len(verification_due)} 件 ===')
        for elapsed_days, title, level, date_str in verification_due:
            level_str = level if level else '-'
            print(f'  [{level_str}] {title}  日付:{date_str}  経過:{elapsed_days}日')
        print()

    if in_verification:
        print(f'=== 30日検証中: {len(in_verification)} 件 ===')
        for title, level, date_str in in_verification:
            level_str = level if level else '-'
            date_disp = date_str if date_str else '-'
            print(f'  [{level_str}] {title}  日付:{date_disp}')


# ---- エントリーポイント ----

def main():
    parser = argparse.ArgumentParser(
        description='Notion なぜなぜ分析 報告書管理スクリプト',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__
    )

    # --- コマンド群 ---
    parser.add_argument('--create-db', action='store_true',
                        help='なぜなぜ分析DBを作成して .env に書き込む')
    parser.add_argument('--list', action='store_true',
                        help='報告書一覧をステータス順で表示')
    parser.add_argument('--add', metavar='タイトル',
                        help='報告書を追加する')
    parser.add_argument('--update', metavar='部分タイトル',
                        help='報告書のプロパティを更新する')
    parser.add_argument('--show', metavar='部分タイトル',
                        help='全プロパティ + 本文を表示する')
    parser.add_argument('--add-block', metavar='部分タイトル',
                        help='ページ本文末尾にブロックを追記する')
    parser.add_argument('--alerts', action='store_true',
                        help='30日検証期限のチェック')

    # --- --add 用オプション ---
    parser.add_argument('--level', default=None,
                        choices=['HIGH', 'MEDIUM', '🔴HIGH', '🟡MEDIUM'],
                        metavar='HIGH|MEDIUM',
                        help=f'対応レベル（--add デフォルト: MEDIUM）')
    parser.add_argument('--area', default=None,
                        choices=AREA_OPTIONS,
                        metavar='領域',
                        help=f'領域（--add デフォルト: {AREA_DEFAULT}）選択肢: {"|".join(AREA_OPTIONS)}')
    parser.add_argument('--root-category', dest='root_category', default='',
                        metavar='真因カテゴリ',
                        help=f'真因カテゴリ 選択肢: {"|".join(ROOT_CATEGORY_OPTIONS)}')
    parser.add_argument('--root-summary', dest='root_summary', default='',
                        metavar='真因要約',
                        help='真因（要約）テキスト')
    parser.add_argument('--status', choices=STATUS_OPTIONS,
                        metavar='ステータス',
                        help=f'ステータス（--add デフォルト: {STATUS_DEFAULT}）選択肢: {"|".join(STATUS_OPTIONS)}')
    parser.add_argument('--related', default='',
                        metavar='関連ファイル',
                        help='関連ファイルパスや備考')
    parser.add_argument('--date', dest='date_str', default='',
                        metavar='YYYY-MM-DD',
                        help='日付（省略時は今日）')

    # --- --add-block 用オプション ---
    parser.add_argument('--text', metavar='テキスト',
                        help='追記するテキスト（--add-block と併用）')

    args = parser.parse_args()

    # コマンドが何も指定されていない場合はヘルプ表示
    commands = [args.create_db, args.list, args.add, args.update,
                args.show, args.add_block, args.alerts]
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
        parent_page_id = env.get('NOTION_ASUKA_PAGE_ID', '')
        if not parent_page_id:
            print('[ERROR] .env に NOTION_ASUKA_PAGE_ID が設定されていません。')
            sys.exit(1)
        cmd_create_db(parent_page_id, token)
        return

    # それ以外は DB ID が必要
    db_id = env.get('NOTION_KAIZEN_DB_ID', '')
    if not db_id:
        print('[ERROR] .env に NOTION_KAIZEN_DB_ID が設定されていません。')
        print('  先に --create-db を実行してください。')
        sys.exit(1)

    if args.list:
        cmd_list(token, db_id)

    elif args.add:
        # --level のエイリアス対応（絵文字なしでも受け付ける）
        level_map = {'HIGH': '🔴HIGH', 'MEDIUM': '🟡MEDIUM'}
        raw_level = args.level if args.level else LEVEL_DEFAULT
        level = level_map.get(raw_level, raw_level)

        # --root-category のバリデーション
        if args.root_category and args.root_category not in ROOT_CATEGORY_OPTIONS:
            print(f'[ERROR] --root-category の値が不正です: {args.root_category}')
            print(f'  選択肢: {", ".join(ROOT_CATEGORY_OPTIONS)}')
            sys.exit(1)

        add_status = args.status if args.status else STATUS_DEFAULT
        add_area = args.area if args.area else AREA_DEFAULT
        cmd_add(
            title=args.add,
            level=level,
            area=add_area,
            root_category=args.root_category,
            root_summary=args.root_summary,
            status=add_status,
            related=args.related,
            date_str=args.date_str,
            token=token,
            db_id=db_id,
        )

    elif args.update:
        if not any([args.status, args.level, args.area]):
            print('[ERROR] --update を使う場合は --status / --level / --area のいずれかを指定してください。')
            sys.exit(1)

        # ステータスのバリデーション
        if args.status and args.status not in STATUS_OPTIONS:
            print(f'[ERROR] --status の値が不正です: {args.status}')
            print(f'  選択肢: {", ".join(STATUS_OPTIONS)}')
            sys.exit(1)

        # --level のエイリアス対応（未指定時はNoneのまま更新しない）
        level_map = {'HIGH': '🔴HIGH', 'MEDIUM': '🟡MEDIUM'}
        new_level = level_map.get(args.level, args.level) if args.level else None

        cmd_update(
            partial_title=args.update,
            new_status=args.status,
            new_level=new_level,
            new_area=args.area,
            token=token,
            db_id=db_id,
        )

    elif args.show:
        cmd_show(args.show, token, db_id)

    elif args.add_block:
        if not args.text:
            print('[ERROR] --add-block を使う場合は --text でテキストを指定してください。')
            sys.exit(1)
        cmd_add_block(args.add_block, args.text, token, db_id)

    elif args.alerts:
        cmd_alerts(token, db_id)


if __name__ == '__main__':
    main()
