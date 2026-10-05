@echo off
setlocal
pushd "%~dp0"
where php >nul 2>&1
if errorlevel 1 (
    echo PHP was not found. Install PHP 8.4.1+ and add it to PATH.
    echo Read docs\GROUPMATE_SETUP.md, then reopen the terminal.
    pause
    popd
    exit /b 1
)
php scripts\setup-local.php %*
set "setupExit=%errorlevel%"
if not "%setupExit%"=="0" pause
popd
exit /b %setupExit%
