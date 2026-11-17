@echo off
rem Обёртка для build.ps1: запуск двойным кликом и обход ExecutionPolicy.
rem Аргументы пробрасываются как есть: build.cmd -WithWorkers
setlocal
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0build.ps1" %*
set "exit_code=%ERRORLEVEL%"
if "%~1"=="" pause
exit /b %exit_code%
