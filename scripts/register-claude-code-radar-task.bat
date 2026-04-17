@echo off
setlocal enabledelayedexpansion

set TASK_NAME=claude-code-radar-daily
set XML_FILE=%USERPROFILE%\.claude\scripts\claude-code-radar-task.xml

echo [INFO] Registering task: %TASK_NAME%

:: Delete existing task if present
schtasks /query /tn "%TASK_NAME%" >nul 2>&1
if !ERRORLEVEL! equ 0 (
  echo [INFO] Removing existing task...
  schtasks /delete /tn "%TASK_NAME%" /f
  if !ERRORLEVEL! neq 0 (
    echo [ERROR] Failed to delete existing task
    exit /b 1
  )
)

:: Register from XML
schtasks /create /tn "%TASK_NAME%" /xml "%XML_FILE%"

if !ERRORLEVEL! equ 0 (
  echo [SUCCESS] Task registered successfully
  echo.
  echo ---- Task details ----
  schtasks /query /tn "%TASK_NAME%" /fo LIST
) else (
  echo [ERROR] Failed to register task (exit_code=!ERRORLEVEL!^)
  echo Hint: try running as Administrator if permission error
  exit /b 1
)

exit /b 0
