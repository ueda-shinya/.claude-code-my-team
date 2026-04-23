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

  notion-kaizen.py --migrate-add-columns
      既存DBに「なぜ(1回目)」「なぜ(2回目)」「なぜ(3回目)」「真の原因に対する対策」「対策実施日」プロパティを追加する
      ※一度だけ実行すればOK。既にプロパティが存在する場合はスキップ。
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

# スクリプトディレクトリを sys.path に追加して notion_schema をインポート
_SCRIPTS_DIR = os.path.dirname(os.path.abspath(__file__))
if _SCRIPTS_DIR not in sys.path:
    sys.path.insert(0, _SCRIPTS_DIR)
from notion_schema import KaizenDB, CrmDB

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


def get_date(props, key):
    """date プロパティの start を取得"""
    d = props.get(key, {}).get('date')
    return d['start'] if d else ''


def get_created_time(page):
    """Notion ページのトップレベル created_time を取得（システムプロパティ）"""
    return page.get('created_time', '')


def page_to_item(page):
    """Notion ページを報告書 dict に変換"""
    p = page['properties']
    return {
        'id': page['id'],
        KaizenDB.TITLE:               get_text(p, KaizenDB.TITLE),
        KaizenDB.LEVEL:               get_select(p, KaizenDB.LEVEL),
        KaizenDB.DATE:                get_date(p, KaizenDB.DATE),
        KaizenDB.IMPLEMENTATION_DATE: get_date(p, KaizenDB.IMPLEMENTATION_DATE),
        KaizenDB.AREA:                get_select(p, KaizenDB.AREA),
        KaizenDB.ROOT_CATEGORY:       get_select(p, KaizenDB.ROOT_CATEGORY),
        KaizenDB.ROOT_SUMMARY:        get_text(p, KaizenDB.ROOT_SUMMARY),
        KaizenDB.STATUS:              get_select(p, KaizenDB.STATUS),
        KaizenDB.RELATED_FILES:       get_text(p, KaizenDB.RELATED_FILES),
        '作成日時':                    get_created_time(page),
        # 追加プロパティ（なぜなぜ分析の過程）
        KaizenDB.WHY_1:               get_text(p, KaizenDB.WHY_1),
        KaizenDB.WHY_2:               get_text(p, KaizenDB.WHY_2),
        KaizenDB.WHY_3:               get_text(p, KaizenDB.WHY_3),
        KaizenDB.COUNTERMEASURE:      get_text(p, KaizenDB.COUNTERMEASURE),
    }


def status_sort_key(item):
    """ステータスを定義順でソートするためのキー関数"""
    s = item[KaizenDB.STATUS]
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
        'filter': {'property': KaizenDB.TITLE, 'title': {'contains': partial_title}}
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
            print(f'  - {t[KaizenDB.TITLE]} [{t[KaizenDB.STATUS]}]')
        sys.exit(1)

    return pages[0]


# ---- --create-db ----

