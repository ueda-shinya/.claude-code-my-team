#!/bin/bash
# 品質チェックリマインダー Stop hook
# アスカが成果物を報告した際、品質チェック未実施であればリマインダーを返す

input=$(cat)

python - <<'PYEOF' "$input"
import sys
import json
import io

# Windows環境でのUTF-8出力対応
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

input_json = sys.argv[1] if len(sys.argv) > 1 else ""

try:
    data = json.loads(input_json)

    # 最後のassistantメッセージを取得
    transcript = data.get("transcript", [])
    last_assistant_text = ""
    for msg in reversed(transcript):
        if msg.get("role") == "assistant":
            content = msg.get("content", [])
            if isinstance(content, list):
                for block in content:
                    if isinstance(block, dict) and block.get("type") == "text":
                        last_assistant_text = block.get("text", "")
                        break
            elif isinstance(content, str):
                last_assistant_text = content
            break

    if not last_assistant_text:
        sys.exit(0)

    # 成果物報告シグナル
    delivery_signals = [
        "完了しました", "実装しました", "作成しました", "報告いたします",
        "完了いたします", "実装いたしました", "作成いたしました",
        "対応しました", "対応いたしました", "修正しました", "修正いたしました",
        "追加しました", "追加いたしました", "更新しました", "更新いたしました",
        "設定しました", "設定いたしました", "登録しました", "登録いたしました",
        "できました", "仕上がりました",
    ]

    # 品質チェック済みシグナル
    quality_signals = [
        "レビュー", "チェック済", "サクラ", "リナ", "ファクトチェック",
        "品質チェック", "確認済み", "レビュー結果", "承認",
        "コードレビュー", "論理チェック", "ロジックチェック",
        "review", "approved",
    ]

    has_delivery = any(sig in last_assistant_text for sig in delivery_signals)
    has_quality = any(sig in last_assistant_text for sig in quality_signals)

    if has_delivery and not has_quality:
        reminder = {
            "additionalContext": "【品質チェックリマインダー】この成果物は品質チェック（レビュー/ファクトチェック/論理チェック）を実施しましたか？未実施なら適切なレビュアーに依頼してください。"
        }
        print(json.dumps(reminder, ensure_ascii=False))

    sys.exit(0)

except Exception:
    # エラーが起きてもClaude Codeを止めない
    sys.exit(0)
PYEOF

exit 0
