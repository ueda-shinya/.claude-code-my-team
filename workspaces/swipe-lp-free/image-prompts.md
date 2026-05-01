# スワイプLP無料配布版 画像プロンプト集（Step 5.5 出力）

**作成**: ルナ（nano-banana）
**日付**: 2026-04-30
**前提**: design-spec.md（DESIGN-CONFIRMED 2026-04-30）/ copy.md / strategy-final.md
**共通パラメータ**:
- model: `gemini-3.1-flash-image-preview`
- aspectRatio: `4:5`
- imageSize: `2K`
- savePath: `~/.claude/workspaces/swipe-lp-free/images/slide-XX.webp`

---

## 役割コード一覧

| スライド | Primary | Secondary |
|---|---|---|
| slide-01 (Hook) | B-3（離脱防止） | E-2（理想未来）/ C-1（世界観設定） |
| slide-02 (Problem) | E-1（課題共感） | C-4（プロセス図示） |
| slide-03 (Empathy×Authority) | E-3（信頼・安心） | E-4（親近感） |
| slide-04 (Reveal) | E-2（理想未来） | C-2（オファー具体化）/ B-3 |
| slide-05 (How it works) | C-4（プロセス図示） | C-3（概念可視化） |
| slide-06 (Meta-Proof) | C-2（オファー具体化） | C-3（概念可視化） |
| slide-07 (CTA) | B-1（視線誘導） | C-1（世界観設定） |

---

## 画像1: slide-02.webp（新規生成）

### 基本情報
- 配置スライド: Slide 2（Problem / 課題3連打）
- アスペクト比: 4:5（1080×1350px）
- 形式: WebP / 200KB以内
- AI-readiness: ILLUSTRATIVE-OK

### 英語プロンプト（推奨）

```
A clean, minimal mobile slide design in portrait 4:5 format. Background color is warm off-white #FDF8F5, filling the entire canvas with no gradients. Layout is top-to-bottom vertical stack.

At the very top, a small section label in charcoal #2D2D2D, font-size 13px: "こんな悩み、ありませんか？"

Below the label, three problem cards arranged vertically, each card rectangular with rounded corners 8px, white background #FFFFFF, subtle drop shadow, left border 3px solid coral orange #E8694A, internal padding 14px 16px. Card height is compact, approximately 16-17% of total canvas height each. Cards are evenly spaced with 14px gaps.

Card 1 (top, visually emphasized): Bold text in charcoal #2D2D2D, 16px weight 700: "外注コーダーへの依頼で、" — next line in coral orange #E8694A, bold 700: "マージンが削られていく"

Card 2 (middle): Bold text in charcoal #2D2D2D, 15px weight 600: "画像の差し替えだけでも、" — next line: "コーダーに頼まないといけない"

Card 3 (bottom): Text in charcoal #2D2D2D, 14px weight 400: "納期がいつになるか、読めなくて困る"

Below the three cards, a narrow bridge text band: right-aligned italic text in medium gray #888888, font-size 13px, line-height 1.7: "こういう場面、わたしも色んなデザイナーさんと" — new line: "仕事しながら、ずっと見てきたんです。"

At the very bottom of the canvas, a swipe hint arrow in coral orange #E8694A: "→" centered, 20px.

NEVER include any blue or green colors. NEVER include any decorative photos or illustrations of people. NEVER add any extra text beyond what is specified. Keep the design flat, clean, editorial — Kinfolk magazine minimal aesthetic. Text must be rendered accurately in Japanese characters.
```

### ネガティブプロンプト
- Blue tones and green tones: completely absent
- Human figures or faces: entirely absent
- Photographic backgrounds: entirely absent
- Decorative elements: entirely absent
- Text other than specified Japanese strings: entirely absent

---

## 画像2: slide-05.webp（新規生成）

### 基本情報
- 配置スライド: Slide 5（How it works / 4ステップ＋ライセンス帯）
- アスペクト比: 4:5（1080×1350px）
- 形式: WebP / 200KB以内
- AI-readiness: ILLUSTRATIVE-OK

