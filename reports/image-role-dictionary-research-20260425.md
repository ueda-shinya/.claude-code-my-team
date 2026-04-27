# LP・Webサイトデザインにおける画像の役割 — 体系化知識ベース

**調査日**: 2026-04-25
**調査者**: ミオ（Researcher）
**目的**: 「画像役割辞書」設計のための基盤知見収集（後日ラウンドテーブルで設計確定予定）

---

## 1. エグゼクティブサマリー

- 「画像の役割」は学術・実務ともに「装飾的 / 説明的 / 説得的 / 象徴的 / 変容的」の5軸で捉えられており、Carney & Levin（2002）の5機能分類が最も参照されている。装飾的画像はほぼ学習・説得効果ゼロとされ、実務での使用は最小化すべきとされる。
- ニールセン・ノーマン・グループのアイトラッキング研究により、ユーザーはストック写真の人物を系統的に無視し、実在人物の写真を精査することが実証されている。
- A/Bテスト複数事例で、本物の人物写真はストック写真比で +35〜161% のCVR改善が確認されている。特に「素材画像 → 実ユーザー撮影」への切り替えでスクロール率 1.5倍（日本国内事例）。
- ELM（精緻化見込みモデル）理論によると、低関与商品では画像が周辺手がかりとして態度変容を担い、高関与商品では画像と論拠の整合性が問われる。画像役割はターゲットの関与水準で変わる。
- Seo（2020）のメタ分析では「テキストに画像を追加する」だけでは説得効果に有意差なし（r=0.055）だが、「機能的に設計された画像」は効果が有意になる。画像の質・目的整合性が決定因。
- Picture Superiority Effect（Paivio）により、画像は文字の2倍以上記憶に残る。言語コード＋視覚コードの二重符号化が記憶保持を高める。
- 視線誘導として「人物の目線方向 → CTA」設計が有効。目線が見出し/CTAへ向いている画像はクリック率・注目率が明確に上昇する。
- LP実務では「Hero / Benefit / Process / Social Proof / CTA支援 / Before-After」の6タイプが業界標準として定着。各タイプで画像が果たす目的と伝えるべき情感が異なる。
- AI生成画像のCTR（0.49〜0.52%）はストック写真（0.53%）をまだ下回るが、AIモデル採用で電話CVRが1.2倍になった国内事例もあり、用途・クオリティコントロールで逆転可能性がある。
- 「画像ブリーフ → AIプロンプト変換」の体系的フレームワークは業界に未確立だが、MintedBrain（2024）が「目的・対象・感情・内容の4問答 → 被写体/スタイル/構図/照明/ムード/縦横比の6要素プロンプト」という実用フローを提示している。

---

## 2. 軸Aの詳細：学術・専門領域の知見

### A-1. 視覚コミュニケーション論・インフォメーションデザイン論

#### Roland Barthes「Rhetoric of the Image」（1964年初出、1977年英訳）

Barthes はひとつの画像に3層のメッセージが共存すると論じた。

- **言語メッセージ（Linguistic Message）**: キャプション・ラベル等のテキスト。アンカリング（anchor）とリレーイング（relay）の2機能を持つ。アンカリングは画像解釈の多義性を絞る機能、リレーイングはテキストが画像の意味を前進させる機能。
- **コード化された図像的メッセージ（Coded Iconic）**: 文化的に習得された含意（connotation）。「白いテーブルクロス→フランスらしさ」等、文化コードに依存する。
- **コード化されていない図像的メッセージ（Non-coded Iconic）**: 写真の字義的（denotative）意味。文化を超えて認識される直接的な視覚情報。

**LP設計への示唆**: ファーストビュー画像には「何を指示するか（denotation）」と「どんな文化的含意を持たせるか（connotation）」の両方を設計する必要がある。アンカリング文（コピー）との組み合わせで画像の意味は大きく変わる。

#### Carney & Levin（2002）「Pictorial Illustrations Still Improve Students' Learning from Text」

画像の5機能分類（教育工学から発祥、視覚コミュニケーション全般に援用可能）:

| 分類名 | 定義 | 学習・説得効果 |
|--------|------|--------------|
| **Decorational（装飾的）** | テキスト・コンテンツとほぼ関係なし | ほぼゼロ。過多だと学習を阻害 |
| **Representational（表象的）** | テキストの一部または全体をそのまま視覚化 | 中程度のプラス効果 |
| **Organizational（組織的）** | テキストの構造的枠組みを提供 | 中程度のプラス効果 |
| **Interpretational（解釈的）** | 難しいテキストを別の概念で補助的に説明 | 大きなプラス効果 |
| **Transformational（変容的）** | 記憶強化のためのニモニック要素を持つ | 最大のプラス効果 |

