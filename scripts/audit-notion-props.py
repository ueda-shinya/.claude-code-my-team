#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Notionプロパティ名ハードコード監査スクリプト

目的:
  コードベース全体で Notion API のプロパティ名がハードコードされている箇所を
  一括検出し、Phase 1-A（notion_schema.py 単一ソース化）の前提情報を提供する。

背景:
  2026-04-21 kaizen にて、議事録DBプロパティ名の不整合（'日時' vs '日付'）が
  3ファイル・24〜26日間未検知だったインシデントが判明。
  本スクリプトは対策S3（Phase 0）として既存コードを一括監査するもの。

使い方:
  python audit-notion-props.py [--root ディレクトリ] [--no-report]
"""

import re
import os
import sys
import argparse
from pathlib import Path
from datetime import datetime
from collections import defaultdict

# Windows環境での文字化け対策
try:
  sys.stdout.reconfigure(encoding='utf-8')
except AttributeError:
  # Python 3.6 以前や一部環境では reconfigure 未対応のためスキップ
  pass


# ===========================================================================
# 定数・設定
# ===========================================================================

# デフォルト検索ルート
DEFAULT_ROOT = Path.home() / '.claude'

# 除外ディレクトリ名（いずれかに一致するディレクトリを探索対象から外す）
# 暗黙の startswith('.') による除外は廃止し、除外意図を定数で明示する
EXCLUDE_DIRS = frozenset({
  '.git',
  '.github',
  '.vscode',
  '.mypy_cache',
  '.pytest_cache',
  '.venv',
  '__pycache__',
  'venv',
  'node_modules',
  'sessions',
  'tmp',
  'onedrive-mcp-venv',  # 案A: 変則名venvを明示除外
  'reports',  # 本スクリプト自身のレポート出力先(REPORT_DIR)を除外。
              # Notionプロパティを含むコードはreports/配下に置かない前提。
              # REPORT_DIR変更時はこの除外も再検討すること。
})

# 検索対象ファイル拡張子
TARGET_EXTENSIONS = frozenset({'.py', '.md'})

# レポート出力先
REPORT_DIR = Path.home() / '.claude' / 'reports'

# ---------------------------------------------------------------------------
# Notion プロパティ参照の正規表現パターン一覧
#
# 「完璧な構文解析より広めに拾って人が確認」方針のため、
# 意図的に緩めのパターンを採用している。
# 各パターンのキャプチャグループ1がプロパティ名になる。
# ---------------------------------------------------------------------------
PATTERNS = [
  # パターン1: フィルタ・ソート指定
  #   例: {'property': '日付', 'date': ...}
  #   例: "property": "ステータス"
  (
    re.compile(r"""['"]\s*property\s*['"]\s*:\s*['"]([^'"]+)['"]"""),
    'filter/sort指定 (property: ...)',
  ),

  # パターン2: ページ作成・更新時のプロパティキー
  #   例: 'properties': { '日付': { 'date': ... } }
  #   例: properties["タイトル"] = ...
  #   ※ dict リテラル内の最初のキーのみ拾う（緩めに検出）
  (
    re.compile(r"""['"]\s*properties\s*['"]\s*:\s*\{\s*['"]([^'"]+)['"]"""),
    'properties dict キー (page作成/更新)',
  ),

  # パターン3: Notion API レスポンス処理 - .get() アクセス
  #   例: properties.get('日付')
  #   例: props.get("ステータス")
  (
    re.compile(r"""(?:properties|props)\s*\.get\s*\(\s*['"]([^'"]+)['"]\s*\)"""),
    'レスポンス処理 (.get())',
  ),

  # パターン4: Notion API レスポンス処理 - [] アクセス
  #   例: p["日付"]
  #   例: properties["タイトル"]
  #   ※ 変数名に p / props / properties / prop を許容
  (
    re.compile(r"""(?:properties|props|prop|p)\s*\[\s*['"]([^'"]+)['"]\s*\]"""),
    'レスポンス処理 ([] アクセス)',
  ),

  # パターン5: Notion フィルタ配列への追加パターン
  #   例: filters.append({'property': '優先度', ...})
  #   ※ パターン1で大半は拾えるが、append形式のインライン検出補完として残す
  #   → パターン1と重複する場合があるが、後でプロパティ名で集約するため問題なし
  (
    re.compile(r"""filters\s*\.\s*append\s*\(\s*\{[^}]*['"]\s*property\s*['"]\s*:\s*['"]([^'"]+)['"]"""),
    'filters.append() 内 property',
  ),

  # パターン6: 日本語キー辞書アクセス（変数名を問わない・広めに拾う）
  #   例: item['ステータス'] / row['担当者'] / data['日付']
  #   対象: 漢字・ひらがな・カタカナ・全角記号で始まるキー、またはタイトルケース英字で始まるキー
  #   ※ 偽陽性（os.environ['PATH'] 等）は増えるが「広めに拾って人が確認」方針と整合
  #   ※ パターン4との重複はプロパティ名集約で吸収
  (
    re.compile(r"""\w+\s*\[\s*['"]([぀-ゟ゠-ヿ一-鿿々〆〤ーA-Z][^'"]*)['"]\s*\]"""),
    '辞書アクセス（日本語/大文字開始キー）',
  ),
]

