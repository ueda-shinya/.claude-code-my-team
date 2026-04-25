# 08. 品質チェックリスト

プロンプト送信前に確認するチェック項目。**全項目クリアしてから送信**することで、Claude Design の出力品質を最大化する。

## 必須チェック（全項目クリア）

### 構造
- [ ] 4要素（**Goal / Layout / Content / Audience**）が揃っている
- [ ] 1プロンプト = 1成果物（複数用途を混在させていない）
- [ ] 矛盾した指示がない（ミニマルと情報量多を同時指定していない等）

### アンチスロップ
- [ ] [00-anti-slop-block.md](./00-anti-slop-block.md) の `<frontend_aesthetics>` ブロックを冒頭に貼った
- [ ] 「Inter / Roboto / Arial 禁止」が明示されている
- [ ] 「purple gradient on white 禁止」が明示されている
- [ ] デフォルトのティール (#16d5e6) を回避するアクセント色を指定した

### デザイン仕様
- [ ] 配色は「支配色 + 鋭いアクセント1色」の2色体制
- [ ] フォント指定は **stack + weight + tracking** を数値で（vibesではなく）
- [ ] スタイル指定は提案調ではなく命令形（"Background is always #..."）
- [ ] エッジケース（empty / error / loading state）を明示的に設計した

### 日本語UI（該当時）
- [ ] [06-japanese-ui.md](./06-japanese-ui.md) のブロックを追加した
- [ ] line-height 1.8（Westernの1.4ではない）
- [ ] font-feature-settings: "palt", "kern" を指定

### モバイル（該当時）
- [ ] タッチターゲット 44px 以上を明示
- [ ] ビューポート寸法を指定（iPhone 15 Pro: 390×844px 等）
- [ ] アニメは初期値・終了値・所要時間まで具体的に指定

### バリエーション・効率
- [ ] 末尾に "Show me 2-3 alternative designs..." を入れた（推奨）
- [ ] スクリーンショット添付は4枚以内に絞った（視覚トークン3倍消費）

### フィードバック粒度（修正フェーズ時）
- [ ] フィードバックは px・hex・要素名で具体的に書く
- [ ] 「これじゃない」「もっとかっこよく」等の曖昧表現を避ける
- [ ] スタイル微調整はチャットではなくスライダーで（トークン浪費回避）

## ブランド一貫性（複数案件にまたがる場合）

- [ ] DESIGN.md を作成・アップロード済み（[07-design-md-recipes.md](./07-design-md-recipes.md) 参照）
- [ ] DESIGN.md に9セクション（Theme / Colors / Typography / Components / Layout / Depth / Do's & Don'ts / Responsive / Agent Prompt Guide）が含まれる
- [ ] ブランドアセット（ロゴ・過去資料）を併せてアップロード

## 苦手領域の事前回避

- [ ] ベクターイラスト・写真は別ツール（Midjourney / DALL-E / Flux）で生成して取り込む前提
- [ ] ロゴデザインは Claude Design に作らせない
- [ ] モノレポ全体ではなく `apps/web/src/styles` 等のサブディレクトリに限定して読み込ませる
- [ ] バージョン管理は外部で（Claude Design 自体には履歴管理なし）

## クォータ管理

- [ ] 視覚トークンが3倍消費されることを認識（添付・スクショは厳選）
- [ ] スタイリング調整はスライダーで（チャット送信を消費しない）
- [ ] 初稿に品質を集中（修正ループより一発書き込みを優先）

## 出典

- [Anthropic Help: Get started with Claude Design](https://support.claude.com/en/articles/14604416-get-started-with-claude-design)
- [Claude Cookbook - Frontend Aesthetics](https://platform.claude.com/cookbook/coding-prompting-for-frontend-aesthetics)
- [Anthropic frontend-design SKILL.md](https://github.com/anthropics/skills/blob/main/skills/frontend-design/SKILL.md)
- [rohitg00/awesome-claude-design](https://github.com/rohitg00/awesome-claude-design)
