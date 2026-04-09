# OneDrive 接続・操作スキル

ユーザーが「OneDriveの○○を見て」「OneDriveから○○をダウンロードして」「OneDriveを検索して」など、OneDrive上のファイル操作を求めたときに参照する。スラッシュコマンドではなく、Asuka が内部的に使うナレッジ。

## できること

- OneDrive 上のファイル・フォルダ一覧の取得
- キーワードによるファイル検索
- ファイルのダウンロード（ローカルに保存して内容確認）
- ファイルのメタデータ取得（サイズ、更新日時など）
- トークン切れ時の再認証

## 前提環境

Windows/Mac 両対応。`PC_PLATFORM` で分岐する。

### Windows（PC_PLATFORM=win）

- ヘルパースクリプト: `~/.claude/scripts/onedrive.py`
- Python: `X:/Python310/python.exe`
- onedrive-mcp バイナリ: `~/.claude/onedrive-mcp-venv/Scripts/onedrive-mcp.exe`
- トークンキャッシュ: `~/.config/onedrive-mcp/token_cache.json`
- **注意**: keyring（Windows Credential Vault）への書き込みが失敗するため、必ず `onedrive.py auth` でファイルキャッシュに保存する

### Mac（PC_PLATFORM=mac）

- ヘルパースクリプト: `~/.claude/scripts/onedrive.py`
- Python: `python3` または `~/.claude/onedrive-mcp-venv/bin/python`
- onedrive-mcp バイナリ: `~/.claude/onedrive-mcp-venv/bin/onedrive-mcp`
- トークンキャッシュ: keyring（macOS Keychain）またはファイル `~/.config/onedrive-mcp/token_cache.json`
- **注意**: Mac は keyring が正常動作するため、`onedrive-mcp auth` でも `onedrive.py auth` でも可

## コマンド一覧

**Windows:**
```bash
"X:/Python310/python.exe" ~/.claude/scripts/onedrive.py <サブコマンド>
```

**Mac:**
```bash
python3 ~/.claude/scripts/onedrive.py <サブコマンド>
```

### ファイル一覧（ルート）
```bash
# Windows
"X:/Python310/python.exe" ~/.claude/scripts/onedrive.py list /
# Mac
python3 ~/.claude/scripts/onedrive.py list /
```

### ファイル一覧（フォルダ指定）
```bash
python onedrive.py list "Documents/仕事"
```

### ファイル検索
```bash
python onedrive.py search "請求書"
```

### ファイルダウンロード
```bash
python onedrive.py download "Documents/見積書.xlsx" "~/.claude/tmp"
```

- 保存先は `~/.claude/tmp/` を推奨

### メタデータ取得
```bash
python onedrive.py metadata "Documents/見積書.xlsx"
```

### トークン再認証
```bash
python onedrive.py auth 2>&1
```

## ダウンロードしたファイルの内容確認

### Excel ファイル (.xlsx)

**Windows:**
```bash
"X:/Python310/python.exe" -c "
import sys, io; sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
import openpyxl
wb = openpyxl.load_workbook('~/.claude/tmp/ファイル名.xlsx', data_only=True)
for sheet in wb.sheetnames:
    ws = wb[sheet]
    print(f'=== {sheet} ===')
    for row in ws.iter_rows(values_only=True):
        if any(v is not None for v in row): print(row)
"
```

**Mac:**
```bash
python3 -c "
import openpyxl
wb = openpyxl.load_workbook('~/.claude/tmp/ファイル名.xlsx', data_only=True)
for sheet in wb.sheetnames:
    ws = wb[sheet]
    print(f'=== {sheet} ===')
    for row in ws.iter_rows(values_only=True):
        if any(v is not None for v in row): print(row)
"
```

### CSV / テキストファイル

Read ツールでそのまま読める。

### PDF

Read ツールで読める（Claude Code は PDF 対応）。

## トークン切れ時の再認証手順

トークンが切れると以下のエラーが返る：
```
{"error": "No cached credentials..."}
```

### 手順

1. `auth` コマンドを `run_in_background: true` で実行し、Monitor で出力を監視する

```bash
# Windows
"X:/Python310/python.exe" ~/.claude/scripts/onedrive.py auth 2>&1
# Mac
python3 ~/.claude/scripts/onedrive.py auth 2>&1
```

2. デバイスコードが表示されたらシンヤさんに伝える：

```
OneDrive のトークンが切れています。再認証が必要です。
https://login.microsoft.com/device にアクセスして、コード「XXXXXXXX」を入力してください。
```

3. シンヤさんが認証を完了したら元の操作を再実行する

## 注意事項

- **Windows**: `onedrive-mcp auth`（バイナリ直接実行）は使わないこと。keyring 書き込み失敗後のファイル保存に問題がある。必ず `onedrive.py auth` を使う
- **Mac**: `onedrive-mcp auth` でも動作するが、`onedrive.py auth` の方が確実
- ダウンロードしたファイルは `~/.claude/tmp/` に保存し、用が済んだら削除を検討する
- OneDrive 上のファイルパスは大文字小文字を区別しない
- 大きなフォルダの一覧取得は時間がかかる場合がある。必要に応じて search を使う
- Windows で Excel 読み取り時は encoding 問題が発生するため、必ず `sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')` を先頭に入れる
