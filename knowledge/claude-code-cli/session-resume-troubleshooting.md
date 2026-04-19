---
name: VSCode Claude セッション予期せぬ再開の切り分け手順
description: 覚えのないセッションが再開された場合、session jsonl を見て「過去の検証セッションか」「外部注入か」を判別する診断手順
type: reference
---

# VSCode Claude セッション予期せぬ再開の切り分け手順

VSCode で Claude Code を開いたとき「自分が送った覚えのないプロンプトから始まる会話」が表示されることがある。多くは過去の検証セッションが VSCode 拡張のセッション復元機能で復活しているだけだが、外部注入・自動化の暴発と区別するため、以下の手順で切り分ける。

## 診断手順

### 1. セッションIDを特定する

現在のセッションの `sessionId` は以下のいずれかで確認できる:

- `~/.claude/sessions/<pid>.json` の `sessionId` フィールド
- `~/.claude/projects/c--Users-ueda---claude/*.jsonl` のうち、直近で mtime が更新されているファイル名（拡張子なし部分）

### 2. 会話の先頭を見て初回メッセージと permissionMode を確認する

Windows の encoded-cwd は `c--Users-ueda---claude`、Mac の encoded-cwd は `-Users-<username>--claude` 形式（現在ディレクトリの非英数字を `-` に置換）。

```bash
# 初回 user メッセージを全走査（head -5 だと system-reminder/attachment で埋まる場合あり）
python3 -c "
import json, sys
path = sys.argv[1]
with open(path, encoding='utf-8') as f:
    for line in f:
        d = json.loads(line)
        if d.get('type') == 'user':
            msg = d.get('message', {})
            content = msg.get('content', '')
            if isinstance(content, str):
                text = content
            else:
                text = next((p['text'] for p in content if isinstance(p, dict) and p.get('type')=='text'), '')
            if text:
                print('ts:', d.get('timestamp'))
                print('mode:', d.get('permissionMode'))
                print('entrypoint:', d.get('entrypoint'))
                print('text:', text[:200])
                break
" ~/.claude/projects/c--Users-ueda---claude/<session-id>.jsonl
```

### 3. 判定基準

| 観察 | 意味 |
|---|---|
| **初回 user メッセージのタイムスタンプと現在時刻に大きな乖離（例: 1日以上）** | 過去のセッションが VSCode 拡張のセッション復元機能で復活した可能性が高い（最重要シグナル） |
| 初回メッセージの内容が「テスト系プロンプト」「自分が書いた覚えのある文言」 | 自分の過去検証セッション |
| `USD budget: $0/$N` が system-reminder に表示 | 元セッション起動時に `--max-budget-usd N` が指定されていた（この値が system-reminder に残っているだけで、resume 側で再指定されているわけではない） |
| `entrypoint: claude-vscode` | VSCode 拡張経由 |
| `entrypoint: cli` | 手動 CLI 実行 |
| `entrypoint: mcp` / その他 | MCP / 自動化スクリプト経由の可能性 |
| `permissionMode: bypassPermissions` | 起動時に `--dangerously-skip-permissions` が付いていた（**常用環境では判定材料にならない**。補助情報としてのみ利用） |
| `permissionMode: acceptEdits` | VSCode デフォルトモード |

### 4. 過去の検証セッションだと確定したら

- `knowledge/claude-code-cli/session-continuation.md` 等、同時期に作成された knowledge / commit を照合すると目的が分かることが多い
- `git log --since="<そのセッションの前日>" --until="<そのセッションの翌日>"` で前後のコミットを確認
- 自分が実施した検証・テストの痕跡なら、そのまま継続するか新セッションに切替を判断

## 実例: 2026-04-18 の LINE WORKS Bot 検証セッション

**Why:** 2026-04-19 に「自分で送った覚えのない『私は田中太郎です。好きな数字は42』で始まるセッション」が出現。実はシンヤさんが 2026-04-18 に LINE WORKS Bot のセッション継続仕様 (`claude -p --resume`) を動作検証するために打ち込んだテストプロンプトが、翌日 VSCode 拡張で復元されたもの。`knowledge/claude-code-cli/session-continuation.md` のコミット（4/18 22:23）と session jsonl の timestamp（4/18 20:19）の一致、`USD budget: $0/$1` と `--max-budget-usd 1.00` の一致、`permissionMode: bypassPermissions` と `--dangerously-skip-permissions` の一致で確定。

**How to apply:** 同種の「覚えのないセッション」が発生したら、まず `~/.claude/projects/<encoded-cwd>/<session-id>.jsonl` の初回 user メッセージを読む。**タイムスタンプの乖離（1日以上）と初回メッセージ内容**が最重要シグナル。`USD budget` と `entrypoint` は補助情報として合わせて見る。それでも不明なら `git log` で前後のコミットを確認する。

## 関連

- [claude-code-cli/session-continuation.md](session-continuation.md) — `claude -p --resume` の動作仕様
