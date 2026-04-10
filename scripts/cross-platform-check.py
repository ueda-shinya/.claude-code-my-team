#!/usr/bin/env python3
# cross-platform-check.py
# git post-merge時にプラットフォーム非互換パターンを検出するスクリプト

import sys
import os
import re
import subprocess
import platform

try:
  sys.stdout.reconfigure(encoding='utf-8')
except AttributeError:
  pass

# 対象拡張子
TARGET_EXTENSIONS = {'.sh', '.py', '.bat', '.ps1'}

# プラットフォームスキップ宣言を確認する行数
PLATFORM_HEADER_LINES = 10


def load_pc_platform():
  """~/.claude/.env から PC_PLATFORM を取得する。未設定時は sys.platform ベースで判定する"""
  env_path = os.path.expanduser('~/.claude/.env')
  if os.path.exists(env_path):
    with open(env_path, encoding='utf-8', errors='replace') as f:
      for line in f:
        line = line.strip()
        if line.startswith('PC_PLATFORM='):
          return line.split('=', 1)[1].strip().strip('"\'')
  # .env に PC_PLATFORM がない場合は実行環境から自動判定
  return 'win' if platform.system() == 'Windows' else 'mac'


def get_changed_files():
  """git diff --name-only ORIG_HEAD..HEAD で変更ファイル一覧を取得する"""
  orig_head = os.path.expanduser('~/.claude/.git/ORIG_HEAD')
  if not os.path.exists(orig_head):
    # ORIG_HEAD がない場合（初回clone等）は無害終了
    return None

  try:
    result = subprocess.run(
      ['git', 'diff', '--name-only', 'ORIG_HEAD..HEAD'],
      cwd=os.path.expanduser('~/.claude'),
      capture_output=True,
      text=True,
      encoding='utf-8'
    )
    if result.returncode != 0:
      return None
    files = [f.strip() for f in result.stdout.splitlines() if f.strip()]
    return files
  except Exception:
    return None


def has_platform_skip_header(lines, platform):
  """先頭10行以内にプラットフォームスキップ宣言があればTrueを返す"""
  for line in lines[:PLATFORM_HEADER_LINES]:
    if f'# platform: {platform}-only' in line:
      return True
  return False


def is_comment_line(line):
  """行がコメント行かどうか判定する"""
  return line.lstrip().startswith('#')


def has_noqa(line):
  """行に # noqa: cross-platform があるかチェック"""
  return '# noqa: cross-platform' in line


def build_patterns_mac():
  """
  PC_PLATFORM=mac 用の検出パターン
  Win→Mac 方向の非互換を検出
  返り値: list of (pattern_regex, description, ext_filter, skip_if_comment)
  """
  return [
    # .sh ファイル内: `python ` または `python -` の呼出（python3でないもの、$PYTHON変数除外）
    {
      'exts': {'.sh'},
      'regex': re.compile(r'(?<!\$)(?<!\w)python\s+(?!3)(?!--version)'),
      'desc': '`python ` → Macでは `python3` のみ利用可能',
      'skip_if_comment': True,
    },
    {
      'exts': {'.sh'},
      'regex': re.compile(r'(?<!\$)(?<!\w)python\s*-(?!3)(?!-)'),
      'desc': '`python -` → Macでは `python3` のみ利用可能',
      'skip_if_comment': True,
    },
    # .py ファイル内: subprocess系で "python" を引数に使用（python3・sys.executable でないもの）
    {
      'exts': {'.py'},
      'regex': re.compile(r'subprocess[^#\n]*["\']python["\'](?!3)'),
      'desc': 'subprocess内 `"python"` → `python3` または `sys.executable` を使用してください',
      'skip_if_comment': True,
    },
    {
      'exts': {'.py'},
      'regex': re.compile(r'\[\"python\"\]|\[\'python\'\]'),
      'desc': 'subprocess内 `["python"]` → `sys.executable` を使用してください',
      'skip_if_comment': True,
    },
    # taskkill コマンド
    {
      'exts': {'.sh', '.py', '.bat', '.ps1'},
      'regex': re.compile(r'\btaskkill\b'),
      'desc': '`taskkill` → Mac環境では使用不可',
      'skip_if_comment': False,
    },
    # ハードコードパス: C:\ X:\ C:/Users X:/
    {
      'exts': {'.sh', '.py', '.bat', '.ps1'},
      'regex': re.compile(r'[CX]:\\|[CX]:/Users', re.IGNORECASE),
      'desc': 'Windowsハードコードパス → Mac環境では使用不可 ※文字列内の可能性あり',
      'skip_if_comment': False,
    },
    # strftime 内の %-m %-d %-H 等（Linux専用フォーマット）
    {
      'exts': {'.py', '.sh'},
      'regex': re.compile(r'strftime\s*\([^)]*%-[mdHIMSj]'),
      'desc': '`strftime` 内の `%-m` 等 → Linux専用フォーマット（Macでは動作が異なる場合あり）',
      'skip_if_comment': True,
    },
  ]


