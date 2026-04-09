#!/usr/bin/env python3
# Claude Code セッションブラウザ
# 過去セッションの一覧表示と再開を行うスクリプト

import sys
import os
import re
import json
import argparse
import subprocess
import shutil
import shlex
from pathlib import Path
from datetime import datetime, timezone

# claude コマンドの実行コマンドリストを解決する
# cmux ラッパー（/Applications/cmux.app/...）は NODE_OPTIONS を注入するため、
# cmux new-workspace の --command で使うとNODE_OPTIONSが二重注入されて壊れる。
# そのため、シンボリックリンクを辿って実体（.jsファイル）を解決し、
# node <実体パス> の形式で実行することで競合を回避する。
def _resolve_claude_cmd():
  """claudeの実行コマンドリストを返す。
  シンボリックリンク経由でNode.jsのcli.jsに解決できた場合は ['node', '/path/to/cli.js']、
  それ以外は ['/path/to/claude'] を返す。
  """
  _CLAUDE_FALLBACK = '/Applications/cmux.app/Contents/Resources/bin/claude'
  found = shutil.which('claude') or _CLAUDE_FALLBACK

  # シンボリックリンクなら実体を解決する
  real = os.path.realpath(found)

  # 実体が .js ファイルなら node で実行する
  if real.endswith('.js') and os.path.isfile(real):
    return ['node', real]

  # フォールバック: 見つかったパスをそのまま使う
  return [found]

CLAUDE_CMD = _resolve_claude_cmd()


PROJECTS_DIR = Path.home() / '.claude' / 'projects'

# UUID形式の正規表現（xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx）
UUID_RE = re.compile(r'^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$', re.IGNORECASE)


def validate_session_id(session_id):
  """session_idがUUID形式かどうか検証する"""
  return bool(UUID_RE.match(session_id))

# system-reminder や ide_opened_file で始まるメッセージは往復数カウントから除外
EXCLUDE_PREFIXES = ('<system-reminder>', '<ide_opened_file>')


def extract_text_from_content(content):
  """contentがstr/listどちらでもテキストを返す"""
  if isinstance(content, str):
    return content
  if isinstance(content, list):
    for item in content:
      if isinstance(item, dict) and item.get('type') == 'text':
        return item.get('text', '')
  return ''


def is_user_message(entry):
  """外部ユーザーの発言かどうか判定（tool_resultは除外）"""
  if entry.get('userType') != 'external':
    return False
  msg = entry.get('message', {})
  if msg.get('role') != 'user':
    return False
  content = msg.get('content', '')
  text = extract_text_from_content(content)
  # tool_result（listでtool_result typeのもの）は除外
  if isinstance(content, list):
    for item in content:
      if isinstance(item, dict) and item.get('type') == 'tool_result':
        return False
  if not text:
    return False
  return True


def is_countable_message(text):
  """往復数カウント対象かどうか（除外プレフィックスチェック）"""
  stripped = text.strip()
  for prefix in EXCLUDE_PREFIXES:
    if stripped.startswith(prefix):
      return False
  return True


def truncate(text, length=60):
  """テキストをlength文字に切り詰める"""
  text = text.replace('\n', ' ').strip()
  if len(text) > length:
    return text[:length] + '...'
  return text


def extract_cwd_from_jsonl(jsonl_path):
  """jsonlの先頭数行から cwd フィールドを取得する。
  見つからない場合はフォルダ名からのフォールバックを返す。
  """
  try:
    with open(jsonl_path, encoding='utf-8') as f:
      for i, line in enumerate(f):
        if i >= 20:
          # 先頭20行以内に見つからなければ諦める
          break
        line = line.strip()
        if not line:
          continue
        try:
          entry = json.loads(line)
        except json.JSONDecodeError:
          continue
        cwd = entry.get('cwd')
        if cwd:
          return cwd
  except (OSError, IOError):
    pass

  # フォールバック: プロジェクトディレクトリ名からcwdを復元する
  # エンコーディングルール: 先頭の `-` → `/`、以降の `-` → `/`
  # ただしパスとして存在する候補を探す
  folder_name = jsonl_path.parent.name
  # 先頭の `-` をルート `/` に変換し、残りの `-` を `/` に変換
  if folder_name.startswith('-'):
    candidate = '/' + folder_name[1:].replace('-', '/')
    if Path(candidate).exists():
      return candidate
  return None


