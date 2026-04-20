# MEMORY INDEX

## user
- [user_shinya.md](user_shinya.md) — シンヤさんのプロフィール・作業スタイル・コミュニケーション傾向

## feedback
- [feedback_notion_task_registration.md](feedback_notion_task_registration.md) — 残件登録はゼロコンテキストの別セッションが読んで実行できる情報量で書く（背景・作業内容・注意・ファイルパスの4項目必須）
- [feedback_cross_platform.md](feedback_cross_platform.md) — スクリプト・スキル修正時はMac・Windows両対応を必ず確認する
- [feedback_lp_team_review.md](feedback_lp_team_review.md) — LP制作は最初の設計からチーム分担。標準フロー確定（ミオ→レン→カイ→コト→ユイ→ツバサ→サクラ）
- [feedback_asuka_role.md](feedback_asuka_role.md) — アスカの役割は全部自分でやることではなく、適材適所でチームにタスクを割り振ること
- [feedback_code_intent.md](feedback_code_intent.md) — コード改善時は元の意図を正確に読んでから変更する（動作を変える場合は明示して確認）
- [feedback_security_review.md](feedback_security_review.md) — ファイル操作・認証・ユーザー入力処理等のコードを書いた直後はサクラに自動レビューを依頼する（確認不要）
- [feedback_data_format.md](feedback_data_format.md) — 業務データ変換時は元のフォーマットを確認してから変換ロジックを設計する（勝手に改善しない）
- [feedback_credential_management.md](feedback_credential_management.md) — 認証情報を丁寧に管理する方針。.env の直接操作を最小限に、定期ローテーション声がけ、echo 追記禁止
- [feedback_agent_study.md](feedback_agent_study.md) — agent-study 提案前に「一般知識か固有知識か」をアスカが先に判断。一般的な教科書系は提案しない
- [feedback_asuka_proactive_proposal.md](feedback_asuka_proactive_proposal.md) — 問題発見・修正だけでなく「なぜ起きたか」報告＋構造的な対案提示までがアスカの仕事
- [feedback_asuka_proactive_flagging.md](feedback_asuka_proactive_flagging.md) — 定型レポート中に気になる情報があれば、頼まれなくても「気になる点」としてフラグを立て調査打診まで行う
- [feedback-web-design.md](feedback-web-design.md) — Web成果物のフォントサイズ最小値ルール（通常テキスト16px・補足10px）
- [feedback_code_review_mandatory.md](feedback_code_review_mandatory.md) — 実装完了後のコードレビューは絶対に省略しない（sandboxでも例外なし）
- [feedback-agent-training.md](feedback-agent-training.md) — エージェント育成は「知識を与える」ではなく「情報からスキルを作る」が正解
- [feedback-slide-tools.md](feedback-slide-tools.md) — スライド制作は Genspark 等の専用ツールを優先。MD→スライド系（Marp/pptx-from-layouts）は積極提案しない
- [feedback-asuka-judgment.md](feedback-asuka-judgment.md) — エージェント出力を検証なしに「良い」と報告しない。判断根拠を明示するか専門レビューを挟む
- [feedback-agent-skill-gap.md](feedback-agent-skill-gap.md) — エージェントの繰り返し失敗はスキル化で根本対応（指示改善は2回まで、3回目でスキル化）
- [feedback-dev-workflow.md](feedback-dev-workflow.md) — アスカのコーディング禁止ルール違反履歴（.env以外のコード・スクリプト・設定ファイルは必ずシュウに委任）
- [feedback-asuka-interpretation.md](feedback-asuka-interpretation.md) — シンヤさんの指示の対象範囲・前提を勝手に広げない（拡大解釈禁止）
- [feedback-rina-risk-threshold.md](feedback-rina-risk-threshold.md) — リナ検証はリスク1-5併記で、リスク3以上対処・2以下許容の閾値運用（無限ループ防止）

## project
- [project_notion_management.md](project_notion_management.md) — Notion DB一本化方針（案件リスト・残件タスク→案件管理に統合、スキーマ設計は持ち越し）
- [project_slide_workflow.md](project_slide_workflow.md) — スライド制作標準フロー（ソラ→Genspark）と品質向上ポイント
- [project_officeueda_web.md](project_officeueda_web.md) — officeueda web事業コーディング方針・GMC対応など
- [project_gmc_sync.md](project_gmc_sync.md) — カラーミー×GMC自動同期ツール開発プロジェクト（officeueda導入代行サービス）
- [project_client_consultation_flow.md](project_client_consultation_flow.md) — クライアント相談受付の標準フロー（顧客確認→Notion登録→clientsディレクトリ作成）

