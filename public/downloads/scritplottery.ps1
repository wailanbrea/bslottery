param(
    [int]$Port = 8765,
    [string]$Token = '',
    [switch]$NoElevate
)

$ErrorActionPreference = 'Stop'
$EmbeddedToken = '__BSLOTTERY_AGENT_TOKEN__'

function Write-Step {
    param([string]$Message)
    Write-Host "[BSLottery] $Message"
}

function Wait-BeforeExit {
    Write-Host ''
    Write-Host 'Presiona una tecla para cerrar...'

    try {
        if ([Environment]::UserInteractive) {
            [void][System.Console]::ReadKey($true)
        } else {
            Start-Sleep -Seconds 10
        }
    } catch {
        Start-Sleep -Seconds 10
    }
}

function Test-IsAdministrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Ensure-Administrator {
    if (Test-IsAdministrator) {
        Write-Step 'Ejecutando como administrador.'
    } else {
        Write-Step 'Ejecutando como usuario normal.'
    }

    return $true
}

function Resolve-PhpBinary {
    $candidates = @(
        'C:\xampp\php\php.exe',
        'C:\laragon\bin\php\php-8.3.0-Win32-vs16-x64\php.exe',
        'C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe',
        'C:\wamp64\bin\php\php8.3.0\php.exe',
        'C:\wamp64\bin\php\php8.2.0\php.exe',
        $env:PHP_BINARY,
        'php.exe'
    ) | Where-Object { -not [string]::IsNullOrWhiteSpace($_) }

    foreach ($candidate in $candidates) {
        if ($candidate -eq 'php.exe') {
            $cmd = Get-Command php.exe -ErrorAction SilentlyContinue
            if ($cmd) {
                return $cmd.Source
            }
            continue
        }

        if (Test-Path $candidate) {
            return $candidate
        }
    }

    throw 'No se encontro php.exe. Instala XAMPP, Laragon, WAMP o agrega PHP al PATH.'
}

function Resolve-AgentToken {
    if (-not [string]::IsNullOrWhiteSpace($Token)) {
        return $Token
    }

    if (-not [string]::IsNullOrWhiteSpace($EmbeddedToken) -and $EmbeddedToken -ne '__BSLOTTERY_AGENT_TOKEN__') {
        return $EmbeddedToken
    }

    return 'CHANGE_ME_BEFORE_USE'
}

function Resolve-AgentWorkDir {
    $candidates = @(
        $env:LOCALAPPDATA,
        $(if ($env:USERPROFILE) { Join-Path $env:USERPROFILE 'AppData\Local' } else { $null }),
        $env:TEMP
    ) | Where-Object { -not [string]::IsNullOrWhiteSpace($_) }

    foreach ($base in $candidates) {
        try {
            if (-not (Test-Path $base)) {
                continue
            }

            $agentDir = Join-Path $base 'BSLotteryPrintAgent'
            if (-not (Test-Path $agentDir)) {
                New-Item -ItemType Directory -Path $agentDir -Force | Out-Null
            }

            return $agentDir
        } catch {
            continue
        }
    }

    throw 'No se pudo crear la carpeta de trabajo del agente en LocalAppData, UserProfile ni Temp.'
}

