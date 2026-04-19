---
name: manga-generator
description: "NanoBananaPRO（Gemini 3.1 Flash Image）で10ページ漫画を生成するためのプロンプト群を、キャラクター画像とテーマから作成する。ハイブリッド言語戦略（視覚=英語/セリフ=日本語）とSTRICT CONSISTENCYでキャラ・画風の一貫性を保つ。「10ページ漫画作って」「漫画プロンプト欲しい」「manga-generator」で起動。"
tools: Read
---

# Manga Generator

NanoBananaPRO（Gemini 3.1 Flash Image）で**10ページ漫画を生成するプロンプト群**を、キャラクター画像とテーマから作成するスキル。

**依拠資料**: かし子氏（@Kashiko_AIart）「NanoBananaPRO 10ページ漫画作成プロンプト Ver.6」

**依拠ナレッジ**: `~/.claude/knowledge/image-prompt-engineering/prompt-engineering.md`（ハイブリッド言語戦略、STRICT CONSISTENCY）

## 適用条件

- 使う場面: キャラクター画像が提供された10ページ前後の漫画・カルーセル制作、SNS用ショート漫画、広告マンガ制作
- 使わない場面: キャラクター未確定の漫画（→ まずキャラデザから）、単発イラスト（→ ルナ直接依頼）、実写ベースのコンテンツ（→ 別スキル・ルナへ）

## 入力

| 項目 | 必須/任意 | 説明 | 例 |
|---|---|---|---|
| キャラクター画像 | 必須 | 主人公の参考画像（実写・イラスト問わず） | （画像添付） |
| テーマ / あらすじ | 必須 | 漫画のテーマまたは大まかな筋 | "AI時代の駆け出しデザイナーの奮闘記" |
| ページ数 | 任意 | デフォルト10ページ | 5ページ / 10ページ / 15ページ |
| 画風の方向性 | 任意 | 入力画像から自動判定するが上書き可 | アニメ調 / 実写風 / 水彩風 |
| 言語 | 任意 | セリフの言語（デフォルト日本語） | 日本語 / 英語 |

## 実行ステップ

### ステップ1: キャラクター画像の確認と方針提示

- 入力: キャラクター画像（またはユーザーからの指示）
- 処理:
  - まずユーザーに「キャラクター画像を確認しました。ではストーリーを作成しますので、テーマやあらすじを教えてください。もしくは、以下の提案から選んでください。」と返答する
  - 画像が **実写** か **イラスト** かを判定する
  - 判定に基づいた**画風定義プロンプト（ドラフト・英語）**を提示する
    - 実写の場合例: `Photorealistic portrait style, cinematic lighting, shallow depth of field, natural skin texture, 4K detail`
    - イラストの場合例: `Thick confident outlines, cel shading with soft gradients, vibrant high-saturation colors, anime screentones, dynamic angles`
- 出力: 画風定義ドラフト（英語）、キャラ判定結果

### ステップ2: ストーリー案を5パターン提案する

- 入力: テーマ（ユーザー指定）、キャラクター情報
- 処理: コトの「ストーリー5パターン提案フレームワーク」を使って以下5案を提示する：

  - **案1：王道・トレンド（Current Trends）** — 今SNS/世間で流行っているネタ・季節イベント等を取り入れたタイムリーな案。この1案のみトレンド重視
  - **案2：ユーザー・コンテキスト（User Context）** — ユーザーの会話履歴や属性・クラスターに刺さる案。この1案のみユーザー傾向重視
  - **案3：意外性・ギャップ（Gap）** — キャラクターの外見・設定とのギャップを極端に広げたコミカル／シュールな展開
  - **案4：エモーショナル（Emotional）** — 読者の感情（癒やし・哀愁・感動）を揺さぶる物語重視の案
  - **案5：イマジネーション（Unbound）** — トレンドや常識を完全に無視した独創的な案

  各案にタイトル + 1行あらすじを付ける
- 出力: 5案リスト

### ステップ3: STRICT CHARACTER CONSISTENCY 定義を作成する

- 入力: キャラクター画像
- 処理:
  - キャラクターの外見を**英語で詳細に**記述する（髪/目/服装/体型/アクセサリー/年齢感）
  - 省略せず、全要素を記述する
- 出力: `# STRICT CHARACTER CONSISTENCY (DO NOT CHANGE)` ブロック

