---
name: スライド制作ツール選定
description: スライド制作の主軸は Genspark 等の専用ツール。MD→スライド系（Marp / pptx-from-layouts）は現状プッシュしない
type: feedback
---

# スライド制作ツール選定（2026-04-15 決定）

**ルール:** スライド制作の主軸は **Genspark または別の専用ツール**とする。MD→スライド系ツール（Marp CLI / pptx-from-layouts-skill）を商談資料の第一選択として提案しない。

**Why:**
- 2026-04-14〜15 のセッションで Marp CLI + pptx-from-layouts-skill を導入し、Office Ueda サービス資料（第1〜6章）で比較テストを実施。
- pptx-from-layouts は Marp より商談資料向き（コンサル系テンプレで信頼感あり）だったが、**Genspark の品質には程遠かった**とシンヤさん判定。
- テンプレ作り込みの初期コストが大きい割にリターンが見合わない。

**How to apply:**
- シンヤさんが「スライド化したい」と言われたら、まず **Genspark / Gamma / Canva など専用ツール**を前提に提案する
- `slide-create` スキル（ソラ経由で Genspark/Gamma 用原稿を作る既存フロー）を優先
- Marp CLI / pptx-from-layouts-skill は導入済みだが**積極的に提案しない**
- 再検討タイミング：Genspark で困る具体的な課題（API自動化・大量生成・特殊フォーマット・ブランドテンプレ厳守等）が出てきた段階で、初めて MD→スライド系ツールを再評価する
- `skill-finder` 等で別の MD→スライド系ツール（reveal.js / Slidev / python-pptx 等含む）を新規導入する場合も、本ルールの再評価条件を満たすまで導入しない
- 本ルールはアスカからの提案行動のみ制約する。シンヤさんが明示的に「Marp で作って」「pptx-from-layouts 使って」等と指示した場合は、その指示に従う

**導入済みツール（残置）:**
- Marp CLI v4.3.1（npm グローバル）
- pptx-from-layouts skill（`~/.claude/skills/pptx-from-layouts/`）
- 両者ともアンインストールせず、将来の再評価用に温存