def build_patterns_win():
  """
  PC_PLATFORM=win 用の検出パターン
  Mac→Win 方向の非互換を検出
  """
  return [
    # .py ファイル内: subprocess系で "python3" を引数に使用
    {
      'exts': {'.py'},
      'regex': re.compile(r'subprocess[^#\n]*["\']python3["\']'),
      'desc': 'subprocess内 `"python3"` → Windowsでは `sys.executable` を使用してください',
      'skip_if_comment': True,
    },
    {
      'exts': {'.py'},
      'regex': re.compile(r'\[\"python3\"\]|\[\'python3\'\]'),
      'desc': 'subprocess内 `["python3"]` → `sys.executable` を使用してください',
      'skip_if_comment': True,
    },
    # Mac固有コマンド
    {
      'exts': {'.sh', '.py'},
      'regex': re.compile(r'\bopen\s+-a\b'),
      'desc': '`open -a` → Mac固有コマンド、Windows環境では使用不可',
      'skip_if_comment': True,
    },
    {
      'exts': {'.sh', '.py'},
      'regex': re.compile(r'\bpbcopy\b|\bpbpaste\b'),
      'desc': '`pbcopy`/`pbpaste` → Mac固有コマンド、Windows環境では使用不可',
      'skip_if_comment': True,
    },
    {
      'exts': {'.sh', '.py'},
      'regex': re.compile(r'\bdefaults\s+(write|read)\b'),
      'desc': '`defaults write/read` → Mac固有コマンド、Windows環境では使用不可',
      'skip_if_comment': True,
    },
    {
      'exts': {'.sh', '.py'},
      'regex': re.compile(r'\bbrew\s+'),
      'desc': '`brew` → Homebrew、Mac固有パッケージマネージャ',
      'skip_if_comment': True,
    },
    # ハードコードパス: /Users/（コメント行を除外）
    {
      'exts': {'.sh', '.py', '.bat', '.ps1'},
      'regex': re.compile(r'/Users/'),
      'desc': '`/Users/` ハードコードパス → Windows環境では使用不可 ※文字列内の可能性あり',
      'skip_if_comment': True,
    },
  ]


def check_file(filepath, patterns, platform):
  """
  ファイルを読み込んでパターン検出を行い、問題箇所リストを返す
  返り値: list of (lineno, line_content, description)
  """
  try:
    with open(filepath, encoding='utf-8', errors='replace') as f:
      lines = f.readlines()
  except Exception:
    return []

  # プラットフォームスキップ宣言チェック
  opposite = 'win' if platform == 'mac' else 'mac'
  if has_platform_skip_header(lines, opposite):
    return []

  ext = os.path.splitext(filepath)[1].lower()
  issues = []

  for pattern_def in patterns:
    # 対象拡張子チェック
    if ext not in pattern_def['exts']:
      continue

    regex = pattern_def['regex']
    desc = pattern_def['desc']
    skip_if_comment = pattern_def['skip_if_comment']

    for lineno, line in enumerate(lines, start=1):
      stripped = line.rstrip('\n')

      # noqa チェック
      if has_noqa(stripped):
        continue

      # コメント行スキップ判定
      if skip_if_comment and is_comment_line(stripped):
        continue

      if regex.search(stripped):
        issues.append((lineno, stripped.strip(), desc))

  return issues


def check_single_file(filepath, platform):
  """--test モード用: 単一ファイルをチェックして結果を出力する"""
  if not os.path.exists(filepath):
    print(f'ファイルが見つかりません: {filepath}')
    sys.exit(1)

  ext = os.path.splitext(filepath)[1].lower()
  if ext not in TARGET_EXTENSIONS:
    print(f'対象外の拡張子です: {ext}')
    sys.exit(0)

  patterns = build_patterns_mac() if platform == 'mac' else build_patterns_win()
  issues = check_file(filepath, patterns, platform)

  if not issues:
    print(f'問題なし: {filepath}')
    sys.exit(0)
  else:
    print('【クロスプラットフォームチェック】')
    print('以下のファイルにプラットフォーム非互換の可能性があります：')
    for lineno, line_content, desc in issues:
      print(f'- {filepath}:{lineno}  {desc}')
      print(f'  該当行: {line_content}')
    sys.exit(1)


def main():
  # --test モード: python cross-platform-check.py --test <filepath>
  if len(sys.argv) >= 3 and sys.argv[1] == '--test':
    target_file = sys.argv[2]
    platform = load_pc_platform()
    check_single_file(target_file, platform)
    return

  platform = load_pc_platform()
  changed_files = get_changed_files()

  # ORIG_HEAD がない場合は無害終了
  if changed_files is None:
    sys.exit(0)

  # 対象拡張子のみ抽出
  target_files = [
    f for f in changed_files
    if os.path.splitext(f)[1].lower() in TARGET_EXTENSIONS
  ]

  # 対象ファイルが0件の場合は無害終了
  if not target_files:
    sys.exit(0)

  patterns = build_patterns_mac() if platform == 'mac' else build_patterns_win()

  all_issues = {}
  repo_root = os.path.expanduser('~/.claude')

  for rel_path in target_files:
    abs_path = os.path.join(repo_root, rel_path)
    if not os.path.exists(abs_path):
      continue
    issues = check_file(abs_path, patterns, platform)
    if issues:
      all_issues[rel_path] = issues

  if not all_issues:
    sys.exit(0)

  # 問題あり: レポート出力
  print('【クロスプラットフォームチェック】')
  print('以下のファイルにプラットフォーム非互換の可能性があります：')
  for rel_path, issues in all_issues.items():
    for lineno, line_content, desc in issues:
      print(f'- {rel_path}:{lineno}  {desc}')
  sys.exit(1)


if __name__ == '__main__':
  main()
