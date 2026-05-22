# Build helper: compila el APK release (apunta al VPS https://bslottery.bsolutions.dev).
#
# Uso desde PowerShell:
#   cd C:\xampp\php\www\BSLotery\android
#   .\build-production-release.ps1
#
# Output:
#   android/app/build/outputs/apk/release/app-release.apk
#
# Si necesitas un APK debug (mas pesado, instalable directo sin firmar):
#   .\build-production-release.ps1 -Variant debug

param(
    [ValidateSet('release', 'debug')]
    [string]$Variant = 'release'
)

$ErrorActionPreference = 'Stop'
$variantCap = ($Variant.Substring(0, 1).ToUpper() + $Variant.Substring(1))
$task = "assemble$variantCap"

Write-Host "==> Compilando APK (variant: $Variant)" -ForegroundColor Cyan
Write-Host "    Tarea Gradle: $task"
Write-Host "    URL backend:  https://bslottery.bsolutions.dev"
Write-Host ""

& .\gradlew $task

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "BUILD FALLIDO. Revisa el log arriba." -ForegroundColor Red
    exit $LASTEXITCODE
}

$apkDir = "app\build\outputs\apk\$Variant"
$apk = Get-ChildItem -Path $apkDir -Filter '*.apk' -ErrorAction SilentlyContinue | Select-Object -First 1

if (-not $apk) {
    Write-Host "BUILD OK pero no encontre APK en $apkDir" -ForegroundColor Yellow
    exit 1
}

Write-Host ""
Write-Host "==> BUILD OK" -ForegroundColor Green
Write-Host "    APK:  $($apk.FullName)"
Write-Host "    Size: $([math]::Round($apk.Length / 1MB, 2)) MB"
Write-Host ""
Write-Host "Instala con:"
Write-Host "    adb install -r `"$($apk.FullName)`"" -ForegroundColor Cyan
