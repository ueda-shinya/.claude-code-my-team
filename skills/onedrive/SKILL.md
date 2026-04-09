# OneDrive 接続・操作スキル

ユーザーが「OneDriveの○○を見て」「OneDriveから○○をダウンロードして」「OneDriveを検索して」など、OneDrive上のファイル操作を求めたときに参照する。スラッシュコマンドではなく、Asuka が内部的に使うナレッジ。

## できること

- OneDrive 上のファイル・フォルダ一覧の取得
- キーワードによるファイル検索
- ファイルのダウンロード（ローカルに保存して内容確認）
- ファイルのメタデータ取得（サイズ、更新日時など）
- トークン切れ時の再認証

## 前提環境

- Windows PC のみ（`PC_PLATFORM=win`）
- ヘルパースクリプト: `C:/Users/ueda-/.claude/scripts/onedrive.py`
- Python: `X:/Python310/python.exe`（システム Python）
- onedrive-mcp バイナリ: `C:/Users/ueda-/.claude/onedrive-mcp-venv/Scripts/onedrive-mcp.exe`
- トークンキャッシュ: `C:/Users/ueda-/.config/onedrive-mcp/token_cache.json`

## コマンド一覧

すべて Bash ツールで実行する。Python パスは `"X:/Python310/python.exe"` を使用する。

### ファイル一覧（ルート）

```bash
"X:/Python310/python.exe" C:/Users/ueda-/.claude/scripts/onedrive.py list /
```

### ファイル一覧（フォルダ指定）

```bash
"X:/Python310/python.exe" C:/Users/ueda-/.claude/scripts/onedrive.py list "Documents/仕事"
```

### ファイル検索

```bash
"X:/Python310/python.exe" C:/Users/ueda-/.claude/scripts/onedrive.py search "請求書"
```

### ファイルダウンロード

```bash
"X:/Python310/python.exe" C:/Users/ueda-/.claude/scripts/onedrive.py download "Documents/見積書.xlsx" "C:/Users/ueda-/.claude/tmp"
```

- 第2引数（保存先ディレクトリ）を省略すると、onedrive-mcp のデフォルト保存先に保存される
- 明示的に `C:/Users/ueda-/.claude/tmp` を指定することを推奨する

### メタデータ取得

```bash
"X:/Python310/python.exe" C:/Users/ueda-/.claude/scripts/onedrive.py metadata "Documents/見積書.xlsx"
```

### トークン再認証

```bash
"X:/Python310/python.exe" C:/Users/ueda-/.claude/scripts/onedrive.py auth 2>&1
```

## ダウンロードしたファイルの内容確認

### Excel ファイル (.xlsx)

openpyxl を使って読む。システム Python に openpyxl がインストール済み。

```bash
"X:/Python310/python.exe" -c "
import openpyxl
wb = openpyxl.load_workbook('C:/Users/ueda-/.claude/tmp/ファイル名.xlsx', data_only=True)
for sheet in wb.sheetnames:
    ws = wb[sheet]
    print(f'=== {sheet} ===')
    for row in ws.iter_rows(values_only=True):
        print(row)
"
```

### CSV / テキストファイル

Read ツールでそのまま読める。

### PDF

Read ツールで読める（Claude Code は PDF 対応）。

## トークン切れ時の再認証手順

トークンが切れると以下のようなエラーが返る：

```
{"error": "No cached credentials..."}
```

この場合、以下の手順で対応する：

1. Bash で `auth` コマンドを実行する（`run_in_background: true` を指定し、Monitor で出力を監視する）

```bash
"X:/Python310/python.exe" C:/Users/ueda-/.claude/scripts/onedrive.py auth 2>&1
```

2. 出力にデバイスコード（例: `EXYZ67DV6`）と URL が表示される
3. シンヤさんに以下を伝える：

```
OneDrive のトークンが切れています。再認証が必要です。
https://login.microsoft.com/device にアクセスして、コード「XXXXXXXX」を入力してください。
```

4. シンヤさんが認証を完了すると、トークンが `token_cache.json` に保存される
5. 認証完了後、元の操作を再実行する

## 注意事項

- `onedrive-mcp auth`（バイナリ直接実行）は使わないこと。keyring への書き込みが Windows で失敗し、ファイル保存に問題がある。必ず `onedrive.py auth` を使う
- ダウンロードしたファイルは `~/.claude/tmp/` に保存し、用が済んだら削除を検討する
- OneDrive 上のファイルパスは大文字小文字を区別しない
- 大きなフォルダの一覧取得は時間がかかる場合がある。必要に応じて search を使う
- このスキルは Windows PC 専用。Mac では別途セットアップが必要
