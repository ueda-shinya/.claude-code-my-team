#!/bin/bash
# Claude Code セッションコストトラッカー Stop hook
# セッション終了時にトークン使用量・推定コストを JSONL に記録する
#
# 課金レート（2026-04-17 公式確認: https://docs.anthropic.com/en/docs/about-claude/pricing）:
#   - sonnet : input $3/MTok, output $15/MTok  (Sonnet 4.6 / 4.5)
#   - opus   : input $5/MTok, output $25/MTok  (Opus 4.7 / 4.6 / 4.5)
#   - haiku  : input $1/MTok, output $5/MTok   (Haiku 4.5)
# ※ デフォルト（model 不明時）は sonnet レートを使用

# stdin を読み取る
input=$(cat)

python3 - <<'PYEOF' "$input"
import sys
import json
import io
import os
from datetime import datetime, timezone

# Windows 環境での UTF-8 出力対応
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

COST_FILE = os.path.expanduser("~/.claude/tmp/claude-code-session-cost.jsonl")

def to_number(value):
    try:
        n = float(value)
        return n if n == n else 0  # NaN チェック
    except (TypeError, ValueError):
        return 0

def estimate_cost(model, input_tokens, output_tokens):
    """モデル名からレートを推定してコストを計算"""
    normalized = str(model or "").lower()
    if "haiku" in normalized:
        rate_in, rate_out = 1.0, 5.0   # Haiku 4.5: $1/MTok input, $5/MTok output
    elif "opus" in normalized:
        rate_in, rate_out = 5.0, 25.0  # Opus 4.7/4.6/4.5: $5/MTok input, $25/MTok output
    else:
        # sonnet をデフォルトとして使用（claude-sonnet-4-6 含む）
        rate_in, rate_out = 3.0, 15.0

    cost = (input_tokens / 1_000_000) * rate_in + (output_tokens / 1_000_000) * rate_out
    return round(cost * 1_000_000) / 1_000_000

def get_session_id(data):
    """セッションIDを複数の候補から取得"""
    candidates = [
        data.get("session_id"),
        os.environ.get("CLAUDE_SESSION_ID"),
        os.environ.get("ECC_SESSION_ID"),
    ]
    for c in candidates:
        if c:
            return str(c)
    return "unknown"

raw_input = sys.argv[1] if len(sys.argv) > 1 else ""

try:
    data = json.loads(raw_input.strip()) if raw_input.strip() else {}

    # usage フィールドの探索（複数パターンに対応）
    usage = data.get("usage") or data.get("token_usage") or {}
    input_tokens = int(to_number(
        usage.get("input_tokens") or
        usage.get("prompt_tokens") or
        data.get("input_tokens") or
        0
    ))
    output_tokens = int(to_number(
        usage.get("output_tokens") or
        usage.get("completion_tokens") or
        data.get("output_tokens") or
        0
    ))

    # model 情報の取得
    model = str(
        data.get("model") or
        (data.get("_cursor") or {}).get("model") or
        os.environ.get("CLAUDE_MODEL") or
        "unknown"
    )

    session_id = get_session_id(data)
    cost_usd = estimate_cost(model, input_tokens, output_tokens)

    row = {
        "timestamp": datetime.now(timezone.utc).isoformat(),
        "session_id": session_id,
        "model": model,
        "input_tokens": input_tokens,
        "output_tokens": output_tokens,
        "cost_usd": cost_usd,
    }

    # tmp ディレクトリ作成（存在しない場合）
    os.makedirs(os.path.dirname(COST_FILE), exist_ok=True)

    # JSONL に追記
    with open(COST_FILE, "a", encoding="utf-8") as f:
        f.write(json.dumps(row, ensure_ascii=False) + "\n")

except Exception:
    # エラーが起きても Claude Code を止めない（フェイルオープン）
    pass

sys.exit(0)
PYEOF

exit 0
