# BSLottery - Registrar tareas en Windows Task Scheduler
# EJECUTAR COMO ADMINISTRADOR: clic derecho en PowerShell -> "Ejecutar como administrador"
# Luego: Set-ExecutionPolicy Bypass -Scope Process; & "C:\xampp\php\www\BSLotery\scripts\register-tasks-admin.ps1"

$ProjectPath = "C:\xampp\php\www\BSLotery"
$PhpExe      = "C:\xampp\php\php.exe"

# Crear carpeta de la tarea
schtasks /Create /F /TN "BSLottery\Scheduler" `
    /TR "`"$PhpExe`" `"$ProjectPath\artisan`" schedule:run" `
    /SC MINUTE /MO 1 `
    /RL HIGHEST `
    /RU SYSTEM

Write-Host "OK: BSLottery\Scheduler creado (cada 1 minuto)" -ForegroundColor Green

# Queue worker: al inicio del sistema, reinicio automatico
schtasks /Create /F /TN "BSLottery\QueueWorker" `
    /TR "`"$PhpExe`" `"$ProjectPath\artisan`" queue:work --sleep=3 --tries=3 --max-time=3600 --queue=default" `
    /SC ONSTART `
    /RL HIGHEST `
    /RU SYSTEM

Write-Host "OK: BSLottery\QueueWorker creado (al inicio de Windows)" -ForegroundColor Green

# Iniciar el worker ahora sin reiniciar
schtasks /Run /TN "BSLottery\QueueWorker"
Write-Host "OK: QueueWorker iniciado ahora" -ForegroundColor Green

Write-Host ""
Write-Host "Verificar tareas:" -ForegroundColor Yellow
Write-Host "  schtasks /Query /TN `"BSLottery\Scheduler`" /FO LIST" -ForegroundColor Cyan
Write-Host "  schtasks /Query /TN `"BSLottery\QueueWorker`" /FO LIST" -ForegroundColor Cyan
Write-Host ""
Write-Host "Ver logs:" -ForegroundColor Yellow
Write-Host "  Get-Content `"$ProjectPath\storage\logs\scheduler.log`" -Tail 20 -Wait" -ForegroundColor Cyan
Write-Host "  Get-Content `"$ProjectPath\storage\logs\queue.log`" -Tail 20 -Wait" -ForegroundColor Cyan
