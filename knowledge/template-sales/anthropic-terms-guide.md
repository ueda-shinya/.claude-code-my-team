# マルチエージェントテンプレート販売 — Anthropic利用規約ガイド

調査日: 2026-04-11

## 結論

自作テンプレート（CLAUDE.md、エージェント定義.md、スキル.md）の第三者への販売は、Anthropicの利用規約上 **問題なし**。

## 判定の詳細

### 1. テンプレート販売（自作ファイルの販売）: OK

- CLAUDE.md・エージェント定義・スキル定義はユーザーが作成した **Input / 設定ファイル** であり、Servicesの再販には該当しない
- Commercial Terms: "Customer may not attempt to... resell the Services except as expressly approved by Anthropic" — テンプレートはServicesではないため抵触しない

### 2. Output（Claude生成物）の商用利用: OK

- 所有権はユーザーに帰属（Consumer Terms / Commercial Terms 両方で明記）
- Consumer Terms: "Subject to your compliance with our Terms, we assign to you all of our right, title, and interest -- if any -- in Outputs."
- Commercial Terms: "Customer (a) retains all rights to its Inputs, and (b) owns its Outputs."

### 3. AUP（利用ポリシー）: 抵触なし

- AUPが禁止するのは違法行為促進・有害コンテンツ生成・欺瞞的行為・スパム・セキュリティ侵害等
- ビジネス用テンプレートの販売はいずれにも該当しない
- ただし、テンプレート購入者がAUP違反の用途に使った場合の販売者責任は不明確 → 販売規約で免責条項を設けること

### 4. 商標「Claude」の扱い

| 用途 | 可否 |
|---|---|
| サービス名・商品名に使用（例: 「Claudeテンプレ」） | NG（事前書面許可が必要） |
| 説明文中の記述的使用（例: 「Claude Code環境で使用するテンプレートです」） | OK |

- 根拠: Consumer Terms: "You may not, without our prior written permission, use our name, logos, or other trademarks in connection with products or services other than the Services"

### 5. 日本法（特定商取引法）

ネット販売時は以下の表記が必要:
- 販売者氏名
- 住所
- 連絡先
- 返品ポリシー

### 6. Input/Output混在テンプレートの扱い

- エージェント定義やスキルの中にClaudeが生成した文章が含まれるケースは現実的
- Output の所有権はユーザーに帰属するため、混在していても販売は問題なし
- ただし、販売時に「テンプレートにはAI生成コンテンツが含まれる場合がある」旨を記載しておくのが望ましい

### 7. 規約改定リスク

- 本ガイドは 2026-04-11 時点の規約に基づく
- Anthropicの規約は随時改定される可能性がある → **販売開始前および定期的に最新規約を確認すること**
- 規約改定により販売が制限される場合に備え、販売規約に「規約変更に伴うサービス変更の可能性」を明記しておく

## 販売規約に明記すべき事項

1. 「Claude Codeの有効なサブスクリプションが別途必要です」
2. 「本商品はAnthropic社の公認・推奨を受けたものではありません」
3. 「購入者はAnthropicの利用規約・AUPを遵守する責任を負います」
4. 「テンプレートにはAI生成コンテンツが含まれる場合があります」
5. 「購入者のAUP違反等に起因する損害について販売者は責任を負いません」
6. 「デジタルコンテンツのため、購入後の返品・返金はできません」（特商法に基づく表記。購入前に同意を得る導線設計が必要）
7. 「Anthropic社の規約変更に伴い、本テンプレートの利用方法が変更される可能性があります」

## 根拠となる原文（主要条項）

- **Consumer Terms:** "Subject to your compliance with our Terms, we assign to you all of our right, title, and interest -- if any -- in Outputs."
- **Commercial Terms:** "Customer (a) retains all rights to its Inputs, and (b) owns its Outputs."
- **Commercial Terms:** "Customer may not attempt to... resell the Services except as expressly approved by Anthropic"
- **Consumer Terms:** "You may not, without our prior written permission, use our name, logos, or other trademarks in connection with products or services other than the Services"
