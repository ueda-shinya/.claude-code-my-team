#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Notion 議事録DB登録スクリプト

使い方:
  notion-minutes.py --add-from-file <path> --title "タイトル"
      [--date YYYY-MM-DD] [--client "顧客名"]
      MarkdownファイルをNotion議事録DBにページとして登録する

  notion-minutes.py --list
      議事録一覧を表示する（最新10件）

オプション:
  --add-from-file   登録するMarkdownファイルのパス
  --title           議事録タイトル（必須）
  --date            日付（省略時は今日）
  --client          顧客名（rich_textの「顧客名」フィールドに設定）
  --list            議事録一覧を表示
"""

import argparse
import json
import os
import re
import ssl
import sys
import urllib.error
import urllib.request
from datetime import datetime, timedelta, timezone
from typing import Optional

# Windows環境での文字化け対策
if hasattr(sys.stdout, 'reconfigure'):
  sys.stdout.reconfigure(encoding='utf-8')


# ── 環境変数読み込み ─────────────────────────────────────────

def load_env() -> dict:
  """~/.claude/.env を読み込んで辞書で返す"""
  env_path = os.path.expanduser('~/.claude/.env')
  if not os.path.exists(env_path):
    print(f'[ERROR] .envファイルが見つかりません: {env_path}', file=sys.stderr)
    sys.exit(1)

  env = {}
  with open(env_path, encoding='utf-8') as f:
    for line in f:
      line = line.strip().strip('\r')
      if not line or line.startswith('#') or '=' not in line:
        continue
      k, v = line.split('=', 1)
      env[k.strip()] = v.strip().strip('"').strip("'")
  return env


# ── Notion API クライアント ──────────────────────────────────

class NotionClient:
  API_BASE = 'https://api.notion.com/v1'
  VERSION = '2022-06-28'

  def __init__(self, token: str):
    self.token = token
    self.ctx = ssl.create_default_context()

  def _request(self, method: str, path: str, body: Optional[dict] = None) -> dict:
    url = f'{self.API_BASE}{path}'
    headers = {
      'Authorization': f'Bearer {self.token}',
      'Notion-Version': self.VERSION,
      'Content-Type': 'application/json',
    }
    data = json.dumps(body).encode() if body is not None else None
    req = urllib.request.Request(url, data=data, headers=headers, method=method)
    try:
      with urllib.request.urlopen(req, context=self.ctx, timeout=30) as res:
        return json.loads(res.read().decode('utf-8'))
    except urllib.error.HTTPError as e:
      body_text = e.read().decode(errors='replace')
      print(f'[ERROR] Notion API エラー: {e.code} {e.reason}', file=sys.stderr)
      print(f'  詳細: {body_text}', file=sys.stderr)
      sys.exit(1)
    except urllib.error.URLError as e:
      print(f'[ERROR] 通信エラー: {e.reason}', file=sys.stderr)
      sys.exit(1)

  def get(self, path: str) -> dict:
    return self._request('GET', path)

  def post(self, path: str, body: dict) -> dict:
    return self._request('POST', path, body)

  def patch(self, path: str, body: dict) -> dict:
    return self._request('PATCH', path, body)


# ── Markdown → Notion ブロック変換 ──────────────────────────

def parse_markdown_to_blocks(md: str) -> list:
  """
  Markdownテキストを Notion ブロック形式に変換する。
  対応: 見出し(#/##/###)、箇条書き(- / *)、番号付きリスト、
        テーブル(|...|)、水平線(---/===)、段落
  Notionの1ブロックあたりのrich_textは2000文字以内の制限があるため
  長いテキストは分割する。
  """
  blocks = []
  lines = md.splitlines()
  i = 0

  # テーブル解析用のバッファ
  table_buffer = []

  def flush_table():
    """テーブルバッファをNotionのtableブロックに変換"""
    nonlocal table_buffer
    if not table_buffer:
      return
    # セパレータ行（|---|---|）を除外
    data_rows = [r for r in table_buffer if not all(
      c.strip().replace('-', '').replace(':', '') == '' for c in r
    )]
    if not data_rows:
      table_buffer = []
      return

    has_header = len(data_rows) >= 1
    table_width = max(len(r) for r in data_rows)

    table_rows = []
    for row_idx, row in enumerate(data_rows):
      cells = []
      for ci in range(table_width):
        cell_text = row[ci].strip() if ci < len(row) else ''
        cells.append(_rich_text_array(cell_text))
      table_rows.append({
        'object': 'block',
        'type': 'table_row',
        'table_row': {'cells': cells}
      })

    blocks.append({
      'object': 'block',
      'type': 'table',
      'table': {
        'table_width': table_width,
        'has_column_header': has_header,
        'has_row_header': False,
        'children': table_rows
      }
    })
    table_buffer = []

  while i < len(lines):
    line = lines[i]
    stripped = line.strip()

    # テーブル行の検出
    if stripped.startswith('|') and stripped.endswith('|'):
      cols = [c for c in stripped.split('|')[1:-1]]
      table_buffer.append(cols)
      i += 1
      continue
    else:
      # テーブルが終わったらフラッシュ
      if table_buffer:
        flush_table()

    # 空行
    if not stripped:
      i += 1
      continue

    # 水平線
    if stripped in ('---', '===', '***') or (
      all(c in '-' for c in stripped) and len(stripped) >= 3
    ):
      blocks.append({'object': 'block', 'type': 'divider', 'divider': {}})
      i += 1
      continue

    # 見出し
    if stripped.startswith('### '):
      blocks.append(_heading(3, stripped[4:]))
      i += 1
      continue
    if stripped.startswith('## '):
      blocks.append(_heading(2, stripped[3:]))
      i += 1
      continue
    if stripped.startswith('# '):
      blocks.append(_heading(1, stripped[2:]))
      i += 1
      continue

    # チェックボックス（- [ ] / - [x]）
    if stripped.startswith('- [ ] ') or stripped.startswith('- [x] ') or stripped.startswith('- [X] '):
      checked = not stripped.startswith('- [ ] ')
      text = stripped[6:]
      blocks.append(_todo(text, checked))
      i += 1
      continue

    # 箇条書き（- または *）
    if stripped.startswith('- ') or stripped.startswith('* '):
      blocks.append(_bullet(stripped[2:]))
      i += 1
      continue

    # 番号付きリスト
    num_match = re.match(r'^\d+\.\s+(.+)$', stripped)
    if num_match:
      blocks.append(_numbered(num_match.group(1)))
      i += 1
      continue

    # 段落（その他）
    blocks.append(_paragraph(stripped))
    i += 1

  # 末尾テーブルのフラッシュ
  if table_buffer:
    flush_table()

  return blocks


def _rich_text_array(text: str) -> list:
  """rich_text配列を生成（**太字** 対応）。Notionの2000文字制限を超える場合は分割。"""
  parts = []
  pattern = re.compile(r'\*\*(.+?)\*\*')
  last_end = 0
  for m in pattern.finditer(text):
    if m.start() > last_end:
      parts.append({'type': 'text', 'text': {'content': text[last_end:m.start()]}})
    parts.append({
      'type': 'text',
      'text': {'content': m.group(1)},
      'annotations': {'bold': True}
    })
    last_end = m.end()
  if last_end < len(text):
    parts.append({'type': 'text', 'text': {'content': text[last_end:]}})
  if not parts:
    parts = [{'type': 'text', 'text': {'content': ''}}]

  # 2000文字制限（各要素ごと）
  safe = []
  for p in parts:
    content = p['text']['content']
    while len(content) > 1990:
      head = dict(p)
      head['text'] = dict(p['text'], content=content[:1990])
      safe.append(head)
      content = content[1990:]
    p = dict(p)
    p['text'] = dict(p['text'], content=content)
    safe.append(p)
  return safe


def _heading(level: int, text: str) -> dict:
  key = f'heading_{level}'
  return {
    'object': 'block',
    'type': key,
    key: {'rich_text': _rich_text_array(text)}
  }


def _bullet(text: str) -> dict:
  return {
    'object': 'block',
    'type': 'bulleted_list_item',
    'bulleted_list_item': {'rich_text': _rich_text_array(text)}
  }


def _numbered(text: str) -> dict:
  return {
    'object': 'block',
    'type': 'numbered_list_item',
    'numbered_list_item': {'rich_text': _rich_text_array(text)}
  }


def _paragraph(text: str) -> dict:
  return {
    'object': 'block',
    'type': 'paragraph',
    'paragraph': {'rich_text': _rich_text_array(text)}
  }


def _todo(text: str, checked: bool) -> dict:
  return {
    'object': 'block',
    'type': 'to_do',
    'to_do': {
      'rich_text': _rich_text_array(text),
      'checked': checked
    }
  }


# ── 議事録DB操作 ─────────────────────────────────────────────

def add_from_file(
  client: NotionClient,
  db_id: str,
  file_path: str,
  title: str,
  date_str: str,
  customer: Optional[str],
) -> str:
  """MarkdownファイルをNotion議事録DBに登録してページURLを返す"""

  # ファイル存在確認
  expanded = os.path.expanduser(file_path)
  if not os.path.exists(expanded):
    print(f'[ERROR] ファイルが見つかりません: {expanded}', file=sys.stderr)
    sys.exit(1)

  with open(expanded, encoding='utf-8') as f:
    md_content = f.read()

  # プロパティ構築
  properties = {
    'タイトル': {'title': [{'text': {'content': title}}]},
    '日付': {'date': {'start': date_str}},
  }
  if customer:
    properties['顧客名'] = {'rich_text': [{'text': {'content': customer}}]}

  # ページ本文ブロック変換（Notionは1リクエストで100ブロックまで）
  all_blocks = parse_markdown_to_blocks(md_content)
  # 最初の100ブロックをページ作成時に含める
  first_batch = all_blocks[:100]
  remaining_blocks = all_blocks[100:]

  page_data = {
    'parent': {'database_id': db_id},
    'properties': properties,
    'children': first_batch,
  }

  result = client.post('/pages', page_data)
  page_id = result.get('id', '')
  page_url = result.get('url', '')

  # 100ブロック超の場合は追記
  if remaining_blocks:
    # 100ブロックずつ追記
    for start in range(0, len(remaining_blocks), 100):
      batch = remaining_blocks[start:start + 100]
      client.patch(f'/blocks/{page_id}/children', {'children': batch})

  return page_url


def list_minutes(client: NotionClient, db_id: str, limit: int = 10):
  """議事録一覧を表示"""
  body = {
    'sorts': [{'property': '日付', 'direction': 'descending'}],
    'page_size': limit,
  }
  result = client.post(f'/databases/{db_id}/query', body)
  pages = result.get('results', [])
  if not pages:
    print('議事録が見つかりませんでした')
    return

  print(f'議事録一覧（最新{len(pages)}件）:')
  for p in pages:
    props = p.get('properties', {})
    title_arr = props.get('タイトル', {}).get('title', [])
    title = ''.join(t.get('plain_text', '') for t in title_arr)
    date_val = props.get('日付', {}).get('date') or {}
    date = date_val.get('start', '日付なし')
    customer_arr = props.get('顧客名', {}).get('rich_text', [])
    customer = ''.join(t.get('plain_text', '') for t in customer_arr) or '-'
    url = p.get('url', '')
    print(f'  [{date}] {title} / 顧客: {customer}')
    print(f'    {url}')


# ── エントリポイント ─────────────────────────────────────────

def main():
  parser = argparse.ArgumentParser(
    description='Notion議事録DBにMarkdownファイルを登録するCLIツール'
  )
  parser.add_argument('--add-from-file', metavar='PATH', help='登録するMarkdownファイルのパス')
  parser.add_argument('--title', help='議事録タイトル')
  parser.add_argument('--date', metavar='YYYY-MM-DD', help='日付（省略時は今日）')
  parser.add_argument('--client', metavar='顧客名', help='顧客名（顧客名フィールドに設定）')
  parser.add_argument('--list', action='store_true', help='議事録一覧を表示')
  args = parser.parse_args()

  env = load_env()
  notion_token = env.get('NOTION_API_TOKEN', '')
  db_id = env.get('NOTION_MINUTES_DB_ID', '')

  if not notion_token:
    print('[ERROR] NOTION_API_TOKEN が .env に設定されていません', file=sys.stderr)
    sys.exit(1)
  if not db_id:
    print('[ERROR] NOTION_MINUTES_DB_ID が .env に設定されていません', file=sys.stderr)
    sys.exit(1)

  notion = NotionClient(notion_token)

  # --list
  if args.list:
    list_minutes(notion, db_id)
    return

  # --add-from-file
  if args.add_from_file:
    if not args.title:
      print('[ERROR] --title を指定してください', file=sys.stderr)
      sys.exit(1)

    # 日付の決定
    if args.date:
      date_str = args.date
    else:
      jst = timezone(timedelta(hours=9))
      date_str = datetime.now(jst).strftime('%Y-%m-%d')

    print(f'登録中: {args.title} ({date_str})')
    if args.client:
      print(f'  顧客名: {args.client}')

    url = add_from_file(
      client=notion,
      db_id=db_id,
      file_path=args.add_from_file,
      title=args.title,
      date_str=date_str,
      customer=args.client,
    )

    print(f'[OK] Notion議事録DBに登録しました')
    print(f'  URL: {url}')
    return

  parser.print_help()


if __name__ == '__main__':
  main()