## knowledge
- [knowledge/line-works-bot/implementation-notes.md](../knowledge/line-works-bot/implementation-notes.md) — LINE WORKS Bot 実装メモ（UUID形式のユーザーID・expires_in型・ngrok制限・署名検証）
- [knowledge/line-works-bot/implementation-gotchas.md](../knowledge/line-works-bot/implementation-gotchas.md) — LINE WORKS Bot マルチBot実装の落とし穴・チェックリスト（署名検証・二重返答・ハンドオフ等）
- [knowledge/team-collaboration/agent-consultation-approach.md](../knowledge/team-collaboration/agent-consultation-approach.md) — エージェント打診アプローチ：新しい役割・ワークフロー設計時に候補エージェントに直接打診して意見を聞く手法。独立審議→自然収束の事例と適用ガイドライン

## LINE WORKS Bot
- [LINE WORKS実装教訓](../knowledge/line-works-bot/implementation-gotchas.md) — Bot追加時の落とし穴・チェックリスト
- スキル: `/lineworks-add-bot` — Bot追加手順

## アナリティクス
- [knowledge/analytics/ga4-bounce-exit-analysis.md](../knowledge/analytics/ga4-bounce-exit-analysis.md) — GA4 バウンス率・離脱数の公式定義・解釈レベル・4ステップ分析・原因仮説マトリクス・報告テンプレート

## エージェント運用
- [knowledge/agent-ops/background-agent-permissions.md](../knowledge/agent-ops/background-agent-permissions.md) — バックグラウンドエージェントはファイル書き込み権限プロンプトに応答できない。書き込みタスクはフォアグラウンドで実行する
- [knowledge/claude-code-cli/session-continuation.md](../knowledge/claude-code-cli/session-continuation.md) — claude -p --resume の動作仕様・cwd依存・コスト・セッションファイル形式（2026-04-18 実機検証）
- [knowledge/claude-code-cli/session-resume-troubleshooting.md](../knowledge/claude-code-cli/session-resume-troubleshooting.md) — VSCode Claude で覚えのないセッション再開が起きた時の切り分け手順（jsonl初回メッセージ・permissionMode・USD budgetの3点診断）
- [knowledge/claude-code-cli/plugins-vs-skills.md](../knowledge/claude-code-cli/plugins-vs-skills.md) — Plugin/Skill/Marketplaceの階層関係、導入ルート3種、公式マーケ、名前空間、シンヤさん環境での選択基準

## PM
- [knowledge/pm/wbs-scope-management.md](../knowledge/pm/wbs-scope-management.md) — WBS・スコープ管理・MoSCoW・依存関係4タイプ・変更管理プロセス（マルチエージェント運用向けPM実務）

## コピーライティング
- [knowledge/copywriting/copywriting-basics-judgment-guide.md](../knowledge/copywriting/copywriting-basics-judgment-guide.md) — コピーライティング3大原則・ヘッドライン判断軸・PAS/AIDA・CTA設計・自己診断チェックリスト・コトへの依頼テンプレート

## 画像生成
- [knowledge/image-prompt-engineering/prompt-engineering.md](../knowledge/image-prompt-engineering/prompt-engineering.md) — 5コンポーネント式プロンプト設計・ドメインモード修飾語・Banned Keywords・テンプレート（banana-claude由来）
- [knowledge/image-prompt-engineering/gemini-imagen-constraints.md](../knowledge/image-prompt-engineering/gemini-imagen-constraints.md) — Gemini Imagen API制約（アスペクト比7:10問題・PNG実体・別モデル情報）
- [knowledge/image-prompt-engineering/x-thumbnail-templates.md](../knowledge/image-prompt-engineering/x-thumbnail-templates.md) — Xサムネイル用プロンプト100選（テキストなし50 + テキスト入り50・用途別インデックス）
- [knowledge/image-prompt-engineering/text-zone-design.md](../knowledge/image-prompt-engineering/text-zone-design.md) — テキスト後載せ画像のゾーン設計パターン（テキスト色×背景条件・コピー感情同期・位置別ガイド）

## 補助金・助成金
- [knowledge/subsidy-support-guide.md](../knowledge/subsidy-support-guide.md) — 補助金・助成金対応ガイド（リサーチ手順・提案チェック・対応フロー・採択ポイント）

## メール運用
- [knowledge/email-routing/xserver-local-delivery.md](../knowledge/email-routing/xserver-local-delivery.md) — Xサーバー ローカル配送問題の診断と解決（同サーバー内からGoogle Workspace運用ドメイン宛で user unknown バウンスする問題の3ステップ診断フロー・強制回収手順・禁忌事項）
