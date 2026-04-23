# ai-seo リファレンス（外部スキル由来）

## 概要

**Corey Haines 氏 `marketingskills` リポジトリの `ai-seo` スキル**から、独自価値のある `references/` 2ファイルのみを資料として退避したもの。

**本ディレクトリはスキルとして発動しない**（`SKILL.md` を置かず `README.md` としてあるため）。`llmo-audit` の改善提案作成時、`/blog-post` 執筆時、`llmo-compliant-coding` の遵守ルール設計時の **参照資料** として利用する。

## 収録ファイル

| ファイル | 用途 |
|---|---|
| [content-patterns.md](content-patterns.md) | AEO / GEO 最適化のコンテンツテンプレート集（FAQ ブロック、比較表、データポイント、ステップバイステップ等のパターン） |
| [platform-ranking-factors.md](platform-ranking-factors.md) | ChatGPT / Perplexity / Claude / Gemini / Copilot / Google AI Overviews の各プラットフォームごとの引用獲得ランキング要因 |
| [LICENSE](LICENSE) | MIT License（Corey Haines 2025） |

## 出典

- 原典リポジトリ: https://github.com/coreyhaines31/marketingskills
- 原典ライセンス: MIT
- 取得日: 2026-04-23
- 取得時点のリポジトリ最終更新: 2026-04-21 (v1.8.0)
- 原典スキル最終更新: 2026-03-14 (ai-seo v1.2.0)

## なぜスキルとして導入しなかったか

- ai-seo の description トリガー語（`AEO` / `GEO` / `LLMO` / `AI Overviews` 等）が既存の自作 `llmo-audit` と **5語以上一致**
- `llmo-audit` が description で「英語の ai-seo audit と呼ばれても本スキル起動」と自己宣言済みのため、ai-seo をスキルとして同居させると **トリガールーティングが自己矛盾**
- ai-seo 本体も "AI Visibility Audit" セクションで監査機能を内包しており、`llmo-audit` と機能重複
- 英語・海外SaaS前提（Wikipedia/Reddit/G2/Capterra 依存）で日本語クライアント案件に不適
- ただし **プラットフォーム別ランキング要因** と **AEO/GEOコンテンツテンプレート** は `llmo-audit` にない独自価値があるため資料として保持

監査詳細は 2026-04-23 のサクラ（code-reviewer）監査結果を参照（CLAUDE.md 内「External Skill Guard Rules」セクションに要点記録）。

## 活用場面

| 場面 | 参照ファイル |
|---|---|
| ブログ記事執筆で FAQ / 比較表を設計するとき | `content-patterns.md` |
| AI 検索引用を狙う記事構造を設計するとき | `content-patterns.md` |
| Gemini 特化・Perplexity 特化等のプラットフォーム別対策を立てるとき | `platform-ranking-factors.md` |
| `llmo-audit` の改善提案欄で具体策を提示するとき | 両方 |

## 再評価条件

原典 `ai-seo` スキルの仕様が変わり、以下のいずれかに該当したら再評価：
- description から `AEO` / `GEO` / `LLMO` / `AI Overviews` のトリガー語が外れる
- 監査機能（"AI Visibility Audit"）が削除され、戦略立案専用になる
- 日本語版が公式に追加される
