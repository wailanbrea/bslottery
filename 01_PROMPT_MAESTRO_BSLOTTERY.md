# PROMPT MAESTRO — BSLotery

## Rol de la IA

Actúa como arquitecto de software senior, líder técnico y auditor de calidad para construir **BSLotery**, una plataforma moderna para venta de loterías/bancas, multiempresa, multisucursal, con app web para PC, app Android, impresión térmica, límites configurables, pagos por sucursal, caja estricta, resultados, premios, nómina, contabilidad, auditoría y reportes.

Debes trabajar como guía de desarrollo y no como generador impulsivo de código. Antes de escribir código, debes diseñar, validar supuestos, dividir por fases, revisar dependencias y marcar avances en el archivo de control del proyecto.

Este prompt debe servir para que diferentes IAs puedan continuar el trabajo sin perder contexto.

---

# 1. Objetivo general

Desarrollar **BSLotery**, un sistema profesional para operación de bancas/loterías capaz de escalar a mínimo **500 bancas/sucursales activas**.

El sistema debe permitir:

1. Manejar múltiples empresas.
2. Manejar múltiples sucursales/bancas por empresa.
3. Tratar cada sucursal como punto de venta.
4. Vender tickets desde PC/Web.
5. Vender tickets desde Android.
6. Permitir venta offline controlada en Android cuando se vaya la luz o internet.
7. Manejar límites por número individual.
8. Manejar límites por grupo/lista exacta de números.
9. Manejar límites por rango, por ejemplo del 00 al 50.
10. Manejar límites diferentes por sucursal.
11. Manejar pagos diferentes por sucursal.
12. Registrar resultados ganadores.
13. Confirmar resultados antes de calcular premios.
14. Calcular tickets ganadores.
15. Autorizar/liberar pagos antes de pagar premios.
16. Controlar caja de manera estricta.
17. Registrar gastos, entradas, salidas, faltantes y sobrantes.
18. Manejar nómina de empleados.
19. Manejar contabilidad interna operativa.
20. Auditar todas las acciones críticas.
21. Generar reportes operativos, financieros, contables y de nómina.
22. Imprimir tickets en impresoras conectadas a PC.
23. Imprimir tickets en Android por Bluetooth ESC/POS.
24. Controlar roles y permisos por menú y acción.
25. Escalar a alto volumen de tickets y jugadas.

---

# 2. Tecnologías obligatorias

## Backend

- Laravel 12.
- PHP 8.3 o superior.
- MySQL 8.0+ o MariaDB estable.
- Laravel Sanctum para autenticación API.
- Laravel Policies/Gates para autorización.
- Laravel Queues y Jobs para procesos pesados.
- Laravel Events/Listeners para auditoría, alertas y contabilidad automática.
- Laravel Form Requests para validación.
- Laravel API Resources para respuestas limpias.
- Laravel Migrations, Seeders y Factories.
- Redis recomendado para cache/colas cuando el sistema pase a producción real.
- Database transactions en todas las operaciones financieras/críticas.
- Row locking/pessimistic locking para consumo de límites.
- Índices compuestos en tablas críticas.
- Soft deletes solo donde aplique.
- No eliminar datos financieros físicamente.

## Frontend web PC

- Laravel Blade.
- Bootstrap 5.
- Argon Dashboard o diseño moderno equivalente.
- JavaScript modular.
- Alpine.js opcional.
- DataTables server-side o tablas paginadas desde backend.
- UI optimizada para PC y teclado físico.
- Menú dinámico según permisos.

## Android

- Kotlin.
- Jetpack Compose.
- Material 3.
- MVVM.
- Repository Pattern.
- Retrofit.
- OkHttp.
- Room.
- DataStore.
- WorkManager.
- Hilt o Koin.
- Coroutines.
- Flow.
- Impresión Bluetooth ESC/POS.
- Sincronización offline controlada.
- Idempotency keys para evitar ventas duplicadas.

## Servicio de impresión PC

- Java LTS moderno para Print Agent o servicio local equivalente.
- Debe ejecutarse en Windows.
- Debe escuchar únicamente en localhost.
- Debe imprimir en impresoras térmicas ESC/POS, USB, red o compartidas en Windows.
- Debe validar token local.
- Debe registrar historial de impresión y errores.

## Infraestructura

- Windows VPS + Apache compatible con el entorno actual.
- HTTPS obligatorio.
- OPcache habilitado.
- Base de datos dedicada.
- Backups automáticos.
- Logs rotativos.
- Variables de entorno `.env`.
- Ambientes separados: local, staging y producción.

---

# 3. Reglas estructurales del negocio

1. Una empresa puede tener muchas sucursales.
2. Una sucursal es lo mismo que una banca o punto de venta.
3. No crear una tabla separada de puntos de venta si la sucursal ya cumple esa función.
4. La entidad operativa principal será `branches`.
5. Cada sucursal puede vender tickets.
6. Cada sucursal puede tener caja.
7. Cada sucursal puede tener usuarios.
8. Cada sucursal puede tener empleados.
9. Cada sucursal puede tener dispositivos Android autorizados.
10. Cada sucursal puede tener impresoras.
11. Cada sucursal puede tener límites propios.
12. Cada sucursal puede tener pagos propios.
13. Cada sucursal puede trabajar offline en Android bajo cupos controlados.
14. Cada ticket debe pertenecer a una empresa y una sucursal.
15. Cada operación financiera debe ser auditable.

Jerarquía del sistema:

```text
PLATAFORMA
└── EMPRESA
    └── SUCURSAL / BANCA / PUNTO DE VENTA
        ├── USUARIOS
        ├── EMPLEADOS
        ├── CAJA
        ├── TICKETS
        ├── DISPOSITIVOS
        ├── IMPRESORAS
        ├── LÍMITES
        ├── PAGOS
        ├── NÓMINA
        └── CONTABILIDAD
```

---

# 4. Dominios del sistema

## 4.1 Identity & Access

- Login.
- Usuarios.
- Roles.
- Permisos.
- Sesiones.
- Dispositivos autorizados.
- Bloqueo de usuarios.
- Auditoría de acceso.

## 4.2 Company Management

- Empresas.
- Sucursales/bancas.
- Configuración por empresa.
- Configuración por sucursal.

## 4.3 Lottery Core

- Loterías.
- Sorteos.
- Tipos de jugadas.
- Posiciones.
- Reglas de pago.
- Reglas de límites.

## 4.4 Sales

- Venta de tickets.
- Detalle de tickets.
- Validación de límites.
- Resolución de pagos.
- Reimpresión.
- Anulación.
- Sincronización offline Android.

## 4.5 Results & Prizes

- Registro de resultados.
- Confirmación de resultados.
- Cálculo de ganadores.
- Autorización/liberación de pagos.
- Pago de premios.

## 4.6 Cash Control

- Apertura de caja.
- Movimientos de caja.
- Cierre de caja.
- Faltantes.
- Sobrantes.
- Confirmación de cierre.

## 4.7 Accounting

- Catálogo de cuentas.
- Asientos contables.
- Movimientos financieros.
- Estado de resultados.
- Flujo de efectivo.

## 4.8 Payroll

- Empleados.
- Nómina.
- Avances.
- Préstamos.
- Deducciones.
- Bonos.
- Comisiones.

## 4.9 Reporting

- Reportes operativos.
- Reportes financieros.
- Reportes de caja.
- Reportes de nómina.
- Exportación PDF/Excel.

## 4.10 Audit

- Historial de acciones.
- Datos antes/después.
- Usuario.
- Sucursal.
- Dispositivo.
- IP.
- Fecha/hora.

---

# 5. Reglas de escalabilidad para 500 bancas

1. No cargar todos los tickets en memoria.
2. Usar paginación server-side.
3. Usar filtros indexados.
4. Usar índices compuestos en tablas críticas.
5. Separar operaciones pesadas en Jobs.
6. Calcular ganadores en cola.
7. Generar reportes grandes en cola.
8. Usar tabla acumulada `limit_consumptions` para límites.
9. No calcular límites con `SUM(ticket_details.amount)` en cada venta.
10. Usar transacciones con row locking para ventas.
11. Usar idempotency keys para ventas desde Android.
12. Usar UUID en tickets offline.
13. Cachear catálogos estables.
14. Cachear permisos del usuario.
15. Registrar auditoría asíncrona cuando no afecte consistencia.
16. Todas las tablas operativas deben incluir `company_id`.
17. Las tablas por sucursal deben incluir `branch_id`.
18. No hacer consultas grandes sin `company_id`.
19. Preparar reportes resumidos si el volumen crece.
20. Separar app, DB, colas y almacenamiento cuando el tráfico lo requiera.
21. Usar OPcache en producción.
22. Usar colas separadas: `default`, `reports`, `winners`, `sync`, `notifications`.
23. Implementar backups automáticos y pruebas de restauración.
24. Tener logs por canal: ventas, caja, sincronización, impresión, seguridad.

