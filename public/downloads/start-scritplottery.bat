@echo off
setlocal
set "SCRIPT_DIR=%~dp0"
powershell.exe -NoExit -ExecutionPolicy Bypass -File "%SCRIPT_DIR%scritplottery.ps1" -Port 8765
echo.
echo Si PowerShell no quedo abierto, presiona una tecla para cerrar esta ventana...
pause >nul
endlocal
