#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Notion SNS投稿管理スクリプト

使い方:
  notion-sns.py --create-db
      SNS投稿管理DBを作成して .env に NOTION_SNS_DB_ID を書き込む

  notion-sns.py --list [--filter-status S] [--filter-platform P] [--filter-date YYYY-MM-DD]
      投稿一覧を表示する

  notion-sns.py --add "投稿タイトル"
      [--date YYYY-MM-DD] [--platform X|Threads]
      [--category カテゴリ] [--type 型]
      [--draft "投稿内容案テキスト"] [--memo メモ]
      投稿を1件追加する

  notion-sns.py --update "部分タイトル"
      [--status ステータス]
      [--likes N] [--impressions N] [--rts N] [--er 3.3]
      [--content "実際に投稿したテキスト"]
      [--memo メモ]
      投稿のプロパティを更新する

  notion-sns.py --show "部分タイトル"
      全プロパティを表示する

  notion-sns.py --weekly-summary
      直近7日間の週次サマリーを表示する
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

# プラットフォーム
PLATFORM_OPTIONS = ['X', 'Threads']

# カテゴリ
CATEGORY_OPTIONS = ['Tips', '裏側・日常', '想い・ビジョン', '問いかけ', '事例', '自己紹介', '教育系長文']

# 型
TYPE_OPTIONS = [
  'Before/After型', '権威×意外性型', '嫁ブロック型', '数字インパクト型',
  'リスト・まとめ型', '失敗談→教訓型', '問いかけ型', '速報・一次情報型'
]

# ステータス
STATUS_OPTIONS = ['下書き', 'レビュー待ち', '承認済み', '投稿済み']
STATUS_DEFAULT = '下書き'
STATUS_ORDER = ['下書き', 'レビュー待ち', '承認済み', '投稿済み']


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


def get_number(props, key):
  """number プロパティの値を取得。未設定は None"""
  return props.get(key, {}).get('number')


def rich_text_prop(text):
  """rich_text プロパティ用の値を生成"""
  content = text[:NOTION_RICH_TEXT_LIMIT] if text else ''
  return {'rich_text': [{'text': {'content': content}}]}


def page_to_item(page):
  """Notion ページをSNS投稿 dict に変換"""
  p = page['properties']
  return {
    'id': page['id'],
    '投稿タイトル': get_text(p, '投稿タイトル'),
    '投稿予定日': get_date(p, '投稿予定日'),
    'プラットフォーム': get_select(p, 'プラットフォーム'),
    'カテゴリ': get_select(p, 'カテゴリ'),
    '型': get_select(p, '型'),
    'ステータス': get_select(p, 'ステータス'),
    '投稿内容案': get_text(p, '投稿内容案'),
    '投稿内容': get_text(p, '投稿内容'),
    'いいね数': get_number(p, 'いいね数'),
    'インプレッション数': get_number(p, 'インプレッション数'),
    'RT数': get_number(p, 'RT数'),
    'ER': get_number(p, 'ER'),
    'メモ': get_text(p, 'メモ'),
  }


def status_sort_key(item):
  """ステータスを定義順でソートするためのキー関数"""
  s = item['ステータス']
  try:
    return STATUS_ORDER.index(s)
  except ValueError:
    return len(STATUS_ORDER)


def find_page_by_partial_title(partial_name, token, db_id):
  """部分名称で Notion DB を検索し、マッチしたページ一覧を返す"""
  result = notion_request('POST', f'/databases/{db_id}/query', {
    'filter': {'property': '投稿タイトル', 'title': {'contains': partial_name}}
  }, token=token)
  return result.get('results', [])


def resolve_single_page(partial_name, token, db_id):
  """
  部分名称で1件に絞り込む。
  0件はエラー、複数件は選択肢を提示して選ばせる。
  """
  pages = find_page_by_partial_title(partial_name, token, db_id)

  if not pages:
    print(f'[ERROR] 「{partial_name}」に一致する投稿が見つかりません。')
    sys.exit(1)

  if len(pages) == 1:
    return pages[0]

  # 複数件の場合はインタラクティブに選択
  print(f'\n{len(pages)} 件見つかりました:')
  for i, p in enumerate(pages, 1):
    item = page_to_item(p)
    print(f'  {i}. {item["投稿タイトル"]} [{item["ステータス"]}] {item["投稿予定日"]}')
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