---

# 6. Modelo de base de datos

## Convenciones generales

- Nombres de tablas y campos en inglés.
- `BIGINT unsigned` para IDs.
- `CHAR(36)` o UUID nativo donde aplique.
- `DECIMAL(14,2)` para dinero.
- Nunca usar `FLOAT` ni `DOUBLE` para dinero.
- Todas las tablas operativas deben tener `company_id`.
- Las tablas por sucursal deben tener `branch_id`.
- Datos financieros no se eliminan físicamente.
- Usar estados controlados mediante enums/clases enum en Laravel.
- Crear índices compuestos desde el inicio.

---

## 6.1 companies

Representa empresas dueñas de bancas.

Campos:

- `id` BIGINT PK.
- `uuid` CHAR(36) UNIQUE.
- `name` VARCHAR(150).
- `legal_name` VARCHAR(200) nullable.
- `rnc` VARCHAR(50) nullable.
- `phone` VARCHAR(50) nullable.
- `email` VARCHAR(150) nullable.
- `address` VARCHAR(255) nullable.
- `logo_path` VARCHAR(255) nullable.
- `status` ENUM: `ACTIVE`, `INACTIVE`, `SUSPENDED`.
- `timezone` VARCHAR(80) default `America/Santo_Domingo`.
- `currency` CHAR(3) default `DOP`.
- `created_at`.
- `updated_at`.

Relaciones:

- `Company hasMany Branch`.
- `Company hasMany User`.
- `Company hasMany Role`.
- `Company hasMany Lottery`.
- `Company hasMany Draw`.
- `Company hasMany Ticket`.
- `Company hasMany Employee`.
- `Company hasMany AccountingAccount`.

Índices:

- UNIQUE `uuid`.
- INDEX `status`.
- INDEX `name`.

---

## 6.2 branches

Representa sucursal/banca/punto de venta.

Campos:

- `id` BIGINT PK.
- `uuid` CHAR(36) UNIQUE.
- `company_id` FK `companies.id`.
- `code` VARCHAR(50).
- `name` VARCHAR(150).
- `phone` VARCHAR(50) nullable.
- `address` VARCHAR(255) nullable.
- `manager_name` VARCHAR(150) nullable.
- `status` ENUM: `ACTIVE`, `INACTIVE`, `SUSPENDED`.
- `can_sell_online` BOOLEAN default true.
- `can_sell_offline` BOOLEAN default false.
- `offline_max_minutes` INT default 120.
- `offline_total_limit` DECIMAL(14,2) default 0.
- `cash_control_enabled` BOOLEAN default true.
- `accounting_enabled` BOOLEAN default true.
- `payroll_enabled` BOOLEAN default true.
- `default_printer_id` BIGINT nullable.
- `created_at`.
- `updated_at`.

Relaciones:

- `Branch belongsTo Company`.
- `Branch hasMany User`.
- `Branch hasMany Employee`.
- `Branch hasMany Ticket`.
- `Branch hasMany CashSession`.
- `Branch hasMany Device`.
- `Branch hasMany PrinterConfig`.
- `Branch hasMany PayoutRule`.
- `Branch hasMany LimitRule`.

Restricciones:

- UNIQUE `company_id + code`.
- No vender si `status != ACTIVE`.
- No vender offline si `can_sell_offline = false`.

Índices:

- INDEX `company_id, status`.
- INDEX `company_id, code`.
- UNIQUE `uuid`.

---

## 6.3 users

Usuarios de acceso.

Campos:

- `id` BIGINT PK.
- `uuid` CHAR(36) UNIQUE.
- `company_id` FK nullable para superadmin.
- `branch_id` FK nullable.
- `name` VARCHAR(150).
- `username` VARCHAR(80).
- `email` VARCHAR(150) nullable.
- `password` VARCHAR(255).
- `role_id` FK `roles.id`.
- `employee_id` FK `employees.id` nullable.
- `status` ENUM: `ACTIVE`, `INACTIVE`, `BLOCKED`.
- `last_login_at` DATETIME nullable.
- `failed_login_attempts` INT default 0.
- `locked_until` DATETIME nullable.
- `created_at`.
- `updated_at`.

Relaciones:

- `User belongsTo Company` nullable.
- `User belongsTo Branch` nullable.
- `User belongsTo Role`.
- `User belongsTo Employee` nullable.
- `User hasMany Ticket`.
- `User hasMany AuditLog`.
- `User hasMany CashSession`.

Restricciones:

- UNIQUE `company_id + username`.
- UNIQUE `company_id + email` cuando aplique.

Índices:

- INDEX `company_id, branch_id`.
- INDEX `role_id`.
- INDEX `status`.
- INDEX `username`.

---

## 6.4 roles

Campos:

- `id` BIGINT PK.
- `company_id` FK nullable.
- `name` VARCHAR(100).
- `slug` VARCHAR(100).
- `level` INT default 0.
- `description` TEXT nullable.
- `status` ENUM: `ACTIVE`, `INACTIVE`.
- `created_at`.
- `updated_at`.

Relaciones:

- `Role belongsTo Company` nullable.
- `Role hasMany User`.
- `Role belongsToMany Permission through role_permissions`.

Restricciones:

- UNIQUE `company_id + slug`.

Roles iniciales:

- `SUPER_ADMIN`.
- `COMPANY_OWNER`.
- `ADMIN`.
- `SUPERVISOR`.
- `CASHIER`.
- `PAYER`.
- `ACCOUNTANT`.
- `PAYROLL_MANAGER`.
- `AUDITOR`.

---

## 6.5 permissions

Campos:

- `id` BIGINT PK.
- `module` VARCHAR(100).
- `action` VARCHAR(100).
- `name` VARCHAR(150).
- `slug` VARCHAR(150) UNIQUE.
- `description` TEXT nullable.
- `created_at`.
- `updated_at`.

Relaciones:

- `Permission belongsToMany Role`.

Permisos mínimos:

- `dashboard.view`.
- `companies.view`, `companies.create`, `companies.update`.
- `branches.view`, `branches.create`, `branches.update`, `branches.suspend`.
- `users.view`, `users.create`, `users.update`, `users.block`.
- `roles.view`, `roles.create`, `roles.update`, `roles.assign_permissions`.
- `employees.view`, `employees.create`, `employees.update`.
- `lotteries.view`, `lotteries.create`, `lotteries.update`.
- `draws.view`, `draws.create`, `draws.update`, `draws.close`, `draws.reopen`.
- `payout_rules.view`, `payout_rules.create`, `payout_rules.update`, `payout_rules.approve`.
- `limit_rules.view`, `limit_rules.create`, `limit_rules.update`, `limit_rules.approve`, `limit_rules.import`.
- `sales.create`, `sales.preview`, `sales.offline`, `sales.cancel`, `sales.reprint`.
- `tickets.view`, `tickets.cancel`, `tickets.reprint`.
- `results.view`, `results.create`, `results.confirm`, `results.modify_confirmed`.
- `winners.calculate`.
- `payments.authorize`.
- `prizes.pay`.
- `cash.open`, `cash.view`, `cash.movement`, `cash.close`, `cash.confirm`, `cash.reopen`.
- `accounting.view`, `accounting.manage_accounts`, `accounting.create_entry`, `accounting.reports`.
- `payroll.view`, `payroll.calculate`, `payroll.approve`, `payroll.pay`.
- `devices.view`, `devices.authorize`, `devices.block`.
- `printers.view`, `printers.configure`, `printers.test`.
- `reports.view`, `reports.export`.
- `audit.view`.
- `settings.view`, `settings.update`.

---

## 6.6 role_permissions

Campos:

- `id` BIGINT PK.
- `role_id` FK.
- `permission_id` FK.
- `created_at`.
- `updated_at`.

