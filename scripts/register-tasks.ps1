# BSLottery - Registrar tareas en Windows Task Scheduler
# Ejecutar como Administrador: powershell -ExecutionPolicy Bypass -File register-tasks.ps1

$ProjectPath = "C:\xampp\php\www\BSLotery"
$PhpExe     = "C:\xampp\php\php.exe"

# --- Tarea 1: Laravel Scheduler (cada minuto) ---
$schedulerAction  = New-ScheduledTaskAction `
    -Execute $PhpExe `
    -Argument "$ProjectPath\artisan schedule:run >> $ProjectPath\storage\logs\scheduler.log 2>&1" `
    -WorkingDirectory $ProjectPath

$schedulerTrigger = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 1) -Once -At (Get-Date)
$schedulerSettings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit (New-TimeSpan -Minutes 1) -MultipleInstances IgnoreNew

Register-ScheduledTask `
    -TaskName    "BSLottery - Laravel Scheduler" `
    -TaskPath    "\BSLottery\" `
    -Action      $schedulerAction `
    -Trigger     $schedulerTrigger `
    -Settings    $schedulerSettings `
    -RunLevel    Highest `
    -Force

Write-Host "✓ Tarea 'BSLottery - Laravel Scheduler' registrada (cada 1 minuto)" -ForegroundColor Green

# --- Tarea 2: Queue Worker (al iniciar Windows, reinicia cada hora si falla) ---
$queueAction = New-ScheduledTaskAction `
    -Execute $PhpExe `
    -Argument "$ProjectPath\artisan queue:work --sleep=3 --tries=3 --max-time=3600 --queue=default >> $ProjectPath\storage\logs\queue.log 2>&1" `
    -WorkingDirectory $ProjectPath

$queueTrigger  = New-ScheduledTaskTrigger -AtStartup
$queueSettings = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit      (New-TimeSpan -Hours 0) `
    -RestartCount            99 `
    -RestartInterval         (New-TimeSpan -Minutes 1) `
    -MultipleInstances       IgnoreNew

Register-ScheduledTask `
    -TaskName    "BSLottery - Queue Worker" `
    -TaskPath    "\BSLottery\" `
    -Action      $queueAction `
    -Trigger     $queueTrigger `
    -Settings    $queueSettings `
    -RunLevel    Highest `
    -Force

Write-Host "✓ Tarea 'BSLottery - Queue Worker' registrada (inicio de Windows, reinicio automático)" -ForegroundColor Green

Write-Host ""
Write-Host "Para iniciar el worker ahora sin reiniciar:" -ForegroundColor Yellow
Write-Host "  Start-ScheduledTask -TaskPath '\BSLottery\' -TaskName 'BSLottery - Queue Worker'" -ForegroundColor Cyan
Write-Host ""
Write-Host "Para ver el log del scheduler:" -ForegroundColor Yellow
Write-Host "  Get-Content '$ProjectPath\storage\logs\scheduler.log' -Tail 20" -ForegroundColor Cyan