### 英語プロンプト（推奨）

```
A clean, minimal mobile slide design in portrait 4:5 format. Background color is warm off-white #FDF8F5, solid fill, no gradients. The overall aesthetic is editorial flat design, Wallpaper* magazine minimal.

At the top of the canvas, a headline in charcoal #2D2D2D, bold weight 800, font-size 24px: "使い方は、シンプルです。" A thin coral orange #E8694A horizontal rule 2px beneath the headline.

Below the headline, four step rows arranged vertically. Each step row consists of: a step number badge on the left (circle 32px diameter, coral orange #E8694A background, white text "1" "2" "3" "4" respectively, font-size 14px bold), and step text on the right in charcoal #2D2D2D, font-size 14px, line-height 1.6, maximum 2 lines per step.

Step 1 text: "Canvaで画像を作る（いつものツールでOK）"
Step 2 text: "テンプレの画像フォルダに差し替えて保存"
Step 3 text: "HTMLファイルを開く。それだけで動きます"
Step 4 text: "スマホでもPCでも、きれいに表示されます（自動対応）"

After the four steps, a horizontally oriented license band: light coral background #F5B09A, rounded corners 6px, height 40px, containing three items separated by slash symbols in charcoal: "商用利用OK　/　MITライセンス　/　クレジット任意" — all in charcoal #2D2D2D, font-size 13px.

At the very bottom of the canvas, tiny gray text font-size 11px in gray #AAAAAA: "コードが書ける方にとっても、画像の差し替えだけで済むのは時短になります。"

At the absolute bottom edge, centered coral orange arrow: "→ このLP、実は"

NEVER include any blue, green, or purple colors anywhere. NEVER include any photographs of people. NEVER add text beyond what is specified. Keep all Japanese text rendered accurately.
```

### 余白指定
- ステップ行間: 12px / 大見出し〜ステップ列間: 20px / ステップ列〜ライセンス帯: 16px / ライセンス帯〜(c)層一言: 12px / 上下マージン: 各40px

---

## 画像3: slide-06.webp（新規生成）

### 基本情報
- 配置スライド: Slide 6（Meta-Proof / 7枚ミニチュア構造解剖図・案4採用）
- アスペクト比: 4:5（1080×1350px）
- 形式: WebP / 200KB以内
- AI-readiness: ILLUSTRATIVE-OK

### 設計方針

7枚のミニチュアサムネイルを横並びで見せ、Slide 6の位置（6枚目）を強調枠で示す「構造解剖図」。AIに7枚のリアルなUIを描かせるとハルシネーションを起こすため、「7個の抽象的な矩形サムネイル枠」として指定する。

### 英語プロンプト（推奨）

```
A clean, minimal mobile slide design in portrait 4:5 format. Background color is warm off-white #FDF8F5, solid fill. A vertical coral orange #E8694A left accent line 4px runs the full height on the left edge of the content area.

At the top, a large headline in charcoal #2D2D2D, bold 800, font-size 22px, line-height 1.4: "実は、このLPも" — new line: "このテンプレで作っています。"

Below the headline, a horizontal row of exactly 7 small thumbnail frames arranged side by side. Each thumbnail is a small vertical rectangle approximately 4:5 proportion, width around 110px, height around 138px, with rounded corners 6px and a subtle border 1px solid #DDDDDD. The thumbnails represent the 7 slides of a swipe LP.

Thumbnail styling:
- Thumbnails 1 through 5: light warm gray background #EEEBE8, no internal text
- Thumbnail 6 (THIS slide, current position): highlighted with coral orange #E8694A solid border 3px and a small coral orange label tag above it reading "← いまここ" in white text on coral background, 10px
- Thumbnail 7: light warm gray background #EEEBE8, slightly faded opacity 60%

Below the thumbnail row, a small caption in charcoal #2D2D2D, font-size 13px, center-aligned: "Slide 1 から 7 まで、すべてこのテンプレの画像差し替えで作っています"

Below the caption, sub-copy in charcoal #2D2D2D, font-size 14px, line-height 1.7: "信じてもらうより、見てもらえればわかります。"

Below that, a (c)-layer note in gray #AAAAAA, font-size 12px: "コーディングと制作の分業の選択肢としても、使えます。"

A powered-by band near the bottom: background #F5B09A light coral, rounded corners 6px, padding 8px 16px, containing text in charcoal #2D2D2D font-size 11px: "powered by swipe-lp template　/　コーダー上田　/　officeueda.com"

At the very bottom edge, centered coral orange swipe hint: "→ 受け取り方は"

NEVER include any blue or green colors. NEVER include any photographs of real slides or real UI screenshots. NEVER add text beyond what is specified. Keep all Japanese characters rendered accurately and legibly.
```

