@echo off
:: LINE WORKS Bot サーバー 自動起動スクリプト
:: PC起動時にタスクスケジューラから実行される

:: ネットワーク接続を待つ（起動直後は不安定なため）
timeout /t 30 /nobreak > nul

:: サーバー起動
"C:\Users\ueda-\AppData\Local\Microsoft\WindowsApps\PythonSoftwareFoundation.Python.3.12_qbz5n2kfra8p0\python.exe" "C:\Users\ueda-\.claude\line-works-bot\scripts\server.py"
