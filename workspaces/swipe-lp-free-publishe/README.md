# スワイプLP 公開版

**公開URL**: `https://lp.officeueda.com/swipe/template-2026gw/`
**管理者**: コーダー上田 / オフィスウエダ（https://officeueda.com/）

lp.officeueda.com/swipe/template-2026gw/ の公開用ソースコードです。
運用者・実装メンテナンス担当者向けのドキュメントです。

---

## ディレクトリ構成

```
swipe-lp-free-publishe/
├── index.html          メインファイル（設定・コンテンツ・ロジックすべて含む）
├── images/
│   ├── .gitkeep
│   ├── slide-01.webp   スライド画像（7枚構成）
│   ├── slide-02.webp
│   ├── ...
│   ├── slide-07.webp
│   └── ogp.webp        OGP画像（公開時に作成・配置が必要）
└── README.md           本ファイル
```

---

## 公開時の設定手順（必須）

### 1. CTAボタンの href を設定する

`index.html` の `#cta-button` を編集します。

**変更前（仮置き状態）:**
```html
<a id="cta-button" href="#" rel="noopener noreferrer">Chatworkでテンプレを受け取る</a>
```

**変更後:**
```html
<a id="cta-button" href="https://www.chatwork.com/#!rid{シンヤさんのChatwork ID}" rel="noopener noreferrer">Chatworkでテンプレを受け取る</a>
```

- `href` を Chatwork URL に差し替えるだけで OK です
- GA4 計測（cta_click イベント）も自動的に有効になります
- `#` のままにしないでください（ガードが発動してアラートが出ます）

### 2. OGP画像を作成・配置する

SNSシェア時に表示されるサムネイル画像です。

- **サイズ**: 1200 × 630px
- **配置場所**: `images/ogp.webp`
- **ファイル形式**: WebP推奨（JPGも可・ファイル名は `ogp.webp` で統一）
- **内容**: LP/テンプレの内容がひと目でわかるデザインを推奨

> 配置前は `og:image` / `twitter:image` に設定されたURLが 404 になります。
> シェア時に正しく表示されるか、以下のツールで確認してください。
> - Facebook Sharing Debugger: https://developers.facebook.com/tools/debug/
> - Twitter Card Validator: https://cards-dev.twitter.com/validator

### 3. JSON-LD の datePublished / dateModified を実際の公開日に更新する

`index.html` の JSON-LD ブロックにある `datePublished` と `dateModified` を公開当日の日付に変更してください。

```json
"datePublished": "2026-05-01",
"dateModified": "2026-05-01"
```

→ 例: 2026-05-03 に公開する場合は両方を `"2026-05-03"` に更新。
→ 以降コンテンツを更新した際は `dateModified` のみ最新日付に更新してください。

### 4. GA4 測定IDを差し替える（2箇所）

```html
<!-- 1箇所目: scriptタグのsrc -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>

<!-- 2箇所目: gtag('config', ...) -->
gtag('config', 'G-XXXXXXXXXX', { transport_type: 'beacon' })
```

### 5. Xserver にデプロイする（FTPアップロード）

`lp.officeueda.com/swipe/template-2026gw/` 配下にフォルダごとアップロードします。

```
アップロード先: lp.officeueda.com/public_html/swipe/template-2026gw/
```

- `index.html` と `images/` フォルダをまとめてアップロード
- 特別なサーバー設定（.htaccess等）は不要

---

## 動作確認チェックリスト

デプロイ後、以下を確認してください。

- [ ] JSON-LD の `datePublished` / `dateModified` が実際の公開日に更新されていること
- [ ] GA4 測定ID `G-XXXXXXXXXX` が実IDに差し替えられていること（HTML 全体検索で2箇所確認）
- [ ] OGP画像 `images/ogp.webp`（1200×630px）が配置されていること
- [ ] DevTools Console に SRI 不一致エラーが出ていないこと
  - `Failed to find a valid digest in the 'integrity' attribute` 等のエラーが出たら CDN のバージョン変更を確認
- [ ] スライド7枚が正常にスワイプできること
- [ ] CTAボタンをクリックして Chatwork が開くこと
- [ ] CTA未設定アラートが出ないこと（href に Chatwork URL が設定されていれば出ない）
- [ ] スマホ・PCそれぞれで 4:5 アスペクト比が正しく表示されること
- [ ] GA4 リアルタイムレポートで `slide_view` イベントが届くこと
- [ ] OGP 画像が SNS シェア時に表示されること（Sharing Debugger で確認）