Restricciones:

- UNIQUE `role_id + permission_id`.

---

## 6.7 devices

Dispositivos autorizados: PC, Android, Print Agent.

Campos:

- `id` BIGINT PK.
- `uuid` CHAR(36) UNIQUE.
- `company_id` FK.
- `branch_id` FK nullable.
- `user_id` FK nullable.
- `name` VARCHAR(150).
- `device_type` ENUM: `WEB_PC`, `ANDROID`, `PRINT_AGENT`.
- `platform` VARCHAR(100) nullable.
- `device_fingerprint` VARCHAR(255).
- `app_version` VARCHAR(50) nullable.
- `status` ENUM: `PENDING`, `AUTHORIZED`, `BLOCKED`, `REVOKED`.
- `last_seen_at` DATETIME nullable.
- `authorized_by` FK nullable.
- `authorized_at` DATETIME nullable.
- `created_at`.
- `updated_at`.

Relaciones:

- `Device belongsTo Company`.
- `Device belongsTo Branch` nullable.
- `Device belongsTo User` nullable.
- `Device hasMany Ticket`.
- `Device hasMany OfflineSession`.
- `Device hasMany PrintJob`.

Índices:

- INDEX `company_id, branch_id`.
- INDEX `device_fingerprint`.
- INDEX `status`.

---

## 6.8 lotteries

Campos:

- `id` BIGINT PK.
- `company_id` FK nullable si se decide catálogo global.
- `name` VARCHAR(150).
- `code` VARCHAR(50).
- `country` VARCHAR(80) default `DO`.
- `status` ENUM: `ACTIVE`, `INACTIVE`.
- `created_at`.
- `updated_at`.

Relaciones:

- `Lottery hasMany Draw`.
- `Lottery hasMany TicketDetail`.
- `Lottery hasMany PayoutRule`.
- `Lottery hasMany LimitRule`.

Restricciones:

- UNIQUE `company_id + code`.

---

## 6.9 draws

Sorteos de cada lotería.

Campos:

- `id` BIGINT PK.
- `uuid` CHAR(36) UNIQUE.
- `company_id` FK.
- `lottery_id` FK.
- `name` VARCHAR(150).
- `draw_date` DATE.
- `scheduled_time` TIME.
- `close_time` TIME.
- `closed_at` DATETIME nullable.
- `status` ENUM: `OPEN`, `CLOSING_SOON`, `CLOSED`, `RESULT_PENDING`, `RESULT_REGISTERED`, `RESULT_CONFIRMED`, `CALCULATING_WINNERS`, `WINNERS_CALCULATED`, `PAYMENTS_RELEASED`, `FINALIZED`, `CANCELLED`.
- `last_ticket_at` DATETIME nullable.
- `result_registered_at` DATETIME nullable.
- `result_confirmed_at` DATETIME nullable.
- `winners_calculated_at` DATETIME nullable.
- `payments_released_at` DATETIME nullable.
- `created_at`.
- `updated_at`.

Relaciones:

- `Draw belongsTo Company`.
- `Draw belongsTo Lottery`.
- `Draw hasMany TicketDetail`.
- `Draw hasOne Result`.
- `Draw hasMany WinnerTicket`.
- `Draw hasMany LimitConsumption`.

Índices:

- INDEX `company_id, lottery_id, draw_date`.
- INDEX `company_id, status`.
- INDEX `company_id, close_time`.
- UNIQUE `uuid`.

Reglas:

- No vender si `status != OPEN`.
- No vender después de `close_time`.
- Cierre automático por job programado.
- Cierre manual solo con permiso.

---

## 6.10 bet_types

Tipos de jugadas.

Campos:

- `id` BIGINT PK.
- `company_id` FK nullable.
- `code` VARCHAR(50).
- `name` VARCHAR(100).
- `digits_count` INT.
- `min_numbers` INT.
- `max_numbers` INT.
- `requires_position` BOOLEAN default false.
- `status` ENUM: `ACTIVE`, `INACTIVE`.
- `created_at`.
- `updated_at`.

Ejemplos:

- `QUINIELA`.
- `PALE`.
- `TRIPLETA`.
- `SUPER_PALE`.
- `PICK3`.
- `PICK4`.

Relaciones:

- `BetType hasMany PayoutRule`.
- `BetType hasMany LimitRule`.
- `BetType hasMany TicketDetail`.

Restricciones:

- UNIQUE `company_id + code`.

---

## 6.11 payout_rules

Reglas de pago configurables por empresa y sucursal.

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `branch_id` FK nullable.
- `lottery_id` FK nullable.
- `draw_id` FK nullable.
- `bet_type_id` FK.
- `position` ENUM nullable: `FIRST`, `SECOND`, `THIRD`, `ANY`, `EXACT`.
- `match_type` ENUM: `DIRECT`, `COMBINATION`, `EXACT_ORDER`, `ANY_ORDER`.
- `payout_multiplier` DECIMAL(10,2).
- `effective_from` DATETIME.
- `effective_to` DATETIME nullable.
- `inherit_from_parent` BOOLEAN default false.
- `requires_approval` BOOLEAN default false.
- `status` ENUM: `DRAFT`, `ACTIVE`, `INACTIVE`, `EXPIRED`.
- `created_by` FK users.
- `approved_by` FK users nullable.
- `approved_at` DATETIME nullable.
- `created_at`.
- `updated_at`.

Relaciones:

- `PayoutRule belongsTo Company`.
- `PayoutRule belongsTo Branch` nullable.
- `PayoutRule belongsTo Lottery` nullable.
- `PayoutRule belongsTo Draw` nullable.
- `PayoutRule belongsTo BetType`.
- `PayoutRule hasMany TicketDetail`.

Índices:

- INDEX `company_id, branch_id`.
- INDEX `company_id, bet_type_id`.
- INDEX `company_id, lottery_id, draw_id`.
- INDEX `effective_from, effective_to`.
- INDEX `status`.

Reglas críticas:

1. Cada `ticket_detail` debe guardar `payout_rule_id` y `payout_multiplier` usado al vender.
2. Cambios futuros de pago no afectan tickets antiguos.
3. Una sucursal puede pagar diferente a otra.
4. Resolución de pago por prioridad:
   - Sucursal + sorteo + jugada + posición.
   - Sucursal + lotería + jugada + posición.
   - Sucursal + jugada + posición.
   - Empresa + sorteo + jugada + posición.
   - Empresa + lotería + jugada + posición.
   - Empresa + jugada + posición.

---

## 6.12 limit_rules

Reglas de límites por número, lista, rango o global.

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `branch_id` FK nullable.
- `lottery_id` FK nullable.
- `draw_id` FK nullable.
- `bet_type_id` FK nullable.
- `rule_type` ENUM: `GLOBAL`, `SINGLE_NUMBER`, `NUMBER_RANGE`, `NUMBER_LIST`.
- `number_value` VARCHAR(10) nullable.
- `number_from` VARCHAR(10) nullable.
- `number_to` VARCHAR(10) nullable.
- `numbers_json` JSON nullable.
- `max_amount_per_number` DECIMAL(14,2).
- `max_total_amount` DECIMAL(14,2) nullable.
- `policy` ENUM: `BLOCK_FULL`, `ALLOW_AVAILABLE`, `REQUEST_AUTHORIZATION`.
- `effective_from` DATETIME.
- `effective_to` DATETIME nullable.
- `status` ENUM: `ACTIVE`, `INACTIVE`, `EXPIRED`.
- `created_by` FK users.
- `approved_by` FK users nullable.
- `approved_at` DATETIME nullable.
- `created_at`.
- `updated_at`.

Relaciones:

- `LimitRule belongsTo Company`.
- `LimitRule belongsTo Branch` nullable.
- `LimitRule belongsTo Lottery` nullable.
- `LimitRule belongsTo Draw` nullable.
- `LimitRule belongsTo BetType` nullable.
- `LimitRule hasMany TicketDetail`.

Ejemplos:

Rango 00 al 50 en Sucursal 1:

```text
rule_type = NUMBER_RANGE
number_from = 00
number_to = 50
max_amount_per_number = 1000
```

Significa:

```text
00 máximo RD$1000
01 máximo RD$1000
02 máximo RD$1000
...
50 máximo RD$1000
```

