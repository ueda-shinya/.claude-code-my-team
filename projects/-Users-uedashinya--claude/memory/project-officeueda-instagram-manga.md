---
name: officeueda Instagram漫画プロジェクト
description: officeueda biz-web向けInstagramマンガ制作の進捗・方針メモ
type: project
---

## 概要

Web事業（biz-web）向けにInstagramでマンガ形式の投稿を行う企画。
AI事業（biz-ai）向けも今後予定。

## キャラクター

- 保存場所：`clients/officeueda/biz-web/instagram/charcters/`
- メインキャラ：神崎美咲（マーケター）、黒田そうま（エンジニア）
- クライアント側キャラ：チームA〜E（各業種の社長＋担当者）
- 設定資料：PNG画像＋PDFで整備済み（JSONデータ・表情・ポーズ・カラーコード含む）

## フォーマット

- Instagram正方形（1080×1080px）
- 4コマ（2×2グリッド）
- 吹き出しはPython（Pillow）で合成

## 画像生成ツール検討状況（2026-03-18時点・保留中）

| 方法 | 状況 |
|---|---|
| Gemini Imagen 4 | テスト済み。キャラ一貫性が弱い（テキストのみ・画像参照不可） |
| Genspark外部API | 一般向け未公開のため利用不可 |
| FAL.ai（FLUX+IP-Adapter） | 未検討。キャラ参照対応で有力候補 |
| Genspark手動 ＋ Python合成 | 現実的な分業案。要フロー整備 |

**→ ツール選定保留中。改めて検討予定。**

## 制作フロー（暫定）

1. ネタ作り（アスカ）
2. コマ構成・セリフ（アスカ）
3. プロンプト設計（Nano Banana）
4. コマ画像生成（未定）
5. 4コマ合成＋吹き出し追加（Python/Pillow）
6. Instagram投稿用画像として出力

## 投稿画像の保存先

`clients/officeueda/biz-web/instagram/posts/`
