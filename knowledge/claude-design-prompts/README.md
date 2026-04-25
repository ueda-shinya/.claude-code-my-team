# Claude Design プロンプト集

Claude Design (claude.ai/design) で**高品質なデザインを引き出す**ためのプロンプトテンプレート集。

## 使い方

### パターンA: 自分で組み立てる（手動運用）
1. 用途に応じて該当ファイルを開く
2. テンプレートをコピー
3. プレースホルダー（[製品名] 等）を実案件に置き換え
4. claude.ai/design に貼り付け

### パターンB: スキル経由で組み立てる（推奨）
- アスカに「Claude Design用に〇〇のプロンプト作って」と依頼
- スキル `claude-design-prompt` が起動し、本ナレッジを参照しながら最適化したプロンプトを生成

## ファイル一覧

| # | ファイル | 用途 |
|---|---|---|
| 00 | [anti-slop-block.md](./00-anti-slop-block.md) | **必ず冒頭に貼る**アンチスロップ・システムプロンプト |
| 01 | [base-template.md](./01-base-template.md) | 4要素テンプレート（Goal/Layout/Content/Audience） |
| 02 | [lp-hero.md](./02-lp-hero.md) | LP・ヒーローセクション |
| 03 | [slide-deck.md](./03-slide-deck.md) | スライドデッキ |
| 04 | [dashboard.md](./04-dashboard.md) | ダッシュボード・管理画面 |
| 05 | [mobile-app.md](./05-mobile-app.md) | モバイルアプリUI |
| 06 | [japanese-ui.md](./06-japanese-ui.md) | 日本語UI追加指定 |
| 07 | [design-md-recipes.md](./07-design-md-recipes.md) | DESIGN.md（ブランドガイド一括読み込み） |
| 08 | [quality-checklist.md](./08-quality-checklist.md) | 送信前チェックリスト（必須・推奨項目） |
| 99 | [anti-patterns.md](./99-anti-patterns.md) | 避けるべき指示・アンチパターン |

## 組み立て順（推奨）

```
[00 アンチスロップ] + [用途別テンプレ 02-05] + [06 日本語UI（必要時）]
                                              + [07 DESIGN.md指定（必要時）]
                                              + バリエーション要求（任意）
```

## 出典（一次情報）

- [Anthropic Help: Get started with Claude Design](https://support.claude.com/en/articles/14604416-get-started-with-claude-design)
- [Claude Cookbook: Frontend Aesthetics](https://platform.claude.com/cookbook/coding-prompting-for-frontend-aesthetics)
- [Anthropic frontend-design SKILL.md](https://github.com/anthropics/skills/blob/main/skills/frontend-design/SKILL.md)
- [VoltAgent/awesome-design-md](https://github.com/VoltAgent/awesome-design-md) (約64,000 stars)
- [kzhrknt/awesome-design-md-jp](https://github.com/kzhrknt/awesome-design-md-jp) (日本語サービス24収録)
- [rohitg00/awesome-claude-design](https://github.com/rohitg00/awesome-claude-design)

## 取得日・再評価

- 取得日: 2026-04-25
- 再評価条件: Claude Design が GA 昇格時、研究プレビュー機能制限が解除された時
- 再評価起点: 次回 `/skill-finder` 発動時、または年次レビュー時