**LP設計への示唆**: 「Decorational」のみの画像（ページを飾るだけの画像）はCVRへの貢献がほぼない。「Representational」以上、特に「Interpretational（抽象的なサービス価値を具体的に視覚化）」や「Transformational（Before-After等、記憶に焼き付ける変容イメージ）」の設計を狙うべき。

#### Picture Superiority Effect と Dual Coding Theory

- **Shepard (1967)** の認識記憶実験: 写真の認識精度98% vs 単語/文章88%（600項目の認識テスト）
- **Paivio (1971/1986)** の Dual Coding Theory: この効果を「視覚コード＋言語コードの二重符号化」で説明
- **Rossiter & Percy (1997)** は雑誌広告における画像の構造的重要性を強調
- 応用: 記憶に残すべきメッセージ（USP・ブランドイメージ）は必ず画像に紐付けて設計する

### A-2. アイトラッキング研究（Nielsen Norman Group）

**「Photos as Web Content」（NNGroup, Jakob Nielsen著）**

- ユーザーはタスク関連情報を含む画像を精査し、「ページを飾るだけの写真」は完全に無視する
- 実在の従業員写真はテキスト（バイオ）より10%多くの時間注視された（FreshBooks事例）
- ストック写真の人物は系統的に無視される。名無しモデルのステージ写真はほぼ飛ばされる
- 商品写真は意思決定補助のために強く注視される（Pottery Barnの本棚写真 vs AmazonのTV写真の比較）

**F字・Z字パターンと画像配置**

- F字パターン: テキスト多めのページで頻出。横方向スキャン→縦方向スキャン。コンテンツページ・ブログに多い
- Z字パターン: テキスト少なめ・シンプルなページ（LPに多い）。左上→右上→斜め下→右下の経路
- Z字パターンのLP設計では「左上にブランドロゴ / 右上にCTA / 左下に信頼要素 / 右下にCTA」が定石。各交点に視線を引く画像を置く

**「Decorative Images: Delightful or Dreadful?」（NNGroup動画）**

- 装飾画像はトーン・感情的アピール形成に役割はあるが、タスク完遂を阻害してはならない
- 機能画像はタスク関連情報を持ち、ユーザーの明示的な注意を引く

### A-3. 広告・マーケティング学術領域

#### Elaboration Likelihood Model（ELM）: Petty & Cacioppo（1986）

- **中心経路（Central Route）**: 高関与・高認知処理 → 論拠の質で説得。画像は「論拠の視覚化」として機能
- **周辺経路（Peripheral Route）**: 低関与・低認知処理 → 表面的手がかりで態度形成。画像の魅力・感情・信頼性シグナルが決定的
- 実験結果（Petty, Cacioppo & Schumann, 1983, Journal of Consumer Research）: 低関与条件では製品の論拠より推薦者（= 画像の人物）の魅力が態度変容に影響

**LP設計への示唆**: BtoC商品（特に感情購買・衝動購買）では周辺経路設計 = 感情喚起画像が有効。BtoBや高額商品（高関与）では、画像はベネフィットを論拠として視覚化する機能が主になる。

#### Seo（2020）「Meta-Analysis on Visual Persuasion」Athens Journal

- 12研究・20効果量・2,452名を分析
- テキストへの画像追加の全体的な説得効果: r=0.055、p=0.161（**統計的有意差なし**）
- ただし、機能的に設計された画像（目的と整合した画像）は有意な効果を持つことを示唆
- 画像の「有無」より「質・目的整合性」が説得効果を決める

**矛盾点・要議論**: 「A/Bテスト事例では画像変更で大幅CVR改善」が複数報告されているにもかかわらず、学術メタ分析では有意差なし。理由候補: (1)メタ分析対象が教育・健康系メッセージ中心で商業LPと文脈が異なる、(2)「機能的設計」の有無が現場の効果差を生んでいる、(3)Seoのサンプルサイズ（12研究・2,452名）はメタ分析としては小規模（一般的には20〜50研究）であり「決定的学術知見」と呼ぶには弱い。一方で Seo はモデレーター分析で「**写真画像** や **ポジティブ画像** は弱いが有意な効果を示す」と報告しており、これは「機能的設計仮説」を学術側からも部分的に支持する材料。