**例:**
```
# STRICT CHARACTER CONSISTENCY (DO NOT CHANGE)
Japanese woman, late twenties, natural medium-length chestnut hair with soft waves,
warm almond-shaped dark brown eyes, wearing a cream oversized knit sweater and dark jeans,
slim build, height approximately 165cm, relaxed natural expression.
```

### ステップ4: STRICT STYLE CONSISTENCY 定義を作成する

- 入力: ステップ1で決めた画風ドラフト、ユーザー調整要望
- 処理:
  - 線（Lines）・彩色（Color）・照明（Lighting）の3要素を英語で詳細記述
- 出力: `# STRICT STYLE CONSISTENCY (DO NOT CHANGE)` ブロック

**例（アニメ調の場合）:**
```
# STRICT STYLE CONSISTENCY (DO NOT CHANGE)
Thick confident outlines, cel shading with soft gradients,
vibrant high-saturation colors with coral-teal-cream palette,
dramatic dynamic lighting from upper left, anime screentones for backgrounds,
Studio Ghibli inspired composition.
```

### ステップ5: 10ページのストーリー構成を作成する

- 入力: 選ばれたストーリー案、ページ数
- 処理:
  - **Page 1 または Page 2** のどちらかに**タイトルコマ**を配置する（タイトル文字を装飾的タイポグラフィで画像内描画）
  - **Page 1 (The Hook)**: 読者の興味を強く惹きつける**強力なフリ**（謎・衝撃・共感）
  - **Page 10 (The Punchline)**: オチまたは次への引きとなる結末
  - 各ページは**4〜8コマ**（3コマ以下は禁止）
  - 読み順は**右上→左下**（日本の漫画スタイル）
- 出力: ページごとのストーリー概要・コマ割り・セリフ

### ステップ6: 各ページのプロンプトを生成する

- 入力: ストーリー構成、Character/Style定義、ページ内コマ割り
- 処理: 以下のテンプレートに従って各ページのプロンプトを作成する
- 出力: ページごとのコピペ用プロンプト（Code Block形式）

#### 各ページのプロンプトテンプレート

```text
【IMAGE_GEN_ONLY】: Generate a high-resolution professional Japanese manga page. Full Color. Vertical 9:16. 4K.

# STRICT CHARACTER CONSISTENCY (DO NOT CHANGE)
[ステップ3で作成した定義を全文コピペ。省略禁止]

# STRICT STYLE CONSISTENCY (DO NOT CHANGE)
[ステップ4で作成した定義を全文コピペ。省略禁止]

# PAGE NUMBER
Small clear text "X" in the bottom-right corner.

# PANEL LAYOUT & VISUALS (Target: 4-8 Panels, Top-Right to Bottom-Left)

## Panel 1 (Top-Right):
- Visual: [英語で場面・アクション・アングルを描写]
- Text: "[日本語のセリフをダブルクォートで]"

## Panel 2 (Top-Left):
- Visual: [英語で描写]
- Text: "[日本語セリフ]"
- Background SFX: "[日本語擬音]"

[Panel 3〜N まで継続]

# NEGATIVE PROMPT
- Text generation, story writing, screenplay format.
- Output ONLY the image generation prompt.
```

**重要な3鉄則**:
1. **ハイブリッド言語戦略**: 視覚描写は英語、セリフ・擬音は日本語
2. **STRICT CONSISTENCY**: Character / Style 定義は全ページで**一言一句同じまま全文記述**（省略・"As above" 禁止）
3. **コマ数**: 各ページ4-8コマ、3コマ以下禁止

### ステップ7: タイトルコマ専用の追加指示

- 入力: 決定したタイトル、Page 1 or 2（どちらに配置するか）
- 処理:
  - タイトルコマのPanelに以下を追加:
    - `Title Text: "【タイトル】" in decorative typography, large prominent placement, stylized manga title design.`
- 出力: タイトル描画指示を含んだ対象ページのプロンプト

### ステップ8: 出力フォーマットにまとめる

- 入力: ステップ3〜7のすべての成果物
- 処理: 以下の最終チェックを行った上で出力フォーマットに整形
  - [ ] Character定義・Style定義が**全ページに全文記述**されているか
  - [ ] 「省略」「As above」「(see page 1)」等の短縮表記がないか
  - [ ] Page 1 または 2 にタイトルコマが配置されているか
  - [ ] Page 1 に強力なフック、Page 10 にオチがあるか
  - [ ] 各ページ4〜8コマか
  - [ ] 視覚描写は英語、セリフは日本語か
  - [ ] Vertical 9:16 アスペクト比指定があるか
- 出力: 最終成果物

## 判断基準

