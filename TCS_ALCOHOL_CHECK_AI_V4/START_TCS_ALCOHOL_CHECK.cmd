@echo off
setlocal
cd /d "%~dp0"

where php >nul 2>nul
if errorlevel 1 (
  echo PHP was not found in PATH.
  echo Install PHP 8.x or add php.exe to PATH, then try again.
  pause
  exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
  "$portOpen = Get-NetTCPConnection -State Listen -LocalPort 8788 -ErrorAction SilentlyContinue; if (-not $portOpen) { Start-Process -FilePath 'php' -ArgumentList @('-S','0.0.0.0:8788') -WorkingDirectory '%~dp0' -WindowStyle Hidden; Start-Sleep -Seconds 2 }; Start-Process 'http://localhost:8788/'"

endlocal
