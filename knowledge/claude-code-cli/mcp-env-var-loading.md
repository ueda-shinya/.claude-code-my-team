# MCP サーバーへの環境変数の渡し方

**検証日：** 2026-04-23（search-analytics MCP でサイト追加作業中に判明）

## 結論

MCPサーバーに環境変数を渡す方法は2系統ある：

1. **`.claude.json` の `mcpServers.<name>.env` に明示列挙**（確実）
2. **サーバー側コードで `.env` を読む**（CWD依存のため不安定）

環境変数が確実に届かないといけない MCP サーバーは **方式1** を使う。

## MCP 登録場所

MCP サーバー定義は以下2箇所のどちらかにある：

| ファイル | スコープ |
|---|---|
| `~/.claude.json` の `mcpServers` | **Claude Code ユーザー全体設定**（本命・こちらを確認） |
| `~/.claude/settings.json` の `mcpServers` | プロジェクト/ユーザー設定 |

search-analytics などのサーバーは `~/.claude.json` 側に登録されている。`~/.claude/settings.json` を grep しても見つからないときはこちらを確認。

## 方式1: env に直接列挙（推奨）

```json
{
  "mcpServers": {
    "search-analytics": {
      "command": "path/to/python.exe",
      "args": ["path/to/server.py"],
      "env": {
        "PYTHONIOENCODING": "utf-8",
        "ANALYTICS_SITES": "site1,site2",
        "SITE1_GSC_URL": "https://example.com/",
        "SITE1_GA4_PROPERTY_ID": "123456789"
      }
    }
  }
}
```

長所：
- 確実に渡る（CWD 依存なし）
- MCP サーバー起動ログで `env` にどの変数が渡ったか確認しやすい

短所：
- `.env` と二重管理になる（`.env` も使っている場合）
- 値を変更するたびに `~/.claude.json` を書き換える必要がある

## 方式2: サーバー側で `load_dotenv()` を呼ぶ（注意）

```python
from dotenv import load_dotenv
load_dotenv()  # ← パス無指定だと CWD の .env を読もうとする
```

**落とし穴：**
- パス無指定の `load_dotenv()` は **CWD（カレントディレクトリ）** の `.env` を探す
- Claude Code が MCP サブプロセスを起動するときの CWD は保証されない
- `mcpServers.<name>.cwd` を指定しても、**Claude Code の実装が `cwd` フィールドを尊重するかは確認が必要**（2026-04-23 時点、検証では効かなかった事例あり）
- `load_dotenv()` は既存の環境変数を **上書きしない** のがデフォルト。親プロセスに同名変数があれば `.env` の値は無視される

**対策：**
- パスを明示：`load_dotenv(dotenv_path=Path.home() / '.claude' / '.env')`
- あるいは方式1を併用

## MCP と他スクリプトの env 独立性

同じ `.env` を読んでいるように見えても、**MCP サーバー経由**と**通常スクリプト経由**で動作が分かれることがある：

| 経路 | 動作 |
|---|---|
| `morning-briefing` → `ga4-report.py` を subprocess 実行 | ga4-report.py が `os.path.expanduser('~/.claude/.env')` で**絶対パス直読み** → `.env` 確実に読める |
| Claude Code → MCP サーバー | MCP の `load_dotenv()` が CWD 依存 → `.env` 読めない可能性 |

**「morning-briefing で動く」は MCP が動く根拠にならない**（2026-04-23 実例あり）。

## 診断の順序

MCP ツールが期待通りに動かないとき：

1. **ツール定義（enum・description）を確認**
   - `ToolSearch` で最新の schema を取得
   - サーバーコード側で enum が動的生成されている場合、ここが古いなら env が届いていない証拠

2. **サーバープロセスの存在確認**
   - `Get-CimInstance Win32_Process` で `python.exe` のコマンドラインを確認
   - 古いプロセスが残っていることがある → Claude Code 再起動で掃除

3. **`~/.claude.json` の `mcpServers` 定義を確認**
   - `env` に必要な変数が列挙されているか
   - `command` / `args` が正しいか

4. **サーバーコード側の `.env` 読み込み実装を確認**
   - `load_dotenv()` のパス指定有無
   - `os.environ.get()` の変数名

5. **代替経路の動作と比較**
   - 同じ `.env` を別経路（絶対パス直読み）で読むスクリプトが動いていれば、`.env` 内容自体は正しい
   - その場合、問題は MCP 側の読み込み実装

## 関連ファイル

- `~/.claude.json` … Claude Code ユーザー全体設定（MCP定義・feature flags 等）
- `~/.claude/settings.json` … プロジェクト/ユーザー設定
- `~/.claude/mcp-servers/*/` … 各 MCP サーバーの実装

## 関連 Notion 案件

- 「search-analytics MCP の .env 二重管理解消＋test_credentials.py 変数名統一」（P3-今月中）
  - 恒久対策: `unified_analytics_server.py` の `load_dotenv()` にパス明示指定 → `.claude.json` 側の env 列挙を削除して `.env` 一本化