| 状況 | 判断 | 理由 |
|---|---|---|
| キャラクター画像が未提供 | ステップ1で「画像または詳細な外見情報」を求める | STRICT CONSISTENCYの土台が作れない |
| テーマがないがキャラ画像あり | ステップ2の5案提案を先に行い、ユーザーが選ぶ | 出発点が必要 |
| ユーザーが画風を指定した | ステップ1の自動判定を上書きしユーザー指定を採用 | ユーザー意図優先 |
| ページ数が5ページ以下 | フック/タイトル/オチ の3要素を圧縮適用 | 短編でも基本構造は保つ |
| コマ数が4に満たないページ案 | 警告し、最低4コマに増やすよう再構成 | 3コマ以下は禁止ルール |
| Character定義を途中で変更したい | ユーザー承認後、全ページのCharacter定義ブロックを一括更新 | 一貫性維持のため一括対応 |
| Nano Banana 2が日本語文字描画できない場合 | セリフを英語にフォールバック、またはフキダシを空にしてCanvaで後載せ | 原典「文字描画可能」の前提が崩れた時の手当て |

## 出力フォーマット

```
## 10ページ漫画プロンプト

### 画風判定・定義
- 入力画像判定: {{実写 or イラスト}}
- 画風定義（全ページで使用）:
  ```
  # STRICT STYLE CONSISTENCY (DO NOT CHANGE)
  {{Style定義全文}}
  ```

### キャラクター定義（全ページで使用）
```
# STRICT CHARACTER CONSISTENCY (DO NOT CHANGE)
{{Character定義全文}}
```

### ストーリー概要
- タイトル: {{決定タイトル}}
- フック (Page 1): {{謎・衝撃・共感の要素}}
- 転換点: {{主人公が変わる瞬間のページ}}
- オチ (Page 10): {{結末・次への引き}}

### 各ページのプロンプト

#### Page 1

```text
{{Page 1 プロンプト全文}}
```

#### Page 2

```text
{{Page 2 プロンプト全文}}
```

（以下 Page 10 まで続く）

### 使い方
1. Gemini アプリ（gemini.google.com）または Nano Banana PRO にアクセス
2. 元のキャラクター画像を添付
3. 各ページのプロンプトをコピペして1ページずつ生成
4. 生成結果のセリフや表情が意図と違う場合は、該当ページのVisual/Textを微調整して再生成

### 注意
- Character定義・Style定義は**全ページで一言一句同じ**にすること。省略すると一貫性が崩れる
- 日本語セリフが正確に描画されない場合は、再生成 or セリフを画像後載せに切替
- 9:16 縦型想定。横型が必要な場合はVisual記述とアスペクト比指定を調整
```

## よくある失敗パターン

| NG | OK | 理由 |
|---|---|---|
| Character定義をPage 1だけ書いて以降「As above」 | 全ページに全文コピペ | AIは各プロンプト独立処理なので前を覚えていない |
| Style定義を「cel shading」等1行で済ます | Lines / Color / Lighting を詳細記述 | 詳細度が低いと画風がブレる |
| セリフ・擬音まで英語で書く | セリフは日本語、視覚は英語のハイブリッド | 英語セリフは日本語漫画に合わない |
| 3コマ以下のページを作る | 最低4コマ、理想5-8コマ | 情報密度と読みやすさのバランス |
| タイトルコマを配置しない | Page 1 or 2 に装飾的タイトル文字を入れる | SNSで目を引くため |
| Page 1 がフックになっていない | 謎・衝撃・共感のどれかを先出し | 読者が続きを読まない |
| 画像生成時にキャラ画像を添付しない | 必ず元画像を添付 | 画像→画像の参照で一貫性が上がる |

## 連携

- **画像生成実行**: 本スキルはプロンプト提案のみ。実際の生成は Gemini アプリ（ユーザー側）または `gemini-3.1-flash-image-preview` API
- **ルナ（nano-banana）との使い分け**:
  - manga-generator: 10ページ漫画・シリーズコンテンツ特化
  - ルナ: 単発画像、5コンポーネント式、LP/スライド等
- **コト（copywriter）との連携**: ストーリー5パターン提案はコトの知識と同期（共通フレームワーク）

## Source

- かし子氏（@Kashiko_AIart）「NanoBananaPRO 10ページ漫画作成プロンプト Ver.6」
- 関連ナレッジ: `~/.claude/knowledge/image-prompt-engineering/prompt-engineering.md`（ハイブリッド言語戦略、STRICT CONSISTENCY セクション）
