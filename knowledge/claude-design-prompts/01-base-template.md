# 01. 基本テンプレート（4要素）

Anthropic 公式が示す Claude Design プロンプトの最小構造。

## 4要素

```
Goal（何を作るか） + Layout（配置）+ Content（掲載情報）+ Audience（対象ユーザー）
```

## 英語テンプレート

```
Build a [page type] for [product name].
[Layout: sections, structure, responsiveness]
[Content: what information goes where]
[Audience: who will use it, their context]
[Tone: emotional/aesthetic direction]
```

## 英語実例

```
Build a pricing page for Acme SaaS.
3 tiers (Free / Pro / Enterprise), annual/monthly toggle,
sticky CTA on mobile.
Mobile-first responsive. Match the tone of our existing homepage.
Audience: mid-market RevOps leaders evaluating data platforms.
Tone: modern and credible.
```

## 日本語テンプレート

```
[ページ種別]を作ってください。製品: [製品名]
レイアウト: [セクション構成・レスポンシブ要件]
コンテンツ: [どこに何を掲載するか]
対象読者: [ユーザーペルソナとそのコンテキスト]
トーン: [感情・美的方向性]
```

## 日本語実例

```
SaaS向けBtoBダッシュボードのランディングページを作ってください。
レイアウト: ヒーロー / 課題提起 / 機能3つ / 価格 / FAQ / CTA。モバイルファースト。
コンテンツ: 時短メリットを前面に、競合比較表、顧客の声、デモ申込CTA。
対象読者: 25〜40歳のプロダクトマネージャー。データ基盤評価フェーズ。
トーン: 技術的・信頼感のあるモダンな雰囲気。
```

## ポイント

- スタイルは「制約」として命令形で書く（"Background is always #0D0D0D"）
- トーンは「感情」で表現（「VC投資家に『これは本物だ』と思わせる」）
- 末尾に `Show me 2-3 alternative designs with different positioning angles.` を追加するとバリエーション提示

出典: [Anthropic Help: Get started with Claude Design](https://support.claude.com/en/articles/14604416-get-started-with-claude-design)
