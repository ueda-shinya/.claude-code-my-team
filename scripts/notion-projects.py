#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Notion プロジェクト管理スクリプト

使い方:
  notion-projects.py --create-db
      プロジェクト管理DBを作成して .env に NOTION_PROJECTS_DB_ID を書き込む

  notion-projects.py --list [--filter-status ステータス]
      プロジェクト一覧を表示

  notion-projects.py --add "プロジェクト名"
      [--status ステータス] [--phase フェーズ] [--assignee 担当]
      [--memo メモ] [--kpi KPI] [--start-date YYYY-MM-DD]
      [--goal-date YYYY-MM-DD]
      プロジェクトを1件追加する

  notion-projects.py --update "部分名称"
      [--status ステータス] [--phase フェーズ] [--assignee 担当]
      [--memo メモ] [--kpi KPI]
      プロジェクトのプロパティを更新する

  notion-projects.py --show "部分名称"
      全プロパティ + ページ本文（作業履歴）を表示

  notion-projects.py --add-block "部分名称" --text テキスト
      ページ本文末尾に作業ブロックを追記する
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

# ステータス
STATUS_OPTIONS = ['準備中', '運用中', '完了', '保留']
STATUS_DEFAULT = '準備中'
STATUS_ORDER = ['運用中', '準備中', '保留', '完了']


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


def rich_text_prop(text):
  """rich_text プロパティ用の値を生成"""
  # Notion は1ブロックあたり2000文字上限
  content = text[:NOTION_RICH_TEXT_LIMIT] if text else ''
  return {'rich_text': [{'text': {'content': content}}]}


def page_to_item(page):
  """Notion ページをプロジェクト dict に変換"""
  p = page['properties']
  return {
    'id': page['id'],
    'プロジェクト名': get_text(p, 'プロジェクト名'),
    'ステータス': get_select(p, 'ステータス'),
    'フェーズ': get_text(p, 'フェーズ'),
    '開始日': get_date(p, '開始日'),
    '目標完了日': get_date(p, '目標完了日'),
    'KPI': get_text(p, 'KPI'),
    '担当': get_text(p, '担当'),
    'メモ': get_text(p, 'メモ'),
  }


def status_sort_key(item):
  """ステータスを定義順でソートするためのキー関数"""
  s = item['ステータス']
  try:
    return STATUS_ORDER.index(s)
  except ValueError:
    return len(STATUS_ORDER)


def find_page_by_partial_name(partial_name, token, db_id):
  """部分名称で Notion DB を検索し、マッチしたページ一覧を返す"""
  result = notion_request('POST', f'/databases/{db_id}/query', {
    'filter': {'property': 'プロジェクト名', 'title': {'contains': partial_name}}
  }, token=token)
  return result.get('results', [])


def resolve_single_page(partial_name, token, db_id):
  """
  部分名称で1件に絞り込む。
  0件はエラー、複数件は選択肢を提示して選ばせる。
  """
  pages = find_page_by_partial_name(partial_name, token, db_id)

  if not pages:
    print(f'[ERROR] 「{partial_name}」に一致するプロジェクトが見つかりません。')
    sys.exit(1)

  if len(pages) == 1:
    return pages[0]

  # 複数件の場合はインタラクティブに選択
  print(f'\n{len(pages)} 件見つかりました:')
  for i, p in enumerate(pages, 1):
    item = page_to_item(p)
    print(f'  {i}. {item["プロジェクト名"]} [{item["ステータス"]}]')
  try:
    idx = int(input('  番号を選択: ').strip()) - 1
    if 0 <= idx < len(pages):
      return pages[idx]
    print('[ERROR] 不正な番号です。')
    sys.exit(1)
  except ValueError:
    print('[ERROR] 番号を入力してください。')
    sys.exit(1)


# ---- --create-db ----

def cmd_create_db(token, parent_page_id):
  """プロジェクト管理DBを作成し、.env を更新する"""
  print(f'プロジェクト管理DBを作成中... (parent_page_id: {parent_page_id})')

  body = {
    'parent': {'page_id': parent_page_id},
    'title': [{'type': 'text', 'text': {'content': 'プロジェクト管理'}}],
    'properties': {
      'プロジェクト名': {'title': {}},
      'ステータス': {
        'select': {
          'options': [
            {'name': '準備中', 'color': 'yellow'},
            {'name': '運用中', 'color': 'green'},
            {'name': '完了', 'color': 'default'},
            {'name': '保留', 'color': 'red'},
          ]
        }
      },
      'フェーズ': {'rich_text': {}},
      '開始日': {'date': {}},
      '目標完了日': {'date': {}},
      'KPI': {'rich_text': {}},
      '担当': {'rich_text': {}},
      'メモ': {'rich_text': {}},
    },
  }

  result = notion_request('POST', '/databases', body, token=token)
  db_id = result.get('id', '')
  if not db_id:
    print('[ERROR] DB ID を取得できませんでした。レスポンスを確認してください。')
    print(json.dumps(result, ensure_ascii=False, indent=2))
    sys.exit(1)

  # .env に DB ID を書き込む
  update_env_key('NOTION_PROJECTS_DB_ID', db_id)
  print(f'DB を作成しました: {db_id}')
  print(f'.env に NOTION_PROJECTS_DB_ID を書き込みました。')


