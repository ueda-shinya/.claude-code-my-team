---
name: ミオ（researcher）の JSON-LD 判定ルール
description: llmo-audit スキル実行時、WebFetch で JSON-LD 未検出でも即 NG 禁止。判定基準の正本は SKILL.md 側の強制ガード。
type: feedback
---

# ミオ（researcher）への永続フィードバック — JSON-LD 判定ルール

llmo-audit スキル実行時、WebFetch で JSON-LD が検出できなくても、**即 `NG` と判定してはならない**。

## ルールの正本（Source of Truth）

判定ロジックの詳細は **`~/.claude/skills/llmo-audit/SKILL.md` ステップ2の「【強制ガード】JSON-LD関連項目の判定前チェックリスト」を参照**。本ファイルはミオの長期記憶として存在意義を明示するための要約に留め、実装詳細の重複記述はしない。

## 要約（詳細は SKILL.md 側）

- 対象項目: 項目2（Schema.org）・項目5（Person）・項目9（FAQPage）
- 項目11（更新日）は本文可視表記が優先判定材料のため強制ガード対象外
- 判定フロー: プラグイン/CMS痕跡の有無 → 痕跡あり/取得不能なら `判定不能`、痕跡なしかつ安全条件該当なら `NG` 判定可
- NG を許す安全条件は SKILL.md に4つ明記（静的HTML申告／HTMLソース直接提供／特定SSG＋全head抽出成功／全メタ抽出成功かつプラグイン痕跡皆無）
- NG 判定時は採点表の根拠欄にガード通過記録を必須明示

## Why（なぜ必要か）

WebFetch で見えない = 不存在確定 ではない。5状態契約の「試みたか否か」原則では「試みたが検証不能」は `[SKIP]`（判定不能）であり `NG` ではない。この切り分けを誤ると、実装済みの構造化データを「未実装」と誤報告しユーザーの意思決定を歪める。

## How to apply（適用場面）

- llmo-audit スキル実行時、SKILL.md の強制ガードに従う
- 改善提案欄に「Google Rich Results Test（https://search.google.com/test/rich-results）で確認を推奨」と明記
- 判定根拠欄にガード通過記録（痕跡検出結果＋安全条件該当有無）を明示

## 背景（2026-04-22 の誤判定インシデント）

- 対象: `https://officeueda.com`
- ミオが項目2（Schema.org）を `NG` と断定
- シンヤさんが Google Rich Results Test で確認したところ、JSON-LD は実装済みだった
- 原因: 対象サイトが WordPress + All in One SEO v4.9.5.1 を使用し、JS 経由で JSON-LD 注入していた
- ミオは All in One SEO の存在を取得済みだったが「WebFetch で見えない = NG」と飛躍した
- リナ検証で「過剰ガード vs 真NG検出」のバランス問題も判明し、本設計に改訂

## 同期運用ルール

- 本ファイルと SKILL.md の内容に乖離が生じた場合、**SKILL.md が正**
- SKILL.md 強制ガードを変更した場合、本ファイルの「要約」セクションも同期更新する義務あり（逆方向も同様）
- 粒度の異なる重複記述は避け、実装詳細は SKILL.md に集約する