def shorten_cwd(cwd):
  """cwdをホームディレクトリ基準の短縮形にする（例: ~/.claude）"""
  if not cwd:
    return ''
  home = str(Path.home())
  if cwd.startswith(home):
    return '~' + cwd[len(home):]
  return cwd


def parse_session(jsonl_path, cached_mtime=None):
  """jsonlファイルを解析してセッション情報を返す。
  cached_mtimeが渡された場合はstat()を呼ばずそれを使用する。
  """
  user_messages = []
  # mtimeキャッシュがあればそちらを使い、I/Oを節約する
  if cached_mtime is not None:
    last_mtime = cached_mtime
  else:
    last_mtime = jsonl_path.stat().st_mtime

  # cwdをjsonlから取得する
  cwd = extract_cwd_from_jsonl(jsonl_path)

  try:
    with open(jsonl_path, encoding='utf-8') as f:
      for line in f:
        line = line.strip()
        if not line:
          continue
        try:
          entry = json.loads(line)
        except json.JSONDecodeError:
          continue

        if not is_user_message(entry):
          continue

        content = entry['message'].get('content', '')
        text = extract_text_from_content(content)
        if not text:
          continue

        user_messages.append(text)

  except (OSError, IOError):
    return None

  # 往復数カウント（除外対象を外したもの）
  countable = [m for m in user_messages if is_countable_message(m)]
  turn_count = len(countable)

  # 最初・最後のユーザー発言（除外済みリストから取る）
  first_msg = truncate(countable[0]) if countable else '(なし)'
  last_msg = truncate(countable[-1]) if countable else '(なし)'

  # セッションIDはファイル名から取得し、UUID形式かバリデーションする
  session_id = jsonl_path.stem
  if not validate_session_id(session_id):
    return None

  # 最終更新日時
  updated_at = datetime.fromtimestamp(last_mtime)

  return {
    'session_id': session_id,
    'updated_at': updated_at,
    'turn_count': turn_count,
    'first_msg': first_msg,
    'last_msg': last_msg,
    'path': jsonl_path,
    'cwd': cwd,
  }


def collect_sessions(limit=10):
  """全プロジェクトからjsonlを収集してセッション一覧を返す"""
  # (path, mtime) のタプルで収集し、ソート時のstat()を1回で済ませる
  jsonl_entries = []

  for project_dir in PROJECTS_DIR.iterdir():
    if not project_dir.is_dir():
      continue
    for f in project_dir.glob('*.jsonl'):
      # subagents配下は除外
      if 'subagents' in f.parts:
        continue
      try:
        mtime = f.stat().st_mtime
      except OSError:
        continue
      jsonl_entries.append((f, mtime))

  # 更新日時の新しい順にソート（mtimeはキャッシュ済みのため再stat不要）
  jsonl_entries.sort(key=lambda e: e[1], reverse=True)

  sessions = []
  for f, mtime in jsonl_entries:
    if len(sessions) >= limit:
      break
    # キャッシュしたmtimeをparse_sessionに渡してI/Oを節約
    info = parse_session(f, cached_mtime=mtime)
    if info is not None:
      sessions.append(info)

  return sessions


