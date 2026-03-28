@echo off
:: LINE WORKS Bot サーバー 自動起動スクリプト
:: PC起動時にタスクスケジューラから実行される

:: ネットワーク接続を待つ（起動直後は不安定なため）
timeout /t 30 /nobreak > nul

:: サーバー起動
"X:\Python310\python.exe" "C:\Users\ueda-\.claude\line-works-bot\scripts\server.py"
