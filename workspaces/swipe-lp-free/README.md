# 横スワイプ LP テンプレート（無料版）

画像を用意するだけで、横スワイプ式のランディングページが作れるテンプレートです。
コーディングの知識がなくても使えます。**無料・商用利用OK**（MITライセンス）。

---

## このテンプレートについて

- 画像を `images/` フォルダに入れるだけでスライドが表示されます
- スマートフォン・PC どちらでもきれいに表示されます
- 画面下部に問い合わせボタン（CTA）を固定表示できます

---

## 使い方（3ステップ）

### ステップ1: 画像を入れる

`images/` フォルダに、以下のルールでファイルを入れてください。

| ファイル名 | 意味 |
|---|---|
| `slide-01.webp` | 1枚目のスライド |
| `slide-02.webp` | 2枚目のスライド |
| `slide-03.webp` | 3枚目のスライド |
| … | … |

**命名のルール:**
- `slide-` のあとに **2桁の番号**（01, 02, 03 …）をつけてください
- 対応している画像形式: **webp / jpg / png**（webp が最優先）
- 上限は 200 枚まで

例: webp が使えない場合は `slide-01.jpg` でも OK です。

---

### ステップ2: ブラウザで確認する

> **注意:** `index.html` をダブルクリックしてブラウザで開いても、画像が表示されない場合があります。
> セキュリティの制限により、**ローカルサーバーを起動する必要があります。**

#### ローカルサーバーの起動方法（どれか1つでOK）

**方法A: VS Code をお使いの場合**
1. 拡張機能「Live Server」をインストール
2. `index.html` を右クリック →「Open with Live Server」

**方法B: Python がインストール済みの場合（コマンドプロンプト / ターミナル）**
```
cd このフォルダのパス
python3 -m http.server 8080
```
↑ `python3` で動かない場合は `python` に読み替えてください
実行後、ブラウザで `http://localhost:8080` を開く

**方法C: Node.js がインストール済みの場合**
```
npx serve .
```

---

### ステップ3: サーバーにアップする

`index.html` と `images/` フォルダをセットでサーバーにアップロードすれば完成です。

---

## カスタマイズ

### アスペクト比を変える

`index.html` の先頭付近にある以下の行を書き換えてください。

```html
<meta name="lp-ratio" content="4:5">
```

| 値 | 比率 | 向き |
|---|---|---|
| `9:16` | 縦長（スマホ全画面） | 縦 |
| `4:5` | 縦長（Instagram投稿） | 縦 |
| `1:1` | 正方形 | 正方形 |
| `16:9` | 横長（動画・PC向け） | 横 |

---

### CTAボタンの文言・リンク先を変える

`index.html` の以下の行を探して書き換えてください。

```html
<a id="cta-button" href="https://www.chatwork.com/#!ridXXXXXX" rel="noopener noreferrer">Chatworkでテンプレを受け取る</a>
```

- `href="..."` の部分: リンク先のURLに変更
- `Chatworkでテンプレを受け取る` の部分: ボタンに表示したい文言に変更

---

### CTAボタンの色を変える

`index.html` の先頭付近にある以下の2行を書き換えてください。

```html
<meta name="cta-bg-color" content="#e74c3c">
<meta name="cta-text-color" content="#ffffff">
```

- `cta-bg-color`: ボタンの**背景色**（16進カラーコード）
- `cta-text-color`: ボタンの**文字色**（16進カラーコード）

**カラーコード例:**

| 色 | 背景色コード | 用途イメージ |
|---|---|---|
| 赤（デフォルト） | `#e74c3c` | 緊急性・注目 |
| 青 | `#2980b9` | 信頼感・落ち着き |
| 緑 | `#27ae60` | 安心感・自然 |
| オレンジ | `#e67e22` | 行動喚起・明るさ |

> meta タグを削除した場合は赤（`#e74c3c`）・白文字（`#ffffff`）に自動でフォールバックします。

---

### ページタイトルを変える

```html
<title>LP タイトル</title>
```

↑ この部分をブラウザのタブに表示したいタイトルに変更してください。

---

## 対応画像形式

