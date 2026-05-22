# Instalacion de BSLotery en VPS — Windows Server + XAMPP

Guia paso a paso para que cualquier IA o desarrollador pueda desplegar BSLotery en un VPS con **Windows Server + XAMPP (Apache + PHP + MySQL/MariaDB)**.

> **Audiencia:** esta guia esta escrita para que una IA (ej. Claude Code) o un sysadmin sin contexto previo pueda hacer la instalacion completa siguiendo cada paso al pie de la letra. Cada comando incluye que hace y donde se ejecuta.

> **Stack target:** Windows Server 2019/2022 + XAMPP (Apache 2.4 + PHP 8.2+ + MariaDB) + Git para Windows + Composer + Node.js LTS.

---

## 0. Resumen del despliegue

```
[Cliente web]  ─HTTPS─>  [Apache (XAMPP)] ─FastCGI─> [PHP-CGI]
                                                          │
                                                          ▼
                                                   [MySQL/MariaDB]
                                                          │
                                                          ▼
                                                   [storage/]
[Schedulers de Windows] ──> php artisan schedule:run (cada minuto)
[NSSM servicio]         ──> php artisan queue:work
[App Android]           ──> /api/mobile/* (HTTPS)
```

Componentes que se levantan:
1. Apache + MySQL (servicios XAMPP)
2. Laravel scheduler (Task Scheduler de Windows, cada minuto)
3. Queue worker (servicio Windows via NSSM)
4. Backups automaticos (parte del scheduler de Laravel)

---

## 1. Pre-requisitos del VPS

### 1.1 Sistema operativo

- Windows Server 2019 Standard o superior (Datacenter tambien funciona).
- Cuenta administrador local.
- Conexion a Internet estable.

### 1.2 Software a instalar

| Herramienta | Version minima | Descarga |
|---|---|---|
| XAMPP para Windows | 8.2.x | https://www.apachefriends.org/download.html |
| Git para Windows | 2.45+ | https://git-scm.com/download/win |
| Composer | 2.7+ | https://getcomposer.org/Composer-Setup.exe |
| Node.js LTS | 20.x | https://nodejs.org/en/download |
| NSSM (servicios Windows) | 2.24 | https://nssm.cc/download |
| Visual C++ Redistributable | 2015-2022 | https://aka.ms/vs/17/release/vc_redist.x64.exe |

> **Nota seguridad:** abrir puertos 80 y 443 en el firewall de Windows. No abrir el puerto MySQL (3306) al exterior — debe quedar accesible solo desde `localhost`.

### 1.3 Crear estructura de carpetas

Abre **PowerShell como Administrador** y ejecuta:

```powershell
New-Item -ItemType Directory -Force -Path 'C:\xampp\php\www\BSLotery'
New-Item -ItemType Directory -Force -Path 'C:\backups\bslottery'
New-Item -ItemType Directory -Force -Path 'C:\logs\bslottery'
```

---

## 2. Instalacion de XAMPP

1. Descarga el instalador desde el link de la tabla anterior.
2. Ejecuta como Administrador. Instala en `C:\xampp`.
3. Componentes que SI necesitas: **Apache**, **MySQL**, **PHP**.
4. Componentes que NO necesitas: phpMyAdmin (opcional), Mercury, Tomcat, Perl, Filezilla. (Si los instalas, no es problema.)
5. Despues de instalar:
   - Abre el **XAMPP Control Panel**.
   - Click en **Config** (Apache) -> **Service settings** -> marca "Apache" y "MySQL" para que se inicien como servicios automaticamente.
   - Click en **Start** para Apache y MySQL.

### 2.1 Habilitar extensiones PHP requeridas

Edita `C:\xampp\php\php.ini`. Verifica que estas lineas NO tengan `;` al inicio:

```ini
extension=bcmath
extension=curl
extension=fileinfo
extension=gd
extension=intl
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=pdo_sqlite
extension=sodium
extension=zip
```

Tambien ajusta limites:

