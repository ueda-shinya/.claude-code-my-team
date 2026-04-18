# Claude Code CLI セッション継続機能

2026-04-18 の実機検証で確認した `claude -p --resume` の動作仕様。将来 `claude.exe` をスクリプトから呼び出してセッションを継続する実装を行う際の参考資料。

## 公式サポート機能（claude --help より）

| フラグ | 動作 |
|---|---|
| `-c`, `--continue` | カレントディレクトリの最新セッションを継続 |
| `-r`, `--resume [value]` | 指定 session_id で再開（引数省略で対話ピッカー） |
| `--session-id <uuid>` | 特定の session_id を使用 |
| `--fork-session` | 再開時に新しい session_id を作成 |
| `--no-session-persistence` | セッションを保存しない（`--print` 専用） |

## 動作確認結果（2026-04-18）

### TEST 結果サマリー

| 項目 | 結果 |
|---|---|
| `-p --output-format json` で session_id 取得 | ✅ stdout JSON のトップレベル `session_id` フィールドから取得可能 |
| `-p --resume <id>` での文脈継続 | ✅ 完全動作（Issue #1967 解消） |
| 別ディレクトリからの resume | ❌ `No conversation found` エラー（cwd 依存） |
| `--dangerously-skip-permissions` + `--resume` 併用 | ✅ 問題なし |

### セッションファイル

- **保存先**: `~/.claude/projects/<encoded-cwd>/<uuid>.jsonl`
- **形式**: JSONL（1行1イベント、`type` フィールドで種別判別）
- **`<encoded-cwd>`**: 現在ディレクトリの非英数字文字を `-` に置換したもの（例: `C:\Users\ueda-\.claude` → `c--Users-ueda---claude`）

### コスト感覚（Opus 4.7 / 1M context での計測値）

| 状況 | コスト | 応答時間 |
|---|---|---|
| 初回（コンテキスト読込込み） | 約 $0.146 | 10〜15秒 |
| 2回目以降（キャッシュ効果） | 約 $0.02 | 5〜7秒 |

キャッシュヒット率が極めて高いため、長期継続セッションでもコスト爆発しない。

## 実装時の必須事項

### 1. cwd 固定（最重要）

セッションファイルパスが cwd に依存するため、スクリプトから `subprocess` で起動する場合は **cwd を必ず固定** する必要がある。

```python
subprocess.run([
    "claude.exe", "-p", "--resume", session_id,
    "--output-format", "json",
    "--max-budget-usd", "1.00",
    "--dangerously-skip-permissions",
    user_message,
], cwd=os.path.expanduser("~/.claude"))  # ← 必須
```

別の cwd から実行すると `No conversation found with session ID: ...` エラーになる。

### 2. session_id 取得パターン

Anthropic 公式推奨: 初回実行で `--output-format json` を指定し、stdout から session_id を抽出して Bot 側 state に保存する。

```python
# 初回実行
result = subprocess.run([
    "claude.exe", "-p", "--output-format", "json",
    "--max-budget-usd", "1.00",
    "--dangerously-skip-permissions",
    user_message,
], cwd=CLAUDE_DIR, capture_output=True, text=True)

data = json.loads(result.stdout)
session_id = data["session_id"]  # ← トップレベルに存在
# session_id を state に保存

# 2回目以降
subprocess.run([
    "claude.exe", "-p", "--resume", session_id,
    "--output-format", "json",
    # ...
], cwd=CLAUDE_DIR)
```

### 3. `--max-budget-usd` の扱い

- `--max-budget-usd` は **1セッション実行あたりの上限**
- 長期継続セッションでは毎回指定する必要あり
- 日次累計を制限するには、呼び出し側で `api-cost-history.json` 型の履歴記録を実装して別途ハードストップする必要がある

### 4. コンテキスト肥大化対策

- 約 167K トークン超で自動コンパクション（会話要約 + 古いメッセージ破棄）
- コンパクション時は JSONL に `compact_boundary` レコードが書き込まれる
- 長期運用では **無操作時間で自動リセット**（例: 24時間）を Bot 側で実装するのが安全

## 既知の制約

### cwd 問題

`claude.exe -p --resume` は **cwd に紐づいたプロジェクト空間内でのみセッションを検索する**。以下のようなケースで "No conversation found" が発生する:

- CLI 実行時の cwd が初回実行時と異なる
- 同一マシン上でも別ディレクトリから呼び出す
- ngrok 経由・タスクスケジューラ等で cwd が想定外になる

### PID 再利用（Windows）

claude.exe のプロセスを `psutil` で追跡する場合、Windows では PID 再利用が短時間で発生するため、`psutil.pid_exists()` だけでは不十分。以下の二重検証を推奨:

```python
proc = psutil.Process(pid)
# ① プロセス名一致
if proc.name() not in ["claude.exe", "claude"]:
    return False
# ② 起動時刻一致（±5秒許容）
if abs(proc.create_time() - recorded_start_epoch) > 5:
    return False
```

### Task ツール経由のサブエージェント

`Task` ツール経由で呼ばれるサブエージェント（ミオ・リナ等）は **親の `--allowedTools` を継承しない**。hook 側で末端ツール呼び出しを防御する必要がある。

## 参考リンク

- 公式: https://code.claude.com/docs/en/cli-reference
- Agent SDK sessions: https://code.claude.com/docs/en/agent-sdk/sessions
- Headless: https://code.claude.com/docs/en/headless
- GitHub Issue #1967（解消済）: https://github.com/anthropics/claude-code/issues/1967
