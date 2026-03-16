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

## 背景
2026-03-16：Google Calendar CLIフォールバックのWindows修正時に指摘を受けた。OS別ファイル運用が必要な場合は報告・合意が必要との追加指示あり。