# Markdown コードブロック開始・終了を判定する正規表現
# 言語指定に関わらずすべての fenced code block を対象とする
# （json / javascript 等で書かれた Notion API サンプルも取りこぼさないため）
RE_CODE_BLOCK_START = re.compile(r'^\s*```[\w+-]*\s*$')
RE_CODE_BLOCK_END = re.compile(r'^\s*```\s*$')


# ===========================================================================
# FileScanner モジュール
#
# 責務: 検索対象ファイルの列挙のみ。
# ディレクトリ除外ロジックと拡張子フィルタをここに集約し、
# 呼び出し側はファイルパスのリストだけを受け取る。
# ===========================================================================

def collect_target_files(root: Path) -> list[Path]:
  """
  root 配下の対象ファイルを再帰的に列挙する。

  除外ディレクトリ（EXCLUDE_DIRS）配下は探索しない。
  対象拡張子（TARGET_EXTENSIONS）のファイルのみ返す。
  """
  result = []
  for dirpath, dirnames, filenames in os.walk(root):
    # 除外ディレクトリをその場で除去（os.walk の探索を打ち切る）
    # EXCLUDE_DIRS 定数のみで制御し、暗黙の startswith('.') 除外は使用しない
    dirnames[:] = [d for d in dirnames if d not in EXCLUDE_DIRS]
    for fname in filenames:
      fpath = Path(dirpath) / fname
      if fpath.suffix not in TARGET_EXTENSIONS:
        continue
      # 案B: site-packages を含むパスは除外(venv命名に依存しない二重防御)
      # 理由: 案A(EXCLUDE_DIRS)はディレクトリ名一致のため、将来新しい
      # 命名のvenv(.venv-xxx等)が追加された際に漏れる可能性がある。
      # site-packagesはPython仮想環境の普遍的な構造であり、
      # これを追加フィルタとすることで命名揺れに対する耐性を得る。
      # fpath.parts でパス要素を判定するため Windows/Mac 両方で動作する
      if 'site-packages' in fpath.parts:
        continue
      result.append(fpath)
  return sorted(result)


# ===========================================================================
# PatternMatcher モジュール
#
# 責務: 1ファイルを受け取り、プロパティ名のマッチ結果を返す。
# 正規表現パターン（PATTERNS）の知識をここに閉じ込め、
# .md ファイルのコードブロック処理もここで吸収する。
# ===========================================================================

class Match:
  """1件のプロパティ検出結果を表す値オブジェクト"""
  __slots__ = ('file', 'line_no', 'prop_name', 'pattern_label', 'raw_line')

  def __init__(self, file: Path, line_no: int, prop_name: str,
               pattern_label: str, raw_line: str):
    self.file = file
    self.line_no = line_no
    self.prop_name = prop_name
    self.pattern_label = pattern_label
    self.raw_line = raw_line.rstrip()