---

## 主要機能

| 機能 | 説明 |
|---|---|
| スワイプ | Swiper.js 11.1.14 / 水平スワイプ / speed: 350ms |
| アスペクト比 | 4:5（meta タグで変更可: 9:16 / 1:1 / 4:5 / 16:9） |
| ページネーション | 上部固定 36px / 「N / 総数」形式 / コーラルOR薄背景 |
| CTA | 下部固定 80px / コーラルオレンジ #E8694A / 全スライド共通 |
| GA4 計測 | slide_view / slide_time / cta_click イベント自動送信 |
| OGP / Twitter Card | 設定済み（ogp.webp の配置が必要） |
| JSON-LD | WebPage / Person / Organization schema 設定済み |
| SRI | Swiper.js CDN に integrity ハッシュ設定済み |
| CTA未設定ガード | href が `#` / 空文字 / `javascript:` のときにコンソール警告 + アラートを表示（href を Chatwork URL に変えるだけで自動解除） |
| ARIA | ページネーションバーに role="status" / aria-live="polite" 設定済み |

---

## カスタマイズメモ

### CTAボタンの色を変える

`index.html` の meta タグで指定しています。

```html
<meta name="cta-bg-color" content="#E8694A">
<meta name="cta-text-color" content="#ffffff">
```

### スライド枚数を変える

`images/` フォルダに `slide-{2桁連番}.webp` を追加・削除するだけです。
HTML を編集する必要はありません（JS が自動で枚数を検出します）。

### ローカル確認

`index.html` をブラウザで直接開くと fetch が CORS エラーになります（`file://` プロトコル制限）。
以下のいずれかでローカルサーバーを起動してから確認してください。

```bash
# Python 3
python3 -m http.server 8080

# Node.js
npx serve .
```

---

## GA4 イベント設計

| イベント名 | 送信タイミング | パラメータ |
|---|---|---|
| `slide_view` | スライド表示時（初回含む） | `slide_index`（0始まり）/ `slide_number`（1始まり）/ `total_slides` |
| `slide_time` | 前スライドから離れる瞬間、またはページ離脱時 | `slide_index` / `slide_time_ms`（滞在ミリ秒） |
| `cta_click` | CTAボタンクリック時 | `current_slide_index` / `current_slide_number` |

---

## トラブルシューティング

| 症状 | 原因 | 対処 |
|---|---|---|
| 画像が表示されない | `images/` に画像がない | `slide-01.webp` から連番で配置する |
| ローカルで画像が読み込まれない | `file://` プロトコル制限 | ローカルサーバーを使う |
| スライドが1枚しか出ない | 連番が飛んでいる | `slide-01`, `slide-02` と連続させる |
| GA4 イベントが届かない | 測定IDが未差し替え | `G-XXXXXXXXXX` を実IDに変更する |
| CTAをクリックするとアラートが出る | href が `#` / 空文字 / `javascript:` のまま | href を Chatwork URL に差し替える（属性削除は不要） |
| SRI エラーが Console に出る | CDN のファイルが変わった | CDN バージョンと integrity ハッシュを再確認する |
| OGP 画像が SNS で表示されない | `images/ogp.webp` が未配置 | 1200×630px の画像を作成して配置する |

---

## 改版履歴

| 日付 | 変更内容 |
|---|---|
| 2026-04-30 | 公開版として仕上げ。title/description/OGP/JSON-LD（WebPage+Person+Organization schema）設定、CTAボタン確定（Chatworkテンプレ受け取り）、CTA未設定ガード移植、ページネーションバーをコーラルOC配色に、Swiper speed:350 追加、ARIA属性追加、img alt 自動生成対応 |
| 2026-04-30 | サクラレビュー指摘対応。H-1: CTA未設定ガードを href ベース判定に一本化（data-cta-unconfigured 属性廃止）、M-1+L-2: JSON-LD に dateModified 追加 + FIXME コメント強調、M-2: チェックリストに必須3項目追加、L-1: href を javascript:void(0) → # に変更（CSP対応）、L-3: twitter:site はX公式アカウント確定後に追加予定（スキップ） |