#### 視覚的説得・感情転移研究

- 感情的視覚刺激は「気持ちのメカニカルな転移」を引き起こし、隣接するブランド・候補への態度に影響
- 近接ショット（クローズアップ）は低レベル解釈を促進し合理的アピールに適合。遠景ショットは高レベル解釈を促し感情的アピールに適合
- 笑顔表情はブランド態度の向上に有意な効果（いいね意図・ブランド評価）

---

## 3. 軸Bの詳細：実務家・業界の方法論

### B-1. CRO（コンバージョン最適化）業界の知見

#### Nielsen Norman Group の実践ガイドライン

- 「画像をページの飾り付けに使うな」 — 関心のある被写体を写した画像のみが効果的
- 本物の人物写真（組織と実際に関係する人物）はストック写真モデルを常に上回る
- 商品写真は意思決定に必要な情報を含む場合に精査され、含まない場合は無視される

#### A/Bテスト実証データ（VWO・MarketingExperiments・Growbo等）

- 本物の人物写真 vs ストック写真 → **+35% CVR改善**（MarketingExperiments事例）
- ストック写真 → 本物の学生写真（160 Driving Academy）→ **+161% CVR改善**（VWO事例）
- Medalia事例（VWO blog）：絵画ページ（CVR 8.8%）→ 作家写真（CVR 17.2%）→ **+95%向上**
- UGC（ユーザー生成コンテンツ）写真の採用：6.6% → 8.1% → **+23%リフト**
- AI生成モデル写真（エアコン修理LP）→ 電話問い合わせCVR **約1.2倍**（conversion-labo.jp 国内事例）

#### Dreamstime A/Bテスト研究（2025年6月公開）

- ストック写真広告CTR: 0.53%
- AI生成写真広告CTR: 0.49〜0.52%
- 実写真の販売転換率: 13% vs AI生成: 9% **[要検証：Dreamstime記事本文の直接確認推奨]**
- **現時点では信頼・変換・真正性でストック写真がAI生成を依然上回る**が、用途限定なら逆転可能性あり

#### Baymard Institute（EC商品画像研究）

- 42% のユーザーが商品画像で寸法・スケールを把握しようとするが、「実寸比較画像」を28%のサイトが未提供
- ユーザーが商品ページで最初にとる行動の56%は商品画像の確認
- 人間モデルが着用した画像（アパレル・アクセサリー等）は必須; 平置き写真だけでは不十分
- 「Inspirational Image」（ライフスタイル文脈での商品）はユーザーを引きつけるが、全商品へのリンク導線が必要

### B-2. LP制作・Webデザイン業界のフレームワーク

#### 業界標準的な画像タイプ分類（Unbounce / Instapage / KlientBoost他）

| 画像タイプ | 英語名 | 主な配置セクション | 伝達目的 |
|-----------|--------|-----------------|---------|
| ヒーロー画像 | Hero Image / Hero Shot | ファーストビュー（FV） | 「誰向けか・何を解決するか」の即時認識 |
| 商品・サービスショット | Product Shot / Service Shot | FV・機能説明 | オファーの具体的形状・UI・実体を見せる |
| ライフスタイル画像 | Lifestyle Image | FV・ベネフィット | 使用後の理想の生活・感情状態を見せる |
| ベネフィット画像 | Benefit Image / Outcome Image | ベネフィットセクション | 得られる結果・変化を視覚化 |
| プロセス図・イラスト | Process Illustration | 仕組み説明 | 複雑な概念・フローをシンプルに可視化 |
| 社会的証明画像 | Social Proof Image | 実績・口コミ・お客様の声 | 信頼性・共感・自分ごと化を促進 |
| ビフォーアフター | Before-After | 実績・成果 | 変化の幅・効果の証拠を可視化 |
| 背景・アンビエント | Background / Ambient | 全セクション | ブランドトーン・世界観の設定 |
| コンテキスト画像 | In-Context / In-Use | 機能・使用場面 | 実際の使用場面でオファーを接地させる |
| CTA支援画像 | CTA-support image | CTAセクション近傍 | 視線誘導・感情的後押し |

#### Instapage「Hero Shot」の5要件（実務フレームワーク）

1. **キーワード整合性**: 広告キーワードと画像の文脈が一致しているか（メッセージマッチ）
2. **独立した伝達力**: テキストなしで画像だけで目的が伝わるか
3. **感情的共鳴**: 悩み状態ではなく「得られた後の状態」を描いているか
4. **デザイン支援**: ヘッドライン・CTA・ボディランゲージが画像を補強しているか
5. **真正性**: 本物の商品・人物がジェネリックなストック素材より優れているか

