# プロジェクトメモリ

## プロジェクト概要
- **場所**: x:\xampp\htdocs\claude-code-website
- **目的**: Webサイト制作マルチエージェントチーム（Python + Anthropic API）

## ファイル構成
- [CLAUDE.md](CLAUDE.md) - エージェントチームの役割・ワークフロー定義（唯一のファイル）
- APIキー不要。Claude Code 自体がオーケストレーター。

## エージェントチーム構成
Phase 1: PM → 要件定義書
Phase 2 (並列): UI/UXデザイナー + マーケター + セールスライター
Phase 3: デザイナー(HTML)
Phase 4 (並列): スタイリスト(CSS) + フロントエンドエンジニア(JS) + バックエンドエンジニア
Phase 5: SEO担当者
Phase 6 (並列): コードレビュー + レビュワー(QA)
Phase 7: QA最終アセンブラー → output/ に成果物

## 実行方法
```
set ANTHROPIC_API_KEY=sk-ant-...
pip install anthropic
python orchestrator.py
```

## 重要な設計メモ
- モデル: claude-sonnet-4-6（全エージェント共通）
- 並列実行: ThreadPoolExecutor（asyncioより単純でI/Oバウンドに適切）
- コンテキスト共有: ファイル（workspace/）+ インメモリdict（utils.context）のハイブリッド
- 再開機能: 起動時に workspace/ の既存ファイルをロードしてフェーズをスキップ
- QA最終出力デリミタ: ===HTML_START=== などで4ファイルを一括出力
- 出力先: output/ ディレクトリ（XAMPP経由: http://localhost/claude-code-website/output/）