```ini
memory_limit = 512M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
date.timezone = America/Santo_Domingo
```

Reinicia Apache desde el XAMPP Control Panel.

### 2.2 Agregar PHP al PATH de Windows

Tener `php` disponible desde cualquier terminal evita rutas absolutas.

```powershell
[Environment]::SetEnvironmentVariable('Path', $env:Path + ';C:\xampp\php', [EnvironmentVariableTarget]::Machine)
```

**Cierra y reabre PowerShell** para que tome el nuevo PATH.

Verifica:

```powershell
php --version
```

Debe mostrar PHP 8.2 o superior.

---

## 3. Instalacion de Git y Composer

### 3.1 Git

Instala con valores por defecto. Importante: durante la instalacion elige "Git from the command line and also from 3rd-party software".

```powershell
git --version
```

### 3.2 Composer

Descarga `Composer-Setup.exe` y ejecuta. Detectara PHP en `C:\xampp\php\php.exe`. Acepta los valores por defecto.

```powershell
composer --version
```

### 3.3 Node.js

Instala el LTS. Verifica:

```powershell
node --version  # debe ser v20.x o superior
npm --version
```

---

## 4. Clonar el repositorio

```powershell
cd C:\xampp\php\www\
git clone https://github.com/wailanbrea/bslottery.git BSLotery
cd BSLotery
```

> **Tip:** Si el repo es privado, configura un Personal Access Token en GitHub y usa `https://USER:TOKEN@github.com/wailanbrea/bslottery.git`. Para repos publicos no requiere autenticacion.

---

## 5. Configuracion de la aplicacion

### 5.1 Copiar `.env` y generar app key

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

### 5.2 Editar `.env`

Abre `C:\xampp\php\www\BSLotery\.env` con cualquier editor (Notepad++, VS Code, notepad) y ajusta:

```ini
APP_NAME=BSLottery
APP_ENV=production
APP_KEY=base64:<el-que-genero-artisan>
APP_DEBUG=false
APP_URL=https://tu-dominio.com
APP_TIMEZONE=America/Santo_Domingo
APP_LOCALE=es

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bslottery
DB_USERNAME=bslottery_user
DB_PASSWORD=<una-clave-fuerte-aqui>

SESSION_DRIVER=database
SESSION_LIFETIME=480
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=<tu-smtp>
MAIL_PORT=587
MAIL_USERNAME=<usuario-smtp>
MAIL_PASSWORD=<password-smtp>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@tu-dominio.com"
MAIL_FROM_NAME="${APP_NAME}"
```

> **Importante:** `APP_DEBUG=false` y `APP_ENV=production` en produccion. Nunca al reves — `APP_DEBUG=true` filtra rutas, env vars y stack traces a cualquier visitante.

### 5.3 Crear base de datos y usuario

Desde el XAMPP Control Panel, abre **Shell** y ejecuta:

```bash
mysql -u root
```

Dentro del prompt MySQL:

```sql
CREATE DATABASE bslottery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bslottery_user'@'localhost' IDENTIFIED BY 'una-clave-fuerte-aqui';
GRANT ALL PRIVILEGES ON bslottery.* TO 'bslottery_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**ASEGURATE de usar la misma clave** que pusiste en `DB_PASSWORD` del `.env`.

> **Hardening:** asignale una clave fuerte a `root` MySQL tambien (`mysqladmin -u root password 'OTRA-CLAVE-FUERTE'`) y actualiza `C:\xampp\phpMyAdmin\config.inc.php` si usas phpMyAdmin. Por defecto XAMPP deja `root` sin clave, lo cual es inaceptable en produccion.

### 5.4 Instalar dependencias y migrar

Desde PowerShell en `C:\xampp\php\www\BSLotery`:

```powershell
composer install --no-dev --optimize-autoloader --no-interaction
npm install
npm run build
php artisan migrate --seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> **Por que `--force`:** `migrate` pregunta "estas en produccion?" y aborta si no le pasas `--force`. En despliegues automatizados se acepta el riesgo a sabiendas.

