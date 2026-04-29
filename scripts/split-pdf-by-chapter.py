"""
split-pdf-by-chapter.py
=======================
PDF を章ごと（またはページ数単位）に分割するスクリプト。

使い方:
    python split-pdf-by-chapter.py --input <PDFパス> --output-dir <出力先>
    python split-pdf-by-chapter.py --input <PDFパス> --output-dir <出力先> --archive-dir <アーカイブ先>
    python split-pdf-by-chapter.py --input <PDFパス> --output-dir <出力先> --fallback-pages-per-chunk 10

引数:
    --input                : 分割対象のPDFパス（必須）
    --output-dir           : 分割後PDFの出力ディレクトリ（必須）
    --archive-dir          : 元PDFを移動するアーカイブ先ディレクトリ（省略時: 移動しない）
    --max-size-mb          : この MB を超える章を細分化する閾値（デフォルト: 5）
    --fallback-pages-per-chunk : 目次なし時の均等分割ページ数（デフォルト: 10）
    --chapters             : 手動で章定義を指定（JSON形式例: '[{"title":"第1章","start":1,"end":10}]'）
                             ページ番号は1始まり

出力例:
    事業戦略パーフェクトガイド_第01章_なぜ今あらためて事業戦略なのか.pdf
    事業戦略パーフェクトガイド_第02章_事業戦略パーフェクトガイド_part1.pdf
    事業戦略パーフェクトガイド_第02章_事業戦略パーフェクトガイド_part2.pdf

制限事項:
    - pypdf ライブラリが必要: pip install pypdf
    - アウトライン（目次）がないPDFはフォールバック（均等分割）か --chapters で手動指定が必要
    - 分割中にエラーが発生した場合は途中出力をロールバック（削除）する
    - 元PDFのアーカイブは --archive-dir を指定した場合のみ実施
"""

import sys
import os
import re
import json
import shutil
import argparse
import pathlib
from typing import Optional


def sanitize_filename(name: str, max_len: int = 30) -> str:
    """Windowsで使えない文字を置換し、最大文字数で切る"""
    # Windowsで使えない文字: / \ : * ? " < > |
    sanitized = re.sub(r'[/\\:*?"<>|]', '-', name)
    # 前後の空白・ドットを除去
    sanitized = sanitized.strip(' .')
    # 最大文字数
    if len(sanitized) > max_len:
        sanitized = sanitized[:max_len]
    return sanitized


def get_pdf_outline_chapters(reader) -> list[dict]:
    """PDFアウトライン（目次）から最上位章リストを取得する"""
    outline = reader.outline
    if not outline:
        return []

    chapters = []
    for item in outline:
        # リスト（子要素）はスキップ
        if isinstance(item, list):
            continue
        try:
            page_num = reader.get_destination_page_number(item)
            title = item.title if hasattr(item, 'title') else str(item)
            chapters.append({'title': title, 'start_page': page_num})
        except Exception:
            continue

    # 終了ページを計算（次の章の開始ページ - 1、最終章は最終ページ）
    total_pages = len(reader.pages)
    for i, ch in enumerate(chapters):
        if i + 1 < len(chapters):
            ch['end_page'] = chapters[i + 1]['start_page'] - 1
        else:
            ch['end_page'] = total_pages - 1  # 0始まり

    return chapters


def get_fallback_chapters(total_pages: int, pages_per_chunk: int) -> list[dict]:
    """目次なし時の均等分割チャプターリストを生成する（1始まりで返す）"""
    chapters = []
    chunk_num = 1
    start = 1
    while start <= total_pages:
        end = min(start + pages_per_chunk - 1, total_pages)
        chapters.append({
            'title': f'part{chunk_num:02d}',
            'start_page_1': start,
            'end_page_1': end,
        })
        start = end + 1
        chunk_num += 1
    return chapters


def write_chapter_pdf(reader, start_0: int, end_0: int, output_path: pathlib.Path) -> int:
    """
    指定ページ範囲（0始まり）を出力先に保存する。
    戻り値: 保存したファイルサイズ（バイト）
    """
    from pypdf import PdfWriter
    writer = PdfWriter()
    for page_idx in range(start_0, end_0 + 1):
        writer.add_page(reader.pages[page_idx])
    with open(output_path, 'wb') as f:
        writer.write(f)
    return output_path.stat().st_size


def split_chapter(
    reader,
    chapter_idx: int,
    chapter_title: str,
    start_0: int,
    end_0: int,
    stem: str,
    output_dir: pathlib.Path,
    max_size_bytes: int,
    pages_per_subchunk: int = 10,
) -> list[pathlib.Path]:
    """
    1章分をファイルに出力する。5MB超えなら10ページ単位に細分化。
    戻り値: 生成したPathのリスト
    """
    safe_title = sanitize_filename(chapter_title)
    out_paths = []

    chapter_label = f'第{chapter_idx:02d}章'
    total_chapter_pages = end_0 - start_0 + 1

    # まず全ページで試し書きしてサイズ確認
    base_filename = f'{stem}_{chapter_label}_{safe_title}.pdf'
    base_path = output_dir / base_filename

    tmp_path = output_dir / f'_tmp_{chapter_label}.pdf'
    size = write_chapter_pdf(reader, start_0, end_0, tmp_path)

    if size <= max_size_bytes:
        # サイズOK: そのままリネーム
        tmp_path.rename(base_path)
        print(f'  -> {base_filename} ({size / 1024 / 1024:.2f} MB, {total_chapter_pages}ページ)')
        out_paths.append(base_path)
    else:
        # サイズ超過: 10ページ単位に細分化
        tmp_path.unlink(missing_ok=True)
        print(f'  -> {base_filename} は {size / 1024 / 1024:.2f} MB 超過 → 細分化します')
        part_num = 1
        sub_start = start_0
        while sub_start <= end_0:
            sub_end = min(sub_start + pages_per_subchunk - 1, end_0)
            part_filename = f'{stem}_{chapter_label}_{safe_title}_part{part_num}.pdf'
            part_path = output_dir / part_filename
            part_size = write_chapter_pdf(reader, sub_start, sub_end, part_path)
            sub_pages = sub_end - sub_start + 1
            print(f'     part{part_num}: {part_filename} ({part_size / 1024 / 1024:.2f} MB, {sub_pages}ページ)')
            out_paths.append(part_path)
            sub_start = sub_end + 1
            part_num += 1

    return out_paths


