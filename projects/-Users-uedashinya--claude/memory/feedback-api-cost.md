---
name: APIコスト管理ポリシー
description: Claude APIを使用するスクリプトのコスト追跡・報告ルール（2026-03-28 合意）
type: feedback
---

## ルール

Claude API（Anthropic）を使用するスクリプト・機能を実装する際は、コスト管理を組み込む。

## 運用モード別の報告ルール

| モード | 報告タイミング |
|---|---|
| **テスト/テスト運用中** | 毎回実行ごとにコストを報告 |
| **通常運用中** | ログに記録のみ。閾値を超えた場合のみアラート報告 |

## 閾値超え時の報告内容

- 推定コスト（USD・JPY概算）
- 解析件数・トークン数
- 超えた要因（**確定 / 推測 のどちらかを明記**）

## 実装パターン（chatwork-sync.py を参照）

- `--test-mode` フラグ：テスト時に明示指定
- `COST_THRESHOLD_USD`：閾値定数（スクリプトごとに適切な値を設定。chatwork-sync は $0.05）
- コスト履歴：`~/.claude/tmp/api-cost-history.json` に追記
  - 項目：timestamp / script名 / cost_usd / input_tokens / output_tokens / analyzed_count
  - 保持件数：最新500件
- `append_cost_history()` 関数：アトミック書き込み（tempfile + os.replace）

## テストモードの切り替え

- CLI実行（手動テスト）：`--test-mode` を付与
- APScheduler（自動運用）：`--test-mode` なし → 通常運用モード（閾値超えのみ報告）

## アスカがテスト実行を指示するときのルール（2026-03-28）

アスカがテスト実行コマンドをシンヤさんに伝える際、または自分で実行する際は、
**`--test-mode` フラグを必ず付ける**こと。

```bash
# 例: chatwork-sync のテスト実行
"X:\Python310\python.exe" ~/.claude/scripts/chatwork-sync.py --test-mode --dry-run

# 通常テスト実行（書き込みあり）
"X:\Python310\python.exe" ~/.claude/scripts/chatwork-sync.py --test-mode
```

同様に、今後 `--test-mode` に相当するフラグを持つスクリプトをテスト実行するときも同じルールを適用する。

## 背景

2026-03-28、Chatwork連携のテスト中にAPIクレジットが大きく減った。
実行前後で残高比較できなかったため、コスト計測の仕組みを要求された。
「テストで仕方ない部分もあるが、コストの見える化と無駄の削減のために」実装。