### 5.5 Permisos de carpetas

En Windows los permisos son menos estrictos que en Linux pero IIS/Apache debe poder escribir en `storage/` y `bootstrap/cache/`. Si Apache corre como `LocalSystem` (default XAMPP), ya tiene acceso. Si lo cambias a otro usuario:

```powershell
icacls "C:\xampp\php\www\BSLotery\storage" /grant 'NT AUTHORITY\LocalSystem:(OI)(CI)F' /T
icacls "C:\xampp\php\www\BSLotery\bootstrap\cache" /grant 'NT AUTHORITY\LocalSystem:(OI)(CI)F' /T
```

---

## 6. Configurar Apache (vhost + HTTPS)

### 6.1 Habilitar virtual hosts

Edita `C:\xampp\apache\conf\httpd.conf`. Verifica que estas lineas NO tengan `#` al inicio:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule ssl_module modules/mod_ssl.so
LoadModule socache_shmcb_module modules/mod_socache_shmcb.so
Include conf/extra/httpd-vhosts.conf
Include conf/extra/httpd-ssl.conf
```

### 6.2 Crear vhost de BSLotery

Edita `C:\xampp\apache\conf\extra\httpd-vhosts.conf` y agrega:

```apache
<VirtualHost *:80>
    ServerName tu-dominio.com
    ServerAlias www.tu-dominio.com
    DocumentRoot "C:/xampp/php/www/BSLotery/public"

    <Directory "C:/xampp/php/www/BSLotery/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Redirigir todo a HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    ErrorLog "C:/logs/bslottery/apache-error.log"
    CustomLog "C:/logs/bslottery/apache-access.log" combined
</VirtualHost>

<VirtualHost *:443>
    ServerName tu-dominio.com
    DocumentRoot "C:/xampp/php/www/BSLotery/public"

    SSLEngine on
    SSLCertificateFile "C:/xampp/apache/conf/ssl.crt/tu-dominio.crt"
    SSLCertificateKeyFile "C:/xampp/apache/conf/ssl.key/tu-dominio.key"
    # Si tu CA da chain:
    # SSLCertificateChainFile "C:/xampp/apache/conf/ssl.crt/chain.crt"

    <Directory "C:/xampp/php/www/BSLotery/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Cabeceras de seguridad
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"

    ErrorLog "C:/logs/bslottery/apache-ssl-error.log"
    CustomLog "C:/logs/bslottery/apache-ssl-access.log" combined
</VirtualHost>
```

Reemplaza `tu-dominio.com` por el dominio real.

### 6.3 Certificado SSL

**Opcion A — Let's Encrypt (gratis, requiere validacion de dominio):**

1. Instala win-acme: https://www.win-acme.com/
2. Ejecuta `wacs.exe` y elige "Create renewal" -> "Manual input" -> ingresa tu dominio.
3. Elige validacion "Self-hosting" (requiere puerto 80 abierto al mundo).
4. Output: copia el `.crt` y `.key` a `C:\xampp\apache\conf\ssl.crt\` y `ssl.key\`.

**Opcion B — Certificado comercial:** sigue las instrucciones de tu CA, coloca los archivos en las mismas rutas.

**Opcion C — Self-signed (solo dev/staging):**

```powershell
cd C:\xampp\apache\conf
& "C:\xampp\apache\bin\openssl.exe" req -new -newkey rsa:2048 -days 365 -nodes -x509 `
    -subj "/C=DO/ST=DN/L=SantoDomingo/O=BSLottery/CN=tu-dominio.com" `
    -keyout ssl.key\tu-dominio.key -out ssl.crt\tu-dominio.crt
```

### 6.4 Reiniciar Apache

```powershell
Restart-Service Apache2.4
```

(O usa el XAMPP Control Panel.)

Verifica abriendo `https://tu-dominio.com` en un navegador. Debe mostrar la pantalla de login de BSLotery.

