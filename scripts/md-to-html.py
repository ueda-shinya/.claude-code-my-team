"""
md-to-html.py — Markdown → HTML 変換スクリプト（印刷PDF用）
使い方: python md-to-html.py <mdファイルパス>
出力:  同ディレクトリに <同名>.html を生成
"""

import sys
import html
import argparse
import string
from pathlib import Path
import markdown

# --- CSS: A4印刷・日本語対応 ---
CSS = """
@charset "UTF-8";

@page {
  size: A4;
  margin: 20mm 18mm;
}

*, *::before, *::after {
  box-sizing: border-box;
}

body {
  font-family: "Yu Gothic", "Meiryo", "ヒラギノ角ゴ Pro", sans-serif;
  font-size: 10.5pt;
  line-height: 1.7;
  color: #1a1a1a;
  text-align: justify;
  word-break: break-all;
  margin: 0;
  padding: 0;
}

/* --- 見出し --- */
h1 {
  font-size: 18pt;
  font-weight: bold;
  page-break-before: always;
  margin-top: 0;
  margin-bottom: 1em;
  padding-bottom: 0.3em;
  border-bottom: 2px solid #1a1a1a;
}

/* 最初のh1は改ページしない */
h1:first-of-type,
body > h1:first-child {
  page-break-before: avoid;
}

h2 {
  font-size: 13pt;
  font-weight: bold;
  margin-top: 1.5em;
  margin-bottom: 0.6em;
  padding-bottom: 0.2em;
  border-bottom: 1px solid #555;
}

h3 {
  font-size: 11.5pt;
  font-weight: bold;
  margin-top: 1.2em;
  margin-bottom: 0.4em;
}

h4 {
  font-size: 10.5pt;
  font-weight: bold;
  margin-top: 1em;
  margin-bottom: 0.3em;
}

/* --- 段落・リスト --- */
p {
  margin: 0.5em 0;
}

ul, ol {
  padding-left: 1.8em;
  margin: 0.5em 0;
}

li {
  margin: 0.2em 0;
}

/* --- 表 --- */
table {
  border-collapse: collapse;
  width: 100%;
  margin: 1em 0;
  page-break-inside: avoid;
  font-size: 9.5pt;
}

th, td {
  border: 1px solid #aaa;
  padding: 6px 10px;
  text-align: left;
  vertical-align: top;
}

th {
  background-color: #e8e8e8;
  font-weight: bold;
}

tr:nth-child(even) td {
  background-color: #f9f9f9;
}

/* --- コードブロック --- */
pre {
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 3px;
  padding: 10px 14px;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-all;
  font-family: "Consolas", "MS Gothic", "Courier New", monospace;
  font-size: 9pt;
  line-height: 1.5;
  margin: 0.8em 0;
  page-break-inside: avoid;
}

code {
  font-family: "Consolas", "MS Gothic", "Courier New", monospace;
  font-size: 9pt;
  background-color: #f5f5f5;
  padding: 1px 4px;
  border-radius: 2px;
}

pre code {
  background: none;
  padding: 0;
  border-radius: 0;
  font-size: inherit;
}

/* --- 引用 --- */
blockquote {
  border-left: 4px solid #888;
  margin: 0.8em 0;
  padding: 0.5em 1em;
  background-color: #f8f8f8;
  color: #444;
}

blockquote p {
  margin: 0;
}

/* --- 水平線 --- */
hr {
  border: none;
  border-top: 1px solid #ccc;
  margin: 1.2em 0;
}

/* --- リンク --- */
a {
  color: #0055aa;
  text-decoration: none;
}

/* --- 強調 --- */
strong {
  font-weight: bold;
}

em {
  font-style: italic;
}

/* --- 目次 --- */
.toc {
  background: #f8f8f8;
  border: 1px solid #ddd;
  padding: 12px 20px;
  margin: 1em 0 1.5em;
  page-break-inside: avoid;
}

.toc ul {
  margin: 0.3em 0;
}

/* --- 印刷調整 --- */
@media print {
  body {
    font-size: 10.5pt;
  }

  h1 {
    page-break-before: always;
  }

  h1:first-of-type {
    page-break-before: avoid;
  }

  h2, h3 {
    page-break-after: avoid;
  }

  p, li {
    orphans: 3;
    widows: 3;
  }

  table {
    page-break-inside: avoid;
  }

  pre {
    page-break-inside: avoid;
  }
}
"""

HTML_TEMPLATE = string.Template("""\
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>$title</title>
  <style>
$css
  </style>
</head>
<body>
$body
</body>
</html>
""")


def convert(md_path: Path) -> Path:
  """MDファイルをHTMLに変換して同ディレクトリに出力する"""
  # UTF-8で読み込み、失敗時はCP932（Windowsシフトジス）でリトライ
  try:
    text = md_path.read_text(encoding='utf-8')
  except UnicodeDecodeError:
    try:
      text = md_path.read_text(encoding='cp932')
    except UnicodeDecodeError as e:
      raise ValueError(
        f'ファイルの文字コードを特定できません（UTF-8 / CP932 いずれも失敗）: {md_path}'
      ) from e

  # markdown変換（拡張: テーブル / フェンスドコード / TOC / 属性リスト）
  md = markdown.Markdown(
    extensions=['tables', 'fenced_code', 'toc', 'attr_list'],
    extension_configs={
      'toc': {
        'title': '目次',
      }
    }
  )
  body_html = md.convert(text)

  # タイトル: 最初のh1テキストから取得、なければファイル名
  title_raw = md_path.stem
  lines = text.splitlines()
  for line in lines:
    stripped = line.strip()
    if stripped.startswith('# '):
      title_raw = stripped[2:].strip()
      break

  # XSS対策: title をHTMLエスケープ
  safe_title = html.escape(title_raw)

  html_output = HTML_TEMPLATE.substitute(
    title=safe_title,
    css=CSS,
    body=body_html,
  )

  out_path = md_path.with_suffix('.html')
  out_path.write_text(html_output, encoding='utf-8')
  return out_path


def main():
  parser = argparse.ArgumentParser(
    description='Markdown → HTML 変換スクリプト（印刷PDF用）'
  )
  parser.add_argument('md_file', help='変換するMarkdownファイルのパス')
  args = parser.parse_args()

  md_path = Path(args.md_file).resolve()

  if not md_path.exists():
    print(f'エラー: ファイルが見つかりません: {md_path}', file=sys.stderr)
    sys.exit(1)

  if not md_path.is_file():
    print(f'エラー: 通常ファイルではありません（ディレクトリ・シンボリックリンク不可）: {md_path}', file=sys.stderr)
    sys.exit(1)

  if md_path.suffix.lower() != '.md':
    print(f'警告: .md 以外のファイルですが続行します: {md_path}', file=sys.stderr)

  print(f'変換中: {md_path}')
  out_path = convert(md_path)
  print(f'出力完了: {out_path}')
  return out_path


if __name__ == '__main__':
  main()
