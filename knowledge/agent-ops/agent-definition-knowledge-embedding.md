# エージェント定義への基礎知識埋め込み原則

## 原則

エージェントが日常的に使うフレームワーク・数式・略号の基礎知識は、**スキル本体への依存ではなく、エージェント定義（agents/*.ja.md）に「基礎知識（必須暗記）」セクションとして埋め込む**こと。

## 背景

2026-05-02 ノゾミ（pr-publicist）新設時の動作確認で、TOPPING 7要素の完全な誤回答（Title/Outline/Problem/Product/Impact/Narrative/Gateway）が発生。スキル本体（press-release-builder）には正解（Trend/Original/Public/People/Inverse/Number/Going forward）が書かれていたが、エージェント定義側に埋め込みがなかったため、ノゾミは即答時に推測でハルシネーションを起こした。

## いつ適用するか

エージェントが「スキルを呼び出さない簡易応答」でも正確に答える必要がある以下のケース：

- フレームワークの構成要素（TOPPING / SUCCESs / TOPPING / 4P / 5C 等）
- 数式・公式（CPA分解式 / CV分解式 / LTV計算 / 各種KPI計算）
- 略号・専門用語（CPA / CPC / CPM / CTR / CVR / ROAS / KGI / KPI / MRR / ARR 等）
- 業界固有の数値基準（メディア掲載率の閾値 / バウンス率の警戒水準 等）

## 実装パターン

### セクション構成例（ノゾミ：pr-publicist.ja.md）

```markdown
## PR フレームワーク基礎知識（必須暗記）

エージェントが日常的に使う基礎知識。スキル本体を呼ばない簡易応答時でも以下は必ず正確に運用すること。

### TOPPING フレームワーク（7要素）

| 要素 | 意味 |
|---|---|
| **T**rend | トレンド・時流 |
| **O**riginal | 最上級・初・独自 |
（以下略）

詳細・実装手順はスキル `press-release-builder` を参照すること。
```

### セクション構成例（ヒカル：ad-operator.ja.md）

```markdown
## 広告運用 基礎知識（必須暗記）

### CPA分解式
CPA = CPC ÷ CVR ＝ (CPM ÷ CTR ÷ 1000) ÷ CVR

### CV分解式
CV = IMP × CTR × CVR

### 主要KPI略号
- CPA: Cost Per Acquisition（顧客獲得単価）
（以下略）
```

## 再発防止ガードの併用

基礎知識セクションだけでは不十分。**品質基準セクションに「取り違えは即ハルシネーション扱い」と明文化**する：

```markdown
## 品質基準
- **TOPPING7要素は正確に運用すること**：Trend / Original / Public / People / Inverse / Number / Going forward の7要素であり、Title / Outline / Problem / Product 等と取り違えない（取り違えは即ハルシネーション扱い）
```

これにより、エージェントが自己出力を検証する際に「これは禁止パターンか？」と即判定できる。

## セルフチェック項目への組込

セルフチェック項目にも基礎知識の正確運用を含める：

```markdown
- [ ] TOPPING7要素を Trend / Original / Public / People / Inverse / Number / Going forward で正しく運用している
- [ ] CPA分解式（CPA = CPC ÷ CVR）／CV分解式（CV = IMP × CTR × CVR）を使っている
```

## JA/EN ペア両方への適用

CLAUDE.md「Agent File Editing Rules」に従い、`.ja.md` で先に整備し、`.md`（英訳版）にも同等の埋め込みを行う。両者の同期を必ず取ること。

## 関連事例

- 2026-05-02 ノゾミ TOPPING ハルシネーション → エージェント定義に基礎知識埋め込み修正で解決
- ヒカル CPA/CV分解式 → 同タイミングで予防的に埋め込み

## 関連ファイル

- `agents/pr-publicist.ja.md` / `.md`（PR フレームワーク基礎知識セクション）
- `agents/ad-operator.ja.md` / `.md`（広告運用 基礎知識セクション）
- `skills/press-release-builder/SKILL.md`（TOPPING 正本）
