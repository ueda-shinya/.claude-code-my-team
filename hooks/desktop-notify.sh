#!/bin/bash
# デスクトップ通知 Stop hook
# Claude Code がレスポンスを返した時に Windows トースト通知を発火する
#
# 対応環境:
#   - Windows (Git Bash): PowerShell BurntToast → WScript.Shell → msg コマンド の順でフォールバック
#   - Mac / その他: 何もしない（exit 0）
#
# PC_PLATFORM 判定: ~/.claude/.env の PC_PLATFORM=win|mac を参照

ENV_FILE="C:/Users/ueda-/.claude/.env"

# PC_PLATFORM を .env から読み取る
PC_PLATFORM=""
if [ -f "$ENV_FILE" ]; then
    PC_PLATFORM=$(grep -E "^PC_PLATFORM=" "$ENV_FILE" | head -1 | cut -d'=' -f2 | tr -d '[:space:]')
fi

# Windows 以外は何もしない
if [ "$PC_PLATFORM" != "win" ]; then
    cat  # stdin をそのまま流す
    exit 0
fi

# stdin を読み取る
input=$(cat)

python3 - <<'PYEOF' "$input"
import sys
import json
import io
import subprocess
import os

# Windows 環境での UTF-8 出力対応
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

MAX_BODY_LENGTH = 100
TITLE = "Claude Code タスク完了"

def extract_summary(data):
    """最後の assistant メッセージから要約テキストを抽出"""
    # last_assistant_message フィールドを優先（desktop-notify.js 参照）
    last_msg = data.get("last_assistant_message")
    if last_msg and isinstance(last_msg, str):
        return _truncate(last_msg)

    # transcript から最後の assistant メッセージを探す
    transcript = data.get("transcript", [])
    for msg in reversed(transcript):
        if msg.get("role") == "assistant":
            content = msg.get("content", [])
            if isinstance(content, list):
                for block in content:
                    if isinstance(block, dict) and block.get("type") == "text":
                        text = block.get("text", "")
                        if text.strip():
                            return _truncate(text)
            elif isinstance(content, str) and content.strip():
                return _truncate(content)

    # session_id の末尾 6 桁をフォールバックに使用
    session_id = data.get("session_id", "")
    suffix = str(session_id)[-6:] if session_id else "------"
    return f"セッション ...{suffix} 完了"

def _truncate(text):
    """最初の非空行を取得し、最大 MAX_BODY_LENGTH 文字に切り詰める"""
    first_line = ""
    for line in text.split("\n"):
        stripped = line.strip()
        if stripped:
            first_line = stripped
            break
    if not first_line:
        return "完了"
    if len(first_line) > MAX_BODY_LENGTH:
        return first_line[:MAX_BODY_LENGTH] + "..."
    return first_line

def notify_burnttoast(title, body):
    """BurntToast モジュールを使ってトースト通知を送信"""
    env = os.environ.copy()
    env["TOAST_TITLE"] = title
    env["TOAST_BODY"] = body
    cmd = "Import-Module BurntToast; New-BurntToastNotification -Text $env:TOAST_TITLE, $env:TOAST_BODY"
    result = subprocess.run(
        ["powershell.exe", "-NoProfile", "-NonInteractive", "-Command", cmd],
        capture_output=True, timeout=8, env=env
    )
    return result.returncode == 0

def notify_wscript(title, body):
    """WScript.Shell Popup でバルーン通知（BurntToast 非インストール時フォールバック）"""
    env = os.environ.copy()
    env["TOAST_TITLE"] = title
    env["TOAST_BODY"] = body
    ps_script = (
        '$wsh = New-Object -ComObject WScript.Shell; '
        '$wsh.Popup($env:TOAST_BODY, 5, $env:TOAST_TITLE, 64) | Out-Null'
    )
    result = subprocess.run(
        ["powershell.exe", "-NoProfile", "-NonInteractive", "-Command", ps_script],
        capture_output=True, timeout=10, env=env
    )
    return result.returncode == 0

def notify_toast_xml(title, body):
    """Windows.UI.Notifications を使ったトースト通知（フォールバック2）"""
    env = os.environ.copy()
    env["TOAST_TITLE"] = title
    env["TOAST_BODY"] = body
    ps_script = """
[Windows.UI.Notifications.ToastNotificationManager, Windows.UI.Notifications, ContentType = WindowsRuntime] | Out-Null
[Windows.Data.Xml.Dom.XmlDocument, Windows.Data.Xml.Dom.XmlDocument, ContentType = WindowsRuntime] | Out-Null
$template = [Windows.UI.Notifications.ToastNotificationManager]::GetTemplateContent([Windows.UI.Notifications.ToastTemplateType]::ToastText02)
$template.SelectSingleNode('//text[@id=1]').InnerText = $env:TOAST_TITLE
$template.SelectSingleNode('//text[@id=2]').InnerText = $env:TOAST_BODY
$notifier = [Windows.UI.Notifications.ToastNotificationManager]::CreateToastNotifier('Claude Code')
$notifier.Show([Windows.UI.Notifications.ToastNotification]::new($template))
"""
    result = subprocess.run(
        ["powershell.exe", "-NoProfile", "-NonInteractive", "-Command", ps_script],
        capture_output=True, timeout=10, env=env
    )
    return result.returncode == 0

raw_input = sys.argv[1] if len(sys.argv) > 1 else ""

try:
    data = json.loads(raw_input.strip()) if raw_input.strip() else {}
    summary = extract_summary(data)

    # BurntToast → Windows.UI.Notifications → WScript.Shell の順で試行
    notified = False
    for notify_fn in [notify_burnttoast, notify_toast_xml, notify_wscript]:
        try:
            if notify_fn(TITLE, summary):
                notified = True
                break
        except Exception:
            continue

    # すべて失敗してもエラーにはしない（Claude Code を止めない）

except Exception:
    pass

sys.exit(0)
PYEOF

exit 0