def cmd_create_db(parent_page_id, token, force=False, reuse=False):
  """SNS投稿管理DBを作成し、.env を更新する"""

  # ---- チェック1: .envにDB IDがある場合 ----
  env = load_env()
  existing_id = env.get('NOTION_SNS_DB_ID', '')
  if existing_id and not force:
    result = notion_request('GET', f'/databases/{existing_id}', token=token, allow_404=True)
    if result is not None:
      db_title = ''.join(t.get('plain_text', '') for t in result.get('title', []))
      print(f'[INFO] 既に存在します: {db_title} ({existing_id})')
      print('  再作成する場合は --force を指定してください。')
      sys.exit(1)
    # 404 → IDが古い/削除済み。チェック2へ進む

  # ---- チェック2: 同名DBを検索 ----
  if not force:
    found_id = search_db_by_title('SNS投稿管理', token)
    if found_id:
      print(f'[INFO] 同名のDBが見つかりました: SNS投稿管理 ({found_id})')
      print('  このDBを使用する場合は --reuse を指定してください。')
      print('  新規作成する場合は --force を指定してください。')
      if reuse:
        update_env_key('NOTION_SNS_DB_ID', found_id)
        print(f'  .env の NOTION_SNS_DB_ID を更新しました: {found_id}')
      sys.exit(1)

  print(f'SNS投稿管理DBを作成中... (parent_page_id: {parent_page_id})')

  body = {
    'parent': {'page_id': parent_page_id},
    'title': [{'type': 'text', 'text': {'content': 'SNS投稿管理'}}],
    'properties': {
      '投稿タイトル': {'title': {}},
      '投稿予定日': {'date': {}},
      'プラットフォーム': {
        'select': {
          'options': [
            {'name': 'X', 'color': 'blue'},
            {'name': 'Threads', 'color': 'purple'},
          ]
        }
      },
      'カテゴリ': {
        'select': {
          'options': [
            {'name': 'Tips', 'color': 'green'},
            {'name': '裏側・日常', 'color': 'yellow'},
            {'name': '想い・ビジョン', 'color': 'pink'},
            {'name': '問いかけ', 'color': 'orange'},
            {'name': '事例', 'color': 'blue'},
            {'name': '自己紹介', 'color': 'purple'},
            {'name': '教育系長文', 'color': 'red'},
          ]
        }
      },
      '型': {
        'select': {
          'options': [
            {'name': 'Before/After型', 'color': 'green'},
            {'name': '権威×意外性型', 'color': 'blue'},
            {'name': '嫁ブロック型', 'color': 'pink'},
            {'name': '数字インパクト型', 'color': 'orange'},
            {'name': 'リスト・まとめ型', 'color': 'yellow'},
            {'name': '失敗談→教訓型', 'color': 'red'},
            {'name': '問いかけ型', 'color': 'purple'},
            {'name': '速報・一次情報型', 'color': 'gray'},
          ]
        }
      },
      'ステータス': {
        'select': {
          'options': [
            {'name': '下書き', 'color': 'gray'},
            {'name': 'レビュー待ち', 'color': 'yellow'},
            {'name': '承認済み', 'color': 'green'},
            {'name': '投稿済み', 'color': 'blue'},
          ]
        }
      },
      '投稿内容案': {'rich_text': {}},
      '投稿内容': {'rich_text': {}},
      'いいね数': {'number': {}},
      'インプレッション数': {'number': {}},
      'RT数': {'number': {}},
      'ER': {'number': {}},
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
  update_env_key('NOTION_SNS_DB_ID', db_id)
  print(f'DBを作成しました: {db_id}')
  print('.env に NOTION_SNS_DB_ID を書き込みました。')


# ---- --list ----

def cmd_list(token, db_id, filter_status=None, filter_platform=None, filter_date=None):
  """投稿一覧を表示する"""
  filters = []

  if filter_status:
    filters.append({'property': 'ステータス', 'select': {'equals': filter_status}})
  if filter_platform:
    filters.append({'property': 'プラットフォーム', 'select': {'equals': filter_platform}})
  if filter_date:
    filters.append({'property': '投稿予定日', 'date': {'equals': filter_date}})

  filter_body = {}
  if len(filters) == 1:
    filter_body['filter'] = filters[0]
  elif len(filters) > 1:
    filter_body['filter'] = {'and': filters}

  pages = notion_query_all(db_id, token, filter_body if filter_body else None)

  if not pages:
    print('投稿データがありません。')
    return

  items = [page_to_item(p) for p in pages]
  items.sort(key=lambda x: (x['投稿予定日'] or '', status_sort_key(x)))

  # ヘッダー
  print(f'\n{"投稿タイトル":20} {"日付":12} {"PF":8} {"カテゴリ":12} {"ステータス":10}')
  print('-' * 66)
  for item in items:
    print(
      f'{item["投稿タイトル"][:20]:20} '
      f'{item["投稿予定日"]:12} '
      f'{item["プラットフォーム"]:8} '
      f'{item["カテゴリ"][:12]:12} '
      f'{item["ステータス"]:10}'
    )
  print(f'\n合計 {len(items)} 件')


# ---- --add ----

def cmd_add(args, token, db_id):
  """投稿を1件追加する"""
  title = args.add
  if not title:
    print('[ERROR] 投稿タイトルを指定してください。')
    sys.exit(1)

  props = {
    '投稿タイトル': {'title': [{'text': {'content': title}}]},
    'ステータス': {'select': {'name': STATUS_DEFAULT}},
  }

  if args.date:
    props['投稿予定日'] = {'date': {'start': args.date}}
  if args.platform:
    if args.platform not in PLATFORM_OPTIONS:
      print(f'[ERROR] プラットフォームは {" / ".join(PLATFORM_OPTIONS)} のいずれかを指定してください。')
      sys.exit(1)
    props['プラットフォーム'] = {'select': {'name': args.platform}}
  if args.category:
    props['カテゴリ'] = {'select': {'name': args.category}}
  if args.type:
    props['型'] = {'select': {'name': args.type}}
  if args.draft:
    props['投稿内容案'] = rich_text_prop(args.draft)
  if args.memo:
    props['メモ'] = rich_text_prop(args.memo)

  notion_request('POST', '/pages', {
    'parent': {'database_id': db_id},
    'properties': props,
  }, token=token)
  print(f'追加しました: {title} [{STATUS_DEFAULT}]')


# ---- --update ----

def cmd_update(args, token, db_id):
  """投稿のプロパティを更新する"""
  page = resolve_single_page(args.update, token, db_id)
  page_id = page['id']
  current = page_to_item(page)

  props = {}
  if args.status:
    if args.status not in STATUS_OPTIONS:
      print(f'[ERROR] ステータスは {" / ".join(STATUS_OPTIONS)} のいずれかを指定してください。')
      sys.exit(1)
    props['ステータス'] = {'select': {'name': args.status}}
  if args.likes is not None:
    props['いいね数'] = {'number': args.likes}
  if args.impressions is not None:
    props['インプレッション数'] = {'number': args.impressions}
  if args.rts is not None:
    props['RT数'] = {'number': args.rts}
  if args.er is not None:
    props['ER'] = {'number': args.er}
  if args.content:
    props['投稿内容'] = rich_text_prop(args.content)
  if args.memo:
    props['メモ'] = rich_text_prop(args.memo)
  if args.date:
    props['投稿予定日'] = {'date': {'start': args.date}}

  if not props:
    print('[ERROR] 更新するプロパティを1つ以上指定してください。')
    sys.exit(1)

  notion_request('PATCH', f'/pages/{page_id}', {'properties': props}, token=token)
  print(f'更新しました: {current["投稿タイトル"]}')


# ---- --show ----

def cmd_show(partial_name, token, db_id):
  """投稿の全プロパティを表示する"""
  page = resolve_single_page(partial_name, token, db_id)
  item = page_to_item(page)

  # 数値は未設定なら「-」で表示
  def fmt_num(val):
    return str(val) if val is not None else '-'

  print(f'\n=== {item["投稿タイトル"]} ===')
  print(f'投稿予定日      : {item["投稿予定日"] or "-"}')
  print(f'プラットフォーム: {item["プラットフォーム"] or "-"}')
  print(f'カテゴリ        : {item["カテゴリ"] or "-"}')
  print(f'型              : {item["型"] or "-"}')
  print(f'ステータス      : {item["ステータス"] or "-"}')
  print(f'いいね数        : {fmt_num(item["いいね数"])}')
  print(f'インプレッション: {fmt_num(item["インプレッション数"])}')
  print(f'RT数            : {fmt_num(item["RT数"])}')
  print(f'ER              : {fmt_num(item["ER"])}%' if item["ER"] is not None else f'ER              : -')
  print(f'\n--- 投稿内容案 ---')
  print(item['投稿内容案'] or '（未記入）')
  print(f'\n--- 投稿内容（実績） ---')
  print(item['投稿内容'] or '（未記入）')
  print(f'\n--- メモ ---')
  print(item['メモ'] or '（なし）')


# ---- --weekly-summary ----

def cmd_weekly_summary(token, db_id):
  """直近7日間の週次サマリーを表示する"""
  now = datetime.now(JST)

  # 直近7日間の開始・終了日を計算
  # 週の始まりを月曜日に揃える（ISO週）
  # 先週の月曜日から今週の日曜日まで（または直近7日間）
  today = now.date()
  # 今日を含む7日前から今日まで
  start_date = today - timedelta(days=6)
  end_date = today

  # 表示用ラベル（%-m はWindows非対応なので f文字列で）
  start_label = f'{start_date.month}/{start_date.day}'
  end_label = f'{end_date.month}/{end_date.day}'

  # DBから投稿済み or 最近7日間の投稿予定日のデータを取得
  filter_body = {
    'filter': {
      'and': [
        {'property': '投稿予定日', 'date': {'on_or_after': start_date.isoformat()}},
        {'property': '投稿予定日', 'date': {'on_or_before': end_date.isoformat()}},
      ]
    }
  }
  pages = notion_query_all(db_id, token, filter_body)

  if not pages:
    print(f'【週次サマリー {start_label}〜{end_label}】')
    print('  対象期間のデータがありません。')
    return

  items = [page_to_item(p) for p in pages]

  # プラットフォーム別カウント
  platform_counts = {}
  for item in items:
    pf = item['プラットフォーム'] or '不明'
    platform_counts[pf] = platform_counts.get(pf, 0) + 1

  platform_str = ' / '.join(f'{pf} {cnt}本' for pf, cnt in sorted(platform_counts.items()))

  # インプレッション・ER集計（投稿済みで数値ありのもの）
  posted = [i for i in items if i['ステータス'] == '投稿済み']
  imp_list = [i['インプレッション数'] for i in posted if i['インプレッション数'] is not None]
  er_list = [i['ER'] for i in posted if i['ER'] is not None]

  avg_imp = sum(imp_list) / len(imp_list) if imp_list else None
  avg_er = sum(er_list) / len(er_list) if er_list else None

  # ベスト・ワースト（いいね数基準）
  liked = [i for i in posted if i['いいね数'] is not None]
  best = max(liked, key=lambda x: x['いいね数']) if liked else None
  worst = min(liked, key=lambda x: x['いいね数']) if liked else None

  print(f'【週次サマリー {start_label}〜{end_label}】')
  print(f'投稿数: {platform_str}（合計 {len(items)} 件）')

  if avg_imp is not None:
    print(f'平均インプレッション: {avg_imp:.0f}')
  else:
    print('平均インプレッション: データなし')

  if avg_er is not None:
    print(f'平均ER: {avg_er:.1f}%')
  else:
    print('平均ER: データなし')

  if best:
    likes_str = f'いいね{best["いいね数"]}'
    imp_str = f', インプ{best["インプレッション数"]}' if best['インプレッション数'] is not None else ''
    print(f'ベスト投稿: {best["投稿タイトル"]}（{likes_str}{imp_str}）')

  if worst and (best is None or worst['id'] != best['id']):
    likes_str = f'いいね{worst["いいね数"]}'
    imp_str = f', インプ{worst["インプレッション数"]}' if worst['インプレッション数'] is not None else ''
    print(f'ワースト投稿: {worst["投稿タイトル"]}（{likes_str}{imp_str}）')

  # ステータス内訳
  status_counts = {}
  for item in items:
    s = item['ステータス'] or '不明'
    status_counts[s] = status_counts.get(s, 0) + 1
  status_str = ' / '.join(f'{s}:{cnt}' for s, cnt in sorted(status_counts.items()))
  print(f'ステータス内訳: {status_str}')


# ---- エントリーポイント ----

def main():
  parser = argparse.ArgumentParser(
    description='Notion SNS投稿管理スクリプト',
    formatter_class=argparse.RawDescriptionHelpFormatter,
    epilog=__doc__
  )

  # メインコマンド（排他）
  group = parser.add_mutually_exclusive_group(required=True)
  group.add_argument('--create-db', action='store_true', help='SNS投稿管理DBを新規作成する')
  group.add_argument('--list', action='store_true', help='投稿一覧を表示する')
  group.add_argument('--add', metavar='投稿タイトル', help='投稿を追加する')
  group.add_argument('--update', metavar='部分タイトル', help='投稿を更新する')
  group.add_argument('--show', metavar='部分タイトル', help='投稿の詳細を表示する')
  group.add_argument('--weekly-summary', action='store_true', help='週次サマリーを表示する')

  # --create-db サブオプション（--force と --reuse は排他）
  idempotent_group = parser.add_mutually_exclusive_group()
  idempotent_group.add_argument('--force', action='store_true',
    help='--create-db: 既存DBがあっても強制的に新規作成する')
  idempotent_group.add_argument('--reuse', action='store_true',
    help='--create-db: 同名DBが見つかった場合にそのIDを .env に設定する（新規作成しない）')

  # --list フィルター
  parser.add_argument('--filter-status', metavar='ステータス',
    help=f'一覧フィルタ: {" / ".join(STATUS_OPTIONS)}')
  parser.add_argument('--filter-platform', metavar='プラットフォーム',
    help=f'一覧フィルタ: {" / ".join(PLATFORM_OPTIONS)}')
  parser.add_argument('--filter-date', metavar='YYYY-MM-DD', help='一覧フィルタ: 投稿予定日（完全一致）')

  # --add / --update 共通オプション
  parser.add_argument('--date', metavar='YYYY-MM-DD', help='投稿予定日')
  parser.add_argument('--platform', metavar='プラットフォーム',
    help=f'プラットフォーム: {" / ".join(PLATFORM_OPTIONS)}')
  parser.add_argument('--category', metavar='カテゴリ',
    help=f'カテゴリ: {" / ".join(CATEGORY_OPTIONS)}')
  parser.add_argument('--type', metavar='型',
    help=f'型: {" / ".join(TYPE_OPTIONS)}')
  parser.add_argument('--status', metavar='ステータス',
    help=f'ステータス: {" / ".join(STATUS_OPTIONS)}')
  parser.add_argument('--draft', metavar='投稿内容案', help='コト/ハルが作った案のテキスト')
  parser.add_argument('--memo', metavar='メモ', help='振り返り等のメモ')

  # --update 専用オプション
  parser.add_argument('--likes', metavar='N', type=int, help='いいね数')
  parser.add_argument('--impressions', metavar='N', type=int, help='インプレッション数')
  parser.add_argument('--rts', metavar='N', type=int, help='RT数')
  parser.add_argument('--er', metavar='N', type=float, help='エンゲージメント率（%%）')
  parser.add_argument('--content', metavar='テキスト', help='実際に投稿した文面')

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
    cmd_create_db(parent_page_id, token, force=args.force, reuse=args.reuse)
    return

  # 以降は DB ID が必須
  db_id = env.get('NOTION_SNS_DB_ID', '')
  if not db_id:
    print('[ERROR] .env に NOTION_SNS_DB_ID が設定されていません。')
    print('  まず --create-db を実行してDBを作成してください。')
    sys.exit(1)

  if args.list:
    cmd_list(
      token, db_id,
      filter_status=args.filter_status,
      filter_platform=args.filter_platform,
      filter_date=args.filter_date,
    )
  elif args.add:
    cmd_add(args, token, db_id)
  elif args.update:
    cmd_update(args, token, db_id)
  elif args.show:
    cmd_show(args.show, token, db_id)
  elif args.weekly_summary:
    cmd_weekly_summary(token, db_id)


if __name__ == '__main__':
  main()
