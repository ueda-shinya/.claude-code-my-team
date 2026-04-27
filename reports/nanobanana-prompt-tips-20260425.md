# Nano Banana 画像生成プロンプトテクニック包括リファレンス

調査日: 2026-04-25
調査者: ミオ（researcher）
対象モデル: Nano Banana 2（gemini-3.1-flash-image-preview）/ Nano Banana Pro（Gemini 3 Pro Image）/ Nano Banana（Gemini 2.5 Flash Image）

---

## Key Points

1. **プロンプトは「シーンを描写する」形式が最も効果的** — キーワード羅列より自然言語の叙述文で書く。「Subject + Action + Location + Composition + Style」の構造が推奨される
2. **カメラ・レンズ用語の活用が構図品質を大幅に向上させる** — "85mm portrait lens, f/1.8 shallow depth of field, golden hour backlighting"のような撮影用語を使うとプロ品質に近づく
3. **否定語（"no cars"）より肯定描写（"empty deserted street"）が安定する** — セマンティック否定（semantic negative prompting）と呼ばれる手法。公式ドキュメントで推奨
4. **テキスト生成は引用符で囲む** — 画像内に特定テキストを入れるとき `"Happy Birthday"` のように引用符を付けると認識率が上がる。フォント指定（"bold white sans-serif"等）も有効
5. **参照画像は最大14枚対応（Flash 3.1世代）** — キャラクター一貫性やブランドスタイルを維持するために参照画像をアップロードする。Pro も最大14枚。Flash 2.5世代は3枚が最適
6. **アスペクト比と解像度はプロンプト内またはAPIパラメータで明示指定する** — 未指定だと1:1になりやすい。Flash（3.1世代）は1:4・4:1・1:8・8:1の極端な縦横比も追加対応
7. **日本語プロンプトは公式サポート済みだが、英語との品質差は未検証** — 公式にサポート言語として明記されているが、文化的ニュアンスや慣用句では精度が落ちる可能性があるとGoogle自身が注記
8. **「手」「小さい文字」「6個以上のオブジェクト配置」は失敗しやすい** — 公式および複数のサードパーティガイドで一貫して指摘される弱点。対処法あり
9. **Nano Banana Pro は「考えてから生成」するThinking機能を持つ** — 論理的矛盾を修正してから生成するため複雑プロンプトに強い。Pro は API から無効化不可（常時有効）、**Nano Banana 2 は Thinking levels を Minimal（デフォルト）/ High / Dynamic で調整可能**。コストと速度のトレードオフをコントロールできる
10. **SynthID透かしが全出力に埋め込まれる** — 不可視の暗号透かし（SynthID）は全 tier で必ず付与。**可視透かし（Geminiスパークル）は Free + Google AI Pro tier で表示、Google AI Ultra および Google AI Studio（開発者ツール）では削除される**。商用利用時は tier 確認が必要

---

## Details

### 1. モデル概要（2026年4月時点）

| モデル名 | API ID | 別名 | 特徴 |
|---|---|---|---|
| Gemini 3.1 Flash Image | gemini-3.1-flash-image-preview | Nano Banana 2 | 2026年3月19日（米国時間）ロールアウト開始。Flash速度でPro品質に近い。Flow では全ユーザー無料・無制限、Gemini app では Free tier にクォータあり（超過時は従来 Nano Banana にフォールバック）、Google AI Plus / Pro / Ultra で上位クォータ |
| Gemini 3 Pro Image | gemini-3-pro-image-preview | Nano Banana Pro | 高品質・高推論。Thinking機能付き（API経由で無効化不可）。最大4K解像度 |
| Gemini 2.5 Flash Image | gemini-2.5-flash-image | Nano Banana | 高速・低レイテンシ。大量生成向け。1024px固定 |

**コンテキストウィンドウ:**
- Nano Banana 2 / Nano Banana Pro 共通: 入力最大1Mトークン
- 出力: テキスト最大64Kトークン / 画像最大4Kトークン

**知識カットオフ:** 2025年1月（リアルタイムウェブ検索グラウンディング機能で補完可能）

---

### 2. プロンプト構造

#### 基本フォーミュラ（Tier 1: Google公式）