def display_sessions(sessions):
  """セッション一覧を表示する"""
  count = len(sessions)
  print(f'\n=== Claude Code セッション一覧（直近{count}件） ===\n')

  for i, s in enumerate(sessions, 1):
    dt = s['updated_at'].strftime('%m/%d %H:%M')
    short_id = s['session_id'][:8]
    turn = s['turn_count']
    cwd_label = f" [{shorten_cwd(s['cwd'])}]" if s.get('cwd') else ''
    print(f" {i:2}. [{dt}] {turn}往復 | ID: {short_id}{cwd_label}")
    print(f"     最初: {s['first_msg']}")
    print(f"     最後: {s['last_msg']}")
    print()


def resume_sessions(sessions, indices):
  """指定インデックスのセッションを再開する（1始まり）"""
  valid = []
  for idx in indices:
    if 1 <= idx <= len(sessions):
      valid.append(sessions[idx - 1])
    else:
      print(f"警告: {idx} は無効な番号です（1〜{len(sessions)}）")

  if not valid:
    print('再開対象がありません。')
    return

  # 1件・複数件ともに cmux new-workspace で別タブに開く
  for s in valid:
    session_id = s['session_id']
    cwd = s.get('cwd')
    # CLAUDE_CMD がリストなので、シェルがパースできる文字列に変換する
    # 例: ['node', '/opt/.../cli.js'] → 'node /opt/.../cli.js --resume <id>'
    # パスにスペースが含まれる可能性があるためシェルクォートする
    cmd = ' '.join(shlex.quote(p) for p in CLAUDE_CMD) + f' --resume {shlex.quote(session_id)}'
    print(f'ワークスペースを開きます: {session_id[:8]}')
    cmux_args = ['cmux', 'new-workspace']
    # cwd が取得できている場合は --cwd を付与してセッション再開が成功するようにする
    if cwd and Path(cwd).is_dir():
      cmux_args += ['--cwd', cwd]
    cmux_args += ['--command', cmd]
    subprocess.run(cmux_args)


def parse_indices(raw):
  """カンマ区切りの番号文字列をintリストに変換"""
  result = []
  for part in raw.split(','):
    part = part.strip()
    if part.isdigit():
      result.append(int(part))
  return result


def main():
  parser = argparse.ArgumentParser(
    description='Claude Code セッションブラウザ'
  )
  parser.add_argument(
    '--limit', type=int, default=10,
    help='表示するセッション数（デフォルト: 10）'
  )
  parser.add_argument(
    '--no-interactive', action='store_true',
    help='一覧表示のみ（再開プロンプトを出さない）'
  )
  parser.add_argument(
    '--resume', type=str, default=None,
    help='直接番号指定して再開（例: --resume 1 または --resume 1,3）'
  )
  args = parser.parse_args()

  # PROJECTS_DIRの存在チェック
  if not PROJECTS_DIR.exists():
    print(f'エラー: プロジェクトディレクトリが見つかりません: {PROJECTS_DIR}')
    sys.exit(1)

  # --resume 直接指定モード（一覧表示なし）
  if args.resume is not None:
    indices = parse_indices(args.resume)
    if not indices:
      print('エラー: --resume に有効な番号を指定してください。')
      sys.exit(1)
    # 必要な件数だけ取得
    needed = max(indices)
    sessions = collect_sessions(limit=needed)
    resume_sessions(sessions, indices)
    return

  # 通常モード: 一覧表示
  sessions = collect_sessions(limit=args.limit)

  if not sessions:
    print('セッションが見つかりませんでした。')
    sys.exit(0)

  display_sessions(sessions)

  # インタラクティブモード
  if not args.no_interactive:
    try:
      raw = input('再開するセッション番号を入力（複数はカンマ区切り、Enterでキャンセル）: ').strip()
    except (EOFError, KeyboardInterrupt):
      print()
      return

    if not raw:
      print('キャンセルしました。')
      return

    indices = parse_indices(raw)
    if not indices:
      print('有効な番号が入力されませんでした。')
      return

    resume_sessions(sessions, indices)


if __name__ == '__main__':
  main()
