#!/bin/bash
# morning-trigger.sh
# UserPromptSubmit hook: 「おはよ」系の挨拶のみの入力を検知し、
# ブリーフィングスキル実行のコンテキストを追加する。

set -o pipefail

# stdin から JSON を読み取る（エラーは無視）
INPUT=$(cat 2>/dev/null) || exit 0

# デバッグログ（問題解決後に削除）
LOG_FILE="/tmp/morning-trigger-debug.log"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] --- hook 起動 ---" >> "$LOG_FILE"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] INPUT: $INPUT" >> "$LOG_FILE"

# transcript の最新ユーザーメッセージを取得
# Claude Code hooks の UserPromptSubmit では、
# prompt フィールドにユーザー入力が入る
MESSAGE=$(echo "$INPUT" | python3 -c "
import sys, json
try:
    data = json.load(sys.stdin)
    # UserPromptSubmit hook の場合、prompt フィールドにユーザー入力がある
    prompt = data.get('prompt', '')
    if not prompt:
        # transcript から最後のユーザーメッセージを取得
        transcript = data.get('transcript', [])
        for msg in reversed(transcript):
            if msg.get('role') == 'user':
                content = msg.get('content', '')
                if isinstance(content, list):
                    for part in content:
                        if isinstance(part, dict) and part.get('type') == 'text':
                            prompt = part.get('text', '')
                            break
                else:
                    prompt = str(content)
                break
    print(prompt.strip())
except:
    pass
" 2>/dev/null) || exit 0

echo "[$(date '+%Y-%m-%d %H:%M:%S')] MESSAGE: $MESSAGE" >> "$LOG_FILE"

# 空の場合は何もしない
[ -z "$MESSAGE" ] && exit 0

# 「おはよ」系の挨拶のみかどうかを判定
# マッチ条件: おはよ / おはよう / おはようございます 等
# 末尾に句読点・感嘆符等があってもOK
# 追加テキストがある場合はマッチしない
IS_GREETING=$(echo "$MESSAGE" | python3 -c "
import sys, re
msg = sys.stdin.read().strip()
# 挨拶パターン: 「おはよ」で始まり、追加の指示がないもの
# 許容する末尾: 。！!〜～ー、句読点、絵文字等
pattern = r'^おはよ(う(ございます)?)?[。！!\~〜ー♪]*$'
if re.match(pattern, msg):
    print('yes')
else:
    print('no')
" 2>/dev/null) || exit 0

echo "[$(date '+%Y-%m-%d %H:%M:%S')] IS_GREETING: $IS_GREETING" >> "$LOG_FILE"

if [ "$IS_GREETING" = "yes" ]; then
    python3 -c "
import json
print(json.dumps({
    'additionalContext': '/morning-briefing スキルを実行してください。ユーザーの挨拶への返答はブリーフィング完了後に行うこと。'
}))
"
fi

exit 0