# ---- --list ----

def cmd_list(token, db_id, filter_status=None):
  """プロジェクト一覧を表示する"""
  filter_body = {}
  if filter_status:
    filter_body['filter'] = {
      'property': 'ステータス',
      'select': {'equals': filter_status}
    }

  pages = notion_query_all(db_id, token, filter_body if filter_body else None)

  if not pages:
    print('プロジェクトデータがありません。')
    return

  items = [page_to_item(p) for p in pages]
  items.sort(key=status_sort_key)

  # ヘッダー
  print(f'\n{"プロジェクト名":24} {"ステータス":8} {"フェーズ":20} {"担当":16} {"開始日":12}')
  print('-' * 84)
  for item in items:
    print(
      f'{item["プロジェクト名"][:24]:24} '
      f'{item["ステータス"]:8} '
      f'{item["フェーズ"][:20]:20} '
      f'{item["担当"][:16]:16} '
      f'{item["開始日"]:12}'
    )
  print(f'\n合計 {len(items)} 件')


# ---- --add ----

def cmd_add(args, token, db_id):
  """プロジェクトを1件追加する"""
  name = args.add
  if not name:
    print('[ERROR] プロジェクト名を指定してください。')
    sys.exit(1)

  props = {
    'プロジェクト名': {'title': [{'text': {'content': name}}]},
  }

  status = args.status or STATUS_DEFAULT
  props['ステータス'] = {'select': {'name': status}}

  if args.phase:
    props['フェーズ'] = rich_text_prop(args.phase)
  if args.assignee:
    props['担当'] = rich_text_prop(args.assignee)
  if args.memo:
    props['メモ'] = rich_text_prop(args.memo)
  if args.kpi:
    props['KPI'] = rich_text_prop(args.kpi)
  if args.start_date:
    props['開始日'] = {'date': {'start': args.start_date}}
  if args.goal_date:
    props['目標完了日'] = {'date': {'start': args.goal_date}}

  notion_request('POST', '/pages', {
    'parent': {'database_id': db_id},
    'properties': props,
  }, token=token)
  print(f'追加しました: {name} [{status}]')


# ---- --update ----

def cmd_update(args, token, db_id):
  """プロジェクトのプロパティを更新する"""
  page = resolve_single_page(args.update, token, db_id)
  page_id = page['id']
  current = page_to_item(page)

  props = {}
  if args.status:
    props['ステータス'] = {'select': {'name': args.status}}
  if args.phase:
    props['フェーズ'] = rich_text_prop(args.phase)
  if args.assignee:
    props['担当'] = rich_text_prop(args.assignee)
  if args.memo:
    props['メモ'] = rich_text_prop(args.memo)
  if args.kpi:
    props['KPI'] = rich_text_prop(args.kpi)
  if args.start_date:
    props['開始日'] = {'date': {'start': args.start_date}}
  if args.goal_date:
    props['目標完了日'] = {'date': {'start': args.goal_date}}

  if not props:
    print('[ERROR] 更新するプロパティを1つ以上指定してください。')
    sys.exit(1)

  notion_request('PATCH', f'/pages/{page_id}', {'properties': props}, token=token)
  print(f'更新しました: {current["プロジェクト名"]}')


# ---- --show ----

def cmd_show(partial_name, token, db_id):
  """プロジェクトの全プロパティ + ページ本文を表示する"""
  page = resolve_single_page(partial_name, token, db_id)
  item = page_to_item(page)

  print(f'\n=== {item["プロジェクト名"]} ===')
  print(f'ステータス  : {item["ステータス"]}')
  print(f'フェーズ    : {item["フェーズ"]}')
  print(f'開始日      : {item["開始日"]}')
  print(f'目標完了日  : {item["目標完了日"]}')
  print(f'KPI         : {item["KPI"]}')
  print(f'担当        : {item["担当"]}')
  print(f'メモ        : {item["メモ"]}')

  # ページ本文（作業履歴）を取得
  blocks_result = notion_request('GET', f'/blocks/{page["id"]}/children', token=token)
  blocks = blocks_result.get('results', [])

  if blocks:
    print('\n--- 作業履歴 ---')
    for block in blocks:
      block_type = block.get('type', '')
      if block_type in ('paragraph', 'bulleted_list_item', 'numbered_list_item', 'heading_1', 'heading_2', 'heading_3'):
        texts = block.get(block_type, {}).get('rich_text', [])
        text_content = ''.join(t.get('plain_text', '') for t in texts)
        if text_content:
          print(f'  {text_content}')
      elif block_type == 'divider':
        print('  ---')
  else:
    print('\n（作業履歴なし）')


