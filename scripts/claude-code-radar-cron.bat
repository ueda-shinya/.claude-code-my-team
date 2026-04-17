@echo off
setlocal enabledelayedexpansion

set CLAUDE_DIR=%USERPROFILE%\.claude
set LOG_FILE=%CLAUDE_DIR%\tmp\claude-code-radar-cron.log
set LOG_OLD=%CLAUDE_DIR%\tmp\claude-code-radar-cron.log.old
set ENV_FILE=%CLAUDE_DIR%\.env
set CLAUDE_EXE=%USERPROFILE%\.local\bin\claude.exe

cd /d "%CLAUDE_DIR%"

:: Ensure tmp directory exists
if not exist "%CLAUDE_DIR%\tmp" mkdir "%CLAUDE_DIR%\tmp"

:: Log rotation: archive if > 100KB
if exist "%LOG_FILE%" (
  for %%F in ("%LOG_FILE%") do set LOG_SIZE=%%~zF
  if !LOG_SIZE! gtr 102400 (
    move /y "%LOG_FILE%" "%LOG_OLD%" >nul 2>&1
  )
)

:: Load ANTHROPIC_API_KEY from .env
if not exist "%ENV_FILE%" (
  echo [ERROR] %DATE% %TIME% .env not found: %ENV_FILE% >> "%LOG_FILE%"
  exit /b 1
)

for /f "usebackq delims=" %%L in ("%ENV_FILE%") do (
  set "LINE=%%L"
  if not "!LINE:~0,1!"=="#" (
    for /f "tokens=1 delims==" %%K in ("%%L") do (
      if "%%K"=="ANTHROPIC_API_KEY" (
        set "ANTHROPIC_API_KEY=!LINE:*ANTHROPIC_API_KEY=!"
      )
    )
  )
)

if not defined ANTHROPIC_API_KEY (
  echo [ERROR] ANTHROPIC_API_KEY not found in .env >> "%LOG_FILE%"
  exit /b 1
)

:: Start log
echo. >> "%LOG_FILE%"
echo ============================================================ >> "%LOG_FILE%"
echo [START] %DATE% %TIME% >> "%LOG_FILE%"
echo ============================================================ >> "%LOG_FILE%"

:: Check claude CLI
if not exist "%CLAUDE_EXE%" (
  echo [ERROR] claude CLI not found: %CLAUDE_EXE% >> "%LOG_FILE%"
  exit /b 1
)

:: Run Claude Code Radar
:: --no-session-persistence: prevent session bloat
:: --max-budget-usd: cost cap
"%CLAUDE_EXE%" -p --dangerously-skip-permissions --max-budget-usd 1.00 --no-session-persistence "/claude-code-radar" >> "%LOG_FILE%" 2>&1

set EXIT_CODE=%ERRORLEVEL%

:: Clear API key from memory after execution
set "ANTHROPIC_API_KEY="

:: End log
echo [END] %DATE% %TIME% / exit_code=%EXIT_CODE% >> "%LOG_FILE%"

exit /b %EXIT_CODE%