---

## 7. Configurar el scheduler de Laravel (Task Scheduler de Windows)

Laravel define el scheduler en `routes/console.php`. Necesita que `php artisan schedule:run` se ejecute **cada minuto**. En Linux es cron; en Windows es Task Scheduler.

### 7.1 Crear la tarea programada

Abre PowerShell **como Administrador** y ejecuta:

```powershell
$action = New-ScheduledTaskAction `
    -Execute 'C:\xampp\php\php.exe' `
    -Argument 'artisan schedule:run' `
    -WorkingDirectory 'C:\xampp\php\www\BSLotery'

$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 1) `
    -RepetitionDuration ([TimeSpan]::MaxValue)

$principal = New-ScheduledTaskPrincipal `
    -UserId 'NT AUTHORITY\SYSTEM' `
    -LogonType ServiceAccount `
    -RunLevel Highest

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5)

Register-ScheduledTask -TaskName 'BSLotery_Scheduler' `
    -Action $action -Trigger $trigger -Principal $principal -Settings $settings `
    -Description 'Ejecuta el scheduler de Laravel cada minuto.'
```

### 7.2 Verificar

```powershell
Get-ScheduledTask -TaskName 'BSLotery_Scheduler' | Format-List
Start-ScheduledTask -TaskName 'BSLotery_Scheduler'
```

Espera 1 minuto y revisa el log:

```powershell
Get-Content C:\xampp\php\www\BSLotery\storage\logs\laravel.log -Tail 30
```

Tambien:

```powershell
Get-Content C:\xampp\php\www\BSLotery\storage\logs\draws-auto-close.log -Tail 10
```

### 7.3 Que hace el scheduler de BSLotery

Definido en `routes/console.php`:

| Comando | Frecuencia | Proposito |
|---|---|---|
| `license:validate` | cada 30 min | Valida que la licencia siga activa con el server de licencias. |
| `monitoring:scan-branches` | cada 5 min | Detecta sucursales inactivas, caja sin abrir, etc. |
| `draws:generate-daily` | diario 00:01 | **Crea los sorteos del dia** para todas las empresas activas, segun su TZ. |
| `draws:auto-close` | cada minuto | **Cierra automaticamente** los sorteos cuyo `close_time` ya paso. Politica KEEP_CURRENT (mantiene tickets activos esperando resultado). |
| `limits:purge --days=90` | semanal (dom 03:30) | Borra `LimitConsumption` historico de draws cerrados >90 dias. |
| `backup:run --only-files` | diario 02:00 | Backup de archivos. |
| `backup:clean` | diario 02:30 | Limpia backups viejos. |
| `queue:prune-failed --hours=168` | semanal (dom 03:00) | Limpia jobs fallidos de mas de 7 dias. |

### 7.4 Reset diario de limites — como funciona

> **Ejemplo del usuario:** "Si juegan 2000 al 8 en la Nacional Tarde hoy, mañana debe volver a tener 3000 disponibles."

**Como funciona el sistema actual:**

1. `draws:generate-daily` corre a las 00:01 cada dia y crea un NUEVO `draw_id` para cada loteria (ej. `Nacional Tarde 2026-05-22`).
2. La tabla `limit_consumptions` esta atada a `draw_id`. El consumo "8 vendido 2000" pertenece al draw del 21, NO al del 22.
3. Cuando un cajero intenta jugar 12 al numero 8 el dia 22, el sistema busca el consumo para `draw_id = (Nacional Tarde del 22)`, que esta en 0, por lo que el disponible vuelve a ser 3000.
4. `limits:purge` solo compacta la tabla borrando registros antiguos; el reset ya ocurre por diseno.

**Resultado:** sin codigo adicional, el reset diario funciona porque cada sorteo del dia tiene su propio `LimitConsumption`.

---

## 8. Configurar el queue worker como servicio Windows (NSSM)

Laravel ejecuta jobs en segundo plano (envio de emails, generacion de reportes, sync mobile). En Linux se usa systemd o supervisor. En Windows usamos **NSSM**.

### 8.1 Instalar NSSM

1. Descarga NSSM desde https://nssm.cc/download (2.24 o superior).
2. Descomprime en `C:\nssm`.
3. Agrega `C:\nssm\win64` al PATH (igual que hicimos con PHP en 2.2).

### 8.2 Crear el servicio

```powershell
nssm install BSLottery_Queue 'C:\xampp\php\php.exe' 'artisan queue:work --tries=3 --max-time=3600 --sleep=3'
nssm set BSLottery_Queue AppDirectory 'C:\xampp\php\www\BSLotery'
nssm set BSLottery_Queue Description 'Worker de queue de Laravel para BSLotery.'
nssm set BSLottery_Queue Start SERVICE_AUTO_START
nssm set BSLottery_Queue AppStdout 'C:\xampp\php\www\BSLotery\storage\logs\queue-worker.log'
nssm set BSLottery_Queue AppStderr 'C:\xampp\php\www\BSLotery\storage\logs\queue-worker.log'
nssm set BSLottery_Queue AppRotateFiles 1
nssm set BSLottery_Queue AppRotateBytes 10485760
nssm start BSLottery_Queue
```

### 8.3 Verificar

```powershell
Get-Service BSLottery_Queue
Get-Content C:\xampp\php\www\BSLotery\storage\logs\queue-worker.log -Tail 10
```

> **Importante:** despues de cada deploy con cambios al codigo de jobs, reinicia el servicio: `Restart-Service BSLottery_Queue`. Sin reiniciar, el worker sigue usando el codigo viejo en memoria.

---

## 9. Setup inicial de la aplicacion

Una vez que Apache responde HTTPS, abre la URL en un navegador. El sistema te llevara a `/setup/initial`:

1. Completa los datos de la empresa (nombre, RNC, telefono, email, direccion).
2. Crea la primera sucursal.
3. Crea el usuario admin (COMPANY_OWNER). **Anota username/password.**
4. Acepta y entra al dashboard.

> **Importante:** despues del setup inicial, el endpoint `/setup/initial` queda deshabilitado automaticamente. Si necesitas reiniciar desde cero, vacia la BD: `php artisan migrate:fresh --seed --force`.

---

## 10. App Android — apuntarla al VPS

La app Android tiene 3 flavors de build en `build.gradle.kts`:

| Flavor | URL apuntada | Cuándo usarlo |
|---|---|---|
| `emulator` | `http://10.0.2.2:8000` | Solo desde AVD de Android Studio contra `php artisan serve`. |
| `lan` | `BSLOTTERY_LAN_SERVER_URL` de `gradle.properties` (default `http://192.168.1.100:8000`) | Cajero en la misma red WiFi que la PC con XAMPP. |
| `production` | `BSLOTTERY_PRODUCTION_SERVER_URL` o el default `https://bslottery.bsolutions.dev` | **VPS público — usar este para producción.** |