# ---- --add-block ----

def cmd_add_block(partial_name, text, token, db_id):
  """ページ本文末尾に作業ブロックを追記する"""
  page = resolve_single_page(partial_name, token, db_id)
  page_id = page['id']
  item = page_to_item(page)

  # タイムスタンプ付きで追記
  now = datetime.now(JST)
  timestamp = f'{now.year}年{now.month}月{now.day}日 {now.hour:02d}:{now.minute:02d}'
  full_text = f'[{timestamp}] {text}'

  # 区切り線 + テキストブロックを追加
  children = [
    {'object': 'block', 'type': 'divider', 'divider': {}},
    {
      'object': 'block',
      'type': 'paragraph',
      'paragraph': {
        'rich_text': [{'type': 'text', 'text': {'content': full_text[:NOTION_RICH_TEXT_LIMIT]}}]
      }
    },
  ]

  notion_request('PATCH', f'/blocks/{page_id}/children', {'children': children}, token=token)
  print(f'追記しました: {item["プロジェクト名"]}')
  print(f'  内容: {full_text[:80]}{"..." if len(full_text) > 80 else ""}')


# ---- エントリーポイント ----

def main():
  parser = argparse.ArgumentParser(
    description='Notion プロジェクト管理スクリプト',
    formatter_class=argparse.RawDescriptionHelpFormatter,
    epilog=__doc__
  )

  # メインコマンド（排他）
  group = parser.add_mutually_exclusive_group(required=True)
  group.add_argument('--create-db', action='store_true', help='プロジェクト管理DBを新規作成する')
  group.add_argument('--list', action='store_true', help='プロジェクト一覧を表示する')
  group.add_argument('--add', metavar='プロジェクト名', help='プロジェクトを追加する')
  group.add_argument('--update', metavar='部分名称', help='プロジェクトを更新する')
  group.add_argument('--show', metavar='部分名称', help='プロジェクトの詳細を表示する')
  group.add_argument('--add-block', metavar='部分名称', help='ページ本文に作業履歴を追記する')

  # オプション
  parser.add_argument('--filter-status', metavar='ステータス', help='一覧のステータスフィルタ')
  parser.add_argument('--status', metavar='ステータス', help=f'ステータス: {" / ".join(STATUS_OPTIONS)}')
  parser.add_argument('--phase', metavar='フェーズ', help='フェーズ（自由記述）')
  parser.add_argument('--assignee', metavar='担当', help='担当エージェント名等')
  parser.add_argument('--memo', metavar='メモ', help='メモ')
  parser.add_argument('--kpi', metavar='KPI', help='KPI（自由記述）')
  parser.add_argument('--start-date', metavar='YYYY-MM-DD', help='開始日')
  parser.add_argument('--goal-date', metavar='YYYY-MM-DD', help='目標完了日')
  parser.add_argument('--text', metavar='テキスト', help='--add-block で追記するテキスト')

  args = parser.parse_args()

  # .env 読み込み
  env = load_env()
  token = env.get('NOTION_API_TOKEN', '')
  if not token:
    print('[ERROR] .env に NOTION_API_TOKEN が設定されていません。')
    sys.exit(1)

  # --create-db は DB ID が不要なため先に処理
  if args.create_db:
    parent_page_id = env.get('NOTION_ASUKA_PAGE_ID', '')
    if not parent_page_id:
      print('[ERROR] .env に NOTION_ASUKA_PAGE_ID が設定されていません。')
      sys.exit(1)
    cmd_create_db(token, parent_page_id)
    return

  # 以降は DB ID が必須
  db_id = env.get('NOTION_PROJECTS_DB_ID', '')
  if not db_id:
    print('[ERROR] .env に NOTION_PROJECTS_DB_ID が設定されていません。')
    print('  まず --create-db を実行してDBを作成してください。')
    sys.exit(1)

  if args.list:
    cmd_list(token, db_id, filter_status=args.filter_status)
  elif args.add:
    cmd_add(args, token, db_id)
  elif args.update:
    cmd_update(args, token, db_id)
  elif args.show:
    cmd_show(args.show, token, db_id)
  elif args.add_block:
    if not args.text:
      print('[ERROR] --add-block には --text が必要です。')
      sys.exit(1)
    cmd_add_block(args.add_block, args.text, token, db_id)


if __name__ == '__main__':
  main()