No significa RD$1000 compartido entre todos; significa RD$1000 por cada número.

Reglas:

1. Evaluar todos los límites aplicables.
2. Usar el disponible más restrictivo.
3. Validar límite de empresa y límite de sucursal.
4. Una sucursal puede tener límites diferentes.
5. Una sucursal puede bloquear un número aunque otra lo permita.
6. Los cambios de límites deben quedar auditados.

Índices:

- INDEX `company_id, branch_id`.
- INDEX `company_id, lottery_id, draw_id`.
- INDEX `company_id, bet_type_id`.
- INDEX `rule_type`.
- INDEX `status`.
- INDEX `effective_from, effective_to`.

---

## 6.13 limit_consumptions

Tabla acumulada para consumo de límites.

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `branch_id` FK.
- `lottery_id` FK.
- `draw_id` FK.
- `bet_type_id` FK.
- `number_value` VARCHAR(10).
- `sold_amount` DECIMAL(14,2) default 0.
- `reserved_offline_amount` DECIMAL(14,2) default 0.
- `cancelled_amount` DECIMAL(14,2) default 0.
- `created_at`.
- `updated_at`.

Restricción única:

- UNIQUE `company_id + branch_id + lottery_id + draw_id + bet_type_id + number_value`.

Índices:

- INDEX `company_id, draw_id`.
- INDEX `company_id, branch_id, draw_id`.
- INDEX `number_value`.

Reglas:

- En venta online bloquear fila con `SELECT FOR UPDATE`.
- Si no existe consumo, crearlo dentro de la transacción.
- En anulación permitida, disminuir `sold_amount` y aumentar `cancelled_amount`.
- En reserva offline, aumentar `reserved_offline_amount`.

---

## 6.14 tickets

Cabecera de ticket.

Campos:

- `id` BIGINT PK.
- `uuid` CHAR(36) UNIQUE.
- `company_id` FK.
- `branch_id` FK.
- `user_id` FK.
- `device_id` FK nullable.
- `cash_session_id` FK nullable.
- `ticket_number` VARCHAR(80).
- `external_offline_number` VARCHAR(100) nullable.
- `sale_mode` ENUM: `ONLINE`, `OFFLINE`.
- `total_amount` DECIMAL(14,2).
- `total_possible_prize` DECIMAL(14,2).
- `status` ENUM: `ACTIVE`, `CANCELLED`, `WINNER`, `LOSER`, `PARTIALLY_PAID`, `PAID`, `PENDING_SYNC`, `SYNC_CONFLICT`.
- `printed_at` DATETIME nullable.
- `print_count` INT default 0.
- `cancelled_at` DATETIME nullable.
- `cancelled_by` FK users nullable.
- `cancel_reason` TEXT nullable.
- `paid_at` DATETIME nullable.
- `idempotency_key` VARCHAR(100) nullable.
- `sold_at` DATETIME.
- `synced_at` DATETIME nullable.
- `created_at`.
- `updated_at`.

Relaciones:

- `Ticket belongsTo Company`.
- `Ticket belongsTo Branch`.
- `Ticket belongsTo User`.
- `Ticket belongsTo Device` nullable.
- `Ticket belongsTo CashSession` nullable.
- `Ticket hasMany TicketDetail`.
- `Ticket hasMany PrintJob`.
- `Ticket hasMany WinnerTicket`.
- `Ticket hasMany PrizePayment`.

Restricciones:

- UNIQUE `company_id + ticket_number`.
- UNIQUE `company_id + idempotency_key` nullable.
- UNIQUE `uuid`.

Índices:

- INDEX `company_id, branch_id, sold_at`.
- INDEX `company_id, user_id, sold_at`.
- INDEX `company_id, status`.
- INDEX `ticket_number`.
- INDEX `cash_session_id`.
- INDEX `sale_mode`.
- INDEX `synced_at`.

---

## 6.15 ticket_details

Detalle de cada jugada.

Campos:

- `id` BIGINT PK.
- `ticket_id` FK.
- `company_id` FK.
- `branch_id` FK.
- `lottery_id` FK.
- `draw_id` FK.
- `bet_type_id` FK.
- `number_value` VARCHAR(20).
- `normalized_number` VARCHAR(20).
- `amount` DECIMAL(14,2).
- `payout_rule_id` FK nullable.
- `payout_multiplier` DECIMAL(10,2).
- `possible_prize` DECIMAL(14,2).
- `limit_rule_id` FK nullable.
- `result_position` ENUM nullable: `FIRST`, `SECOND`, `THIRD`, `ANY`, `EXACT`.
- `status` ENUM: `ACTIVE`, `CANCELLED`, `WINNER`, `LOSER`, `PAID`.
- `created_at`.
- `updated_at`.

Relaciones:

- `TicketDetail belongsTo Ticket`.
- `TicketDetail belongsTo Company`.
- `TicketDetail belongsTo Branch`.
- `TicketDetail belongsTo Lottery`.
- `TicketDetail belongsTo Draw`.
- `TicketDetail belongsTo BetType`.
- `TicketDetail belongsTo PayoutRule` nullable.
- `TicketDetail belongsTo LimitRule` nullable.
- `TicketDetail hasOne WinnerTicket`.

Índices:

- INDEX `ticket_id`.
- INDEX `company_id, branch_id, draw_id`.
- INDEX `company_id, draw_id, bet_type_id, number_value`.
- INDEX `company_id, lottery_id, draw_id`.
- INDEX `status`.

Regla crítica:

- `possible_prize = amount * payout_multiplier`.
- `payout_multiplier` se guarda al momento de venta.
- No recalcular premios de tickets viejos usando configuración actual.

---

## 6.16 results

Resultados ganadores.

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `lottery_id` FK.
- `draw_id` FK UNIQUE.
- `first_number` VARCHAR(10).
- `second_number` VARCHAR(10) nullable.
- `third_number` VARCHAR(10) nullable.
- `status` ENUM: `DRAFT`, `REGISTERED`, `CONFIRMED`, `CANCELLED`.
- `registered_by` FK users.
- `registered_at` DATETIME.
- `confirmed_by` FK users nullable.
- `confirmed_at` DATETIME nullable.
- `confirmation_notes` TEXT nullable.
- `created_at`.
- `updated_at`.

Relaciones:

- `Result belongsTo Company`.
- `Result belongsTo Lottery`.
- `Result belongsTo Draw`.
- `Result registeredBy User`.
- `Result confirmedBy User`.

Reglas:

- Resultado debe confirmarse antes de calcular ganadores.
- El mismo usuario no debe registrar y confirmar salvo permiso especial.
- Modificar resultado confirmado requiere permiso especial y auditoría.

---

## 6.17 winner_tickets

Jugadas ganadoras calculadas.

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `branch_id` FK.
- `ticket_id` FK.
- `ticket_detail_id` FK.
- `lottery_id` FK.
- `draw_id` FK.
- `bet_type_id` FK.
- `number_value` VARCHAR(20).
- `matched_position` VARCHAR(50) nullable.
- `amount_played` DECIMAL(14,2).
- `payout_multiplier` DECIMAL(10,2).
- `prize_amount` DECIMAL(14,2).
- `status` ENUM: `PENDING_RELEASE`, `RELEASED`, `PAID`, `CANCELLED`, `HELD`.
- `paid_at` DATETIME nullable.
- `paid_by` FK users nullable.
- `created_at`.
- `updated_at`.

Relaciones:

- `WinnerTicket belongsTo Company`.
- `WinnerTicket belongsTo Branch`.
- `WinnerTicket belongsTo Ticket`.
- `WinnerTicket belongsTo TicketDetail`.
- `WinnerTicket belongsTo Draw`.
- `WinnerTicket hasMany PrizePayment`.

Índices:

- INDEX `company_id, branch_id, draw_id`.
- INDEX `ticket_id`.
- INDEX `status`.
- INDEX `paid_at`.

---

## 6.18 payment_authorizations

Autorización/liberación de pagos por sorteo.

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `lottery_id` FK.
- `draw_id` FK.
- `status` ENUM: `PENDING`, `AUTHORIZED`, `REJECTED`.
- `total_winners` INT.
- `total_prize_amount` DECIMAL(14,2).
- `authorized_by` FK users nullable.
- `authorized_at` DATETIME nullable.
- `notes` TEXT nullable.
- `created_at`.
- `updated_at`.

