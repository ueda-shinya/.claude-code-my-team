# Claude Design リファレンス

## 概要

**Claude Design** は Anthropic Labs が 2026-04-17 にリリースした、**claude.ai 内機能ではなく独立プロダクト**としてのデザイン生成ツール。Claude Opus 4.7 を動力源とし、テキスト・スクリーンショット・既存コードベース・各種ドキュメントを入力に、本番品質のデザインアウトプット（HTML / PPTX / PDF / Canva 等）を生成する。

**本ディレクトリはリファレンス資料**であり、スキルとして発動しない（`SKILL.md` を置かず `README.md` のみ）。LP・クライアント案件で「Claude Design を採用するか」をシンヤさん／アスカが判断する際の **事実情報集** として参照する。

運用判断ルール（いつ Claude Design を選ぶか・既存フローとの住み分け）は `CLAUDE.md` の「External Skill Guard Rules」セクション内 **「Claude Design (Anthropic Labs プロダクト本体)」節** を正とする。本ファイルは事実情報のみを扱う。

## 1. プロダクト基本情報

| 項目 | 内容 |
|---|---|
| 提供元 | Anthropic Labs |
| リリース | 2026-04-17 |
| アクセス URL | claude.ai/design |
| 動力源 | Claude Opus 4.7 |
| 位置づけ | claude.ai 内の機能ではなく **独立プロダクト** |
| 段階 | 研究プレビュー（Research Preview） |

## 2. 利用条件・料金

| プラン | 利用可否 | 追加料金 | 備考 |
|---|---|---|---|
| Free | 不可（明記なし） | — | — |
| Pro | 可 | なし | サブスク制限内で利用 |
| Max | 可 | なし | サブスク制限内で利用 |
| Team | 可 | なし | サブスク制限内で利用 |
| Enterprise | 可（要有効化） | なし | **デフォルト OFF**。組織設定 > Capabilities > Anthropic Labs で有効化必要 |

## 3. 入出力仕様

### 入力（Input）

- テキストプロンプト
- スクリーンショット（画像）
- ドキュメント: DOCX / PPTX / XLSX
- 既存コードベース指定（リポジトリ参照）
- Figma ファイル参照（補完スキル経由含む）

### エクスポート（Output）

- `.zip`（複数ファイルバンドル）
- PDF
- PPTX
- Canva（連携エクスポート）
- スタンドアロン HTML

### 特徴的機能

- **既存コードベース・Figma ファイルからデザインシステムを自動継承**（カラー・タイポ・コンポーネント命名等を読み取り）
- **Claude Code へのハンドオフバンドル送付** が 1 命令で可能（実装フェーズへの引き渡しが構造化されている）

## 4. 制限事項（研究プレビュー段階）

- **ベクターイラスト生成不可**（ラスター/HTML 出力のみ）
- **リアルタイム多人数協業なし**（Figma の同時編集相当機能は非搭載）
- **Figma Inspect 相当のコードアノテーションなし**（spacing/typography の数値ピッキングが不可）
- **大規模リポジトリで遅延が発生**（ファイル数・サイズに応じてレスポンス低下）

## 5. 補完スキル・関連プロダクト整理

Claude Design 単体ではカバーしきれない領域を補う関連スキル・プロダクトの位置づけ。

| 名称 | 提供元 | 役割 | Claude Design との関係 |
|---|---|---|---|
| **frontend-design** | Anthropic 公式（スキル） | 本番 UI 生成（Claude Code 内で発動）。Inter / Roboto / Arial / Space Grotesk は明示禁止 | Claude Design がプロト/コンセプト、frontend-design が実装寄り（本リポ A/B 評価中） |
| **brand-guidelines** | Anthropic 公式（スキル） | Anthropic ブランドカラー/タイポを自動適用 | Anthropic 自社ブランド向け。クライアント案件では使用しない |
| **web-design-guidelines** | Vercel Labs（スキル） | 100+ ルールでのデザイン監査 | 生成ではなく **監査**。Claude Design 出力のレビュー用途で併用可 |
| **Huashu Design**（参考） | alchaincyf（OSS） | 中国語圏発オルタナティブ。MP4 エクスポート対応 | 機能比較のための参考。本リポでは未導入 |

## 6. ベストプラクティス（公式・コミュニティ知見）

### LP / マーケティング用途

```
Claude Design でプロト生成
  ↓
Claude Code で実装（ハンドオフバンドル経由）
  ↓
必要に応じて Figma MCP で Figma へ書き出し（デザイナー納品時）
```

### UI / UX 用途

- Figma MCP 接続 + Claude Code でデザインシステム継承して生成
- Claude Design 単体より、Figma MCP 併用のほうが既存システム整合性が高い

### ツール使い分け基準（公式記載ベース）

| 用途 | 推奨ツール |
|---|---|
| 個人プロト・コンセプト検証 | Claude Design |
| フルスタックアプリ生成 | Lovable |
| 既存リポへの UI 追加 | v0 |
| 本番運用・チーム共有のデザインシステム | Figma |

## 7. セキュリティ警告（外部スキル全般）

Snyk が 2026 年に公表した「ToxicSkills」研究によると、**外部スキルマーケットプレイスの 13.4%（534 件）に critical-level の欠陥**が報告されている。Claude Design 本体ではなく外部スキルの話だが、補完スキル導入時は以下を必ず実施：

- `SKILL.md` の目視確認
- ツール要求（`tools:`）に過剰な権限要求がないかチェック
- 外部 fetch / subprocess / 認証情報アクセスの有無確認
- ライセンス確認

本リポの外部スキル導入プロセスは `CLAUDE.md` の「External Skill Guard Rules」に従う。

## 8. 出典（一次情報・全件裏取済み）

- https://www.anthropic.com/news/claude-design-anthropic-labs
- https://support.claude.com/en/articles/14604416-get-started-with-claude-design
- https://support.claude.com/en/articles/14604406-claude-design-admin-guide-for-team-and-enterprise-plans
- https://github.com/anthropics/claude-code/blob/main/plugins/frontend-design/skills/frontend-design/SKILL.md
- https://snyk.io/blog/toxicskills-malicious-ai-agent-skills-clawhub/

## 9. 未確認情報（ファクトチェック未達・参考扱い）

以下はミオの調査過程で出た数値だが、リクのファクトチェックでは一次情報での確定に至らなかったため、参考扱いとする。意思決定には使用しない。

- frontend-design 277K インストール（出典確認できず）
- v0 / Vercel 関連 19,487 Stars（出典確認できず）
- その他、コミュニティ集計サイト由来の利用統計

これらが必要になった場合は、再度ミオに一次情報の取り直しを依頼してから採用すること。

## 10. 関連リソース（本リポ内）

- 運用判断ルール: `CLAUDE.md` 「External Skill Guard Rules」内 Claude Design 節
- frontend-design A/B 評価ログ: `memory/evaluation-frontend-design.md`
- Figma MCP セットアップ: `knowledge/figma-mcp/README.md`
- LP 制作フロー: `CLAUDE.md` Image Generation Flow / 関連エージェント定義（Ren / Kai / Yui / Luna / Shu）

## 11. 取得日・再評価条件

- 取得日: 2026-04-25
- リリース直後（2026-04-17 から 8 日経過時点）の情報のため、研究プレビュー段階特有の制限が早期に解消される可能性あり
- **再評価トリガー**:
  - 研究プレビューから一般提供（GA）への昇格時
  - ベクターイラスト生成・リアルタイム協業・コードアノテーションのいずれかが解禁された時
  - frontend-design A/B 評価が結論に至った時（評価ログ参照）
  - 料金体系・プラン構成に変更があった時
