@echo off
setlocal
powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\connect-govern.ps1" %*
exit /b %errorlevel%