### 重要設計補足

枠6に「← いまここ」タグを付けることで、閲覧者が「自分が今見ているこのLPがまさにこの構造で動いている」と気づく瞬間を作る。これがMeta-Proofとして機能する核心部分。AIへの指示では「7枚のリアルなスクリーンショット」は要求せず、「7枚の抽象的なサムネイル枠」として指定することでハルシネーションを防止する。

---

## 既存画像 加工指針（4枚 / Canva手動加工推奨）

### slide-01.webp 加工（Hook / 窓辺女性後ろ姿）

**手動加工手順**:
1. 元画像を1080×1350pxキャンバスに配置（上60%）
2. コーラルOR#E8694Aレイヤー透明度10%でオーバーレイ
3. 下部45%に白(#FFFFFF)塗りつぶし帯
4. 白帯内にSlide 1テキスト配置
5. 最下部「スワイプして読む →」コーラル

### slide-03.webp 加工（Empathy×Authority）

**素材**: `images/shinyaueda.jpg`（提供済・2026-04-30確認・72KB）

**手動合成手順（顔写真提供後）**:
1. 上部30%にslide-06.webp（暗室キーボード）+ コーラルOR40%オーバーレイ
2. 下部70%は白帯`#FDF8F5`
3. Slide 3コピーを配置（接続コピー・人物ブロック・Empathy・配布動機削除済）
4. 顔写真80px丸型クロップ
5. Authorityバッジ2個（横並び・コーラルOR背景白文字）

### slide-04.webp 加工（Reveal）

**手動加工手順**:
1. 元画像を1080×1350pxに全画面配置
2. コーラルOR#E8694A透明度15%全面オーバーレイ
3. 下部30%に白帯透明度90%
4. 白帯内にお客様の声テキスト（コーラル左ボーダー）

### slide-07.webp 加工（CTA / チャットUI）

**手動加工手順**:
1. 元画像（slide-08.webp）を1080×1350pxに全画面配置
2. コーラルOR#E8694A透明度35%全面オーバーレイ
3. 全テキスト白系で「ゴール画面感」演出
4. Chatwork IDブロック・合言葉例文枠・Zoom補足・フッター配置（design-spec.md参照）

---

## 生成優先度・運用メモ

**新規3枚の生成優先度**:
1. slide-02.webp（最も成功率高・最初に生成推奨）
2. slide-05.webp（情報量やや多・4:5に収まらなければ調整）
3. slide-06.webp（最難関・7枚サムネイル列のハルシネーション注意）

**slide-06.webp の代替案**: もし7枚列の再現が困難なら「5枚＋『全7枚』のキャプション表記」へ変更検討。

**Hybrid Language Strategy**: 新規3枚はすべて`gemini-3.1-flash-image-preview`で日本語直接レンダリング。文字化け発生時は英語プロンプトを生成用に使い、テキストをCanva後載せに切替。

**API呼び出し時の注意**: `imagen-4.0-generate-001`は`4:5`非対応のため使用禁止。`gemini-3.1-flash-image-preview`一択。

---

## 既存画像 AI再生成プロンプト（参考・万一の代替用）

slide-01〜07.webpの既存画像をAI再生成する場合の代替プロンプトは、ルナのStep 5.5出力を参照（slide-01: 窓辺女性 / slide-04: デザイン作業中女性 / slide-07: スマホチャットUI）。

通常はCanva手動加工を優先する。