#### AIDA構造と各セクションの画像役割

| セクション | AIDA対応 | 画像役割 | 推奨画像タイプ |
|-----------|---------|---------|-------------|
| ファーストビュー | A（Attention） | 即時引き止め・世界観設定 | Hero + 人物（目線がCTA向き） |
| ベネフィット | I（Interest） | 「使ったら自分はこうなる」の想像促進 | Lifestyle / Outcome |
| 機能・仕組み | D（Desire一部） | 「どうやって実現するか」の論拠提示 | Process Illustration / Product shot |
| 実績・社会的証明 | D（Desire） | 信頼・安心の構築 | Social Proof / Before-After / 実績写真 |
| CTA | A（Action） | 行動への最後の後押し | 笑顔人物 / 視線誘導 / CTA支援 |

#### 日本のLP実務知見（Conversion Labo等）

- LP種別ごとの画像選定基準:
  - **商品LP**: 質感・使用イメージ優先。「使った後の自分」を想像できる写真
  - **サービスLP**: スタッフの表情・利用者の自然なリアクション・現場のリアルな様子
  - **採用LP**: 「働く姿が伝わる写真」——現場の温度感が応募率を左右
  - **BtoBサービスLP**: トップシェア感・専門性を伝えるシンプルな構成 or イラスト
- 視線誘導テクニック: 人物の視線方向 × コピーの向きの組み合わせでCTAへ視線を誘導
- 国内CVR改善事例:
  - 妊活サプリ: 素材画像 → 実ユーザー写真でスクロール率 **1.5倍**
  - 女性医療クリニック: 素材画像 → 実スタッフ写真でCVR改善
  - エアコン修理: AI生成モデル写真採用で電話CVR **1.2倍**

### B-3. 広告業界の Creative Brief / Image Brief 文化

#### Photo Brief の標準的構成要素

信頼性の高い発注ブリーフには以下が含まれる（VSCO・MagicBrief・MilanoNote等の業界標準）:

1. **Purpose（目的）**: この写真は何のためか？どこに使うか？
2. **Audience（対象者）**: 誰が見るか？
3. **Emotion to Evoke（喚起したい感情）**: 見る人にどう感じてほしいか？
4. **Specific Content（必要な内容）**: 何が写っていなければならないか？
5. **Visual References（参考画像）**: 5〜7枚の参考画像＋カラーパレット＋タイポガイド
6. **Shot List（撮影リスト）**: ディテールショット / ビフォーアフター / 季節性 / ヒーローショット等
7. **Usage Context（使用文脈）**: 印刷/オンライン/SNS等、最終使用媒体

#### Photo Director（フォトディレクター）が事前に言語化する4問

- この写真が何を伝えるべきか（What to communicate）
- 具体的なコンセプト・ビジョンはあるか（Vision）
- 見る人にどう感じてほしいか（Feeling）
- どんなストーリーを語るか（Story）

**LP画像設計への直接応用**: 各セクションの画像に上記4問を事前に回答させることが、「画像役割辞書」の核心フォーマットになりうる。

---

## 4. 軸Cの詳細：AI画像生成 × LP/Web制作の最新事例

### C-1. 目的から逆算してプロンプトを作るフレームワーク

#### MintedBrain「Your AI Visual Design Workflow: From Brief to Finished Asset」（2024）

最も体系化されたフレームワークとして現時点で確認できる:

**6ステップワークフロー**:
1. Write the Brief（ブリーフ作成）— ツールを開く前に目的を文書化
2. Build your prompt（プロンプト構築）— ブリーフを6要素に変換
3. Generate and select（生成と選択）— 4枚生成→反復
4. Refine（精緻化）— インペインティング・ネガティブプロンプトで修正
5. Edit and finish（編集・仕上げ）— デザインツールでブランド要素を追加
6. Export at correct size（サイズ合わせ書き出し）

**4問ブリーフ（目的定義）**:
- What is the image for? （この画像は何のためか）
- Who will see it?（誰が見るか）
- What feeling should it create?（どんな感情を作るべきか）
- What specific content should it show?（具体的に何を写すか）

**6要素プロンプト変換式**:
- Subject（被写体）
- Style（美学的アプローチ）
- Composition（構図・配置）
- Lighting（照明の質）
- Mood（感情トーン）
- Aspect Ratio（縦横比）

