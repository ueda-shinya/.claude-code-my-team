#!/usr/bin/env python3
# platform: mac-only
"""
sync-cmux-sessions.py

cmux で作成した Claude Code セッションを ~/.claude/history.jsonl に同期する。
VSCode 拡張の Claude Code がセッションを認識できるようにすることが目的。

注意: このスクリプトは macOS 専用です。
      cmux は macOS 環境でのみ動作するため、Windows では使用しません。

使い方（macOS）:
  python3 ~/.claude/scripts/sync-cmux-sessions.py
"""

import json
import os
import sys
import time
from pathlib import Path


# ─── 定数 ───────────────────────────────────────────────────────────────────

CLAUDE_DIR = Path.home() / '.claude'
SESSIONS_DIR = CLAUDE_DIR / 'sessions'
PROJECTS_DIR = CLAUDE_DIR / 'projects'
HISTORY_FILE = CLAUDE_DIR / 'history.jsonl'

# セッション本体 JSONL を先頭から何行読むか（大ファイルへの対策）
MAX_LINES_TO_SCAN = 30

# ユーザー発言が見つからない場合のフォールバック表示名
FALLBACK_DISPLAY = '(cmux session)'


# ─── cwd → encoded-cwd 変換 ──────────────────────────────────────────────────

def encode_cwd(cwd: str) -> str:
  """
  cwd（作業ディレクトリのフルパス）を projects/ 以下のディレクトリ名形式に変換する。

  変換ルール:
    - パス中の '/' を '-' に変換
    - '.' も '-' に変換
    - 先頭の '-' はそのまま残す

  例:
    /Users/uedashinya         → -Users-uedashinya
    /Users/uedashinya/.claude → -Users-uedashinya--claude
  """
  return cwd.replace('/', '-').replace('.', '-')


# ─── セッション本体 JSONL からユーザーの最初の発言を取得 ─────────────────────

def extract_first_human_message(session_jsonl: Path) -> str:
  """
  セッション本体の .jsonl ファイルを先頭から数行スキャンし、
  最初のユーザー発言テキストを返す。

  取得できない場合は FALLBACK_DISPLAY を返す。
  """
  if not session_jsonl.exists():
    return FALLBACK_DISPLAY

  try:
    with session_jsonl.open('r', encoding='utf-8') as f:
      for i, raw_line in enumerate(f):
        if i >= MAX_LINES_TO_SCAN:
          break

        raw_line = raw_line.strip()
        if not raw_line:
          continue

        try:
          entry = json.loads(raw_line)
        except json.JSONDecodeError:
          continue

        # type: "user" かつ message.role: "user" のエントリを探す
        if entry.get('type') != 'user':
          continue

        message = entry.get('message', {})
        if not isinstance(message, dict):
          continue
        if message.get('role') != 'user':
          continue

        content = message.get('content', '')

        # content が文字列の場合
        if isinstance(content, str):
          text = content.strip()
          if text:
            return text

        # content が配列の場合（[{"type": "text", "text": "..."}, ...]）
        elif isinstance(content, list):
          for block in content:
            if isinstance(block, dict) and block.get('type') == 'text':
              text = block.get('text', '').strip()
              if text:
                return text

  except OSError:
    pass

  return FALLBACK_DISPLAY


# ─── history.jsonl の読み込み・書き込み ──────────────────────────────────────

def load_registered_session_ids(history_file: Path) -> set:
  """
  history.jsonl に登録済みの sessionId を set で返す。
  ファイルが存在しない場合、または読み込みに失敗した場合は空 set を返す。
  """
  registered = set()
  if not history_file.exists():
    return registered

  try:
    f_handle = history_file.open('r', encoding='utf-8')
  except OSError as e:
    print(f'警告: history.jsonl を開けませんでした: {e}', file=sys.stderr)
    return registered

  with f_handle as f:
    for raw_line in f:
      raw_line = raw_line.strip()
      if not raw_line:
        continue
      try:
        entry = json.loads(raw_line)
        session_id = entry.get('sessionId')
        if session_id:
          registered.add(session_id)
      except json.JSONDecodeError:
        continue

  return registered


def append_history_entry(history_file: Path, entry: dict) -> None:
  """
  history.jsonl に1エントリ追記する。ファイルが存在しない場合は新規作成する。
  """
  history_file.parent.mkdir(parents=True, exist_ok=True)
  with history_file.open('a', encoding='utf-8') as f:
    f.write(json.dumps(entry, ensure_ascii=False) + '\n')


# ─── cmux sessions/*.json の読み込み ─────────────────────────────────────────

def load_cmux_sessions(sessions_dir: Path) -> list[dict]:
  """
  sessions/*.json を全て読み込み、セッション情報のリストを返す。
  読み込みに失敗したファイルはスキップする。
  """
  sessions = []
  if not sessions_dir.exists():
    return sessions

  for json_file in sessions_dir.glob('*.json'):
    try:
      with json_file.open('r', encoding='utf-8') as f:
        data = json.load(f)
      # 最低限必要なフィールドが揃っているか確認
      if 'sessionId' in data and 'cwd' in data:
        sessions.append(data)
    except (json.JSONDecodeError, OSError):
      continue

  return sessions


# ─── メイン処理 ──────────────────────────────────────────────────────────────

def main() -> None:
  # 1. cmux セッション一覧を取得
  cmux_sessions = load_cmux_sessions(SESSIONS_DIR)
  if not cmux_sessions:
    print('セッションファイルが見つかりませんでした。')
    return

  # 2. history.jsonl に登録済みの sessionId を取得
  registered_ids = load_registered_session_ids(HISTORY_FILE)

  # 3. 未登録セッションを history.jsonl に追記
  added_count = 0

  for session in cmux_sessions:
    session_id = session['sessionId']
    cwd = session['cwd']

    # 冪等性チェック：既に登録済みならスキップ
    if session_id in registered_ids:
      continue

    # セッション本体 JSONL のパスを組み立てる
    encoded_cwd = encode_cwd(cwd)
    session_jsonl = PROJECTS_DIR / encoded_cwd / f'{session_id}.jsonl'

    # ユーザーの最初の発言を取得
    display_text = extract_first_human_message(session_jsonl)

    # timestamp: startedAt があれば使用、なければ現在時刻（ms）
    timestamp = session.get('startedAt', int(time.time() * 1000))

    # history.jsonl に追記するエントリを構築（display は最大200文字に制限）
    history_entry = {
      'display': display_text[:200],
      'pastedContents': {},
      'timestamp': timestamp,
      'project': cwd,
      'sessionId': session_id,
    }

    append_history_entry(HISTORY_FILE, history_entry)
    registered_ids.add(session_id)  # 重複追記防止のため即時反映
    added_count += 1

    print(f'  追加: {session_id} — {display_text[:50]}')

  # 4. 結果報告
  print(f'\n完了: {added_count} 件追加しました。（スキップ: {len(cmux_sessions) - added_count} 件）')


if __name__ == '__main__':
  main()