function Ensure-AgentRouter {
    $agentDir = Resolve-AgentWorkDir
    $routerPath = Join-Path $agentDir 'print-agent-router.php'
    $routerContent = @'
<?php

declare(strict_types=1);

$token = getenv('BSLOTTERY_PRINT_AGENT_TOKEN') ?: 'CHANGE_ME_BEFORE_USE';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function isAuthorized(string $expectedToken): bool
{
    if ($expectedToken === '') {
        return true;
    }

    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    return hash_equals('Bearer '.$expectedToken, $header);
}

function payload(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

function runPowerShell(string $command): string
{
    $full = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -Command '.escapeshellarg($command).' 2>&1';
    $output = shell_exec($full);

    return is_string($output) ? trim($output) : '';
}

function listPrintersResult(): array
{
    $commands = [
        <<<'PS'
$printers = Get-Printer | Sort-Object Name | Select-Object @{Name='name';Expression={$_.Name}}, @{Name='is_default';Expression={[bool]$_.Default}}, @{Name='shared';Expression={[bool]$_.Shared}}, @{Name='driver_name';Expression={$_.DriverName}}, @{Name='port_name';Expression={$_.PortName}}
$printers | ConvertTo-Json -Depth 4
PS,
        <<<'PS'
$printers = Get-CimInstance Win32_Printer | Sort-Object Name | Select-Object @{Name='name';Expression={$_.Name}}, @{Name='is_default';Expression={[bool]$_.Default}}, @{Name='shared';Expression={[bool]$_.Shared}}, @{Name='driver_name';Expression={$_.DriverName}}, @{Name='port_name';Expression={$_.PortName}}
$printers | ConvertTo-Json -Depth 4
PS,
        <<<'PS'
$printers = Get-WmiObject Win32_Printer | Sort-Object Name | Select-Object @{Name='name';Expression={$_.Name}}, @{Name='is_default';Expression={[bool]$_.Default}}, @{Name='shared';Expression={[bool]$_.Shared}}, @{Name='driver_name';Expression={$_.DriverName}}, @{Name='port_name';Expression={$_.PortName}}
$printers | ConvertTo-Json -Depth 4
PS,
    ];

    $errors = [];

    foreach ($commands as $command) {
        $output = runPowerShell($command);
        $decoded = json_decode($output, true);

        if (is_array($decoded)) {
            $printers = array_is_list($decoded) ? $decoded : [$decoded];

            return [
                'printers' => $printers,
                'error' => null,
            ];
        }

        if ($output !== '') {
            $errors[] = $output;
        }
    }

    return [
        'printers' => [],
        'error' => $errors !== [] ? $errors[0] : 'No se pudieron enumerar impresoras en Windows.',
    ];
}

function printText(string $printerName, string $content): array
{
    $tempFile = tempnam(sys_get_temp_dir(), 'bslottery-print-');
    if ($tempFile === false) {
        throw new RuntimeException('No se pudo crear archivo temporal.');
    }

    file_put_contents($tempFile, $content);

    $resolvedName = str_replace("'", "''", $printerName);
    $resolvedPath = str_replace("'", "''", $tempFile);

    if ($resolvedName === '') {
        $command = <<<'PS'
$default = Get-CimInstance Win32_Printer | Where-Object { $_.Default } | Select-Object -First 1
if ($null -eq $default) { throw 'No hay impresora predeterminada configurada en Windows.' }
$content = Get-Content -LiteralPath '%s'
$content | Out-Printer -Name $default.Name
$default.Name
PS;
        $command = sprintf($command, $resolvedPath);
    } else {
        $command = <<<'PS'
$content = Get-Content -LiteralPath '%s'
$content | Out-Printer -Name '%s'
'%s'
PS;
        $command = sprintf($command, $resolvedPath, $resolvedName, $resolvedName);
    }

    $output = runPowerShell($command);
    @unlink($tempFile);

    if ($output === '') {
        return ['printer' => $printerName];
    }

    if (stripos($output, 'Exception') !== false || stripos($output, 'Error') !== false) {
        throw new RuntimeException($output);
    }

    return ['printer' => trim($output)];
}

if ($path === '/api/status') {
    jsonResponse([
        'success' => true,
        'version' => '1.0.0',
        'os' => PHP_OS_FAMILY,
        'server' => 'php',
    ]);
}

if (in_array($path, ['/api/printers', '/api/test', '/api/print'], true) && ! isAuthorized($token)) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

try {
    if ($path === '/api/printers') {
        $result = listPrintersResult();

        jsonResponse([
            'success' => true,
            'printers' => $result['printers'],
            'error' => $result['error'],
        ]);
    }

    if ($path === '/api/test') {
        $data = payload();
        $printerName = (string) ($data['printer_name'] ?? '');
        $paperWidth = (string) ($data['paper_width'] ?? '80MM');
        $content = "BSLottery - Prueba de impresion\r\n"
            ."----------------------------------------\r\n"
            .'Fecha: '.date('Y-m-d H:i:s')."\r\n"
            .'Impresora: '.($printerName !== '' ? $printerName : '[Predeterminada]')."\r\n"
            .'Papel: '.$paperWidth."\r\n"
            ."----------------------------------------\r\n\r\n\r\n";

        $result = printText($printerName, $content);

        jsonResponse([
            'success' => true,
            'printer' => $result['printer'] ?? $printerName,
        ]);
    }

    if ($path === '/api/print') {
        $data = payload();
        $printerName = (string) ($data['printer_name'] ?? '');
        $content = (string) ($data['content'] ?? '');

        if (trim($content) === '') {
            jsonResponse(['success' => false, 'error' => 'Contenido vacio.'], 422);
        }

        $result = printText($printerName, $content);

        jsonResponse([
            'success' => true,
            'printer' => $result['printer'] ?? $printerName,
            'job_uuid' => (string) ($data['job_uuid'] ?? ''),
        ]);
    }

    jsonResponse(['success' => false, 'error' => 'Not found'], 404);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}
'@

    Set-Content -Path $routerPath -Value $routerContent -Encoding UTF8
    return $routerPath
}

function Get-AgentStatus {
    try {
        return Invoke-RestMethod -Uri ("http://127.0.0.1:{0}/api/status" -f $Port) -Method Get -TimeoutSec 2
    } catch {
        return $null
    }
}

function Get-AgentPrinters {
    param([string]$ResolvedToken)

    $headers = @{}
    if (-not [string]::IsNullOrWhiteSpace($ResolvedToken)) {
        $headers['Authorization'] = 'Bearer ' + $ResolvedToken
    }

    return Invoke-RestMethod -Uri ("http://127.0.0.1:{0}/api/printers" -f $Port) -Method Get -Headers $headers -TimeoutSec 4
}

function Stop-AgentOnPort {
    try {
        $connections = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction Stop
        $pids = $connections | Select-Object -ExpandProperty OwningProcess -Unique
        foreach ($pid in $pids) {
            $proc = Get-Process -Id $pid -ErrorAction SilentlyContinue
            if ($proc -and $proc.ProcessName -in @('php', 'cmd', 'powershell')) {
                Write-Step ("Deteniendo proceso anterior en puerto {0}: PID {1} ({2})" -f $Port, $pid, $proc.ProcessName)
                Stop-Process -Id $pid -Force -ErrorAction SilentlyContinue
            }
        }
    } catch {
    }
}

try {
    Ensure-Administrator | Out-Null

    $phpBin = Resolve-PhpBinary
    $router = Ensure-AgentRouter
    $resolvedToken = Resolve-AgentToken
    $logPath = Join-Path $env:TEMP 'bslottery-print-agent.log'

    Write-Host ''
    Write-Step 'Diagnostico del launcher'
    Write-Step ('Administrador: ' + ($(if (Test-IsAdministrator) { 'SI' } else { 'NO' })))
    Write-Step ('PHP: ' + $phpBin)
    Write-Step ('Router: ' + $router)
    Write-Step ('Puerto: ' + $Port)
    Write-Step ('Token: ' + $(if ([string]::IsNullOrWhiteSpace($resolvedToken)) { '[vacio]' } else { '[configurado]' }))

    $status = Get-AgentStatus
    if ($status -eq $null) {
        Stop-AgentOnPort

        $tokenEscaped = $resolvedToken.Replace('"', '""')
        $routerEscaped = $router.Replace('"', '""')
        $phpEscaped = $phpBin.Replace('"', '""')
        $logEscaped = $logPath.Replace('"', '""')
        $command = 'set "BSLOTTERY_PRINT_AGENT_TOKEN=' + $tokenEscaped + '" && "' + $phpEscaped + '" -S 127.0.0.1:' + $Port + ' "' + $routerEscaped + '" >> "' + $logEscaped + '" 2>&1'

        Write-Step 'Iniciando Print Agent...'
        Start-Process -FilePath 'cmd.exe' -WindowStyle Hidden -ArgumentList '/c', $command | Out-Null

        for ($attempt = 0; $attempt -lt 8; $attempt++) {
            Start-Sleep -Milliseconds 600
            $status = Get-AgentStatus
            if ($status -ne $null) {
                break
            }
        }
    }

    if ($status -eq $null) {
        throw 'No se pudo levantar el Print Agent. Revisa el log en %TEMP%\bslottery-print-agent.log'
    }

    Write-Host ''
    $statusVersion = if ($null -ne $status.version -and "$($status.version)".Trim() -ne '') { $status.version } else { '?' }
    Write-Step ('Agent activo: version ' + $statusVersion + ' en Windows')

    $printers = Get-AgentPrinters -ResolvedToken $resolvedToken

    Write-Host ''
    if ($printers.error) {
        throw ('El agent inicio, pero Windows bloqueo o fallo la enumeracion de impresoras: ' + $printers.error)
    }

    $printerList = @($printers.printers)
    if ($printerList.Count -eq 0) {
        throw 'El agent inicio correctamente, pero no encontro impresoras instaladas en Windows.'
    }

    Write-Step ('Impresoras detectadas: ' + $printerList.Count)
    foreach ($printer in $printerList) {
        $suffix = if ($printer.is_default) { ' [predeterminada]' } else { '' }
        Write-Host (' - ' + $printer.name + $suffix)
    }

    Write-Host ''
    Write-Step 'Launcher completado. El agent quedo funcionando y listo para usarse desde la web.'
} catch {
    Write-Host ''
    Write-Step 'Error durante el launcher'
    Write-Step $_.Exception.Message
} finally {
    Wait-BeforeExit
}