**実例**: 「プロダクティビティアプリのLPヒーロー画像」→「落ち着いた集中したプロフェッショナル / ミニマルデスク / 整然 / 楽観的 / クールブルー＆ホワイト」

#### UX Design Institute（Midjourney for UI Design）

- UIデザイン前にブリーフを書く: 「ピザレストラン用Webサイト。赤・白・緑。ミニマル、写真あり」
- ブリーフ→プロンプトへの変換: ブリーフのアイデアを基に詳細プロンプトを構築
- Midjourney 6では最初の40語が最重要。精度が高い

#### n8n AI Design Team ワークフロー（2024）

- Ideogram + OpenAI を組み合わせた「生成→レビュー」の自動化ワークフローが公開
- 生成後に別AIがデザイン品質をレビューする人間とAIの協働モデル

### C-2. AI生成画像のCVR・広告効果

#### Dreamstime社 A/Bテスト研究（2025年6月公開・Meta Ads Manager使用）

- **現状結論**: ストック写真 > AI生成写真（信頼・変換・真正性で）
- CTR差: 0.53%（ストック）vs 0.49〜0.52%（AI）— 差は小さいが一貫してストックが上回る
- 販売転換率: 実写13% vs AI 9% **[要検証：Dreamstime記事本文の直接確認推奨]**

#### 国内事例（conversion-labo.jp, 2024）

- エアコン修理LP: AI生成モデル採用で電話問い合わせCVR **約1.2倍**（条件: 人物写真を必要とする場面でのコスト削減目的での使用）
- **ただし**: 最終的なレタッチが必要（AI人物写真の品質課題）

### C-3. AIらしさ問題への対処

#### Uncanny Valley 問題（2024〜2025年研究まとめ）

- 60%以上のユーザーがAI生成特有のアーティファクトを認識する（2025年davinci.ai記事）**[要検証：一次出典未特定]**
- 問題部位: 手・指・目の非対称性・過度な肌の滑らかさ
- 45%のシニアクリエイティブディレクターがティア1ブランドキャンペーンでAI生成アセットを拒否 **[要検証：一次出典未特定]**

**対処法（実務）**:
- プロンプトで照明・角度・感情を詳細指定することでアーティファクト最小化
- インペインティングで手・目の後処理が必須
- 「抽象的・概念的な画像」や「人物の顔が主要でない構図」ではAI品質が著しく向上する
- Anti-AI デザイントレンド（2026）: 手書き感・テクスチャ・ノイズを意図的に加えてAI感を消す手法が台頭

### C-4. Claude Code / エージェント領域の先行事例

- Claude Code subagent + Figma MCP によるデザイン・コード統合パイプラインは2024〜2025年に急増
- 「生成→レビューサブエージェント」の二段構成（n8n事例、wshobson/agents等）が先行事例として存在
- 「画像ブリーフ → AIプロンプト」を自動変換するエージェントスキルは**現時点で公開事例を確認できず**（gap）— これはシンヤさんチームの独自実装余地として有望

---

## 5. 特に有望な型・フレームワーク（実装に取り込めそうなもの）

### フレームワーク1: Carney & Levin 5機能分類（学術的基盤）

**実装案**: 各セクション画像の設計時に「D/R/O/I/T」ラベルを付与するチェックボックスを設ける。「D（装飾）のみ」の画像は設計上の警告フラグを立てる。

### フレームワーク2: 4問ブリーフ × 6要素プロンプト変換（実装最有望）

```
[画像ブリーフ]
1. 目的: この画像はどのセクションのどの機能を担うか
2. 対象者: 誰に見せるか（ペルソナ）
3. 喚起感情: 見た人にどう感じてほしいか
4. 必須要素: 画像に含まれていなければならない要素

  ↓ 変換

[AIプロンプト要素]
- 被写体: [subject]
- スタイル: [style]
- 構図: [composition]
- 照明: [lighting]
- ムード: [mood]
- 縦横比: [aspect ratio]
```

### フレームワーク3: ELM×関与水準別画像設計マトリクス

| 関与水準 | 購買検討時間 | 画像の主な役割 | 推奨画像タイプ |
|---------|------------|-------------|-------------|
| 低関与（衝動・感情購買） | 数秒〜分 | 感情喚起・周辺経路での態度形成 | Lifestyle / Aspirational / 感情的人物 |
| 中関与 | 数分〜数時間 | 感情 + 論拠の視覚化の両立 | Benefit + Social Proof + Product shot |
| 高関与（BtoB・高額・慎重購買） | 数時間〜日 | 論拠・信頼性・ROI可視化 | Process / Before-After / Testimonial / 実績 |