### 10.1 Compilar el APK production

Desde PowerShell en el repo:

```powershell
cd C:\xampp\php\www\BSLotery\android
.\build-production-release.ps1
```

Esto ejecuta `gradlew assembleProductionRelease` y deja el APK en
`android/app/build/outputs/apk/production/release/`.

> **CRÍTICO:** NO uses `gradlew assembleRelease` o `assembleLanRelease` para
> apuntar al VPS. Esos flavors embeben una IP LAN en el binario y el cajero
> verá errores tipo `failed to connect to /192.168.x.x` cuando esté fuera de
> esa red. Siempre `assembleProductionRelease` para distribución externa.

### 10.2 Override personalizado de URL en build

Si necesitas una URL diferente al default para producción (ej. staging):

1. Edita `android/gradle.properties` y agrega:
   ```
   BSLOTTERY_PRODUCTION_SERVER_URL=https://staging.tu-dominio.com
   ```
2. Compila: `.\build-production-release.ps1`

### 10.3 Instalar y configurar la app

1. Transfiere el APK al dispositivo (USB, ADB, descarga directa).
2. Instala (acepta "Permitir orígenes desconocidos" la primera vez).
3. Abre la app — solo verás **Usuario** y **Contraseña**. La URL del VPS está
   embebida en el APK; el cajero no la ve.
