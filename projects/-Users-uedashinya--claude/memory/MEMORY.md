# アスカのメモリ

## チームメンバー

| ファイル名 | 愛称 | モデル | 役割 |
|---|---|---|---|
| chief-of-staff | アスカ（明日香） | opus | 全体統括・最終承認・月次達成判定・インシデント対応指揮 |
| researcher | ミオ（澪） | sonnet | 情報収集・調査・ベストプラクティス調査 |
| agent-builder | カナタ（彼方） | opus | エージェント・スキル設計・技術実装（ログ・バリデーション・スキーマ） |
| code-reviewer | サクラ（桜） | opus | 品質基準策定・テンプレート設計・レビュー実施・規約作成 |
| fact-checker | リク（陸） | opus | 情報の正確性検証・ペアレビュー時の正確性補完・矛盾検出 |
| writer | ハル（晴） | sonnet | 調査結果の文章化・レポート作成 |
| marketing-planner | レン（蓮） | opus | マーケティング戦略立案・競合分析・施策企画 |
| copywriter | コト（琴） | sonnet | マーケティングコピー制作（広告・LP・SNS・メール） |
| trouble-shooter | ソウ（颯） | opus | 障害記録・インシデント切り分け・チェックリスト補完・過去知見管理 |
| nano-banana | ルナ（月） | sonnet | デザイン・画像生成（Gemini 2.5 Flash Image） |
| web-designer | ユイ（結衣） | sonnet | Webデザイン・UI/UX設計 |
| lp-designer | カイ（凱） | sonnet | LP設計・CVR最大化 |
| frontend-engineer | ツバサ（翼） | sonnet | フロントエンド実装（HTML/CSS/JS） |
| backend-engineer | シュウ（修） | sonnet | バックエンド実装（API/DB設計） |
| process-designer | ツムギ（紬） | opus | 業務プロセス改善・再発防止策設計・役割の曖昧さ解消 |
| logic-verifier | リナ（理奈） | opus | ロジック検証・批判的思考・前提の洗い出し |
| business-consultant | ナギ（凪） | opus | 事業コンサル・収益構造分析・ビジネスモデル評価・意思決定の壁打ち（関西弁） |
| legal-advisor | ケン（賢） | opus | 法律相談・契約書チェック・法的リスク確認（日本法基準） |

## シンヤさんの仕事
- クライアントのマーケティング・業務改善を支援している
- クライアント情報は `~/.claude/clients/` ディレクトリで管理
- 技術ナレッジ（クライアント横断）は `~/.claude/knowledge/` で管理
  - `knowledge/wordpress-plugin/` ← WPプラグイン開発知見
  - `knowledge/figma-mcp/` ← Figma MCP Server 活用知見
  - `knowledge/ai-image-layered/` ← AI画像生成レイヤー分割ワークフロー
  - `knowledge/windows-python/coding-rules.md` ← Windows Python コーディングルール（2026-03-28）
- → オフィスウエダ詳細は `clients/officeueda/README.md` を参照（biz-web/ と biz-ai/ の2事業）
- → ランドプランニング詳細は `clients/lando-planning/README.md` を参照
- → US-SAIJO詳細は `clients/us-saijo/README.md` を参照

## シンヤさんの人物像
→ 詳細は [user-shinya.md](user-shinya.md) を参照（思考スタイル・アスカへの期待）

## シンヤさんの職務経歴・プロフィール
→ `~/.claude/profile.md` を参照（職務要約・経歴・資格・スキルサマリー。プロフィール文・自己紹介作成時に使う）

## シンヤさんの好み
- 業務・作業時は正確さを最優先
- 普段の会話では冗談OK
- 回答は日本語で
- インデント：スペース2つ、セミコロン不要（JS）

## エージェント設計の知見
→ 詳細は [agent-design.md](agent-design.md) を参照（変更フロー・インシデント対応フロー含む）

## 品質管理プロジェクト
→ 詳細は [quality-management.md](quality-management.md) を参照（対策一覧・担当・着手順序）

## 言葉遣いフィードバック
→ 詳細は [feedback-language.md](feedback-language.md) を参照

## 新事業検出のフィードバック
→ 詳細は [feedback-new-biz-detection.md](feedback-new-biz-detection.md) を参照（新事業の話が出たらアスカから確認・ディレクトリ変更まで自動実施）

## デザイン説明のフィードバック
→ 詳細は [feedback-design-explanation.md](feedback-design-explanation.md) を参照（OG画像・バナー・FV生成時は必ず意図説明を添える）

## 編集スコープのフィードバック
→ 詳細は [feedback-edits.md](feedback-edits.md) を参照（依頼外の変更を加えない）

## 質問スタイルのフィードバック
→ 詳細は [feedback-questions.md](feedback-questions.md) を参照（漠然とした依頼への質問の仕方）

## ラウンドテーブルの活用スタイル
→ 詳細は [feedback-roundtable.md](feedback-roundtable.md) を参照（複数領域にまたがる相談で自動実施・2026-03-28確立）

## ナレッジ化スタイルのフィードバック
→ 詳細は [feedback-knowledge-style.md](feedback-knowledge-style.md) を参照（要点絞り・元ファイル削除まで一括）

## effortコントロールのフィードバック
→ 詳細は [feedback-effort-control.md](feedback-effort-control.md) を参照（設計・実装セッション開始時のみ /effort high を提案）

## /loop提案タイミングのフィードバック
→ 詳細は [feedback-loop-suggestion.md](feedback-loop-suggestion.md) を参照（必要なケースが来たときだけ提案・事前説明不要）

## sync タイミングのフィードバック
→ 詳細は [feedback-sync-timing.md](feedback-sync-timing.md) を参照（「作業＋おつかれ」同時の場合は作業を先に完了してから sync）

## 開発ワークフローのフィードバック
→ 詳細は [feedback-dev-workflow.md](feedback-dev-workflow.md) を参照（担当が決まっているものは即委譲・確認不要。2026-03-28 再整理）

## officeueda Instagramマンガプロジェクト
→ 詳細は [project-officeueda-instagram-manga.md](project-officeueda-instagram-manga.md) を参照（キャラ設定・フォーマット・ツール選定保留中）

## 保留タスク
→ 詳細は [pending-tasks.md](pending-tasks.md) を参照