### フレームワーク4: 視線誘導設計パターン

- 人物の目線方向を「見出し → CTA」へのベクトルとして使う
- Z字パターンの交点に画像を配置（左上:ブランド / 右上:CTA画像 / 左下:信頼画像 / 右下:CTA画像）
- クローズアップ（近接ショット）→ 合理的アピール強化。遠景 → 感情的アピール強化

---

## 6. 画像役割の分類試案（議論の出発点）

*ラウンドテーブルでの確定を前提とした「たたき台」として提示。最終決定は7名の検討による。*

### 軸1: 認知的機能（Cognitive Function）— 何を「分からせるか」

| タイプコード | 名称 | 役割 | 典型配置 |
|------------|------|------|---------|
| C-1 | 世界観設定 | ブランド・トーン・世界観を視覚化。「ここはどんな場所か」を伝える | FVヒーロー背景 |
| C-2 | オファー具体化 | 商品/サービスの実体・UIを見せる | FV・機能説明 |
| C-3 | 概念可視化 | 抽象的なサービス・仕組みをイラスト等で説明 | 仕組みセクション |
| C-4 | プロセス図示 | 「どう機能するか」のフローを可視化 | How it works |
| C-5 | 結果・変化の証拠 | Before-After・実績数値の視覚的補強 | 実績・成果セクション |

### 軸2: 感情的機能（Emotional Function）— 何を「感じさせるか」

| タイプコード | 名称 | 役割 | 典型配置 |
|------------|------|------|---------|
| E-1 | 課題共感 | 「この悩み分かる」を視覚化（慎重に。ネガティブ感情は滞在を下げるリスク） | 課題提示（使用は限定的に） |
| E-2 | 理想未来 | 「使った後の自分」の感情的イメージ | FV・ベネフィット |
| E-3 | 信頼・安心 | 本物の人・実績・権威を通じた安心感 | 社会的証明・会社情報 |
| E-4 | 親近感・共感 | 「自分と似たような人が使っている」の自分ごと化 | お客様の声・体験談 |

### 軸3: 行動的機能（Behavioral Function）— 何を「させるか」

| タイプコード | 名称 | 役割 | 典型配置 |
|------------|------|------|---------|
| B-1 | 視線誘導 | ユーザーの視線をCTA・見出しに向ける | 全セクション（特にCTA近傍） |
| B-2 | スクロール促進 | 「続きを見たい」と思わせる引き | セクション境界・FV下部 |
| B-3 | 離脱防止 | 「自分には関係ない」という判断を防ぐ | FV・価格提示前 |

*3軸12タイプが初期案。1枚の画像が複数タイプを担うことは多い（例: ヒーロー画像がC-1+E-2+B-1を同時に担う）。*

---

## 7. 未解決論点・要議論事項

### 論点1: 学術メタ分析 vs 実務A/Bテストの乖離

**状況**: Seo（2020）のメタ分析では「画像追加の説得効果に有意差なし（r=0.055）」だが、CRO業界のA/Bテスト事例では「画像変更で+35〜161%のCVR改善」が報告されている。

**候補仮説**:
- 学術研究の多くは「教育・健康情報伝達」文脈で行われており、「商業LP」文脈との一般化に限界がある
- 「画像の追加」と「画像の質的変更（機能的設計）」は別現象。後者の効果が実務事例で観察されている
- 測定指標の違い（記憶・態度変容 vs CVR/CTR）

**リクへの確認依頼**: 上記乖離の根拠として引用可能な学術論文・レビューがあるか確認。

### 論点2: AI生成画像の効果は「まだ発展途上」の可能性

**状況**: 2024年時点ではストック写真 > AI生成（信頼・CVR）だが、国内事例ではAIモデルでCVR1.2倍も確認。

**要議論**: AI生成画像が「本物っぽさ」を追求するほど Uncanny Valley に嵌まるリスクがある一方、「意図的に抽象化・コンセプチュアル化した画像」はリスクを回避できる可能性がある。ルナ（Nano Banana）の運用指針に直結。

### 論点3: 「Decorational」画像の完全排除 vs ブランドトーン設定の必要性