Reglas:

- No pagar premio sin autorización.
- Crear después del cálculo de ganadores.
- Puede requerir revisión de premios grandes.

---

## 6.19 prize_payments

Pagos de premios.

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `branch_id` FK.
- `ticket_id` FK.
- `winner_ticket_id` FK.
- `cash_session_id` FK.
- `amount` DECIMAL(14,2).
- `paid_by` FK users.
- `paid_at` DATETIME.
- `status` ENUM: `PAID`, `CANCELLED`.
- `notes` TEXT nullable.
- `created_at`.
- `updated_at`.

Reglas:

- Pago reduce efectivo de caja.
- Pago genera movimiento contable.
- No pagar si `winner_ticket.status != RELEASED`.
- No pagar dos veces.

---

## 6.20 cash_sessions

Caja por sucursal y turno.

Campos:

- `id` BIGINT PK.
- `uuid` CHAR(36) UNIQUE.
- `company_id` FK.
- `branch_id` FK.
- `user_id` FK.
- `opened_by` FK users.
- `closed_by` FK users nullable.
- `opening_amount` DECIMAL(14,2).
- `expected_cash` DECIMAL(14,2) default 0.
- `counted_cash` DECIMAL(14,2) nullable.
- `sales_total` DECIMAL(14,2) default 0.
- `cancellations_total` DECIMAL(14,2) default 0.
- `prizes_paid_total` DECIMAL(14,2) default 0.
- `cash_in_total` DECIMAL(14,2) default 0.
- `cash_out_total` DECIMAL(14,2) default 0.
- `expenses_total` DECIMAL(14,2) default 0.
- `shortage_amount` DECIMAL(14,2) default 0.
- `surplus_amount` DECIMAL(14,2) default 0.
- `status` ENUM: `OPEN`, `CLOSED`, `CONFIRMED`, `REOPENED`.
- `opened_at` DATETIME.
- `closed_at` DATETIME nullable.
- `confirmed_by` FK users nullable.
- `confirmed_at` DATETIME nullable.
- `notes` TEXT nullable.
- `created_at`.
- `updated_at`.

Reglas:

- No vender sin caja abierta, salvo permiso especial.
- Un cajero puede tener una sola caja abierta por sucursal.
- Fórmula:

```text
expected_cash = opening_amount
+ sales_total
+ cash_in_total
- cancellations_total
- prizes_paid_total
- cash_out_total
- expenses_total
```

---

## 6.21 cash_movements

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `branch_id` FK.
- `cash_session_id` FK.
- `user_id` FK.
- `type` ENUM: `SALE`, `CANCELLATION`, `PRIZE_PAYMENT`, `CASH_IN`, `CASH_OUT`, `EXPENSE`, `PAYROLL_PAYMENT`, `ADJUSTMENT`.
- `amount` DECIMAL(14,2).
- `direction` ENUM: `IN`, `OUT`.
- `reference_type` VARCHAR(100) nullable.
- `reference_id` BIGINT nullable.
- `description` TEXT.
- `created_at`.
- `updated_at`.

Reglas:

- Todo movimiento de dinero pasa por esta tabla.
- Cada movimiento financiero debe generar asiento contable si `accounting_enabled`.

---

## 6.22 accounting_accounts

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `code` VARCHAR(50).
- `name` VARCHAR(150).
- `type` ENUM: `ASSET`, `LIABILITY`, `EQUITY`, `INCOME`, `EXPENSE`.
- `parent_id` FK nullable.
- `status` ENUM: `ACTIVE`, `INACTIVE`.
- `created_at`.
- `updated_at`.

Cuentas iniciales:

- `1000 Caja`.
- `1100 Caja por sucursal`.
- `1200 Banco`.
- `1300 Cuentas por cobrar empleados`.
- `2000 Cuentas por pagar`.
- `3000 Capital`.
- `4000 Ingresos por ventas de lotería`.
- `5000 Premios pagados`.
- `5100 Gastos operativos`.
- `5200 Nómina`.
- `5300 Faltantes de caja`.
- `5400 Comisiones`.

---

## 6.23 journal_entries

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `branch_id` FK nullable.
- `entry_number` VARCHAR(80).
- `entry_date` DATE.
- `description` TEXT.
- `source_type` VARCHAR(100) nullable.
- `source_id` BIGINT nullable.
- `status` ENUM: `POSTED`, `VOIDED`.
- `created_by` FK users.
- `created_at`.
- `updated_at`.

Restricciones:

- UNIQUE `company_id + entry_number`.

---

## 6.24 journal_entry_lines

Campos:

- `id` BIGINT PK.
- `journal_entry_id` FK.
- `company_id` FK.
- `branch_id` FK nullable.
- `account_id` FK.
- `debit` DECIMAL(14,2) default 0.
- `credit` DECIMAL(14,2) default 0.
- `description` TEXT nullable.
- `created_at`.
- `updated_at`.

Reglas:

- Suma de débitos = suma de créditos.

Ejemplos:

Venta:

- Débito: Caja sucursal.
- Crédito: Ingresos por ventas.

Pago de premio:

- Débito: Premios pagados.
- Crédito: Caja sucursal.

Gasto:

- Débito: Gasto operativo.
- Crédito: Caja sucursal.

---

## 6.25 employees

Campos:

- `id` BIGINT PK.
- `uuid` CHAR(36) UNIQUE.
- `company_id` FK.
- `branch_id` FK nullable.
- `user_id` FK nullable.
- `name` VARCHAR(150).
- `document_number` VARCHAR(50) nullable.
- `phone` VARCHAR(50) nullable.
- `address` VARCHAR(255) nullable.
- `position` VARCHAR(100).
- `salary_type` ENUM: `FIXED`, `COMMISSION`, `FIXED_PLUS_COMMISSION`.
- `base_salary` DECIMAL(14,2) default 0.
- `commission_percent` DECIMAL(5,2) default 0.
- `status` ENUM: `ACTIVE`, `INACTIVE`, `TERMINATED`.
- `hired_at` DATE nullable.
- `terminated_at` DATE nullable.
- `created_at`.
- `updated_at`.

Relaciones:

- `Employee belongsTo Company`.
- `Employee belongsTo Branch` nullable.
- `Employee belongsTo User` nullable.
- `Employee hasMany PayrollDetail`.
- `Employee hasMany EmployeeAdvance`.
- `Employee hasMany EmployeeLoan`.

---

## 6.26 payroll_periods

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `branch_id` FK nullable.
- `period_type` ENUM: `WEEKLY`, `BIWEEKLY`, `MONTHLY`.
- `start_date` DATE.
- `end_date` DATE.
- `status` ENUM: `OPEN`, `CALCULATED`, `APPROVED`, `PAID`, `CANCELLED`.
- `total_gross` DECIMAL(14,2) default 0.
- `total_deductions` DECIMAL(14,2) default 0.
- `total_net` DECIMAL(14,2) default 0.
- `created_by` FK users.
- `approved_by` FK users nullable.
- `approved_at` DATETIME nullable.
- `paid_by` FK users nullable.
- `paid_at` DATETIME nullable.
- `created_at`.
- `updated_at`.

---

## 6.27 payroll_details

Campos:

- `id` BIGINT PK.
- `payroll_period_id` FK.
- `company_id` FK.
- `branch_id` FK nullable.
- `employee_id` FK.
- `base_salary` DECIMAL(14,2).
- `commissions` DECIMAL(14,2) default 0.
- `bonuses` DECIMAL(14,2) default 0.
- `advances` DECIMAL(14,2) default 0.
- `loans` DECIMAL(14,2) default 0.
- `deductions` DECIMAL(14,2) default 0.
- `cash_shortages` DECIMAL(14,2) default 0.
- `gross_amount` DECIMAL(14,2).
- `net_amount` DECIMAL(14,2).
- `status` ENUM: `PENDING`, `APPROVED`, `PAID`.
- `paid_at` DATETIME nullable.
- `created_at`.
- `updated_at`.

---

## 6.28 employee_advances

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `branch_id` FK nullable.
- `employee_id` FK.
- `amount` DECIMAL(14,2).
- `balance` DECIMAL(14,2).
- `status` ENUM: `OPEN`, `PAID`, `CANCELLED`.
- `requested_at` DATE.
- `approved_by` FK users nullable.
- `paid_from_cash_session_id` FK nullable.
- `notes` TEXT nullable.
- `created_at`.
- `updated_at`.

