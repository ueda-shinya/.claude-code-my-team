# Claude Agent SDK — 仕様メモ（2026-04-27 検証）

LINE WORKS Bot v3.5 検討の過程でミオ調査＋リク検証を経て確定した、Claude Agent SDK の主要仕様メモ。SDK ベースで Claude Code 相当のエージェントをセルフホスト実装する際の参照資料。

## 概要

- **旧称**：Claude Code SDK（Claude Agent SDK に改名、移行ガイドあり）
- **位置づけ**：Claude Code（claude.exe）と同等の機能をプログラム組み込み形式で提供。CLI が対話用、SDK がプログラム組み込み用、内部バイナリは共有
- **ライセンス**：MIT（SDK 自体は無料）
- **提供形態**：
  - Python：`pip install claude-agent-sdk`
  - TypeScript：`npm install @anthropic-ai/claude-agent-sdk`
- **公式ドキュメント**：https://platform.claude.com/docs/en/agent-sdk/overview

## ランタイム要件

- Python 3.10 以上 / Node.js 18 以上
- **Windows x86-64 wheel が PyPI で提供されている**（claude.exe 別途インストール不要、SDK パッケージにネイティブバイナリが自動バンドル）
- 推奨スペック：RAM 1GiB / ディスク 5GiB / CPU 1コア（タスク規模により調整）

## 【重大】ファイルシステム設定のデフォルト挙動（Claude Code SDK → Agent SDK 改名時の主要変更点）

**SDK はデフォルトでファイルシステム設定を一切読み込まない。**

- 公式ドキュメント原文：「The SDK no longer reads from filesystem settings (CLAUDE.md, settings.json, slash commands, etc.) by default」
- `setting_sources` 省略時 = `None` = ファイルシステム設定無効
- `~/.claude/skills/`、`CLAUDE.md`、エージェント定義、ローカル MCP（`~/.claude/.mcp.json`）等を活用するには **`setting_sources=["user", "project"]` の明示指定が必須**

### 正しい呼び出し例（Python）

```python
from claude_agent_sdk import query, ClaudeAgentOptions

async for message in query(
  prompt=user_message,
  options=ClaudeAgentOptions(
    setting_sources=["user", "project"],  # 必須：~/.claude/ 配下を読み込み
    allowed_tools=["Read", "Grep", "Glob", "WebFetch", "WebSearch", "Skill"],
    resume=previous_session_id,             # セッション継続時に指定
    mcp_servers={...},                       # ローカル MCP サーバー（必要なら）
  ),
):
  ...
```

### 必須指定を忘れた場合に起きること

- `~/.claude/skills/<name>/SKILL.md` のカスタムスキルが読み込まれない
- `CLAUDE.md` のグローバルルールが反映されない
- `~/.claude/agents/*.md` のサブエージェント定義が利用不可
- `~/.claude/.mcp.json` のローカル MCP 設定が無視される

## セッション継続

- セッション ID は `ResultMessage.session_id` で取得（成功・失敗問わず付与される）
- 継続時は `ClaudeAgentOptions(resume=session_id)` で指定
- セッション保存先：`~/.claude/projects/<encoded-cwd>/*.jsonl`（ディスク永続化、デフォルト）
- マルチホスト環境では Session Store アダプターまたは jsonl 共有で対応
- ユーザーごとの会話継続（Bot 等）は session_id を ユーザー ID と紐付けて DB/JSON に保存し `resume=` で渡す設計が公式 Pattern 3（Hybrid Sessions）

## ローカル MCP 利用

- `mcp_servers` パラメータで stdio 型ローカルプロセスを指定可能
- 例：`{"filesystem": {"command": "npx", "args": ["-y", "@modelcontextprotocol/server-filesystem", "/path/to/dir"]}}`
- `~/.claude/.mcp.json` も `setting_sources=["user"]` を含めれば自動読み込み

## 課金

- **SDK 利用料：無料**（MIT）
- **トークン課金**：通常の Claude API と同レート（実装時に Anthropic 公式 Pricing ページで最新単価を確認）
- **Managed Agents の $0.08/h セッションランタイム課金は SDK には適用されない**（Managed Agents との大きな差別化点）
- コスト取得：`ResultMessage.total_cost_usd` でリアルタイム取得可能

## Managed Agents との比較（参考）

| 観点 | Managed Agents | Agent SDK |
|---|---|---|
| ホスト | Anthropic クラウド固定（BYO/セルフホスト不可）| 任意（顧客サーバー / コンテナ / オンプレ）|
| ローカル `~/.claude/` アクセス | 不可 | 可能（`setting_sources` 明示）|
| ローカル MCP | 利用不可 | 利用可能 |
| 追加ランタイム課金 | $0.08/h | なし |
| メモリ機能（セッション間学習）| `/mnt/memory/` ストア（公式提供）| 自前実装が必要 |
| 用途 | クラウド完結のステートフルエージェント | ローカル統合 / セルフホストエージェント |

## バージョン管理上の注意

- 実装着手時は PyPI Release history（https://pypi.org/project/claude-agent-sdk/#history）で `claude-agent-sdk` の最新バージョンを再確認すること
- 2026-04-27 時点でリク検証により PyPI Release history で確認できた最新版は **v0.1.66（2026-04-23 リリース）**。それ以降のバージョンは PyPI 直接確認推奨

## 想定ユースケース

- LINE WORKS Bot 等の Webhook 経由 Claude Code 呼び出し（v3.5 候補設計の核心）
- ローカル `~/.claude/` の知見（CLAUDE.md / カスタムスキル / カスタムエージェント）を活用したクライアント案件 Bot
- セッション間のステートフルな対話を必要とする業務用エージェントの自前実装
- Managed Agents が不適となる「ローカル統合が必要な」エージェント基盤

## 出典

- 公式 overview：https://platform.claude.com/docs/en/agent-sdk/overview
- 移行ガイド（Claude Code SDK → Agent SDK 改名・`setting_sources` 仕様変更）：https://platform.claude.com/docs/en/agent-sdk/migration-guide
- Skills 仕様：https://platform.claude.com/docs/en/agent-sdk/skills
- Sessions 仕様：https://platform.claude.com/docs/en/agent-sdk/sessions
- Hosting：https://platform.claude.com/docs/en/agent-sdk/hosting
- MCP 仕様：https://platform.claude.com/docs/en/agent-sdk/mcp
- Cost tracking：https://platform.claude.com/docs/en/agent-sdk/cost-tracking
- PyPI：https://pypi.org/project/claude-agent-sdk/

## 検証履歴

- 2026-04-27：ミオ調査（researcher）→ リク検証（fact-checker）→ Approved。`setting_sources` のデフォルト挙動について重大な誤情報訂正がリクから入り、本ドキュメントには訂正後の正しい仕様を反映済み
