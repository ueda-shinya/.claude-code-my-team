# 04. ダッシュボード・管理画面

## 標準テンプレート（英語）

```
Create a dashboard showing [primary metric] with filters for [filter1] and
[filter2].
Data density: high — use tight spacing.
Theme: dark background ([#bg-hex]), [accent color] accent ([#accent-hex]).
Components needed:
- KPI cards ([metric1], [metric2], [metric3]) with sparklines
- Bar chart: [data dimension] (animate bars on load)
- Table: top [N] [entity] with sortable columns
- Sidebar navigation with icon-only collapsed mode
Font: IBM Plex Mono for numbers, IBM Plex Sans for labels.
```

## 標準テンプレート（日本語）

```
[主要指標]を表示するダッシュボードを作成してください。
[フィルタ1] / [フィルタ2] 別のフィルター機能付き。
情報密度: 高め（タイトなスペーシング）
テーマ: ダーク背景 [#bg-hex]、[アクセント色名] アクセント [#accent-hex]
必要なコンポーネント:
- KPIカード（[指標1] / [指標2] / [指標3]）＋スパークライン
- [データ次元] の棒グラフ（ロード時にアニメーション）
- ソート可能な上位 [N] 件のテーブル
- アイコンのみのコラプス可能なサイドナビ
フォント: 数値はIBM Plex Mono、ラベルはIBM Plex Sans
```

## 実例（売上ダッシュボード）

```
Create a dashboard showing monthly revenue with filters for region and
product line.
Data density: high — use tight spacing.
Theme: dark background (#0f0f14), yellow accent (#faff69).
Components needed:
- KPI cards (MRR, churn rate, NRR) with sparklines
- Bar chart: monthly revenue by product line (animate bars on load)
- Table: top 10 accounts with sortable columns
- Sidebar navigation with icon-only collapsed mode
Font: IBM Plex Mono for numbers, IBM Plex Sans for labels.
```

## ポイント

- データ密度を「high / medium / low」で必ず指定
- 数値表示はモノスペースフォント（IBM Plex Mono / JetBrains Mono / Fira Code）
- ダークテーマの場合、アクセントは1色だけに絞る（イエロー / グリーン / シアン等）
- チャートのロード時アニメーションは指定しないと出ない

出典: [Anthropic Help: Get started with Claude Design](https://support.claude.com/en/articles/14604416-get-started-with-claude-design)