**状況**: Carney & Levin では装飾的画像の効果はほぼゼロとされ、NNGroupも「fluff」として批判。しかしLPのブランドトーン設定・世界観構築において、純粋装飾的な背景画像・テクスチャが必要な場面がある。

**要議論**: 「装飾」と「世界観設定（C-1）」の境界をどこに引くか。カイ・ユイ・レンの視点から「許容される装飾的使用の条件」を定義する必要がある。

### 論点4: 日本市場固有の画像効果データの不足

**状況**: 学術研究・A/Bテスト事例の大多数が英語圏（主に北米・欧州）のデータ。日本では文化的コンテキスト（間接的表現の好み・色彩感覚・空白の使い方）が異なる可能性がある。

**要議論**: 日本市場向けLP・Webサイトでの「本物の人物写真優位性」は同様に成立するか。変換率が高い日本語LP画像のパターンを独自に収集すべきか。

### 論点5: 「画像役割辞書」の粒度設定

**状況**: 本調査で示した3軸12タイプは提案レベル。粒度が細かすぎると使いにくく、粗すぎるとプロンプト変換に役立たない。

**要議論**: カイ（lp-designer）・ユイ（web-designer）が実際に設計フローで使いやすい粒度はどこか。また「セクション × 役割 × 感情」のマトリクスとして構造化するか、タグ付けとして構造化するかの選択。

---

## 8. Sources

### Tier 1（公式・学術文献）

