# swipe-lp テンプレート

横スワイプ型 LP の汎用テンプレートです。ビルド不要・1ファイル完結で、画像を置くだけで動きます。

---

## ディレクトリ構成

```
swipe-lp/
├── index.html      メインファイル（これだけで動く）
├── images/
│   ├── .gitkeep
│   ├── slide-01.webp   ← ここに画像を置く
│   ├── slide-02.webp
│   └── ...
└── README.md
```

---

## クイックスタート（4ステップ）

### Step 1: 画像を配置する

`images/` フォルダに `slide-01.webp` から連番で画像を置きます。

```
images/slide-01.webp
images/slide-02.webp
images/slide-03.webp
...
```

- 命名規則: `slide-{2桁連番}.{拡張子}`（例: `slide-01`, `slide-02`）
- 対応拡張子: `webp` / `jpg` / `png`（この順で優先される）
- 枚数は HTML に書く必要はありません。JS が自動で検出します
- 最初に見つからなかった番号で打ち止めになります（`slide-01`, `slide-03` と飛び番にしないこと）

### Step 2: GA4 測定IDを差し替える

`index.html` を開き、`G-XXXXXXXXXX` を2箇所、自分の測定IDに差し替えます。

```html
<!-- 1箇所目: scriptタグのsrc -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>

<!-- 2箇所目: gtag('config', ...) -->
gtag('config', 'G-XXXXXXXXXX')
```

### Step 3: アスペクト比を選ぶ

`<head>` 内の `<meta name="lp-ratio">` で指定します。

```html
<!-- 縦型（Instagram 等） -->
<meta name="lp-ratio" content="9:16">

<!-- 正方形 -->
<meta name="lp-ratio" content="1:1">

<!-- Instagram フィード推奨 -->
<meta name="lp-ratio" content="4:5">

<!-- 横型（YouTube サムネ等） -->
<meta name="lp-ratio" content="16:9">
```

### Step 4: CTA を設定する

`<a id="cta-button">` の `href` とボタンテキストを差し替えます。

```html
<a id="cta-button" href="https://example.com/contact" rel="noopener noreferrer">お問い合わせはこちら</a>
```

> **注意**: 外部URLに差し替える場合は `target="_blank"` と `rel="noopener noreferrer"` を必ず併用してください。`target="_blank"` 単独はフィッシング攻撃（Reverse Tabnabbing）のリスクがあります。

---

## GA4 イベント設計

| イベント名 | 送信タイミング | パラメータ |
|---|---|---|
| `slide_view` | スライド表示時（初回含む） | `slide_index`（0始まり）/ `slide_number`（1始まり）/ `total_slides` |
| `slide_time` | 前スライドから離れる瞬間、またはページ離脱時 | `slide_index` / `slide_time_ms`（滞在ミリ秒） |
| `cta_click` | CTAボタンクリック時 | `current_slide_index` / `current_slide_number` |

GA4 の「カスタムイベント」として自動で計測されます。BigQuery 連携で詳細分析も可能です。

> **beacon transport について**: GA4送信は `transport_type: 'beacon'` を設定しているため、ページ離脱時（bfcache含む）もブラウザが送信を保証します。また iOS Safari の bfcache 対策として `pagehide` イベントも `visibilitychange` と併用しています。

---

## デプロイ手順

### Vercel（推奨・最速）

```bash
# Vercel CLI が未インストールの場合
npm i -g vercel

cd swipe-lp/
vercel
```

初回デプロイ後は `vercel --prod` でプロダクション反映。

> **注意**: `images/` フォルダの画像は git に含める必要があります（`.gitkeep` を画像に差し替えてから `git add`）。

### GitHub Pages

1. このフォルダを GitHub リポジトリとして push
2. Settings → Pages → Source: `main` ブランチ / `/ (root)`
3. `https://{username}.github.io/{repo-name}/` でアクセス

### 静的ファイルサーバー（Apache / Nginx）

フォルダごとアップロードするだけで動きます。特別な設定は不要です。

### ローカル確認

`index.html` をブラウザで直接開くと、`fetch` が CORS エラーになります（`file://` プロトコル制限）。
以下のいずれかでローカルサーバーを立ててください。

```bash
# Python 3
python3 -m http.server 8080

# Node.js
npx serve .

# VS Code: Live Server 拡張
```

---

## カスタマイズのヒント

### CTAボタンの色を変える

`index.html` の `#cta-button` スタイルを編集します。

```css
#cta-button {
  background: #e53935;  /* ← 好みの色コードに変更 */
}
```

### ページタイトル・description・OGP を差し替える

コピー後、以下のプレースホルダを実際の値に差し替えてください。

#### description（検索結果に表示される説明文）

```html
<meta name="description" content="ここにページ説明を記入（全角80〜120字推奨）">
```

#### OGP / Twitter Card（SNSシェア時に表示される情報）

```html
<!-- OGP -->
<meta property="og:title" content="ページタイトル">
<meta property="og:description" content="ここにページ説明を記入">
<meta property="og:url" content="https://your-domain.com/">
<meta property="og:image" content="https://your-domain.com/images/ogp.webp">
<meta property="og:site_name" content="サイト名">

<!-- Twitter Card -->
<meta name="twitter:title" content="ページタイトル">
<meta name="twitter:description" content="ここにページ説明を記入">
<meta name="twitter:image" content="https://your-domain.com/images/ogp.webp">
```

**OGP画像の準備:**
- サイズ: `1200 × 630px` 推奨
- 配置場所: `images/ogp.webp`
- ファイル形式: WebP 推奨（JPG も可）

#### JSON-LD も忘れずに差し替える

```html
<script type="application/ld+json">
{
  "name": "ページタイトル",
  "description": "ここにページ説明を記入",
  "url": "https://your-domain.com/"
}
</script>
```

#### OGP デバッグツール

シェアした際の表示を事前確認できます。

- **Facebook Sharing Debugger**: https://developers.facebook.com/tools/debug/
- **Twitter Card Validator**: https://cards-dev.twitter.com/validator

### スワイプヒントのテキストを変える

```html
<div id="swipe-hint">← スワイプ →</div>
```

### ループ再生を有効にする

`index.html` 内の Swiper 設定を変更します（GA4 の `slide_index` 管理が複雑になるため注意）。

```js
const swiper = new Swiper('#swiper-container', {
  loop: true,   // false → true に変更
  ...
})
```

---

## トラブルシューティング

| 症状 | 原因 | 対処 |
|---|---|---|
| 画像が表示されない（エラーメッセージが出る） | `images/` に画像がない | `slide-01.webp` を配置する |
| ローカルで画像が読み込まれない | `file://` プロトコル制限 | ローカルサーバーを使う |
| スライドが1枚しか出ない | 連番が飛んでいる | `slide-01`, `slide-02` と連続させる |
| GA4 イベントが届かない | 測定IDが未差し替え | `G-XXXXXXXXXX` を実IDに変更する |
| PC でスライダーが小さい | アスペクト比が縦長 | `16:9` や `4:5` に変更するか、画面を縦にする |
