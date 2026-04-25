# 07. DESIGN.md（ブランドガイド一括読み込み）

事前にブランド仕様を Markdown 1ファイルにまとめてアップロードすると、**全プロンプトに自動適用**される。

## DESIGN.md 9セクション標準構造

```markdown
# [Brand Name] Design System

## 1. Visual Theme & Atmosphere
[アトモスフィアの記述]

## 2. Color Palette & Roles
- Primary: #xxxxxx
- Text Primary: #xxxxxx
- Background: #xxxxxx
- Accent: #xxxxxx
[等]

## 3. Typography Rules
Font stack: [...]
Hierarchy:
- h1: [size] / [weight] / line-height [value]
- body: [size] / [weight] / line-height [value]

## 4. Component Stylings
[Button, Card, Input 等のスタイル]

## 5. Layout Principles
- Article width: [px]
- Main content: [px]
- Breakpoints: [...]

## 6. Depth & Elevation
[shadow, blur 等]

## 7. Do's and Don'ts
[使ってよい / 禁止]

## 8. Responsive Behavior
[ブレークポイント別挙動]

## 9. Agent Prompt Guide
[Claudeへの指示テンプレ]
```

## 既製コレクション

| リポジトリ | 内容 |
|---|---|
| [VoltAgent/awesome-design-md](https://github.com/VoltAgent/awesome-design-md) | 約64,000 stars、69ブランド標準 |
| [getdesign.md](https://getdesign.md/) | 60+ブランド配布サイト |
| [kzhrknt/awesome-design-md-jp](https://github.com/kzhrknt/awesome-design-md-jp) | 日本語サービス24収録（note / SmartHR / freee / Mercari / 楽天 / Cookpad 他） |
| [rohitg00/awesome-claude-design](https://github.com/rohitg00/awesome-claude-design) | アンチスロップ・リミックスレシピ集 |

## 用途別の推奨

| 用途 | 推奨 DESIGN.md | 特徴 |
|---|---|---|
| スタートアップLP | Linear | 極端ミニマル、パープルアクセント |
| SaaSダッシュボード | Sentry / ClickHouse | データ密度高 |
| ブランドサイト（温かみ） | Notion / Anthropic | テラコッタ、エディトリアル |
| 開発者ドキュメント | Vercel | 黒白精度、Geist |
| フィンテック | Stripe | パープルグラデ、weight-300 |
| クリエイティブ | Framer / Clay | ボールドタイポ、モーション先行 |
| 日本語メディア | note.com (kzhrknt版) | 620px記事幅、行間1.8 |

## 自前 DESIGN.md の作り方

1. ブランド素材をフォルダに集める（ロゴ・過去スライド・写真・PDF・スクショ）
2. Claudeに以下を依頼：
   ```
   このフォルダの素材を分析し、フォント・色・グラフィックスタイル・
   コンポーネントパターン・トーン・レイアウト規約を含む完全な
   デザインシステムを DESIGN.md として生成してください。
   9セクション構造（Visual Theme / Colors / Typography / Components /
   Layout / Depth / Do's & Don'ts / Responsive / Agent Prompt Guide）
   に従ってください。
   ```
3. 生成された DESIGN.md を Claude Design のコンテキストにアップロード

## リミックスレシピ（2ブランド合成）

```
Combine [Brand A]'s typography system with [Brand B]'s [characteristic].
Use the attached DESIGN.md files for both brands.
Result aesthetic: [desired outcome description]
```

実例:
```
Combine Linear's typography system with Anthropic Claude's terracotta
accent (#c96442) and warm neutrals.
Use the attached DESIGN.md files for both brands.
Result aesthetic: editorial SaaS with soul.
```

## 注意

- 細部数値（カラーコード・行間値）は採用前に GitHub で原文確認推奨
- 日本語サービスのDESIGN.mdは [awesome-design-md-jp](https://github.com/kzhrknt/awesome-design-md-jp) 経由で取得

出典: [VoltAgent/awesome-design-md](https://github.com/VoltAgent/awesome-design-md), [getdesign.md](https://getdesign.md/), [kzhrknt/awesome-design-md-jp](https://github.com/kzhrknt/awesome-design-md-jp)