```
[Subject] + [Action] + [Location/Context] + [Composition/Camera] + [Lighting/Atmosphere] + [Style/Media]
```

例:
```
A weathered fisherman mending nets on a wooden dock at golden hour, low-angle shot with
shallow depth of field (f/1.8), warm backlighting creating long shadows,
cinematic color grading with muted teal tones, shot on Fujifilm with authentic film grain
```

#### 拡張フォーミュラ（テキスト・ブランド案件向け）

```
[Subject] + [Action] + [Location] + [Composition] + [Lighting] + [Style] +
[Text instruction: "テキスト内容", font style] + [Factual constraints]
```

#### 編集指示フォーミュラ

```
Change only the [target element] to [new element], keeping everything else unchanged,
maintaining the original lighting and perspective
```

---

### 3. カメラ・レンズ用語リファレンス

**焦点距離:**
- `24mm wide-angle` — 広大なスケール、建築・風景
- `50mm natural` — 肉眼に近い自然な視点
- `85mm portrait lens` — ポートレートに最適、背景ぼけ
- `200mm telephoto` — 遠景圧縮効果

**絞り（被写界深度）:**
- `f/1.4` — 超浅い被写界深度（背景大きくぼける）
- `f/1.8` — 浅い被写界深度（ポートレート定番）
- `f/8` — バランス良い深度
- `f/16` — 深い被写界深度（全体にピント）

**アングル・構図:**
- `low-angle shot` — 見上げ、迫力
- `aerial view / overhead shot` — 俯瞰
- `Dutch angle` — 傾斜アングル、不安感
- `macro shot` — 極近接、微細テクスチャ
- `extreme close-up` — 超クローズアップ
- `wide shot` — 引きの画
- `cinematic framing` — シネマスコープ的構成

**ライティング:**
- `golden hour backlighting` — 黄金時間の逆光
- `Chiaroscuro lighting` — 強いコントラストの劇的照明
- `three-point softbox setup` — スタジオ3点照明
- `Rembrandt / butterfly / split / rim lighting` — 定番ポートレート照明パターン
- `color temperature 2700K（warm）/ 5600K（daylight）/ 7000K（cool）`

**色調・フィルム:**
- `1980s color film, slightly grainy` — レトロフィルム感
- `cinematic color grading with muted teal tones` — 映画的トーン
- `Fujifilm authentic color science` — 富士フイルムの色味
- `GoPro immersive distorted` — アクション感のある広角歪み
- `disposable camera raw nostalgic` — 使い捨てカメラ風ノスタルジー

---

### 4. スタイル・メディア用語リファレンス

**写真・リアル系:**
- `photorealistic` / `hyper-realistic photography`
- `ultra-realistic finish`
- `stock photo natural authentic posing`

**アート・イラスト系:**
- `watercolor painting`
- `Impressionist oil painting`
- `Octane render` / `8K cinematic rendering`
- `vector art aesthetics`
- `cel-shading`
- `impasto brushstrokes`
- `high-contrast black and white inks`

**サブカルチャー系:**
- `kawaii-style`
- `cyberpunk aesthetic with volumetric fog`
- `retro-futuristic atomic-age aesthetics`
- `anime character illustration expressive features`
- `film noir gritty high-contrast`
- `psychedelic-inspired`

**クリエイティブ特殊系:**
- `calligram`（文字でシルエットを形成）
- `minimalist negative space`
- `1990s product photography`
- `1960s / 1970s retro aesthetics`

---

### 5. 否定表現の使い方（セマンティック否定）

Nano Banana は `"no X"` のような直接否定より、望む状態を肯定的に描写する「セマンティック否定（semantic negative prompting）」が有効。公式ドキュメント・Google Cloud Blog ともに同方針を推奨。

| やりたいこと | NG（直接否定） | OK（セマンティック否定） |
|---|---|---|
| 車を除外 | "no cars" | "an empty, deserted street with no signs of traffic" |
| 人を除外 | "no people" | "a completely empty plaza, utterly abandoned" |
| 背景をシンプルに | "no background clutter" | "clean white studio background" / "vast empty canvas" |
| 手を自然に | "no deformed hands" | "realistic hands, natural hand pose, accurate finger count" |
| 肌を自然に | "no smooth plastic skin" | "visible skin texture, natural pores, realistic imperfections" |

