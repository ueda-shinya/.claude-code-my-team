# Notion「なぜなぜ分析」DB重複インシデント

## 発生日
2026-04-11

## ステータス
解決済み

## 概要
notion-kaizen.py の `--create-db` を Mac/Win 両方で実行した結果、Notion 上に同名の「なぜなぜ分析」DBが2つ作成された。Mac 側で削除済みDBのIDを参照し 404 エラーが発生。

## 原因分析

### 直接原因
`--create-db` コマンドに冪等性がなく、実行するたびに無条件で新規DBを作成する設計だった。

### 構造的原因
- `.env` は gitignore 対象のため、DB作成結果（DB ID）が PC 間で自動共有されない
- 「一度だけ実行する」という前提が暗黙の運用ルールに依存していた
- 複数PC環境（Mac/Win）での運用が設計時に考慮されていなかった

## 対処内容
- notion-*.py（kaizen / tasks / projects）の `--create-db` に2段階の既存チェックを追加
  - 第1段階: `.env` に ID あり → API で存在確認 → あれば中断
  - 第2段階: ID なし or 404 → 親ページ配下を同名検索 → あれば案内
  - `--force` オプションで強制再作成、`--reuse` で既存DB再利用
- なぜなぜ分析DBに「なぜ(1〜3回目)」「真の原因に対する対策」プロパティを追加

## 見送った対策
- DB ID の git 管理分離（`config/notion-db-ids.json`）→ P4 タスクとして積み。現時点では費用対効果が合わない

## 教訓
- DB作成のような副作用を持つコマンドには、必ず冪等性チェックを組み込む
- 複数PC環境では `.env` に依存する状態共有が破綻する前提で設計する
- 「気をつける」ではなく仕組みで防ぐ

## 関連ファイル
- `~/.claude/scripts/notion-kaizen.py`
- `~/.claude/scripts/notion-tasks.py`
- `~/.claude/scripts/notion-projects.py`