def scan_file(fpath: Path) -> list[Match]:
  """
  ファイル1件を走査し、プロパティ参照の一覧を返す。

  .md ファイルは ```python / ```bash ブロック内の行のみを対象とする。
  .py ファイルは全行を対象とする。
  """
  try:
    text = fpath.read_text(encoding='utf-8', errors='replace')
  except OSError as e:
    # 読み取り不可のファイルはスキップし、警告を出力（権限エラー等）
    print(f'[WARN] 読み込みスキップ: {fpath} ({e})', file=sys.stderr)
    return []

  lines = text.splitlines()
  is_md = fpath.suffix == '.md'
  matches = []

  in_code_block = False  # .md のコードブロック内フラグ

  for line_no, line in enumerate(lines, start=1):
    # .md: コードブロックの開始・終了を追跡
    if is_md:
      if not in_code_block:
        if RE_CODE_BLOCK_START.match(line):
          in_code_block = True
        continue  # コードブロック外はスキップ
      else:
        if RE_CODE_BLOCK_END.match(line):
          in_code_block = False
          continue

    # 各パターンでマッチを試みる
    for pattern, label in PATTERNS:
      for m in pattern.finditer(line):
        prop_name = m.group(1)
        # 空文字・スペースのみは除外
        if prop_name.strip():
          matches.append(Match(
            file=fpath,
            line_no=line_no,
            prop_name=prop_name,
            pattern_label=label,
            raw_line=line,
          ))

  return matches


# ===========================================================================
# ReportGenerator モジュール
#
# 責務: Match リストを受け取り、標準出力サマリとMarkdownレポートを生成する。
# 集計ロジックとフォーマット知識をここに閉じ込める。
# ===========================================================================

def _relative_display_path(fpath: Path, root: Path) -> str:
  """レポート表示用の相対パスを返す（~/.claude/ からの相対）"""
  try:
    return str(fpath.relative_to(root))
  except ValueError:
    return str(fpath)


def _aggregate(matches: list[Match], root: Path) -> dict:
  """
  Match リストを集計してレポート生成用の辞書を返す。

  返り値:
    {
      'by_prop': {prop_name: [Match, ...]},        # プロパティ名別
      'by_file': {display_path: [Match, ...]},     # ファイル別
      'total_files': int,
    }
  """
  by_prop: dict[str, list[Match]] = defaultdict(list)
  by_file: dict[str, list[Match]] = defaultdict(list)

  for m in matches:
    by_prop[m.prop_name].append(m)
    display = _relative_display_path(m.file, root)
    by_file[display].append(m)

  return {
    'by_prop': dict(sorted(by_prop.items(), key=lambda kv: -len(kv[1]))),
    'by_file': dict(sorted(by_file.items())),
    'total_files': len(by_file),
  }


def print_summary(matches: list[Match], root: Path, scanned_file_count: int) -> None:
  """標準出力に要約サマリを出力する"""
  agg = _aggregate(matches, root)

  print('=' * 60)
  print('Notion プロパティ名ハードコード監査 — サマリ')
  print('=' * 60)
  print(f'検索ルート    : {root}')
  print(f'スキャンファイル数: {scanned_file_count}件')
  print(f'検出件数      : {len(matches)}件')
  print(f'対象ファイル数  : {agg["total_files"]}件')
  print()

  if not matches:
    print('検出なし。')
    return

  print('【プロパティ名別 出現回数（上位20件）】')
  print(f'  {"プロパティ名":<20} {"出現回数":>6} {"ファイル数":>6}')
  print(f'  {"-"*20} {"------":>6} {"------":>6}')
  for prop, ms in list(agg['by_prop'].items())[:20]:
    file_cnt = len({m.file for m in ms})
    print(f'  {prop:<20} {len(ms):>6} {file_cnt:>6}')
  print()

  print('【ファイル別 検出件数】')
  for display, ms in agg['by_file'].items():
    print(f'  {display} ({len(ms)}件)')
  print()