def cmd_create_db(parent_page_id, token, force=False, reuse=False):
    """なぜなぜ分析DBを作成し、.env を更新する"""

    # ---- チェック1: .envにDB IDがある場合 ----
    env = load_env()
    existing_id = env.get('NOTION_KAIZEN_DB_ID', '')
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
        found_id = search_db_by_title('なぜなぜ分析', token)
        if found_id:
            print(f'[INFO] 同名のDBが見つかりました: なぜなぜ分析 ({found_id})')
            print('  このDBを使用する場合は --reuse を指定してください。')
            print('  新規作成する場合は --force を指定してください。')
            if reuse:
                update_env_key('NOTION_KAIZEN_DB_ID', found_id)
                print(f'  .env の NOTION_KAIZEN_DB_ID を更新しました: {found_id}')
            sys.exit(1)

    # --reuse のみ指定（--force なし）でDBが見つからなかった場合は通常作成
    print(f'なぜなぜ分析DBを作成中... (parent_page_id: {parent_page_id})')

    body = {
        'parent': {'page_id': parent_page_id},
        'title': [{'type': 'text', 'text': {'content': 'なぜなぜ分析'}}],
        'properties': {
            KaizenDB.TITLE: {'title': {}},
            KaizenDB.LEVEL: {
                'select': {
                    'options': [
                        {'name': '🔴HIGH', 'color': 'red'},
                        {'name': '🟡MEDIUM', 'color': 'yellow'},
                    ]
                }
            },
            KaizenDB.DATE: {'date': {}},
            KaizenDB.IMPLEMENTATION_DATE: {'date': {}},
            KaizenDB.AREA: {
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
            KaizenDB.ROOT_CATEGORY: {
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
            KaizenDB.ROOT_SUMMARY: {'rich_text': {}},
            KaizenDB.STATUS: {
                'select': {
                    'options': [
                        {'name': '未実施', 'color': 'gray'},
                        {'name': '対策実施済み', 'color': 'blue'},
                        {'name': '30日検証中', 'color': 'yellow'},
                        {'name': '完了', 'color': 'green'},
                    ]
                }
            },
            KaizenDB.RELATED_FILES: {'rich_text': {}},
            # なぜなぜ分析の過程を記録するプロパティ
            KaizenDB.WHY_1: {'rich_text': {}},
            KaizenDB.WHY_2: {'rich_text': {}},
            KaizenDB.WHY_3: {'rich_text': {}},
            KaizenDB.COUNTERMEASURE: {'rich_text': {}},
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
        level_str = item[KaizenDB.LEVEL] if item[KaizenDB.LEVEL] else '-'
        area_str = item[KaizenDB.AREA] if item[KaizenDB.AREA] else '-'
        date_str = item[KaizenDB.DATE] if item[KaizenDB.DATE] else '-'
        root_str = item[KaizenDB.ROOT_CATEGORY] if item[KaizenDB.ROOT_CATEGORY] else '-'
        summary_str = f'\n      真因: {item[KaizenDB.ROOT_SUMMARY]}' if item[KaizenDB.ROOT_SUMMARY] else ''
        # 「真の原因に対する対策」の有無を表示（詳細は --show で確認）
        countermeasure_str = '✓対策あり' if item[KaizenDB.COUNTERMEASURE] else '対策なし'
        print(
            f'[{item[KaizenDB.STATUS]}] {item[KaizenDB.TITLE]}'
            f'  {level_str} / {area_str} / {root_str}  ({date_str})  [{countermeasure_str}]'
            f'{summary_str}'
        )

    print(f'\n合計 {len(items)} 件')


# ---- --add ----

def cmd_add(title, level, area, root_category, root_summary, status, related, date_str,
            why1, why2, why3, countermeasure, token, db_id):
    """報告書を1件追加する"""
    if not date_str:
        date_str = datetime.now(JST).date().isoformat()

    props = {
        KaizenDB.TITLE: {'title': [{'text': {'content': title}}]},
        KaizenDB.LEVEL: {'select': {'name': level}},
        KaizenDB.DATE: {'date': {'start': date_str}},
        KaizenDB.STATUS: {'select': {'name': status}},
    }

    if area:
        props[KaizenDB.AREA] = {'select': {'name': area}}

    if root_category:
        props[KaizenDB.ROOT_CATEGORY] = {'select': {'name': root_category}}

    if root_summary:
        props[KaizenDB.ROOT_SUMMARY] = rich_text_prop(root_summary)

    if related:
        props[KaizenDB.RELATED_FILES] = rich_text_prop(related)

    # なぜなぜ分析の過程プロパティ（任意）
    if why1:
        props[KaizenDB.WHY_1] = rich_text_prop(why1)

    if why2:
        props[KaizenDB.WHY_2] = rich_text_prop(why2)

    if why3:
        props[KaizenDB.WHY_3] = rich_text_prop(why3)

    if countermeasure:
        props[KaizenDB.COUNTERMEASURE] = rich_text_prop(countermeasure)

    notion_request('POST', '/pages', {
        'parent': {'database_id': db_id},
        'properties': props,
    }, token=token)
    print(f'追加しました: {title}')
    print(f'  対応レベル: {level} / 領域: {area or "-"} / ステータス: {status}')
    print(f'  真因カテゴリ: {root_category or "-"} / 日付: {date_str}')
    if why1:
        print(f'  なぜ(1回目): {why1[:60]}{"..." if len(why1) > 60 else ""}')
    if why2:
        print(f'  なぜ(2回目): {why2[:60]}{"..." if len(why2) > 60 else ""}')
    if why3:
        print(f'  なぜ(3回目): {why3[:60]}{"..." if len(why3) > 60 else ""}')
    if countermeasure:
        print(f'  真の原因に対する対策: {countermeasure[:60]}{"..." if len(countermeasure) > 60 else ""}')


# ---- --update ----

def cmd_update(partial_title, new_status, new_level, new_area,
               why1, why2, why3, countermeasure, token, db_id):
    """部分タイトルに一致する報告書のプロパティを更新する"""
    page = resolve_single_page(partial_title, token, db_id)
    item = page_to_item(page)

    props = {}
    changes = []

    if new_status:
        props[KaizenDB.STATUS] = {'select': {'name': new_status}}
        changes.append(f'ステータス: [{item[KaizenDB.STATUS]}] → [{new_status}]')
        # 「対策実施済み」に変更する際は対策実施日を当日日付で自動設定する
        if new_status == '対策実施済み':
            today_str = datetime.now(JST).date().isoformat()
            props[KaizenDB.IMPLEMENTATION_DATE] = {'date': {'start': today_str}}
            changes.append(f'対策実施日: {today_str} (自動設定)')

    if new_level:
        props[KaizenDB.LEVEL] = {'select': {'name': new_level}}
        changes.append(f'対応レベル: [{item[KaizenDB.LEVEL]}] → [{new_level}]')

    if new_area:
        props[KaizenDB.AREA] = {'select': {'name': new_area}}
        changes.append(f'領域: [{item[KaizenDB.AREA]}] → [{new_area}]')

    # なぜなぜ分析プロパティ（空文字指定でクリアも可能）
    if why1 is not None:
        props[KaizenDB.WHY_1] = rich_text_prop(why1)
        changes.append(f'なぜ(1回目): [{item[KaizenDB.WHY_1]}] → [{why1}]')

    if why2 is not None:
        props[KaizenDB.WHY_2] = rich_text_prop(why2)
        changes.append(f'なぜ(2回目): [{item[KaizenDB.WHY_2]}] → [{why2}]')

    if why3 is not None:
        props[KaizenDB.WHY_3] = rich_text_prop(why3)
        changes.append(f'なぜ(3回目): [{item[KaizenDB.WHY_3]}] → [{why3}]')

    if countermeasure is not None:
        props[KaizenDB.COUNTERMEASURE] = rich_text_prop(countermeasure)
        changes.append(f'真の原因に対する対策: [{item[KaizenDB.COUNTERMEASURE]}] → [{countermeasure}]')

    if not props:
        print('[ERROR] 更新するプロパティを --status / --level / --area / --why1 / --why2 / --why3 / --countermeasure で指定してください。')
        sys.exit(1)

    notion_request('PATCH', f'/pages/{page["id"]}', {'properties': props}, token=token)
    print(f'更新しました: {item[KaizenDB.TITLE]}')
    for c in changes:
        print(f'  {c}')


# ---- --show ----

def cmd_show(partial_title, token, db_id):
    """全プロパティ + ページ本文を表示する"""
    page = resolve_single_page(partial_title, token, db_id)
    item = page_to_item(page)

    print(f'== {item[KaizenDB.TITLE]} ==')
    print(f'  対応レベル        : {item[KaizenDB.LEVEL] if item[KaizenDB.LEVEL] else "-"}')
    print(f'  日付              : {item[KaizenDB.DATE] if item[KaizenDB.DATE] else "-"}')
    print(f'  領域              : {item[KaizenDB.AREA] if item[KaizenDB.AREA] else "-"}')
    print(f'  真因カテゴリ      : {item[KaizenDB.ROOT_CATEGORY] if item[KaizenDB.ROOT_CATEGORY] else "-"}')
    print(f'  真因（要約）      : {item[KaizenDB.ROOT_SUMMARY] if item[KaizenDB.ROOT_SUMMARY] else "-"}')
    print(f'  ステータス        : {item[KaizenDB.STATUS] if item[KaizenDB.STATUS] else "-"}')
    print(f'  関連ファイル      : {item[KaizenDB.RELATED_FILES] if item[KaizenDB.RELATED_FILES] else "-"}')
    print(f'  作成日時          : {item["作成日時"]}')
    # なぜなぜ分析の過程
    print(f'  なぜ(1回目)       : {item[KaizenDB.WHY_1] if item[KaizenDB.WHY_1] else "-"}')
    print(f'  なぜ(2回目)       : {item[KaizenDB.WHY_2] if item[KaizenDB.WHY_2] else "-"}')
    print(f'  なぜ(3回目)       : {item[KaizenDB.WHY_3] if item[KaizenDB.WHY_3] else "-"}')
    print(f'  真の原因に対する対策: {item[KaizenDB.COUNTERMEASURE] if item[KaizenDB.COUNTERMEASURE] else "-"}')

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
    print(f'ブロックを追記しました: {item[KaizenDB.TITLE]}')
    print(f'  [{today}] {text[:80]}{"..." if len(text) > 80 else ""}')


# ---- --migrate-add-columns ----

def cmd_migrate_add_columns(token, db_id):
    """
    既存DBに分析過程プロパティ4つ + 対策実施日（date型）を追加する。
    既にプロパティが存在する場合はスキップ。

    追加対象プロパティ（タプル: (プロパティ名, Notion型)）:
      - なぜ(1回目)           : rich_text
      - なぜ(2回目)           : rich_text
      - なぜ(3回目)           : rich_text
      - 真の原因に対する対策   : rich_text
      - 対策実施日             : date
    """
    # 現在のDBスキーマを取得して既存プロパティを確認
    db_info = notion_request('GET', f'/databases/{db_id}', token=token)
    existing_props = set(db_info.get('properties', {}).keys())

    # 追加対象プロパティ（プロパティ名, Notion型）のタプルリスト
    new_props_with_type = [
        (KaizenDB.WHY_1,               'rich_text'),
        (KaizenDB.WHY_2,               'rich_text'),
        (KaizenDB.WHY_3,               'rich_text'),
        (KaizenDB.COUNTERMEASURE,      'rich_text'),
        (KaizenDB.IMPLEMENTATION_DATE, 'date'),
    ]

    # 追加が必要なプロパティだけ抽出（既存は除外）
    to_add = [(p, t) for p, t in new_props_with_type if p not in existing_props]

    if not to_add:
        print('[INFO] 対象プロパティはすべて既に存在します。マイグレーション不要です。')
        for p, _ in new_props_with_type:
            print(f'  ✓ {p}')
        return

    # 型情報から動的に PATCH body を組み立てる
    props_body = {p: {t: {}} for p, t in to_add}
    notion_request('PATCH', f'/databases/{db_id}', {'properties': props_body}, token=token)

    print(f'プロパティを追加しました ({len(to_add)}件):')
    for p, t in to_add:
        print(f'  + {p} ({t})')

    # スキップしたプロパティを表示
    skipped = [(p, t) for p, t in new_props_with_type if p in existing_props]
    if skipped:
        print('スキップ（既存）:')
        for p, t in skipped:
            print(f'  = {p} ({t})')


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
        status = item[KaizenDB.STATUS]

        # 対策実施済み → 「対策実施日」から30日経過でアラート
        # 「対策実施日」が未設定の場合は「日付」にフォールバック
        if status == '対策実施済み':
            date_str = item[KaizenDB.IMPLEMENTATION_DATE] or item[KaizenDB.DATE]
            if not date_str:
                continue
            try:
                impl_date = datetime.fromisoformat(date_str).date()
            except ValueError:
                continue
            elapsed_days = (today - impl_date).days
            if elapsed_days >= 30:
                verification_due.append((elapsed_days, item[KaizenDB.TITLE], item[KaizenDB.LEVEL], date_str))

        # 30日検証中 → 一覧表示
        elif status == '30日検証中':
            in_verification.append((item[KaizenDB.TITLE], item[KaizenDB.LEVEL], item[KaizenDB.DATE]))

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
    idempotent_group = parser.add_mutually_exclusive_group()
    idempotent_group.add_argument('--force', action='store_true',
                        help='--create-db: 既存DBがあっても強制的に新規作成する')
    idempotent_group.add_argument('--reuse', action='store_true',
                        help='--create-db: 同名DBが見つかった場合にそのIDを .env に設定する（新規作成しない）')
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
    parser.add_argument('--migrate-add-columns', action='store_true',
                        help='既存DBに分析過程プロパティ4つ + 対策実施日（date型）を追加する（一度だけ実行）')

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

    # --- なぜなぜ分析過程オプション（--add と併用） ---
    parser.add_argument('--why1', default='',
                        metavar='なぜ(1回目)',
                        help='なぜ(1回目) — 1段目のなぜ')
    parser.add_argument('--why2', default='',
                        metavar='なぜ(2回目)',
                        help='なぜ(2回目) — 2段目のなぜ')
    parser.add_argument('--why3', default='',
                        metavar='なぜ(3回目)',
                        help='なぜ(3回目) — 3段目のなぜ')
    parser.add_argument('--countermeasure', default='',
                        metavar='真の原因に対する対策',
                        help='真の原因に対する対策の内容')

    # --- --add-block 用オプション ---
    parser.add_argument('--text', metavar='テキスト',
                        help='追記するテキスト（--add-block と併用）')

    args = parser.parse_args()

    # コマンドが何も指定されていない場合はヘルプ表示
    commands = [args.create_db, args.list, args.add, args.update,
                args.show, args.add_block, args.alerts, args.migrate_add_columns]
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
        cmd_create_db(parent_page_id, token, force=args.force, reuse=args.reuse)
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
            why1=args.why1,
            why2=args.why2,
            why3=args.why3,
            countermeasure=args.countermeasure,
            token=token,
            db_id=db_id,
        )

    elif args.update:
        # why1/why2/why3/countermeasure は default='' なので、未指定時は '' が入る
        # Noneと区別するため、指定された場合のみ更新する（''指定でクリア可能）
        # argparse の default='' の場合、未指定時も '' になるため、
        # 指定有無を判別できるよう None をデフォルトとして扱う
        why1_val = args.why1 if args.why1 != '' else None
        why2_val = args.why2 if args.why2 != '' else None
        why3_val = args.why3 if args.why3 != '' else None
        countermeasure_val = args.countermeasure if args.countermeasure != '' else None

        # 空文字明示指定（クリア目的）を許容するため、引数が実際に渡されたか確認
        # argparse では default='' と明示指定'' を区別できないため、
        # 呼び出し時に値がある場合は None 以外として扱う。
        # ただし現行の仕様上、空文字指定 = クリアとして機能させる。
        if not any([args.status, args.level, args.area,
                    why1_val is not None, why2_val is not None,
                    why3_val is not None, countermeasure_val is not None]):
            print('[ERROR] --update を使う場合は --status / --level / --area / --why1 / --why2 / --why3 / --countermeasure のいずれかを指定してください。')
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
            why1=why1_val,
            why2=why2_val,
            why3=why3_val,
            countermeasure=countermeasure_val,
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

    elif args.migrate_add_columns:
        cmd_migrate_add_columns(token, db_id)


if __name__ == '__main__':
    main()
