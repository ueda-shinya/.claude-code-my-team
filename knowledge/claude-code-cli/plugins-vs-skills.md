# Claude Code Plugin と Skill の関係（2026-04-18 調査）

## 階層構造

```
Plugin（Anthropic公式の上位配布単位）
├── Skills（skills/ ディレクトリ）
├── Agents（agents/ ディレクトリ）
├── Hooks（hooks/hooks.json）
├── MCP servers（.mcp.json）
└── その他（LSP servers、Monitors、Executables）
```

**Plugin は Skills を包含する上位概念**。`.claude-plugin/plugin.json` マニフェストで管理される。

## 導入ルート3種の使い分け

| ルート | コマンド | 用途 | インストール先 |
|---|---|---|---|
| **Anthropic公式 Plugin** | `claude plugin install <name>@claude-plugins-official` | 公式配布の包括パッケージ | `~/.claude/plugins/` |
| **GitHub repo経由 Plugin** | `claude plugin marketplace add <owner>/<repo>` 後に install | 第三者マーケ | 同上 |
| **Agent Skills 単発** | `npx skills add <owner>/<repo> -a claude-code --copy` | 個別スキル | `~/.claude/skills/` |

## マーケットプレイス

### 自動登録（起動時）
- `claude-plugins-official`（Anthropic公式）
  - カテゴリ: Code intelligence（LSP）/ External integrations（github等）/ Development workflows / Output styles

### 手動追加推奨
- `claude-code-plugins`（anthropics/claude-code、デモ用）
  - `claude plugin marketplace add anthropics/claude-code`

### 外部ディレクトリ
- `skills.sh`（Vercel Labs、91K+スキル、インストール数ランキング・セキュリティ評価付き）
- `vercel-labs/agent-skills`（Vercel純正スキル集）
- `anthropics/skills`（Anthropic公式スキル集、frontend-design等）

## 名前空間

Plugin 経由で導入された Skill は `/plugin-name:skill-name` で発動する（例: `/claude-md-management:audit`）。**既存のカスタムスキルとは名前空間分離されるので衝突しない**。

## シンヤさん環境での選択基準

| 目的 | 推奨ルート |
|---|---|
| 包括的な機能追加（複数Skill+Agent+Hook） | Plugin（公式マーケ優先） |
| 単発スキルの追加 | Agent Skills（`npx skills add`） |
| Windows環境 | `-a claude-code` フラグで他AIエージェント用ディレクトリ生成を回避 |
| 既存体制を壊したくない | 名前空間化で安全、ただし発動条件の衝突は別問題（CLAUDE.md External Skill Guard Rulesで対処） |

## マーケティング特化プラグインの有無

**公式マーケに直接のマーケティング特化プラグインは存在しない**（2026-04-18 時点）。開発系（commit-commands / pr-review-toolkit / plugin-dev 等）が中心。

- マーケ寄りはスキル単位で skills.sh から探す（coreyhaines31/marketingskills 等）
- ただし英語圏型なので、日本語運用のコト/ハル/humanizer と住み分け設計が必要

## 再起動の必要性

Plugin インストール後は `/reload-plugins` で即反映。**ただし VSCode 環境では `/reload-plugins` は利用不可**（2026-04-18 時点）→ Claude Code セッションを再起動する。

## 既存環境との干渉

- Plugin キャッシュは `~/.claude/plugins/cache/` に分離配置
- 既存 `~/.claude/skills/` や `~/.claude/agents/` には影響しない
- アンインストール: `claude plugin uninstall <name>@<marketplace>`
- 7日間のグレースピリオド付きロールバック可能

## 運用ノウハウ

### 副作用対策
- `npx skills add` デフォルト挙動は `~/.agents/` 配下にも他AIエージェント用スキルをコピーする
- シンヤさんは Claude Code のみ使用なので、**必ず `-a claude-code` フラグで絞る**

### 検索順序（skill-finder準拠）
1. skills.sh で最初に検索（ランキング・セキュリティ評価が揃う）
2. anthropics/skills と vercel-labs/agent-skills を個別確認
3. awesome-* 系カタログで個人作を探索
4. MCP の場合は MCP Registry → PulseMCP

## 関連ファイル
- CLAUDE.md 「External Skill Guard Rules」セクション
- `~/.claude/skills/skill-finder/SKILL.md`
- `~/.claude/skills/feature-flow/SKILL.md`（ステップ2.5 既存資産リサーチ）