**コンテンツポリシーのトリガー回避:** Googleのフィルタリングは Midjourney / Stable Diffusion より保守的。物理的描写の形容詞がフィルターに引っかかる場合は別の語で言い換える（公式はキーワードベースフィルタリングを採用）。

---

### 6. Nano Banana 固有の強みとクセ

**強み:**
- テキストレンダリング精度が競合より高い（日本語・アラビア語等多言語対応）
- マルチターン会話編集（「さっきの画像の照明を暖かくして」が自然に機能）
- Google Search グラウンディングによるリアルタイム情報反映（ダイアグラム・データビジュアル向け）
- 多様なアスペクト比対応（特にFlash 3.1世代は1:8・8:1等の極端比も対応）
- APIコスト：競合比で約$0.04/megapixel（Imagen等の$0.17比で低コスト）[UNVERIFIED: Tier 3情報]

**クセ・注意点:**
- ファインアート・アニメ・抽象シュルレアリズムは Midjourney v6 に劣ると複数サイトが指摘
- 6個以上のオブジェクトを1シーンに正確に配置するのが苦手（ワークアラウンド: Canvaでオブジェクトに番号ラベルを付けた参照画像を使う）[UNVERIFIED: Tier 3情報]
- キャラクター一貫性はセッション依存（新セッション開始時に参照画像の再アップロードが必要）
- テキスト精度は約85%（6回に1回は修正が必要）[UNVERIFIED: Tier 3情報]
- 透明背景は非対応（2026年4月時点）

---

### 7. 日本語プロンプト vs 英語プロンプト

**公式の立場（Google公式・複数ソース）:**
- 日本語は公式サポート対象言語として明記されている
- Nano Banana 2 は「16言語の多言語プロンプトライブラリ」コミュニティが成立するほど各言語に対応
- 文法・スペル・文化的ニュアンス・慣用句では精度が落ちる可能性があるとGoogle自身が注記

**実測比較データ: 未確認（2026-04-25 時点）**
- 英語 vs 日本語での生成品質の定量比較データは公式・信頼できるサードパーティともに見当たらない
- 「サポートしている」と「品質が同等である」は別の主張であり、要検証

**実務上の推奨（情報不足のため暫定）:**
- 日本語画像内テキスト生成（日本語の看板・ポスター等）は日本語プロンプトが有利な可能性
- 構図・照明・スタイルの制御は英語の撮影用語・美術用語が豊富なため、英語プロンプトの方がコントロールが効きやすい可能性
- ただし上記は論理的推測であり、実測値ではない

---

### 8. 解像度とアスペクト比

**Nano Banana 2（Gemini 3.1 Flash Image）:**

| 解像度 | 表記 | 備考 |
|---|---|---|
| 512px | 0.5K | Flash独自オプション |
| 1024px | 1K | — |
| 2048px | 2K | — |
| 4096px | 4K | — |

**注意:** APIパラメータで解像度を指定する場合は大文字の「K」を使用（小文字は拒否される）

**対応アスペクト比（Nano Banana 2 フル一覧）:**
`1:1, 1:4, 1:8, 2:3, 3:2, 3:4, 4:1, 4:3, 4:5, 5:4, 8:1, 9:16, 16:9, 21:9`

**Pro / 2.5 Flash の対応比（標準）:**
`1:1, 2:3, 3:2, 3:4, 4:3, 4:5, 5:4, 9:16, 16:9, 21:9`

**マルチ画像入力時の挙動:** 複数画像を入力した場合、出力は最後に入力した画像のアスペクト比を採用。固定したい場合はプロンプトで明示指定。

---

### 9. 参照画像活用

- **Flash（3.1世代）:** 最大14枚の参照画像
- **Flash（2.5世代）:** 3枚が最適（それ以上は品質が不安定）
- **対応フォーマット:** PNG, JPEG, WebP, HEIC, HEIF
- **ドキュメント入力:** PDFおよびテキストファイルも入力可能（PDFは最大50MB / 1,000ページまで。inline送信時はリクエスト全体で20MB上限あり、それを超える場合は Files API または Cloud Storage 経由で送信）