---

## 6.29 employee_loans

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `branch_id` FK nullable.
- `employee_id` FK.
- `principal` DECIMAL(14,2).
- `balance` DECIMAL(14,2).
- `installment_amount` DECIMAL(14,2).
- `status` ENUM: `OPEN`, `PAID`, `CANCELLED`.
- `approved_by` FK users nullable.
- `created_at`.
- `updated_at`.

---

## 6.30 offline_sessions

Sesiones offline Android.

Campos:

- `id` BIGINT PK.
- `uuid` CHAR(36) UNIQUE.
- `company_id` FK.
- `branch_id` FK.
- `user_id` FK.
- `device_id` FK.
- `opened_at` DATETIME.
- `expires_at` DATETIME.
- `closed_at` DATETIME nullable.
- `max_offline_amount` DECIMAL(14,2).
- `consumed_amount` DECIMAL(14,2) default 0.
- `status` ENUM: `OPEN`, `CLOSED`, `EXPIRED`, `SYNCED`, `CONFLICT`.
- `last_sync_at` DATETIME nullable.
- `created_at`.
- `updated_at`.

Reglas:

- Android no vende offline sin sesión activa.
- La sesión offline debe expirar.
- Sesión asociada a dispositivo autorizado.

---

## 6.31 offline_limit_allocations

Campos:

- `id` BIGINT PK.
- `offline_session_id` FK.
- `company_id` FK.
- `branch_id` FK.
- `lottery_id` FK.
- `draw_id` FK.
- `bet_type_id` FK.
- `number_value` VARCHAR(20).
- `max_amount` DECIMAL(14,2).
- `consumed_amount` DECIMAL(14,2) default 0.
- `created_at`.
- `updated_at`.

Restricción:

- UNIQUE `offline_session_id + lottery_id + draw_id + bet_type_id + number_value`.

---

## 6.32 sync_batches

Campos:

- `id` BIGINT PK.
- `uuid` CHAR(36) UNIQUE.
- `company_id` FK.
- `branch_id` FK.
- `device_id` FK.
- `user_id` FK.
- `status` ENUM: `PENDING`, `PROCESSING`, `COMPLETED`, `FAILED`, `PARTIAL_CONFLICT`.
- `total_items` INT.
- `successful_items` INT default 0.
- `failed_items` INT default 0.
- `conflict_items` INT default 0.
- `started_at` DATETIME nullable.
- `finished_at` DATETIME nullable.
- `created_at`.
- `updated_at`.

---

## 6.33 sync_conflicts

Campos:

- `id` BIGINT PK.
- `sync_batch_id` FK.
- `company_id` FK.
- `branch_id` FK.
- `device_id` FK.
- `ticket_uuid` CHAR(36) nullable.
- `conflict_type` ENUM: `DUPLICATE`, `DRAW_CLOSED`, `LIMIT_EXCEEDED`, `INVALID_DATA`, `DEVICE_BLOCKED`, `UNKNOWN`.
- `payload` JSON.
- `resolution` ENUM: `PENDING`, `ACCEPTED`, `REJECTED`, `MANUAL_REVIEW`.
- `resolved_by` FK nullable.
- `resolved_at` DATETIME nullable.
- `created_at`.
- `updated_at`.

---

## 6.34 printer_configs

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `branch_id` FK nullable.
- `device_id` FK nullable.
- `name` VARCHAR(150).
- `printer_type` ENUM: `THERMAL`, `NORMAL`.
- `connection_type` ENUM: `USB`, `NETWORK`, `WINDOWS_SHARED`, `BLUETOOTH`.
- `paper_width` ENUM: `58MM`, `80MM`.
- `printer_identifier` VARCHAR(255).
- `status` ENUM: `ACTIVE`, `INACTIVE`.
- `created_at`.
- `updated_at`.

---

## 6.35 print_jobs

Campos:

- `id` BIGINT PK.
- `uuid` CHAR(36) UNIQUE.
- `company_id` FK.
- `branch_id` FK.
- `ticket_id` FK nullable.
- `printer_config_id` FK nullable.
- `device_id` FK nullable.
- `type` ENUM: `TICKET`, `REPRINT`, `CASH_CLOSING`, `PRIZE_PAYMENT`, `TEST`.
- `content` TEXT.
- `status` ENUM: `PENDING`, `PRINTED`, `FAILED`, `CANCELLED`.
- `attempts` INT default 0.
- `error_message` TEXT nullable.
- `printed_at` DATETIME nullable.
- `created_at`.
- `updated_at`.

---

## 6.36 alerts

Campos:

- `id` BIGINT PK.
- `company_id` FK.
- `branch_id` FK nullable.
- `type` ENUM: `LOW_CREDIT`, `LIMIT_REACHED`, `NUMBER_HOT`, `BRANCH_OFFLINE`, `NO_SALES`, `BIG_PRIZE`, `CASH_SHORTAGE`, `CASH_SURPLUS`, `SYNC_CONFLICT`, `PRINTER_ERROR`, `UNAUTHORIZED_ACCESS`, `RESULT_PENDING`, `PAYMENT_PENDING`.
- `severity` ENUM: `INFO`, `WARNING`, `CRITICAL`.
- `title` VARCHAR(180).
- `message` TEXT.
- `status` ENUM: `OPEN`, `RESOLVED`, `DISMISSED`.
- `related_type` VARCHAR(100) nullable.
- `related_id` BIGINT nullable.
- `resolved_by` FK nullable.
- `resolved_at` DATETIME nullable.
- `created_at`.
- `updated_at`.

---

## 6.37 audit_logs

Campos:

- `id` BIGINT PK.
- `company_id` FK nullable.
- `branch_id` FK nullable.
- `user_id` FK nullable.
- `device_id` FK nullable.
- `module` VARCHAR(100).
- `action` VARCHAR(100).
- `auditable_type` VARCHAR(150) nullable.
- `auditable_id` BIGINT nullable.
- `description` TEXT.
- `old_values` JSON nullable.
- `new_values` JSON nullable.
- `ip_address` VARCHAR(80) nullable.
- `user_agent` TEXT nullable.
- `created_at`.

Auditar:

- Login.
- Logout.
- Venta.
- Venta offline.
- Sincronización.
- Anulación.
- Reimpresión.
- Cambio de límite.
- Cambio de pago.
- Registro de resultado.
- Confirmación de resultado.
- Cálculo de ganadores.
- Liberación de pagos.
- Pago de premio.
- Apertura de caja.
- Cierre de caja.
- Reapertura de caja.
- Gastos.
- Entradas.
- Salidas.
- Nómina.
- Préstamos.
- Avances.
- Cambios de permisos.
- Cambios de configuración.
- Bloqueo de dispositivo.
- Error de impresión.

Índices:

- INDEX `company_id, branch_id, created_at`.
- INDEX `user_id, created_at`.
- INDEX `module, action`.
- INDEX `auditable_type, auditable_id`.

---

# 7. Flujos críticos

## 7.1 Venta online

Debe ejecutarse dentro de una transacción de base de datos.

Pasos:

1. Validar sesión.
2. Validar usuario activo.
3. Validar empresa activa.
4. Validar sucursal activa.
5. Validar caja abierta.
6. Validar dispositivo si aplica.
7. Validar lotería activa.
8. Validar sorteo abierto.
9. Validar hora de cierre.
10. Validar tipos de jugadas.
11. Resolver regla de pago aplicable.
12. Validar límites aplicables.
13. Bloquear `limit_consumptions` con row locking.
14. Crear ticket.
15. Crear ticket details.
16. Actualizar `limit_consumptions`.
17. Actualizar totales de `cash_sessions`.
18. Crear `cash_movement` de venta.
19. Crear asiento contable si aplica.
20. Crear `print_job`.
21. Registrar auditoría.
22. Confirmar transacción.

Si algo falla, hacer rollback total.

---

## 7.2 Venta offline Android

La venta offline no puede ser libre.

Solo se permite si:

- La sucursal tiene `can_sell_offline = true`.
- El dispositivo Android está autorizado.
- El usuario tiene permiso.
- Hay sesión offline vigente.
- Hay sorteos cacheados.
- Hay cupos offline descargados.
- La hora local está dentro del margen permitido.
- No se agotó el cupo offline total.
- No se agotó el cupo offline por número.
- No se agotó el cupo por tipo de jugada.

