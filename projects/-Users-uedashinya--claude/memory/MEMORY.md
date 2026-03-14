# アスカのメモリ

## チームメンバー

| ファイル名 | 愛称 | モデル | 役割 |
|---|---|---|---|
| chief-of-staff | アスカ | opus | 全体統括・最終承認・月次達成判定・インシデント対応指揮 |
| researcher | ミオ | sonnet | 情報収集・調査・ベストプラクティス調査 |
| agent-builder | カナタ | opus | エージェント・スキル設計・技術実装（ログ・バリデーション・スキーマ） |
| code-reviewer | サクラ | opus | 品質基準策定・テンプレート設計・レビュー実施・規約作成 |
| fact-checker | リク | opus | 情報の正確性検証・ペアレビュー時の正確性補完・矛盾検出 |
| writer | ハル | sonnet | 調査結果の文章化・レポート作成 |
| marketing-planner | レン | opus | マーケティング戦略立案・競合分析・施策企画 |
| copywriter | コト | sonnet | マーケティングコピー制作（広告・LP・SNS・メール） |
| trouble-shooter | ソウ | opus | 障害記録・インシデント切り分け・チェックリスト補完・過去知見管理 |
| nano-banana | ルナ | sonnet | デザイン・画像生成（Gemini 2.5 Flash Image） |

## シンヤさんの仕事
- クライアントのマーケティング・業務改善を支援している
- クライアント情報は `~/.claude/clients/` ディレクトリで管理
- → ランドプランニング詳細は `clients/lando-planning/README.md` を参照
- → US-SAIJO詳細は `clients/us-saijo/README.md` を参照

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

## 質問スタイルのフィードバック
→ 詳細は [feedback-questions.md](feedback-questions.md) を参照（漠然とした依頼への質問の仕方）

## 保留タスク
→ 詳細は [pending-tasks.md](pending-tasks.md) を参照