**枚数 vs 一貫性の上限（公式明示）:**
- **Nano Banana Pro:** 入力枚数は最大14枚だが、人物の一貫性が高品質に保たれるのは **最大5人** まで
- **Nano Banana 2:** 入力枚数は最大14枚、人物類似性は **最大4人**、オブジェクト忠実度は **最大10個** まで

**活用パターン:**
- キャラクター一貫性: 人物や製品の参照画像を渡し、名前（例:"Hana"）を付けて参照
- ブランドスタイル移植: ブランドガイドラインの画像を入力してスタイルを踏襲させる
- 構図コントロール: Canvaなどでラベル付き参照画像を作成し、複雑なオブジェクト配置を補助

---

### 10. 失敗パターンと対処法

| 失敗パターン | 発生率・傾向 | 対処法 |
|---|---|---|
| 手の解剖学的誤り（指の本数・形状） | 高頻度 | "realistic hands, natural hand pose, accurate finger count" を追加 |
| テキスト誤字・文字化け | 約15%（6回に1回）[Tier 3] | フォローアッププロンプトで修正 / 重要テキストは再生成 |
| 6個以上オブジェクトの配置ミス | 高頻度[Tier 3] | Canvaでオブジェクトに番号ラベルを付けた参照画像を使用 |
| 背景置換後の光源不整合 | 中程度 | "match perspective, color temperature, contact shadow" を指定 |
| キャラクタードリフト（複数ターン後の顔変化） | セッション依存 | 詳細な人物描写をプロンプト先頭に再記述 / 参照画像を再アップロード |
| 過度に滑らかな肌 | 中程度 | "visible skin texture, natural pores, realistic imperfections" |
| ステリル（清潔すぎる）外観 | 中程度 | "dust particles in light, subtle lens flare, slight film grain" |
| フィルタートリガー誤作動 | 不定 | 物理的形容詞を別の表現に言い換える |
| 抽象・アニメ・ファインアート | 系統的弱点 | この領域はMidjourneyを検討 |

---

## すぐ使えるプロンプトテンプレート（7選）

### Template 1: ポートレート（プロ品質）

```
A [年齢・性別] with [外見の特徴], wearing [服装の詳細],
standing in [場所・環境]. Shot with 85mm portrait lens at f/2.8,
[照明タイプ] light from the [方向].
[感情・雰囲気]. Photorealistic, high-fidelity skin texture.
```

例:
```
A 35-year-old Japanese woman with short silver hair, wearing a navy linen blazer,
standing in a sunlit bookstore. Shot with 85mm portrait lens at f/2.8,
soft diffused light from the left. Thoughtful, quietly confident expression.
Photorealistic, high-fidelity skin texture, visible skin pores.
```

### Template 2: 商品モックアップ

```
A [商品] on [背景・素材]. Three-point softbox studio lighting,
[視点・アングル]. Ultra-sharp focus on [強調したい部分],
[素材感の描写]. Product photography, 16:9.
```

例:
```
A minimalist ceramic coffee mug with "Morning Ritual" written in clean sans-serif font
on a slate grey stone surface. Three-point softbox studio lighting,
elevated angle. Ultra-sharp focus on the typography,
matte ceramic texture with subtle imperfections. Product photography, 16:9.
```

### Template 3: データ/インフォグラフィック（Nano Banana Proの強み領域）

```
Create a [タイプ] diagram showing [内容].
Use [カラーパレット] color palette with [フォントスタイル] typography.
Include the following text elements: "[テキスト1]", "[テキスト2]".
Clean, modern design, white background, 16:9 aspect ratio.
```

### Template 4: SNSコンテンツ（縦型）

```
[シーン描写], [スタイル].
Bold text overlay at the top: "[見出しテキスト]" in [フォントスタイル].
Vertical format, 9:16 aspect ratio. High contrast for mobile viewing.
```

### Template 5: ロゴ/ブランドアセット

```
A logo for [ブランド名/業種], [ブランドの性格・トーン].
Symbol: [シンボルの描写]. Text: "[ブランド名]" in [フォントスタイル].
[カラーパレット]. Clean vector aesthetic, white background, 1:1.
```

### Template 6: 日本語テキストを含む画像

```
[シーン描写]. Include a [看板/ポスター/パッケージ] in the scene
that reads "「[日本語テキスト]」" in [フォントスタイル] Japanese typography.
[スタイル・雰囲気].
```