4. Login con el COMPANY_OWNER creado en el setup inicial.
5. La app autoriza el device automáticamente. Ve al panel web del VPS
   (`/admin/devices`) y marca el device como `AUTHORIZED`.
6. Vuelve a la app y reintenta el login (o usa el botón **Reintentar** en
   la pantalla de sincronización).

### 10.4 Cambio temporal de URL desde Settings (soporte/QA)

El admin puede cambiar la URL **desde dentro de la app** sin recompilar:

1. Login normal.
2. **Configuración → Servidor**.
3. Verás el indicador "Usando default del APK" o "Override de admin activo"
   con la URL actual.
4. Edita el campo URL y guarda → todas las llamadas siguientes irán a esa URL.
5. Para volver al default del APK → toca **Restablecer**.

> Esto es un override persistente en el dispositivo. Se mantiene aunque el
> usuario haga logout. Solo lo borra **Restablecer** o desinstalar la app.

### 10.5 Troubleshooting Android

#### "failed to connect to /192.168.x.x (port 8000)"

Causas comunes:

1. **El APK se compiló con flavor `lan` o `emulator`.** Recompila con
   `.\build-production-release.ps1` y reinstala.
2. **Hay un override viejo en el SessionStore.** Soluciones (en orden):
   - **Configuración → Servidor → Restablecer** (más rápido si el cajero
     puede entrar).
   - Si no puede entrar al login: Settings de Android → Apps → BSLottery →
     **Borrar datos**. Esto limpia el DataStore y la app arrancará con el
     default del APK.
   - Última opción: desinstalar y reinstalar el APK.
3. **El dispositivo no tiene internet o el VPS está caído.** Verifica
   abriendo `https://bslottery.bsolutions.dev` en el navegador del teléfono.

#### "Dispositivo PENDING — autorízalo en el panel web"

El device se registró pero ningún admin lo autorizó. Ve al panel web →
`/admin/devices` → busca el device por nombre/UUID → click **Autorizar**.

#### "Sesión expirada. Inicia sesión de nuevo."

El token Sanctum caducó o se revocó. Logout → login.

#### "No se encuentra el servidor."

DNS no resuelve. Verifica:
- El teléfono tiene internet (abre `8.8.8.8` en el navegador).
- El dominio resuelve (desde otro device: `nslookup bslottery.bsolutions.dev`).
- El cert SSL del VPS no está expirado (`curl -I https://bslottery.bsolutions.dev`).

---

## 11. Mantenimiento y operacion

### 11.1 Ver logs en vivo

```powershell
Get-Content C:\xampp\php\www\BSLotery\storage\logs\laravel.log -Tail 50 -Wait
```

Logs importantes:

| Archivo | Contiene |
|---|---|
| `storage/logs/laravel.log` | Errores y warnings de la app |
| `storage/logs/draws-auto-close.log` | Cierre automatico de sorteos |
| `storage/logs/limits-purge.log` | Purga semanal de limites |
| `storage/logs/backup.log` | Backups |
| `storage/logs/queue-worker.log` | Jobs en background |
| `C:/logs/bslottery/apache-error.log` | Errores de Apache |
| `C:/logs/bslottery/apache-ssl-access.log` | Trafico HTTPS |

### 11.2 Deploy de actualizaciones

```powershell
cd C:\xampp\php\www\BSLotery
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
Restart-Service Apache2.4
Restart-Service BSLottery_Queue
```

Tambien borra la cache de Laravel si actualizaste config/rutas:

```powershell
php artisan optimize:clear
php artisan optimize
```

### 11.3 Backups

El scheduler ya hace backups diarios a las 02:00. Confirma que se estan creando:

```powershell
Get-ChildItem C:\xampp\php\www\BSLotery\storage\app\backup-bslottery -Recurse | Sort-Object LastWriteTime -Descending | Select-Object -First 5
```

> **Backups offsite:** muy recomendado copiar tambien a almacenamiento externo (OneDrive, S3, otra maquina). Configura una tarea programada de Windows que use `robocopy` o `aws s3 sync` cada noche despues de las 02:30.

### 11.4 Backup de la BD

El backup default solo hace files. Para backup de MySQL:

```powershell
# Tarea diaria que respalda la BD
$dumpScript = @'
$ts = Get-Date -Format 'yyyyMMdd_HHmmss'
& 'C:\xampp\mysql\bin\mysqldump.exe' --user=bslottery_user --password='LA-CLAVE' --single-transaction --routines --triggers bslottery | Out-File "C:\backups\bslottery\db_$ts.sql" -Encoding utf8
# Retencion: borrar dumps > 30 dias
Get-ChildItem C:\backups\bslottery\db_*.sql | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-30) } | Remove-Item -Force
'@

$dumpScript | Out-File C:\backups\bslottery\dump.ps1 -Encoding utf8

$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument '-ExecutionPolicy Bypass -File C:\backups\bslottery\dump.ps1'
$trigger = New-ScheduledTaskTrigger -Daily -At 02:45
$principal = New-ScheduledTaskPrincipal -UserId 'NT AUTHORITY\SYSTEM' -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName 'BSLotery_DBBackup' -Action $action -Trigger $trigger -Principal $principal -Description 'Dump diario MySQL.'
```

### 11.5 Verificar el scheduler esta corriendo

```powershell
Get-ScheduledTask -TaskName 'BSLotery_Scheduler' | Get-ScheduledTaskInfo
```

Si `LastRunTime` esta vieja: la tarea no se esta disparando. Revisa que el usuario `NT AUTHORITY\SYSTEM` tenga permisos para ejecutar PHP y leer/escribir en la carpeta del proyecto.

---

## 12. Troubleshooting

### 12.1 "permission denied" al escribir en `storage/`

```powershell
icacls "C:\xampp\php\www\BSLotery\storage" /grant 'NT AUTHORITY\LocalSystem:(OI)(CI)F' /T
icacls "C:\xampp\php\www\BSLotery\bootstrap\cache" /grant 'NT AUTHORITY\LocalSystem:(OI)(CI)F' /T
```

### 12.2 Apache no inicia (puerto 80 ocupado)

```powershell
netstat -ano | findstr ":80"
# Identifica el PID, busca el proceso:
Get-Process -Id <PID>
```

Tipicamente IIS o Skype usan el puerto 80. Para detenerlos:

```powershell
# IIS:
Stop-Service W3SVC
Set-Service W3SVC -StartupType Disabled
```

### 12.3 MySQL no inicia

XAMPP control panel -> MySQL -> Logs. Lo mas comun es que falta `ibdata1` o esta corrupto. Restaura desde backup.

### 12.4 `php artisan schedule:run` no se ejecuta

```powershell
# Test manual
cd C:\xampp\php\www\BSLotery
php artisan schedule:run
# Ver siguiente ejecucion programada
php artisan schedule:list
```

Si el comando manual funciona pero la tarea no: verifica que la tarea programada use el WorkingDirectory correcto y la cuenta tenga permisos.

### 12.5 El queue worker se detiene solo

Causas comunes: memoria, jobs que tardan demasiado, mysql desconectado. Revisa `storage/logs/queue-worker.log`. NSSM lo reinicia automaticamente; si quieres ajustar el delay de reinicio:

```powershell
nssm set BSLottery_Queue AppExit Default Restart
nssm set BSLottery_Queue AppRestartDelay 3000
```

### 12.6 Sorteos no se cierran automaticamente