- [Barthes, R. "Rhetoric of the Image" 1964/1977 - UCSD PDF](https://pages.ucsd.edu/~bgoldfarb/cocu108/data/texts/barthes_rhetoric_of.pdf)
- [Carney & Levin (2002) "Pictorial Illustrations Still Improve Students' Learning from Text" - Springer](https://link.springer.com/article/10.1023/A:1013176309260)
- [Petty & Cacioppo (1986) "The Elaboration Likelihood Model of Persuasion" Advances in Experimental Social Psychology Vol.19, pp.123-205 - PDF](https://richardepetty.com/wp-content/uploads/2019/01/1986-advances-pettycacioppo.pdf)
- [Petty, Cacioppo & Schumann (1983) "Central and Peripheral Routes to Advertising Effectiveness" Journal of Consumer Research Vol.10, pp.135-146 - PDF](https://richardepetty.com/wp-content/uploads/2019/01/1983-jcr-pettycaciposchumann.pdf)
- [Shepard (1967) "Recognition memory for words, sentences, and pictures" — Picture Superiority数値の一次出典](https://en.wikipedia.org/wiki/Picture_superiority_effect)
- [Seo (2020) "Meta-Analysis on Visual Persuasion" - Athens Journal](https://www.athensjournals.gr/media/2020-6-3-3-Seo.pdf)
- [Seo (2020) ResearchGate版](https://www.researchgate.net/publication/342569514_Meta-Analysis_on_Visual_Persuasion-_Does_Adding_Images_to_Texts_Influence_Persuasion)
- [Picture Superiority Effect - Wikipedia（Paivio研究まとめ）](https://en.wikipedia.org/wiki/Picture_superiority_effect)
- [ELM - Wikipedia（Petty & Cacioppo総説）](https://en.wikipedia.org/wiki/Elaboration_likelihood_model)

### Tier 2（業界権威・専門メディア）

- [NNGroup "Photos as Web Content" (Nielsen/Pernice)](https://www.nngroup.com/articles/photos-as-web-content/)
- [NNGroup "Decorative Images: Delightful or Dreadful?" (動画)](https://www.nngroup.com/videos/decorative-images/)
- [NNGroup "Eyetracking Web Usability" 書籍情報](https://www.nngroup.com/books/eyetracking-web-usability/)
- [NNGroup "Image-Focused Design: Is Bigger Better?"](https://www.nngroup.com/articles/image-focused-design/)
- [CXL "How Images Can Boost Your Conversion Rate"](https://cxl.com/blog/how-images-can-boost-your-conversion-rate/)
- [CXL "Hero Image: The Marketer's Guide (+26 Examples)"](https://cxl.com/blog/hero-image/)
- [Baymard Institute "In-Scale Product Images"](https://baymard.com/blog/in-scale-product-images)
- [Baymard Institute "Inspirational Product Images"](https://baymard.com/blog/inspirational-product-images-links)
- [VWO "Do Human Photos Increase Website Conversions?"](https://vwo.com/blog/human-landing-page-increase-conversion-rate/)
- [VWO "160 Driving Academy +161% Conversions"](https://vwo.com/blog/stock-image-or-real-image/)
- [MarketingExperiments "Stock images or real people?"](https://marketingexperiments.com/digital-advertising/stock-images-tested)
- [Unbounce "5 Tips for Choosing the Best Images for Your Landing Page"](https://unbounce.com/landing-pages/improve-landing-page-imagery/)
- [Unbounce "The Anatomy of a Landing Page"](https://unbounce.com/landing-page-articles/the-anatomy-of-a-landing-page/)
- [Instapage "Unleash Your Visual Potential: Embracing the Hero Shot"](https://instapage.com/blog/what-is-a-hero-shot)
- [Instapage "Directional Cues: Design Tips That Get Visitors to Convert"](https://instapage.com/blog/what-are-directional-cues)
- [MIT Technology Review "Reckoning with generative AI's uncanny valley" (2024)](https://www.technologyreview.com/2024/10/24/1106110/reckoning-with-generative-ais-uncanny-valley/)
- [99designs "Using F and Z patterns to create visual hierarchy in landing page designs"](https://99designs.com/blog/tips/visual-hierarchy-landing-page-designs/)
- [IxDF "Elaboration Likelihood Model Theory"](https://ixdf.org/literature/article/elaboration-likelihood-model-theory-using-elm-to-get-inside-the-user-s-mind)
- [SimplyPsychology "Elaboration Likelihood Model"](https://www.simplypsychology.org/elaboration-likelihood-model.html)

### Tier 3（個人発信・実務ブログ・補足情報）

- [Conversion Labo "ランディングページにおける写真の訴求力-成果を高めるビジュアル戦略"](https://conversion-labo.jp/report/lp_design/14206/)
- [Dreamstime "Why Stock Photos Still Beat AI Photos in A/B Tests" (2024)](https://www.dreamstime.com/blog/running-ads-here-s-why-stock-photos-still-beat-ai-photos-b-tests-75955)
- [MintedBrain "Your AI Visual Design Workflow: From Brief to Finished Asset" (2024)](https://mintedbrain.com/tutorials/ai-image-workflow-capstone)
- [KlientBoost "Landing Page Hero Shots: 18 Insightful Examples"](https://www.klientboost.com/landing-pages/landing-page-hero-shots/)
- [Oreate AI "The Uncanny Valley of AI Faces"](https://www.oreateai.com/blog/the-uncanny-valley-of-ai-faces-why-your-generated-images-might-not-look-quite-right/63bbc04f9ca250c322c9b39db853ca9f)
- [Crea8ive Solution "Anti-AI Design Trends 2026"](https://crea8ivesolution.net/anti-ai-design-trends-2026/)
- [Vanseo Design "Icon, Index, and Symbol — Peirce semiotics web design"](https://vanseodesign.com/web-design/icon-index-symbol/)
- [CommlabIndia "7 Communicative Functions of Graphics in eLearning"](https://www.commlabindia.com/blog/graphics-elearning-communicative-function)
- [ferret-plus "26の事例から学ぶ、ヒーロー画像改善とCVR向上"（CXL翻訳）](https://dlpo.jp/blog/cxl-converting-hero-image-26-examples.php)
- [wehubworks "LPの構成の作り方と必須の要素"](https://wehubworks.com/colum-knowledge/lp-composition/)

---

## ファクトチェック履歴

- **2026-04-25** ミオ（researcher）一次調査
- **2026-04-25** リク（fact-checker）ファクトチェック実施
  - **重要度: 高 3件修正済**：Schumann綴り誤り / Picture Superiority数値の出典をShepard (1967)へ分離 / Petty&Cacioppo 1986 リンクを論文版（Advances vol.19）と整合
  - **重要度: 中 2件修正済**：Medalia事例の出典をVWO blogに明示 / Dreamstime研究の年代を「2025年6月公開」へ修正
  - **要検証マーキング 2件付与**：実写真13% vs AI 9%（Dreamstime）/ Uncanny Valley 60% & 45% 数値（一次出典未特定）
  - **論点1にリク追加コメント反映**：Seo メタ分析のサンプルサイズ問題＋モデレーター分析の補足
- **総合判定（リク）**: 修正反映後はラウンドテーブル参考資料として合格水準

## 次工程

- ラウンドテーブル開催（カイ・ユイ・レン・コト・ルナ・リナ・カナタの7名）
- 「画像役割辞書」のたたき台（§6 の3軸12タイプ）を議論の出発点とする
- 論点1〜5の中で実装方針に直結する論点を優先議論
