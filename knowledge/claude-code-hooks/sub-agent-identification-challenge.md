# Claude Code hooks: サブエージェント識別の限界

**背景:** 2026-04-17、アスカのコーディング違反対策として `code-edit-guard.sh` PreToolUse hook を導入したが、サブエージェント（シュウ等）の正当な編集までブロックしてしまい、委任フローが壊れる事態が発生した。

## 問題
Claude Code の hook は tool_name / tool_input を受け取るが、**「誰が（メインエージェント vs サブエージェント）このツールを呼んだか」を判別する公式な方法が現時点でない**。

したがって：
- メインエージェント（アスカ）の Edit/Write をブロックしたい
- サブエージェント（シュウ/カナタ等）の Edit/Write は許可したい

という「呼び出し元依存の制御」を hook だけで実装できない。

## 暫定対応（2026-04-17）
`code-edit-guard.sh` を **warn-only モード**に変更（`exit 2` → `exit 0`、`BLOCKED` → `[WARN]`）。業務は止まらないがブロック効果もない = セルフチェックに逆戻り。

## 再kaizen で検討すべき方向性

### Option A: サブエージェント識別マーカーファイル
サブエージェントがファイル編集前に `~/.claude/tmp/bypass-hook.lock` を touch、hook がマーカー存在時は許可。
- 問題: サブエージェントのプロンプトに追加手順を入れる必要、マーカー削除漏れリスク

### Option B: Claude Code の環境変数活用
Claude Code が将来的に `CLAUDE_AGENT_TYPE=main|sub` 等を露出するのを待つ。
- 問題: 公式機能待ち、現時点では未実装

### Option C: hook ではなく Stop hook + 事後検知
編集後に記録して、アスカがルール違反したことを検知・通知。
- 問題: 防止ではなく事後検知、Rina が kaizen 時点で弱いと指摘済み

### Option D: ルールベース設計の見直し
「Asuka は Edit/Write しない」ではなく「Asuka は `hooks/` `scripts/` 等 特定ディレクトリの Edit/Write しない」のようにディレクトリベースに変更。セルフチェック + hook 警告の二段構え。
- 問題: ルールの複雑化

### Option E（現実解）: 委任ワークフロー自体の強制
アスカのプロンプト/指示テンプレートに「コード編集タスクは必ず Agent(shu) で送出」を組み込み、アスカが直接編集しない構造を強化。hook は補助的な警告のみ。
- 問題: プロンプト逸脱時に効かない

## 参考
- 初回kaizenの議論: 2026-04-17 セッション
- 違反履歴: `memory/feedback-dev-workflow.md`
- 現行hook: `hooks/code-edit-guard.sh`（warn-only）
- settings.json 登録: `settings.json` の `hooks.PreToolUse`