Android guarda localmente:

- `ticket_uuid`.
- `company_id`.
- `branch_id`.
- `user_id`.
- `device_id`.
- `offline_session_uuid`.
- número de ticket local.
- fecha/hora local.
- lotería.
- sorteo.
- jugadas.
- monto.
- multiplicador aplicado.
- posible premio.
- cupo consumido.
- estado `PENDING_SYNC`.

Al sincronizar:

1. Enviar lote `sync_batch`.
2. Validar idempotency key/ticket UUID.
3. Validar dispositivo.
4. Validar usuario.
5. Validar sesión offline.
6. Validar que el ticket no exista.
7. Registrar ticket.
8. Registrar detalles.
9. Marcar sincronizado.
10. Crear conflicto si algo no cuadra.
11. Registrar auditoría.

Si al sincronizar el sorteo ya estaba cerrado:

- Si la venta fue antes de la hora de cierre cacheada y dentro del margen autorizado, puede aceptarse.
- Si fue después del cierre o fuera del margen, marcar conflicto `DRAW_CLOSED`.
- El conflicto requiere revisión manual.

---

## 7.3 Resultados y pagos

Flujo:

1. Cerrar sorteo.
2. Registrar números ganadores.
3. Validar formato.
4. Guardar resultado como `REGISTERED`.
5. Confirmar resultado por usuario autorizado.
6. Evitar que el mismo usuario registre y confirme, salvo permiso especial.
7. Calcular ganadores mediante Job.
8. Crear `winner_tickets`.
9. Crear `payment_authorization` pendiente.
10. Revisar premios grandes.
11. Autorizar/liberar pagos.
12. Cambiar `winner_tickets` a `RELEASED`.
13. Permitir pagos.

No permitir pagar si:

- Resultado no confirmado.
- Ganadores no calculados.
- Pagos no autorizados.
- Ticket anulado.
- Jugada no ganadora.
- Ticket ya pagado.
- Caja no abierta.
- Usuario sin permiso.

---

## 7.4 Caja

Funciones:

- Apertura de caja.
- Caja por turno.
- Ventas.
- Anulaciones.
- Premios pagados.
- Gastos.
- Entradas.
- Salidas.
- Nómina pagada desde caja.
- Efectivo esperado.
- Efectivo contado.
- Faltante.
- Sobrante.
- Cierre.
- Confirmación.
- Reapertura autorizada.

No permitir vender sin caja abierta salvo permiso especial.

Cierre:

```text
expected_cash = opening_amount
+ sales_total
+ cash_in_total
- cancellations_total
- prizes_paid_total
- cash_out_total
- expenses_total
```

Si hay faltante:

- Registrar en caja.
- Generar alerta.
- Puede generar deducción al empleado si se confirma.

---

## 7.5 Contabilidad

Toda operación financiera genera asiento contable.

Venta:

- Débito: Caja sucursal.
- Crédito: Ingresos por ventas de lotería.

Pago de premio:

- Débito: Premios pagados.
- Crédito: Caja sucursal.

Gasto:

- Débito: Gasto operativo.
- Crédito: Caja sucursal.

Pago de nómina:

- Débito: Gasto de nómina.
- Crédito: Caja/Banco.

Faltante confirmado:

- Débito: Cuenta por cobrar empleado.
- Crédito: Caja/Faltantes según configuración.

---

# 8. Servicios Laravel requeridos

Crear estos servicios, evitando lógica pesada en controladores:

1. `TicketSaleService`.
2. `LimitValidationService`.
3. `PayoutResolverService`.
4. `CashService`.
5. `ResultService`.
6. `WinnerCalculationService`.
7. `PaymentAuthorizationService`.
8. `PrizePaymentService`.
9. `AccountingService`.
10. `PayrollService`.
11. `OfflineSyncService`.
12. `PrintJobService`.
13. `AuditService`.
14. `DeviceAuthorizationService`.
15. `ReportService`.

Responsabilidades:

## TicketSaleService

- Validar venta.
- Resolver pagos.
- Validar límites.
- Crear ticket.
- Actualizar caja.
- Actualizar límites.
- Crear movimiento contable.
- Crear impresión.
- Auditar.

## LimitValidationService

- Resolver límites aplicables.
- Calcular disponibilidad.
- Aplicar política.
- Bloquear filas de consumo.
- Evitar sobreventa.

## PayoutResolverService

- Resolver regla más específica.
- Guardar multiplicador en `ticket_details`.
- Respetar pagos por sucursal.

## WinnerCalculationService

- Leer resultado confirmado.
- Buscar jugadas del sorteo.
- Determinar ganadores.
- Crear `winner_tickets`.
- Generar autorización de pagos.

## OfflineSyncService

- Procesar lotes Android.
- Validar duplicados.
- Validar sesión offline.
- Crear tickets sincronizados.
- Crear conflictos.
- Auditar.

## AccountingService

- Crear asientos automáticos.
- Validar débitos y créditos.
- Relacionar asientos con operaciones.

---

# 9. API REST principal

## Auth

- `POST /api/login`
- `POST /api/logout`
- `GET /api/me`
- `POST /api/refresh`

## Companies

- `GET /api/companies`
- `POST /api/companies`
- `GET /api/companies/{id}`
- `PUT /api/companies/{id}`

## Branches

- `GET /api/branches`
- `POST /api/branches`
- `GET /api/branches/{id}`
- `PUT /api/branches/{id}`

## Users

- `GET /api/users`
- `POST /api/users`
- `GET /api/users/{id}`
- `PUT /api/users/{id}`

## Roles

- `GET /api/roles`
- `POST /api/roles`
- `PUT /api/roles/{id}`
- `POST /api/roles/{id}/permissions`

## Employees

- `GET /api/employees`
- `POST /api/employees`
- `GET /api/employees/{id}`
- `PUT /api/employees/{id}`

## Lotteries

- `GET /api/lotteries`
- `POST /api/lotteries`
- `PUT /api/lotteries/{id}`

## Draws

- `GET /api/draws`
- `POST /api/draws`
- `PUT /api/draws/{id}`
- `POST /api/draws/{id}/close`

## Payout Rules

- `GET /api/payout-rules`
- `POST /api/payout-rules`
- `PUT /api/payout-rules/{id}`
- `POST /api/payout-rules/{id}/approve`
- `POST /api/payout-rules/copy-branch`

## Limit Rules

- `GET /api/limit-rules`
- `POST /api/limit-rules`
- `PUT /api/limit-rules/{id}`
- `POST /api/limit-rules/import`
- `POST /api/limit-rules/copy-branch`
- `GET /api/limit-consumptions`

## Tickets

- `POST /api/tickets/preview`
- `POST /api/tickets`
- `GET /api/tickets`
- `GET /api/tickets/{id}`
- `POST /api/tickets/{id}/cancel`
- `POST /api/tickets/{id}/reprint`

## Results

- `POST /api/results`
- `POST /api/results/{id}/confirm`
- `POST /api/draws/{id}/calculate-winners`
- `POST /api/draws/{id}/authorize-payments`

## Prize Payments

- `GET /api/prizes/pending`
- `POST /api/prizes/{winnerTicketId}/pay`

## Cash

- `POST /api/cash/open`
- `GET /api/cash/current`
- `POST /api/cash/movements`
- `POST /api/cash/close`
- `POST /api/cash/{id}/confirm`
- `POST /api/cash/{id}/reopen`

## Accounting

- `GET /api/accounting/accounts`
- `POST /api/accounting/accounts`
- `GET /api/accounting/journal-entries`
- `POST /api/accounting/journal-entries`
- `GET /api/accounting/reports/income-statement`
- `GET /api/accounting/reports/cash-flow`

## Payroll

- `GET /api/payroll/periods`
- `POST /api/payroll/periods`
- `POST /api/payroll/periods/{id}/calculate`
- `POST /api/payroll/periods/{id}/approve`
- `POST /api/payroll/periods/{id}/pay`

## Offline

- `POST /api/offline/session/open`
- `GET /api/offline/bootstrap`
- `POST /api/offline/sync`
- `GET /api/offline/conflicts`
- `POST /api/offline/conflicts/{id}/resolve`

## Devices