例:
```
A bustling Tokyo street market scene at night. Include a neon sign in the background
that reads "「新鮮市場」" in bold, illuminated katakana and kanji.
Cyberpunk aesthetic, volumetric fog, deep shadows with neon glow, 16:9.
```

### Template 7: 画像編集（アップロード済み画像を変更）

```
In this image, change only the [変更したい要素] to [変更内容].
Keep everything else exactly the same — maintain the original lighting,
color temperature, perspective, and all other elements unchanged.
```

---

## 注意: 矛盾・要検証事項

1. **日本語プロンプト品質の定量的比較データが存在しない（要検証）**
   - 公式は「サポートしている」と述べるが、英語と同等品質であるという証拠は見当たらない
   - 実務上は英語プロンプトとの A/B テストを推奨

2. **参照画像の最大枚数に情報の揺れあり**
   - Google Cloud Blog では「6〜14枚」と記載、ai.google.dev の公式ドキュメントでは「最大14枚」と記載
   - APIドキュメントの「14枚」を Tier 1 情報として採用。「6枚」は旧情報または別モデル向けの可能性

3. **"Nano Banana" という名称の由来について**
   - aivideobootcamp.com が「この名称を最も広めたコミュニティはAI Video Bootcamp」と主張しているが、これは自己申告であり第三者検証なし

4. **Canva ラベル法の信頼性（Tier 3 情報）**
   - 複数オブジェクト配置問題への「Canvaでラベリング」ワークアラウンドは公式ドキュメントに記載なし
   - コミュニティ発見の非公式テクニックであり、モデル更新で挙動が変わる可能性あり

5. **APIコスト比較（$0.04/megapixel）の信頼性（Tier 2 情報）**
   - superprompt.com（Tier 2）の記載。公式価格ページとの照合は未実施
   - 2026年4月時点での最新料金は https://ai.google.dev/pricing で要確認

---

## Sources

### Tier 1（公式ドキュメント・公式ブログ）

