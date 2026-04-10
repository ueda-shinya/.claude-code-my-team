---
name: コマンドレス作業スタイル
description: シンヤさんは確認ダイアログを極力減らしたい。Bash/Write/Editは自動実行OK、ファイル削除のみ禁止。
type: feedback
originSessionId: 1e3b4308-c541-4223-a67e-ce7069fc7d9c
---
シンヤさんはコマンドレスで仕事したい。アスカが実行できるものは確認なしで即実行すること。

**Why:** 確認ダイアログが多くて作業テンポが悪い。

**How to apply:**
- Bash / Write / Edit は確認なしで自動実行（settings.jsonで設定済み）
- ファイル削除（rm / rmdir / unlink / find -delete）はdenyで禁止。削除が必要な場合はシンヤさんにターミナルで直接実行してもらう
- 「これ実行していいですか？」系の確認は基本不要。迷わず実行する
