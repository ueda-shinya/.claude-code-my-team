# 画像役割辞書（Image Role Dictionary）

LP / Web サイト / 提案資料 / SNS 投稿等で使用する画像の「役割」を、3 軸 12 タイプに体系化した辞書。

ブリーフ → AI プロンプト変換時に「この画像が何を担うのか」を一意化することで、ルナ（nano-banana）のプロンプト精度を高め、サイト全体のメッセージ整合性を保つことを目的とする。

---

## ディレクトリ構成

```
~/.claude/knowledge/image-role-dictionary/
├── README.md           ← 本ファイル（インデックス・使い方）
└── role-taxonomy.md    ← 3 軸 12 タイプの定義本体（Phase 1 実装）
```

Phase 2 以降で追加予定のファイル：

```
├── prompt-conversion.md    ← 役割タイプ → 5 コンポーネントプロンプト変換ルール（Phase 2）
├── case-studies.md         ← 実案件での適用事例ログ（Phase 2）
└── audit-checklist.md      ← LP 全体での画像役割バランス監査（Phase 3）
```

---

## 3 軸 12 タイプ（概要）

詳細は `role-taxonomy.md` 参照。

### 軸 C：認知的機能（何を「分からせるか」）

| コード | 名称 | AI フラグ |
|---|---|---|
| C-1 | 世界観設定 | `AI-READY` |
| C-2 | オファー具体化 | `AI-READY` / `PHOTO-PREFERRED` |
| C-3 | 概念可視化 | `ILLUSTRATIVE-OK` |
| C-4 | プロセス図示 | `ILLUSTRATIVE-OK` |
| C-5 | 結果・変化の証拠 | `PHOTO-PREFERRED` |

### 軸 E：感情的機能（何を「感じさせるか」）

| コード | 名称 | AI フラグ |
|---|---|---|
| E-1 | 課題共感 | `AI-READY` |
| E-2 | 理想未来 | `AI-READY` |
| E-3 | 信頼・安心 | `PHOTO-PREFERRED` |
| E-4 | 親近感・共感 | `PHOTO-PREFERRED` / `AI-READY` |

### 軸 B：行動的機能（何を「させるか」）

| コード | 名称 | AI フラグ |
|---|---|---|
| B-1 | 視線誘導 | `AI-READY` |
| B-2 | スクロール促進 | `AI-READY` |
| B-3 | 離脱防止 | `AI-READY` / `PHOTO-PREFERRED` |

---

## 使い方（Phase 1 時点）

### Step 1：画像が必要になったら

LP / 提案資料等で画像が必要になったら、まずその画像の **Primary 役割（最も強く担う機能）** を 3 軸 12 タイプから 1 つ選ぶ。

### Step 2：Secondary 役割を最大 2 つ追加

Primary を補完する Secondary 役割を最大 2 つまで指定可能。複数タグの並列（3 つすべてが主役）は禁止。

### Step 3：ルナ（nano-banana）に依頼

ルナへの依頼時に、以下を必ず明記する：

```
画像役割コード:
  Primary: E-2（理想未来）
  Secondary: B-1（視線誘導）, C-1（世界観設定）

ブリーフ追加情報（role-taxonomy.md の該当タイプから埋める）:
  - ターゲット属性: 30代女性デザイナー
  - 理想状態: 集中して心地よく仕事している
  - 目線方向: 右下の CTA ボタン方向
  - ブランドトーン: 温かい / 知的 / 軽やか
  ...
```

役割コード未指定でルナに依頼すると、ルナは辞書 (`role-taxonomy.md`) の参照を促す差し戻しメッセージを返す。Phase 1 では `/image-brief` スキルは未実装のため、呼び出し元（通常はアスカ）が辞書を手動参照して役割コードを補完してからルナに渡す運用となる。

---

## Phase 進捗状況

| Phase | スコープ | 状態 |
|---|---|---|
| **Phase 1** | 3 軸 12 タイプ辞書本体 + ルナ改修 | 2026-04-25 実装完了 |
| **Phase 2** | (a) 役割→プロンプト変換ルール / (b) `/image-brief` スキル化 / (c) 軸 MECE 精緻化 / (d) C-3とC-4の統合可否検討 / (e) E-5（権威・専門性）追加可否 / (f) C-6（In-Context）追加可否 / (g) サイトタイプ別重み付けマップ / (h) モバイル発現ガイドライン / (i) 実在性スコア / (j) ターゲット属性軸 / (k) 日本市場向けトーン補正 / (l) ブランドガイドラインとの分離 / (m) USP の二重符号化具体化 | 未着手 |
| **Phase 3** | LP 全体の画像役割バランス監査スキル / 運用ログからの軸再編 | 未着手 |

---

## 設計の出典

- リサーチレポート: `~/.claude/reports/image-role-dictionary-research-20260425.md`
  - ミオ（researcher）一次調査 + リク（fact-checker）ファクトチェック済
- ラウンドテーブル合意事項：
  1. 3 軸 12 タイプを採用
  2. Decorational 概念は排除（C-1 の判定基準は「他のブランド LP に転用可能なら装飾扱い」）
  3. Primary 1 つ + Secondary 最大 2 つの階層化
  4. AI 向き不向きフラグ内蔵（`AI-READY` / `PHOTO-PREFERRED` / `ILLUSTRATIVE-OK`）
  5. 各タイプにメタデータを埋める

## 学術的・実務的根拠

- Carney & Levin (2002): 5 機能分類（Decorational / Representational / Organizational / Interpretational / Transformational）
- Roland Barthes (1964): 画像の 3 層メッセージ（言語 / コード化図像 / 非コード化図像）
- Petty & Cacioppo (1986): Elaboration Likelihood Model（中心経路 / 周辺経路）
- Nielsen Norman Group: アイトラッキング研究（実在人物 vs ストック写真）
- MintedBrain (2024): 4 問ブリーフ × 6 要素プロンプト変換式
- 国内 A/B テスト事例（Conversion Labo 等）：実ユーザー写真でスクロール率 1.5 倍 / CVR 1.2〜2 倍

詳細は `~/.claude/reports/image-role-dictionary-research-20260425.md` 参照。

---

2026-04-25 Phase 1 実装