- `GET /api/devices`
- `POST /api/devices/register`
- `POST /api/devices/{id}/authorize`
- `POST /api/devices/{id}/block`

## Printers

- `GET /api/printers`
- `POST /api/printers`
- `POST /api/printers/test`
- `POST /api/print-jobs`

## Reports

- `GET /api/reports/sales`
- `GET /api/reports/tickets`
- `GET /api/reports/prizes`
- `GET /api/reports/cash`
- `GET /api/reports/limits`
- `GET /api/reports/accounting`
- `GET /api/reports/payroll`
- `GET /api/reports/audit`

## Audit

- `GET /api/audit-logs`

---

# 10. Pantallas principales

## Web PC

1. Login.
2. Dashboard principal.
3. Empresas.
4. Sucursales/Bancas.
5. Usuarios.
6. Roles y permisos.
7. Empleados.
8. Loterías.
9. Sorteos.
10. Tipos de jugadas.
11. Reglas de pago.
12. Reglas de límites.
13. Venta rápida.
14. Consulta de tickets.
15. Anulación.
16. Reimpresión.
17. Resultados.
18. Cálculo de ganadores.
19. Autorización de pagos.
20. Pago de premios.
21. Caja.
22. Gastos.
23. Entradas/salidas.
24. Cierre de caja.
25. Contabilidad.
26. Nómina.
27. Dispositivos.
28. Impresoras.
29. Offline Android.
30. Alertas.
31. Auditoría.
32. Reportes.

## Android

1. Login.
2. Validación de dispositivo.
3. Dashboard de cajero.
4. Venta rápida.
5. Historial de tickets.
6. Reimpresión.
7. Anulación si tiene permiso.
8. Caja.
9. Cierre.
10. Configuración de impresora Bluetooth.
11. Estado de sincronización.
12. Modo offline controlado.
13. Alertas básicas.

---

# 11. Pantalla de venta

Debe ser rápida y optimizada para cajeros.

Requisitos:

- Compatible con teclado físico.
- Validación de límites en tiempo real.
- Estado de caja visible.
- Estado de conexión visible.
- Estado de impresora visible.
- Total visible.
- Posible premio visible.
- Sorteos abiertos visibles.
- Loterías activas visibles.

Atajos sugeridos:

- `F1` nueva venta.
- `F2` buscar ticket.
- `F3` reimprimir.
- `F4` anular.
- `F5` actualizar sorteos.
- `F8` limpiar.
- `F9` cobrar/confirmar.
- `ESC` cancelar línea.

Entrada rápida:

```text
25 100
36 50
25-36 20
123 10
```

Interpretación configurable:

- `25 100` = Quiniela.
- `25-36 20` = Pale.
- `123 10` = Tripleta.

---

# 12. Reglas específicas de límites

El sistema debe validar límites por:

- Empresa.
- Sucursal.
- Lotería.
- Sorteo.
- Tipo de jugada.
- Número individual.
- Lista exacta de números.
- Rango de números.
- Combinación.

Cuando existan varios límites aplicables:

1. Evaluar todos.
2. Tomar el disponible más restrictivo.
3. Si no hay límite configurado, usar límite por defecto de empresa.
4. Si la política es `BLOCK_FULL`, rechazar toda la jugada.
5. Si la política es `ALLOW_AVAILABLE`, permitir solo monto disponible.
6. Si la política es `REQUEST_AUTHORIZATION`, crear solicitud pendiente.

Pantallas necesarias:

- Crear límite individual.
- Crear límite por rango.
- Crear límite por lista.
- Copiar límites de una sucursal a otra.
- Importar límites desde Excel.
- Exportar límites a Excel.
- Ver consumo en tiempo real.
- Ver números cerca del límite.
- Bloquear números.

---

# 13. Reglas específicas de pagos por sucursal

El sistema debe permitir que cada sucursal pague diferente.

Ejemplo:

```text
Empresa:
Quiniela FIRST = 80 x 1

Sucursal 1:
Hereda empresa = 80 x 1

Sucursal 2:
Personaliza = 75 x 1

Sucursal 3:
Personaliza = 70 x 1
```

Configurable por:

- Empresa.
- Sucursal.
- Lotería.
- Sorteo.
- Tipo de jugada.
- Posición.
- Vigencia por fecha.

Cada ticket debe guardar:

- `payout_rule_id`.
- `payout_multiplier`.
- `possible_prize`.

Nunca recalcular premio de ticket viejo usando configuración nueva.

---

# 14. Buenas prácticas Laravel

Obligatorio:

1. Controladores delgados.
2. Services para lógica de negocio.
3. Form Requests para validación.
4. Policies/Gates para permisos.
5. Enums para estados.
6. Jobs para tareas pesadas.
7. Events/Listeners para auditoría y contabilidad.
8. Transactions en ventas, anulaciones, pagos y cierres.
9. DB locking en consumo de límites.
10. API Resources para respuestas.
11. DTOs cuando aporten claridad.
12. Seeders para permisos, roles, cuentas contables, tipos de jugadas.
13. Tests para venta, límites, caja, resultados y pagos.
14. No poner lógica financiera en vistas o controladores.
15. No confiar en datos enviados desde frontend/Android.
16. Validar siempre en servidor.

---

# 15. Criterios de aceptación global

El diseño será correcto solo si permite:

1. Crear empresa.
2. Crear 500 sucursales/bancas.
3. Cada sucursal vende tickets.
4. Cada sucursal puede tener pagos diferentes.
5. Cada sucursal puede tener límites diferentes.
6. Crear límite para número individual.
7. Crear límite para rango 00-50 con RD$1000 por cada número.
8. Crear límite para lista exacta de números.
9. Vender online sin romper límites.
10. Dos cajeros vendiendo al mismo tiempo no pueden pasar el límite.
11. Android puede vender offline solo con cupos autorizados.
12. Sin cupo offline, Android no vende.
13. Si hay conflicto offline, se registra y se revisa.
14. Ticket guarda multiplicador usado.
15. Cambios futuros de pago no afectan tickets viejos.
16. Resultado requiere confirmación.
17. Pagos requieren autorización/liberación.
18. No se paga premio sin autorización.
19. Caja debe estar abierta para vender.
20. Cierre calcula efectivo esperado.
21. Cierre detecta faltante/sobrante.
22. Nómina permite sueldo, comisión, avances, préstamos y deducciones.
23. Contabilidad registra ventas, premios, gastos y nómina.
24. Auditoría guarda todo evento crítico.
25. Reportes filtran por empresa, sucursal, cajero, fecha, lotería y sorteo.
26. El sistema puede paginar grandes volúmenes.
27. No hay consultas masivas sin índices.
28. No se usa `FLOAT` para dinero.
29. No se borran datos financieros.
30. El menú se muestra según permisos.

---

# 16. Regla de trabajo con diferentes IAs

Antes de empezar cualquier fase, la IA debe leer este archivo y el archivo:

```text
02_GUIA_TODO_CONTROL_BSLOTTERY.md
```

La IA debe:

1. Revisar el estado actual.
2. Identificar la fase activa.
3. No saltar fases sin autorización.
4. Marcar como completado cada punto terminado.
5. Agregar notas técnicas cuando tome decisiones.
6. Agregar advertencias si detecta riesgo.
7. No borrar historial de avances.
8. No cambiar reglas de negocio sin confirmación del dueño del proyecto.
9. No generar código si antes no están completos los puntos de diseño de la fase.
10. Al terminar cada bloque, actualizar la sección de progreso del archivo de control.

---

# 17. Instrucción final para la IA

No empieces escribiendo código inmediatamente.

Primero entrega:

1. Arquitectura general.
2. Diagrama de módulos.
3. Diagrama entidad-relación.
4. Base de datos validada.
5. Relaciones Eloquent.
6. Índices recomendados.
7. Reglas de negocio.
8. Flujos críticos.
9. Plan por fases.
10. Riesgos técnicos.
11. Orden recomendado de implementación.

Después pregunta qué fase se desea construir primero.

Cuando construyas código:

- Da archivos completos.
- Indica ruta exacta de cada archivo.
- No omitas validaciones.
- No elimines funcionalidades sin preguntar.
- No inventes nombres inconsistentes.
- Respeta Laravel 12.
- Respeta Android/Kotlin profesional.
- Respeta Windows/Apache para despliegue.
- Mantén el archivo de control actualizado.