```powershell
cd C:\xampp\php\www\BSLotery
php artisan draws:auto-close --dry-run  # ver que cerraria
php artisan schedule:list                # confirmar que el job esta registrado
Get-Content storage\logs\draws-auto-close.log -Tail 30
```

Si no hay log: la tarea de Windows no esta corriendo (ver 12.4).

### 12.7 Cajeros se quejan "el numero esta agotado pero ayer no"

Significa que el cierre/apertura del dia no se ejecuto y siguen vendiendose contra el draw de ayer:

```powershell
php artisan draws:generate-daily --days=1  # crea sorteos faltantes para hoy
```

Verifica `storage/logs/laravel.log` cerca de las 00:01 para entender por que no corrio.

---

## 13. Hardening de seguridad (recomendado para produccion)

1. **Cambiar todas las claves default** de XAMPP (root MySQL, phpMyAdmin si lo usas).
2. **Firewall**: solo permitir puertos 80, 443. Bloquear 3306, 8080 (Tomcat) y todo lo demas desde el exterior.
3. **Deshabilitar phpMyAdmin** si no lo usas (es vector de ataque comun): comenta el alias en `C:\xampp\apache\conf\extra\httpd-xampp.conf`.
4. **Auto-update Windows**: configura updates automaticos para parches de seguridad.
5. **Antivirus**: instala Windows Defender o equivalente.
6. **HSTS**: ya esta en el vhost de la seccion 6.2.
7. **Rotacion de claves**: cambia `APP_KEY`, `DB_PASSWORD` y claves de admin cada 90-180 dias.
8. **Bloquear acceso a `.git`**: agrega al vhost:
   ```apache
   <DirectoryMatch "/\.git">
       Require all denied
   </DirectoryMatch>
   ```
9. **Bloquear acceso directo a `storage/` y `vendor/`**: ya esta cubierto porque `DocumentRoot` apunta a `public/`. No subas el resto al webroot.
10. **Rate limiting**: usar mod_evasive de Apache o un WAF (Cloudflare gratis es buena opcion frente al VPS).

---

## 14. Checklist final de despliegue

Antes de declarar "listo para produccion":

- [ ] HTTPS funciona y redirige desde HTTP
- [ ] `APP_ENV=production`, `APP_DEBUG=false` en `.env`
- [ ] BD creada, migrada y seeded
- [ ] Setup inicial completado con admin real
- [ ] Tarea programada `BSLotery_Scheduler` esta `Running`
- [ ] Servicio `BSLottery_Queue` esta `Running`
- [ ] `php artisan schedule:list` muestra los 8 comandos
- [ ] Backups diarios funcionando (verifica directorio `storage/app/backup-bslottery`)
- [ ] Backup de BD configurado (tarea `BSLotery_DBBackup`)
- [ ] Cajeros pueden vender y los sorteos se cierran a su hora
- [ ] App Android conecta y vende contra el VPS
- [ ] Firewall correctamente configurado
- [ ] Permisos `storage/` y `bootstrap/cache` validados
- [ ] Logs rotando (Laravel daily + NSSM 10MB)
- [ ] Plan de recuperacion ante fallos documentado para el cliente

---

## 15. Referencias rapidas

```powershell
# Estado de servicios
Get-Service | Where-Object {$_.Name -in 'Apache2.4','MySQL','BSLottery_Queue'}

# Reiniciar todo el stack
Restart-Service Apache2.4
Restart-Service MySQL
Restart-Service BSLottery_Queue

# Limpiar todas las caches de Laravel
php artisan optimize:clear

# Re-aplicar caches optimizadas
php artisan optimize

# Disparar manualmente los schedulers (testing)
php artisan draws:generate-daily
php artisan draws:auto-close --dry-run
php artisan limits:purge --dry-run

# Ver el log de la app en vivo
Get-Content storage\logs\laravel.log -Tail 50 -Wait
```

---

**Contacto:** Si algo en esta guia falla o queda ambiguo, ajustala y documenta el cambio para que la proxima IA o desarrollador no tropiece con lo mismo.
