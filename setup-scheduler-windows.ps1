# ============================================================
# BSLotery — Configuración del scheduler de Laravel en Windows
# Ejecutar COMO ADMINISTRADOR:
#   Click derecho → "Ejecutar con PowerShell" (como admin)
#   o desde PowerShell admin: .\setup-scheduler-windows.ps1
# ============================================================

$taskName   = "BSLotery Laravel Scheduler"
$batFile    = "C:\xampp\php\www\BSLotery\scheduler.bat"
$phpExe     = "C:\xampp\php\php.exe"
$projectDir = "C:\xampp\php\www\BSLotery"

# --- Validaciones previas ---
if (-not (Test-Path $phpExe)) {
    Write-Host "ERROR: No se encontro php.exe en $phpExe" -ForegroundColor Red
    Write-Host "Edita la variable `$phpExe en este script con la ruta correcta." -ForegroundColor Yellow
    pause; exit 1
}

if (-not (Test-Path $batFile)) {
    Write-Host "ERROR: No se encontro $batFile" -ForegroundColor Red
    pause; exit 1
}

# --- Verificar que el proyecto responde ---
$test = & $phpExe "$projectDir\artisan" "--version" 2>&1
Write-Host "Laravel: $test" -ForegroundColor Cyan

# --- Eliminar tarea existente si hay ---
Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction SilentlyContinue

# --- Crear acción ---
$action = New-ScheduledTaskAction -Execute $batFile

# --- Trigger: cada 1 minuto indefinidamente desde ahora ---
$trigger = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 1)

# --- Configuración ---
$settings = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit  (New-TimeSpan -Minutes 1) `
    -MultipleInstances   IgnoreNew `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable:$false `
    -Hidden

# --- Registrar ---
$task = Register-ScheduledTask `
    -TaskName   $taskName `
    -Action     $action `
    -Trigger    $trigger `
    -Settings   $settings `
    -RunLevel   Highest `
    -Description "Ejecuta php artisan schedule:run cada minuto para BSLotery (sorteos diarios, licencias, monitoreo)" `
    -Force

if ($task) {
    Write-Host ""
    Write-Host "Tarea registrada correctamente: '$taskName'" -ForegroundColor Green
    Write-Host "Se ejecutara cada minuto mientras Windows este encendido." -ForegroundColor Green
    Write-Host ""
    Write-Host "Comandos activos en el scheduler:" -ForegroundColor Cyan
    Write-Host "  00:01 diario  -> draws:generate-daily  (sorteos del dia)" -ForegroundColor White
    Write-Host "  cada 30 min   -> license:validate      (validacion de licencia)" -ForegroundColor White
    Write-Host "  cada 5 min    -> monitoring:scan-branches (monitoreo de sucursales)" -ForegroundColor White
    Write-Host ""
    Write-Host "Log en: $projectDir\storage\logs\scheduler.log" -ForegroundColor Yellow
} else {
    Write-Host "ERROR al registrar la tarea." -ForegroundColor Red
}

pause
