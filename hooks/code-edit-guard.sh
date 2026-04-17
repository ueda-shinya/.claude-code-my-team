#!/bin/bash
# code-edit-guard.sh — PreToolUse hook
# 現在 warn-only モード: サブエージェントもブロックされるため一時的に警告のみ（2026-04-17）
# 恒久的な解決策はサブエージェント識別機能を追加する再kaizenで対応予定
# 対象ツール: Edit, Write（Claude Code がツール実行前に呼び出す）
# 動作環境: Windows Git Bash

# stdin から JSON を受け取る（失敗してもブロックしない）
input=$(cat) || true

# tool_input.file_path を抽出
if command -v jq &>/dev/null; then
  # jq が使える場合はそちらを優先
  file_path=$(echo "$input" | jq -r '.tool_input.file_path // empty' 2>/dev/null) || true
else
  # jq 不使用 / grep + sed による簡易パース
  # ※ エスケープ済みクォートは非対応
  file_path=$(echo "$input" | grep -o '"file_path"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed 's/"file_path"[[:space:]]*:[[:space:]]*"//;s/"$//') || true
fi

# file_path が取得できなかった場合は許可（ブロックしない）
if [ -z "$file_path" ]; then
  exit 0
fi

# 拡張子を抽出（ドットが含まれない場合は拡張子なし → 許可）
case "$file_path" in
  *.*) ext=$(echo "$file_path" | sed 's/.*\.//' | tr '[:upper:]' '[:lower:]') ;;
  *)   exit 0 ;;
esac

# ブロック対象の拡張子リスト（配列形式）
block_extensions=(
  py js ts jsx tsx
  sh bat ps1
  css html php
  rb go rs java c cpp h
  xml
)

# 拡張子がブロックリストに含まれるか判定
for blocked in "${block_extensions[@]}"; do
  if [ "$ext" = "$blocked" ]; then
    # warn-only モード: サブエージェント識別ができないため、一時的に警告のみ
    # ブロックせず処理続行（exit 0）
    echo "[WARN] コードファイル編集: $file_path — アスカが直接編集している場合はシュウへの委任を検討してください" >&2
    exit 0
  fi
done

# ブロックリスト外 → 許可
exit 0
