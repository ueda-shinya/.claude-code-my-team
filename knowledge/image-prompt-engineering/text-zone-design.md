# テキスト後載せ画像のゾーン設計パターン

LP・スライド・バナー等で「画像生成 → Canvaでテキスト後載せ」を行う場合のプロンプト設計パターン。

## 基本原則

「テキストエリアを確保」という抽象指示では不十分。背景の明度・均一性・コントラストを**プロンプトで明示的に制御する**。

## テキスト色 × 背景条件マトリクス

| テキスト色 | 必要な背景条件 | プロンプト指示例（英語） |
|---|---|---|
| **白（#ffffff）** | 指定領域を均一に暗く | `The upper fifteen to twenty-five percent of the frame is a uniformly dark, visually uncluttered zone, providing an optimal area for white text overlay` |
| **チャコール（#1a1a1a）** | 指定領域を均一に明るく | `The upper fifteen to twenty-five percent is the brightest and most evenly lit zone, providing an ideal area for dark charcoal text overlay` |
| **アクセントカラー（コーラルオレンジ等）** | 背景に同系光源を入れず、深い単色域を確保 | `The navy dominates with authority, the coral hints are restrained to the lowest fifteen percent only, ensuring centered coral text maintains maximum contrast` |
| **複数色混在（白＋チャコール等）** | 最も視認性が厳しい色を基準に設計。ゾーン別に条件を満たせない場合は複数ゾーンを設計 | ゾーン別にテキスト色に対応した条件を個別指示 |
| **複数ゾーン（上部＋下部にテキスト）** | 各ゾーンを個別にプロンプトで制御 | `The upper 15-25% uniformly bright for dark text overlay, AND the lower 35-50% uniformly dark for white text overlay` |
| **画像に既存テキスト/ロゴがある** | 既存要素の位置を避けてゾーン設計。または既存要素を削除する指示を明示 | `NEVER include any text, logos, or brand marks in the image` |

## よくある失敗

| NG | OK |
|---|---|
| "keep text space in the upper area" | "The upper fifteen to twenty-five percent of the frame is uniformly dark and visually clean, free of detail, for white text overlay" |
| 背景がテキスト色と似た明度になる | テキスト色と逆の明度を「均一に」確保する指示を入れる |
| オレンジ文字なのに背景にもオレンジ光源を入れる | 背景のアクセント光は画面端に限定（"restrained to the lowest 15%"等） |

## コピー内容との同期

プロンプトにコピー文を直接入れない（AIが文字を描こうとする）。代わりに：

1. **コピーの感情を分析**: 解放 / 共感 / 焦燥 / 転換 / 信頼 / 安心 / 行動促進 など
2. **感情を画像要素に変換**: 光・色・被写体・構図で表現
3. **コピーが載った完成形を想像**: テキストと画像が一体として機能するか

### 例：「コーディング、誰かに頼んでいいんです。」（解放感）

- ❌ コピー文を入れる → AIが文字を描き失敗
- ❌ 抽象的な指示 "a relaxing image" → 意味不明
- ✅ 感情を要素に変換: "No laptop, no screen, no digital device anywhere in the frame. A closed notebook and a coffee mug. The stillness that follows letting something go."

## テキストゾーン位置別の設計ガイド

### 上部15-25%にテキスト（FV・サービス・差別化等）
- 被写体は画面中部〜下部に配置
- 上部は背景（壁・空・机の余白）を均一に
- 光源の方向は上部の明度を乱さないものを選ぶ（サイド光・下からの反射光など）

### 下部35-55%にテキスト（共感・問題深掘り等）
- 被写体は画面上部〜中部に配置
- 下部はビジュアル要素をフェードアウトさせ暗部グラデーションを作る
- 深夜・薄暗い空間のシーンは下部が自然に暗くなる

### 中央にテキスト（転換点・感情ピーク）
- 全面背景系プロンプト（抽象アート・空・グラデーション）が相性良い
- 中央に被写体を置かない
- 背景は単色または緩やかなグラデーションで、文字の可読性を最優先

### 全面オーバーレイ型
- テキストの可読性はCanva側でオーバーレイ処理（黒40-60%→透明のグラデ）で確保
- 画像はビジュアル主体で設計OK
- テキスト色は基本白
- **※このパターンはプロンプト制御の対象外**（Canva側で処理）。agent定義のマトリクスには含まれない