**製品アナウンス・概要:**
- [Nano Banana 2: Google's latest AI image generation model - blog.google](https://blog.google/innovation-and-ai/technology/ai/nano-banana-2/) — リリース日・利用可能 tier の根拠
- [Build with Nano Banana 2 - blog.google](https://blog.google/innovation-and-ai/technology/developers-tools/build-with-nano-banana-2/) — 開発者向けアナウンス
- [Nano Banana Pro: Gemini 3 Pro Image model - blog.google](https://blog.google/innovation-and-ai/products/nano-banana-pro/) — Pro 製品ページ

**プロンプト・ベストプラクティス:**
- [Nano Banana image generation - Google AI for Developers](https://ai.google.dev/gemini-api/docs/image-generation) — 公式APIドキュメント（アスペクト比・解像度・パラメータ）
- [Gemini image generation best practices | Google Cloud Documentation](https://docs.cloud.google.com/vertex-ai/generative-ai/docs/multimodal/gemini-image-generation-best-practices) — Google Cloud 公式ベストプラクティス
- [Nano Banana Pro image generation in Gemini: Prompt tips | Google Blog](https://blog.google/products-and-platforms/products/gemini/prompting-tips-nano-banana-pro/) — Google 公式プロンプトガイド
- [How to prompt Gemini 2.5 Flash Image Generation | Google Developers Blog](https://developers.googleblog.com/en/how-to-prompt-gemini-2-5-flash-image-generation-for-the-best-results/) — Google Developers 公式ガイド
- [How to create effective image prompts with Nano Banana — Google DeepMind](https://deepmind.google/models/gemini-image/prompt-guide/) — Google DeepMind 公式プロンプトガイド
- [Tips for getting the best image generation | Google Blog](https://blog.google/products-and-platforms/products/gemini/image-generation-prompting-tips/) — Google 公式Geminiアプリ向けチップス

**モデル仕様・コンテキストウィンドウ・Thinking:**
- [Gemini 3 Developer Guide - ai.google.dev](https://ai.google.dev/gemini-api/docs/gemini-3) — コンテキストウィンドウ・Thinking levels の根拠
- [Gemini 3.1 Flash Image Preview - ai.google.dev](https://ai.google.dev/gemini-api/docs/models/gemini-3.1-flash-image-preview) — Nano Banana 2 モデルカード
- [Gemini 3 Pro Image Preview - ai.google.dev](https://ai.google.dev/gemini-api/docs/models/gemini-3-pro-image-preview) — Nano Banana Pro モデルカード
- [Gemini 2.5 Flash Image - ai.google.dev](https://ai.google.dev/gemini-api/docs/models/gemini-2.5-flash-image) — 旧 Nano Banana モデルカード
- [Gemini 3.1 Flash Image - Vertex AI Docs](https://docs.cloud.google.com/vertex-ai/generative-ai/docs/models/gemini/3-1-flash-image) — 参照画像枚数・一貫性上限の根拠
- [Gemini 3.1 Flash Image Model Card - deepmind.google](https://deepmind.google/models/model-cards/gemini-3-1-flash-image/) — DeepMind モデルカード
- [Gemini 3 Pro Image (Nano Banana Pro) - deepmind.google](https://deepmind.google/models/gemini-image/pro/) — Pro 一貫性上限の根拠
- [Gemini 3.1 Flash Image (Nano Banana 2) - deepmind.google](https://deepmind.google/models/gemini-image/flash/) — Flash 3.1 モデル公式ページ

**ドキュメント入力・SynthID:**
- [Document understanding - ai.google.dev](https://ai.google.dev/gemini-api/docs/document-processing) — PDF入力上限の根拠
- [File input methods - ai.google.dev](https://ai.google.dev/gemini-api/docs/file-input-methods) — inline 20MB / Files API 50MB の根拠
- [SynthID - deepmind.google](https://deepmind.google/models/synthid/) — SynthID 透かし仕様の根拠

### Tier 2（技術メディア・確立された開発者ブログ）

- [Ultimate prompting guide for Nano Banana | Google Cloud Blog](https://cloud.google.com/blog/products/ai-machine-learning/ultimate-prompting-guide-for-nano-banana) — Google Cloud Blog（Tier 1 に準じる信頼性）
- [Ultimate Nano Banana Pro Prompting Guide [2026] - Atlabs AI](https://www.atlabs.ai/blog/the-ultimate-nano-banana-pro-prompting-guide-mastering-gemini-3-pro-image) — 技術系 AI ブログ
- [60 Best Nano Banana Prompts - superprompt.com](https://superprompt.com/blog/best-nano-banana-prompts-google-gemini-image-generator) — プロンプトライブラリ専門サイト
- [eWeek - 6 Best Nano Banana 2 Prompts in 2026](https://www.eweek.com/news/best-nano-banana-2-prompts-gemini-3-1-flash-image/) — 確立されたテックメディア

### Tier 3（補足参考・信頼性低め）

- [Nano Banana Pro Complete Guide 2026 - aivideobootcamp.com](https://aivideobootcamp.com/blog/nano-banana-pro-complete-guide-2026/) — コミュニティサイト（失敗パターン・制限事項の具体数値はTier 3情報として扱うこと）
- [GitHub - YouMind-OpenLab/awesome-nano-banana-pro-prompts](https://github.com/YouMind-OpenLab/awesome-nano-banana-pro-prompts) — コミュニティキュレーション（16言語対応を傍証）
- [Lucidpic - Nano Banana Prompts](https://lucidpic.com/prompts/nano-banana) — プロンプトギャラリー（参考程度）

---

## ファクトチェック履歴

- **2026-04-25** ミオ（researcher）一次調査
- **2026-04-25** リク（fact-checker）ファクトチェック実施 → 事実誤認3件（Nano Banana 2 のリリース日 / コンテキストウィンドウ / PDF入力上限）と追記推奨4件（tier別利用条件 / 参照画像の枚数vs一貫性 / SynthID 可視透かしのtier差 / Thinking levels）を指摘
- **2026-04-25** アスカ（chief-of-staff）が指摘内容をレポートに反映、Tier 1 ソースを再構成
- **総合判定（リク）**: 修正反映後のレポートは実務利用に耐える品質。[UNVERIFIED] / [Tier 3] マーカーが付いた項目（APIコスト / テキスト精度 / 6個以上配置失敗 / Canvaラベル法）は公式裏付けが取れず現状維持