def build_markdown_report(matches: list[Match], root: Path,
                           scanned_file_count: int) -> str:
  """Markdown形式のレポート文字列を生成して返す"""
  now = datetime.now().strftime('%Y-%m-%d %H:%M')
  agg = _aggregate(matches, root)

  lines = []
  lines.append(f'# Notionプロパティ名監査レポート {now}')
  lines.append('')
  lines.append('## サマリ')
  lines.append(f'- 対象ディレクトリ: {root}')
  lines.append(f'- 検索対象拡張子: {", ".join(sorted(TARGET_EXTENSIONS))}')
  lines.append(f'- スキャンファイル数: {scanned_file_count}件')
  lines.append(f'- 検出件数: {len(matches)}件')
  lines.append(f'- 対象ファイル数: {agg["total_files"]}件')
  lines.append('')

  if not matches:
    lines.append('検出なし。')
    return '\n'.join(lines)

  # プロパティ名別集計テーブル
  lines.append('## プロパティ名別集計')
  lines.append('')
  lines.append('| プロパティ名 | 出現回数 | 出現ファイル数 |')
  lines.append('|---|---|---|')
  for prop, ms in agg['by_prop'].items():
    file_cnt = len({m.file for m in ms})
    lines.append(f'| {prop} | {len(ms)} | {file_cnt} |')
  lines.append('')

  # ファイル別詳細
  lines.append('## ファイル別詳細')
  lines.append('')
  for display, ms in agg['by_file'].items():
    lines.append(f'### {display}')
    # 行番号順・パターン名順にソート（行番号が同一の場合に順序を安定化）
    for m in sorted(ms, key=lambda x: (x.line_no, x.pattern_label)):
      lines.append(f'- L{m.line_no}: `{m.prop_name}` — {m.pattern_label}')
      # 生の行を折り返しなしで添付（長い場合は120文字で切る）
      raw = m.raw_line.strip()
      if len(raw) > 120:
        raw = raw[:117] + '...'
      # コードブロック内に ``` が含まれる場合はバックスラッシュでエスケープ
      raw = raw.replace('```', r'\`\`\`')
      lines.append(f'  ```')
      lines.append(f'  {raw}')
      lines.append(f'  ```')
    lines.append('')

  return '\n'.join(lines)


def write_report(content: str) -> Path:
  """レポートを ~/.claude/reports/ に書き出し、パスを返す"""
  REPORT_DIR.mkdir(parents=True, exist_ok=True)
  date_str = datetime.now().strftime('%Y%m%d')
  report_path = REPORT_DIR / f'notion-props-audit-{date_str}.md'
  report_path.write_text(content, encoding='utf-8')
  return report_path


# ===========================================================================
# エントリポイント
# ===========================================================================

def parse_args() -> argparse.Namespace:
  parser = argparse.ArgumentParser(
    description='Notionプロパティ名ハードコード箇所を一括監査する'
  )
  parser.add_argument(
    '--root',
    type=Path,
    default=DEFAULT_ROOT,
    help=f'検索ルートディレクトリ（デフォルト: {DEFAULT_ROOT}）',
  )
  parser.add_argument(
    '--no-report',
    action='store_true',
    help='ファイル出力をスキップし、標準出力のみ表示する',
  )
  return parser.parse_args()


def main() -> None:
  args = parse_args()
  root = args.root.expanduser().resolve()

  if not root.is_dir():
    print(f'[ERROR] ディレクトリが存在しません: {root}', file=sys.stderr)
    sys.exit(1)

  print(f'スキャン開始: {root}')
  print(f'除外ディレクトリ: {", ".join(sorted(EXCLUDE_DIRS))}')
  print()

  # ファイル列挙
  target_files = collect_target_files(root)
  print(f'{len(target_files)}件のファイルを検索します...')

  # パターンマッチング
  all_matches: list[Match] = []
  for fpath in target_files:
    file_matches = scan_file(fpath)
    all_matches.extend(file_matches)

  print(f'スキャン完了。{len(all_matches)}件のプロパティ参照を検出しました。')
  print()

  # 標準出力サマリ
  print_summary(all_matches, root, len(target_files))

  # ファイル出力
  if not args.no_report:
    report_content = build_markdown_report(all_matches, root, len(target_files))
    report_path = write_report(report_content)
    print(f'レポートを出力しました: {report_path}')


if __name__ == '__main__':
  main()