| 形式 | 拡張子 | 備考 |
|---|---|---|
| WebP | `.webp` | 最優先・ファイルサイズが小さい（推奨） |
| JPEG | `.jpg` | 一般的な写真形式 |
| PNG | `.png` | 透過が必要な場合など |

---

## 制作のご相談

「計測機能（GA4）やSEO対策も入れたい」「デザインをカスタマイズしたい」「LP全体を制作してほしい」など、お気軽にご相談ください。

高機能版（GA4計測 / SEO / OGP対応）もご提供しています。

**お問い合わせ:** https://officeueda.com/contact

---

## ライセンス

MIT License — 詳細は `LICENSE` ファイルをご覧ください。

無料・商用利用OK。クレジット表記は任意ですが、残していただけると励みになります。

---

## 公開時の設定手順（運用者向け）

### 1. Chatwork URL設定

`index.html` の以下の箇所を編集してください。

```html
<a id="cta-button" href="ここにChatwork URLを入力" rel="noopener noreferrer">
```

Chatwork Web URL の形式:
```
https://www.chatwork.com/#!rid{あなたのChatwork ID}
```

**設定例:**
```html
<a id="cta-button" href="https://www.chatwork.com/#!rid1234567" rel="noopener noreferrer">Chatworkでテンプレを受け取る</a>
```

> Chatwork IDは、Chatworkにログイン後、プロフィール画面から確認できます。
> Web URLはスマートフォンでChatworkアプリが未インストールの場合でも開けるため、ブラウザ版URLの使用を推奨します。

**重要:** URL設定後は、`<a>` タグの `data-cta-unconfigured="true"` 属性を**必ず削除**してください。削除しないとボタンが機能しません。

```html
<!-- 設定前（この状態では警告アラートが出ます） -->
<a id="cta-button" href="javascript:void(0)" data-cta-unconfigured="true" rel="noopener noreferrer">

<!-- 設定後（data-cta-unconfigured を削除し、href を実際のURLに変更） -->
<a id="cta-button" href="https://www.chatwork.com/#!rid1234567" rel="noopener noreferrer">
```

---

### 2. 画像差し替え

`images/slide-01.webp`〜`slide-07.webp` を任意の4:5画像（推奨1080×1350px）に差し替えてください。

| ファイル名 | 内容 |
|---|---|
| `slide-01.webp` | Hook（フック・1枚目） |
| `slide-02.webp` | Problem（課題提示・2枚目） |
| `slide-03.webp` | Empathy×Authority（共感・実績・3枚目） |
| `slide-04.webp` | Reveal（解決策・4枚目） |
| `slide-05.webp` | How it works（使い方・5枚目） |
| `slide-06.webp` | Meta-Proof（証拠・6枚目） |
| `slide-07.webp` | CTA（行動促進・7枚目） |

**画像仕様:**
- 形式: WebP（推奨） / JPG / PNG
- アスペクト比: 4:5
- 推奨サイズ: 1080×1350px
- 1枚あたりのファイルサイズ: 200KB以内推奨（表示速度のため）

---

### 3. デプロイ

`index.html` と `images/` ディレクトリ一式をXserverまたは任意のWebサーバーへアップロードするだけで動作します。

**Xserverへのアップロード手順:**
1. XserverのファイルマネージャーまたはFTPソフト（FileZilla等）でサーバーに接続
2. 公開ディレクトリ（例: `public_html/swipe-template/`）に `index.html` と `images/` フォルダをアップロード
3. ブラウザで該当URLにアクセスして動作確認

---

### 4. Nginx環境の場合

本テンプレートは静的HTMLのみのため、Nginx特別設定は不要です。`index.html` と `images/` フォルダをアップロードするだけで動作します。

---

## 改版履歴

| バージョン | 日付 | 変更内容 |
|---|---|---|
| v1.1.0 | 2026-04-30 | スワイプLP無料配布版対応。CTAボタンをChatwork受け取り用に変更。コーラルオレンジカラー適用。Swiper speed調整。公開時設定手順をREADMEに追記 |
| v1.0.0 | 2026-04-16 | 初版作成。無料配布版（MITライセンス）としてリリース。画像を `images/` に置くだけで横スワイプ LP が動作する簡易版 |