def main():
    parser = argparse.ArgumentParser(description='PDF を章ごとに分割するスクリプト')
    parser.add_argument('--input', required=True, help='分割対象のPDFパス')
    parser.add_argument('--output-dir', required=True, help='分割後PDFの出力ディレクトリ')
    parser.add_argument('--archive-dir', default=None, help='元PDFを移動するアーカイブ先ディレクトリ（省略時: 移動しない）')
    parser.add_argument('--max-size-mb', type=float, default=5.0, help='細分化閾値MB（デフォルト: 5）')
    parser.add_argument('--fallback-pages-per-chunk', type=int, default=10, help='目次なし時の均等分割ページ数（デフォルト: 10）')
    parser.add_argument('--chapters', default=None,
                        help='手動章定義JSON: \'[{"title":"章名","start":1,"end":10},...]\' （ページ番号は1始まり）')
    args = parser.parse_args()

    # ライブラリの遅延インポート（エラーを明確化するため）
    try:
        from pypdf import PdfReader
    except ImportError:
        print('[FAIL] pypdf がインストールされていません。pip install pypdf を実行してください。')
        sys.exit(1)

    input_path = pathlib.Path(args.input).expanduser().resolve()
    output_dir = pathlib.Path(args.output_dir).expanduser().resolve()
    archive_dir = pathlib.Path(args.archive_dir).expanduser().resolve() if args.archive_dir else None
    max_size_bytes = int(args.max_size_mb * 1024 * 1024)

    # 入力ファイル確認
    if not input_path.exists():
        print(f'[FAIL] 入力ファイルが見つかりません: {input_path}')
        sys.exit(1)

    # 出力ディレクトリ作成
    output_dir.mkdir(parents=True, exist_ok=True)

    # PDF読み込み
    print(f'[読み込み] {input_path}')
    reader = PdfReader(str(input_path))
    total_pages = len(reader.pages)
    print(f'  総ページ数: {total_pages}')

    stem = input_path.stem  # 拡張子なしのファイル名

    # 章定義を決定
    if args.chapters:
        # 手動指定
        raw_chapters = json.loads(args.chapters)
        chapters = [
            {
                'title': ch['title'],
                'start_0': ch['start'] - 1,
                'end_0': ch['end'] - 1,
            }
            for ch in raw_chapters
        ]
        print(f'[章定義] 手動指定: {len(chapters)} 章')
    else:
        # アウトライン取得を試みる
        outline_chapters = get_pdf_outline_chapters(reader)
        if outline_chapters:
            chapters = [
                {
                    'title': ch['title'],
                    'start_0': ch['start_page'],
                    'end_0': ch['end_page'],
                }
                for ch in outline_chapters
            ]
            print(f'[章定義] アウトラインから取得: {len(chapters)} 章')
        else:
            # フォールバック: 均等分割
            print(f'[章定義] アウトラインなし → {args.fallback_pages_per_chunk} ページ単位で均等分割')
            fallback = get_fallback_chapters(total_pages, args.fallback_pages_per_chunk)
            chapters = [
                {
                    'title': ch['title'],
                    'start_0': ch['start_page_1'] - 1,
                    'end_0': ch['end_page_1'] - 1,
                }
                for ch in fallback
            ]
            print(f'  -> {len(chapters)} チャンクに分割')

    # 分割実行（エラー時はロールバック）
    generated_paths: list[pathlib.Path] = []
    try:
        print(f'\n[分割開始] {len(chapters)} 章')
        for idx, ch in enumerate(chapters, start=1):
            print(f'\n第{idx:02d}章: {ch["title"]} (p{ch["start_0"] + 1}〜p{ch["end_0"] + 1})')
            paths = split_chapter(
                reader=reader,
                chapter_idx=idx,
                chapter_title=ch['title'],
                start_0=ch['start_0'],
                end_0=ch['end_0'],
                stem=stem,
                output_dir=output_dir,
                max_size_bytes=max_size_bytes,
                pages_per_subchunk=10,
            )
            generated_paths.extend(paths)

    except Exception as e:
        # ロールバック: 途中まで生成したファイルを削除
        print(f'\n[FAIL] 分割中にエラーが発生しました: {e}')
        print('[ロールバック] 途中生成ファイルを削除します...')
        for p in generated_paths:
            if p.exists():
                p.unlink()
                print(f'  削除: {p.name}')
        sys.exit(1)

    # アーカイブ（元PDFの移動）
    if archive_dir is not None:
        archive_dir.mkdir(parents=True, exist_ok=True)
        dest = archive_dir / input_path.name
        shutil.move(str(input_path), str(dest))
        print(f'\n[アーカイブ] {input_path.name} -> {dest}')

    # 完了サマリ
    print(f'\n[完了]')
    print(f'  分割章数: {len(chapters)} 章')
    print(f'  生成ファイル数: {len(generated_paths)} ファイル')
    print(f'  出力先: {output_dir}')
    if archive_dir:
        print(f'  元PDF退避先: {archive_dir}')


if __name__ == '__main__':
    main()
