---
name: クロスプラットフォーム対応の注意
description: スクリプト・スキル修正時はMac・Windows両対応を必ず確認する
type: feedback
---

シンヤさんはMacとWindows両方で同じ ~/.claude 環境を使用している。

スキルやスクリプトを修正する際は、必ずMac・Windows両方で動作することを確認すること。

## 鉄則

- **「Macで直したらWindowsで動かない」「Windowsで直したらMacで動かない」は絶対にNG**
- 修正は常にクロスプラットフォーム対応で行う
- やむを得ずOS別ファイルに分ける必要がある場合は、**必ずシンヤさんに報告し、合意を取ってからその運用に移行する**

## 具体的な注意点

- パス指定は `os.path.expanduser('~/.claude/...')` のようにOSに依存しない書き方を使う
- bash の `~` 展開は Mac では `/Users/...`、Windows（Git Bash）では `/c/Users/...` になるが、python3 が Windows パスとして認識できないケースがある
- Windows固有パス（`C:\Users\...`）をハードコードしない
- Mac固有の書き方（`/Users/...`）もハードコードしない
- **シェルスクリプト(.sh)内でPythonを呼ぶ場合**: Macでは `python3`、Windowsでは `python` が標準。Pythonスクリプト内では `sys.executable` を使う
  - .sh内の推奨パターン: `PYTHON=$(command -v python3 || command -v python)` を冒頭で定義し、以降 `$PYTHON` で呼び出す
  - Mac専用の.shスクリプト（hookなど）は `python3` 直接呼び出しで可。ただしファイル冒頭にコメント `# platform: mac-only` を付けて明示すること
- OS固有コマンド（`taskkill`=Win, `open -a`/`pbcopy`=Mac）を使う場合は対向環境での代替を確認する

## 背景
2026-03-16：Google Calendar CLIフォールバックのWindows修正時に指摘を受けた。OS別ファイル運用が必要な場合は報告・合意が必要との追加指示あり。
