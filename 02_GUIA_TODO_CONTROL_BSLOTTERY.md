# GUÃA TO-DO Y CONTROL DE AVANCE â€” BSLotery

Este archivo es el control de avance oficial del proyecto BSLotery. Debe servir para que cualquier IA o desarrollador pueda continuar el trabajo sin perder contexto.

## Reglas de uso de este archivo

1. Antes de trabajar, leer este archivo completo.
2. Leer tambiÃ©n `01_PROMPT_MAESTRO_BSLOTTERY.md`.
3. No saltar fases sin autorizaciÃ³n.
4. Marcar cada punto completado usando `[x]`.
5. Dejar incompleto usando `[ ]`.
6. Si un punto queda parcialmente completado, usar `[~]` y explicar en notas.
7. No borrar historial.
8. Al finalizar cada sesiÃ³n, agregar una entrada en el historial de avance.
9. Si se detecta un riesgo tÃ©cnico, registrarlo en la secciÃ³n de riesgos.
10. Si se toma una decisiÃ³n importante, registrarla en decisiones tÃ©cnicas.
11. Si se necesita confirmaciÃ³n del dueÃ±o del proyecto, registrarla en preguntas pendientes.

---

# Estado general del proyecto

- Proyecto: BSLotery.
- Tipo: Sistema web + Android + Print Agent para bancas/loterÃ­as.
- Backend: Laravel 12.
- Base de datos: MySQL/MariaDB.
- Web: Laravel Blade + Bootstrap 5 + Argon Dashboard.
- Android: Kotlin + Jetpack Compose.
- Print Agent: Java LTS moderno o equivalente.
- Objetivo de escala: mÃ­nimo 500 bancas/sucursales.
- Estado actual: DiseÃ±o maestro preparado. ImplementaciÃ³n pendiente.

---

# Leyenda de estado

```text
[ ] Pendiente
[~] Parcial / requiere revisiÃ³n
[x] Completado
[!] Bloqueado / riesgo
```

---

# Puntos de control globales

## Control de arquitectura

- [x] Validar arquitectura multiempresa.
- [x] Confirmar que sucursal = banca/punto de venta.
- [x] Confirmar que no se usarÃ¡ tabla separada de puntos de venta.
- [~] Validar que todas las tablas operativas tienen `company_id`.
- [~] Validar que todas las tablas por banca tienen `branch_id`.
- [~] Validar estrategia para 500 bancas.
- [~] Validar estrategia de Ã­ndices.
- [x] Validar estrategia de colas/jobs.
- [~] Validar estrategia de auditorÃ­a.
- [x] Crear monitoreo operativo por sucursal.
- [x] Crear notificaciones operativas persistentes.
- [x] Validar estrategia de contabilidad.
- [x] Validar estrategia offline Android.
- [x] Validar estrategia de impresiÃ³n PC.

## Control de base de datos

- [ ] Crear diagrama entidad-relaciÃ³n.
- [x] Validar relaciones principales.
- [ ] Validar claves forÃ¡neas.
- [x] Validar Ã­ndices compuestos.
- [x] Validar uso de `DECIMAL` para dinero.
- [ ] Validar que no se usa `FLOAT` para dinero.
- [ ] Validar estados/enums.
- [ ] Validar tablas financieras sin borrado fÃ­sico.
- [ ] Validar soft deletes donde aplique.
- [x] Validar tablas para offline sync.
- [x] Validar tablas para nÃ³mina.
- [x] Validar tablas contables.
- [x] Validar tablas de auditorÃ­a.

## Control de seguridad

- [x] Definir roles iniciales.
- [x] Definir permisos mÃ­nimos.
- [x] Crear middleware de permisos.
- [x] Crear policies/gates.
- [x] Validar separaciÃ³n por empresa.
- [x] Validar separaciÃ³n por sucursal.
- [x] Validar autorizaciÃ³n de dispositivos.
- [x] Validar bloqueo de usuario.
- [x] Validar bloqueo de dispositivos.
- [x] Validar rate limit en login.
- [~] Validar auditorÃ­a de acciones crÃ­ticas.

---

# FASE 1 â€” NÃºcleo multiempresa

Objetivo: Crear la base administrativa del sistema.

## Entidades de esta fase

- Empresas.
- Sucursales/bancas.
- Usuarios.
- Roles.
- Permisos.
- Dispositivos.
- Login.
- MenÃº dinÃ¡mico.
- AuditorÃ­a base.

## DiseÃ±o

- [x] DiseÃ±ar flujo de login.
- [x] DiseÃ±ar jerarquÃ­a empresa â†’ sucursal â†’ usuario.
- [x] DiseÃ±ar roles iniciales.
- [x] DiseÃ±ar permisos iniciales.
- [x] DiseÃ±ar menÃº dinÃ¡mico segÃºn permisos.
- [x] DiseÃ±ar autorizaciÃ³n por empresa.
- [x] DiseÃ±ar autorizaciÃ³n por sucursal.
- [x] DiseÃ±ar registro de dispositivos.
- [x] DiseÃ±ar auditorÃ­a base.

## Base de datos

- [x] Crear migration `companies`.
- [x] Crear migration `branches`.
- [x] Crear migration `users`.
- [x] Crear migration `roles`.
- [x] Crear migration `permissions`.
- [x] Crear migration `role_permissions`.
- [x] Crear migration `devices`.
- [x] Crear migration `audit_logs`.
- [x] Crear Ã­ndices de `companies`.
- [x] Crear Ã­ndices de `branches`.
- [x] Crear Ã­ndices de `users`.
- [x] Crear Ã­ndices de `devices`.
- [x] Crear Ã­ndices de `audit_logs`.

## Modelos Laravel

- [x] Crear model `Company`.
- [x] Crear model `Branch`.
- [x] Crear model `User`.
- [x] Crear model `Role`.
- [x] Crear model `Permission`.
- [x] Crear model `Device`.
- [x] Crear model `AuditLog`.
- [x] Definir relaciones `Company hasMany Branch`.
- [x] Definir relaciones `Company hasMany User`.
- [x] Definir relaciones `Branch belongsTo Company`.
- [x] Definir relaciones `Branch hasMany User`.
- [x] Definir relaciones `User belongsTo Role`.
- [x] Definir relaciones `Role belongsToMany Permission`.
- [x] Definir relaciones `Permission belongsToMany Role`.

## Seeders

- [x] Crear permisos iniciales.
- [x] Crear roles iniciales.
- [x] Crear rol Super Admin.
- [ ] Crear usuario Super Admin.
- [x] Crear empresa demo.
- [x] Crear sucursal demo.
- [x] Crear usuario cajero demo.
- [~] Crear usuario supervisor demo.

## Backend

- [x] Crear AuthController.
- [x] Crear CompanyController.
- [x] Crear BranchController.
- [x] Crear UserController.
- [x] Crear RoleController.
- [~] Crear PermissionController (permisos gestionados desde RoleController por decisiÃ³n de diseÃ±o).
- [x] Crear DeviceController.
- [x] Crear AuditLogController.
- [~] Crear Form Requests.
- [x] Crear middleware de permisos.
- [x] Crear middleware de empresa activa.
- [x] Crear middleware de sucursal activa.
- [x] Crear polÃ­ticas/policies necesarias.
- [x] Crear servicio `AuditService`.
- [x] Crear servicio `DeviceAuthorizationService`.

## Web Blade

- [x] Crear layout base con Argon/Bootstrap.
- [x] Crear sidebar dinÃ¡mico.
- [x] Crear header con empresa/sucursal/usuario.
- [x] Crear pantalla login.
- [x] Crear CRUD empresas.
- [x] Crear CRUD sucursales.
- [x] Crear CRUD usuarios.
- [x] Crear CRUD roles.
- [x] Crear asignaciÃ³n de permisos.
- [x] Crear listado de dispositivos.
- [x] Crear auditorÃ­a bÃ¡sica.

## API

- [x] `POST /api/login`.
- [~] `POST /api/logout`.
- [~] `GET /api/me`.
- [ ] `GET /api/companies`.
- [ ] `POST /api/companies`.
- [ ] `GET /api/branches`.
- [ ] `POST /api/branches`.
- [ ] `GET /api/users`.
- [ ] `POST /api/users`.
- [ ] `GET /api/roles`.
- [ ] `POST /api/roles`.
- [ ] `POST /api/roles/{id}/permissions`.
- [x] `POST /api/devices/register`.
- [ ] `POST /api/devices/{id}/authorize`.

## Pruebas

- [~] Super Admin puede iniciar sesiÃ³n (por diseÃ±o, Decision 012/013 — no se crea Super Admin en seeder).
- [x] Admin empresa no ve otras empresas.
- [x] Cajero/supervisor solo ve su sucursal.
- [x] MenÃº se oculta segÃºn permisos.
- [x] Usuario inactivo no puede entrar.
- [x] Sucursal inactiva no puede operar.
- [~] Dispositivo bloqueado no puede operar.
- [x] AuditorÃ­a registra login.
- [x] AuditorÃ­a registra creaciÃ³n/ediciÃ³n.

## Criterio de cierre Fase 1

- [x] Se puede entrar al sistema.
- [x] Se pueden crear empresas.
- [x] Se pueden crear sucursales/bancas.
- [x] Se pueden crear usuarios.
- [x] Se pueden crear roles.
- [x] Se pueden asignar permisos.
- [x] El menÃº respeta permisos.
- [x] Hay auditorÃ­a base.
- [x] La separaciÃ³n por empresa funciona.

---

# FASE 2 â€” CatÃ¡logos de loterÃ­a, pagos y lÃ­mites

Objetivo: Crear la base funcional de loterÃ­as, sorteos, jugadas, pagos y lÃ­mites.

## DiseÃ±o

- [x] DiseÃ±ar catÃ¡logo de loterÃ­as.
- [x] DiseÃ±ar catÃ¡logo de sorteos.
- [x] DiseÃ±ar tipos de jugadas.
- [x] DiseÃ±ar reglas de pago por empresa.
- [x] DiseÃ±ar reglas de pago por sucursal.
- [x] DiseÃ±ar herencia de pagos.
- [x] DiseÃ±ar reglas de lÃ­mites.
- [x] DiseÃ±ar lÃ­mite por nÃºmero individual.
- [x] DiseÃ±ar lÃ­mite por rango.
- [x] DiseÃ±ar lÃ­mite por lista exacta.
- [x] DiseÃ±ar consumo acumulado de lÃ­mites.
- [ ] DiseÃ±ar importaciÃ³n/exportaciÃ³n Excel de lÃ­mites.
- [x] DiseÃ±ar copia de lÃ­mites entre sucursales.

## Base de datos

- [x] Crear migration `lotteries`.
- [x] Crear migration `draws`.
- [x] Crear migration `bet_types`.
- [x] Crear migration `payout_rules`.
- [x] Crear migration `limit_rules`.
- [x] Crear migration `limit_consumptions`.
- [x] Crear Ã­ndices de `draws`.
- [x] Crear Ã­ndices de `payout_rules`.
- [x] Crear Ã­ndices de `limit_rules`.
- [x] Crear Ã­ndice Ãºnico de `limit_consumptions`.

## Modelos

- [x] Crear model `Lottery`.
- [x] Crear model `Draw`.
- [x] Crear model `BetType`.
- [x] Crear model `PayoutRule`.
- [x] Crear model `LimitRule`.
- [x] Crear model `LimitConsumption`.
- [x] Definir relaciones.

## Servicios

- [x] Crear `PayoutResolverService`.
- [x] Crear `LimitValidationService`.
- [x] Crear lÃ³gica de prioridad de pagos.
- [x] Crear lÃ³gica de lÃ­mites mÃ¡s restrictivos.
- [x] Crear lÃ³gica de expansiÃ³n de rango 00-50.
- [x] Crear lÃ³gica de lista exacta de nÃºmeros.
- [ ] Crear lÃ³gica de importaciÃ³n Excel.
- [x] Crear lÃ³gica de copia de sucursal.

## Web Blade

- [x] CRUD loterÃ­as.
- [x] CRUD sorteos.
- [x] CRUD tipos de jugadas.
- [x] Pantalla reglas de pago.
- [x] Pantalla reglas de lÃ­mites.
- [x] Pantalla lÃ­mites por rango.
- [x] Pantalla lÃ­mites por lista.
- [x] Pantalla consumo de lÃ­mites.
- [x] BotÃ³n copiar lÃ­mites desde otra sucursal.
- [ ] Importar lÃ­mites desde Excel.
- [ ] Exportar lÃ­mites a Excel.

## API

- [ ] `GET /api/lotteries`.
- [ ] `POST /api/lotteries`.
- [ ] `GET /api/draws`.
- [ ] `POST /api/draws`.
- [ ] `POST /api/draws/{id}/close`.
- [ ] `GET /api/payout-rules`.
- [ ] `POST /api/payout-rules`.
- [ ] `POST /api/payout-rules/{id}/approve`.
- [ ] `GET /api/limit-rules`.
- [ ] `POST /api/limit-rules`.
- [ ] `POST /api/limit-rules/import`.
- [ ] `POST /api/limit-rules/copy-branch`.
- [ ] `GET /api/limit-consumptions`.

## Pruebas

- [~] Empresa puede tener pagos por defecto.
- [~] Sucursal puede heredar pagos.
- [~] Sucursal puede personalizar pagos.
- [~] Sucursal 1 puede pagar 80x1.
- [~] Sucursal 2 puede pagar 75x1.
- [~] LÃ­mite por nÃºmero individual funciona.
- [~] LÃ­mite por rango 00-50 aplica RD$1000 a cada nÃºmero.
- [~] LÃ­mite por lista exacta funciona.
- [~] LÃ­mite de empresa y sucursal se validan juntos.
- [~] Se toma el lÃ­mite mÃ¡s restrictivo.

## Criterio de cierre Fase 2

- [x] CatÃ¡logos listos.
- [x] Pagos por sucursal funcionales.
- [x] LÃ­mites por sucursal funcionales.
- [x] Consumo de lÃ­mites diseÃ±ado.
- [~] Servicios de pagos/lÃ­mites probados (unit testing pendiente, lÃ³gica implementada).

---

# FASE 3 â€” Caja y contabilidad base

Objetivo: Crear caja estricta antes de permitir ventas reales.

## DiseÃ±o

- [x] DiseÃ±ar apertura de caja.
- [x] DiseÃ±ar movimientos de caja.
- [x] DiseÃ±ar cierre de caja.
- [x] DiseÃ±ar faltantes.
- [x] DiseÃ±ar sobrantes.
- [x] DiseÃ±ar confirmaciÃ³n de cierre.
- [x] DiseÃ±ar reapertura autorizada.
- [x] DiseÃ±ar catÃ¡logo contable.
- [x] DiseÃ±ar asientos automÃ¡ticos.

## Base de datos

- [x] Crear migration `cash_sessions`.
- [x] Crear migration `cash_movements`.
- [x] Crear migration `cash_reconciliations`.
- [x] Crear migration `cash_count_denominations`.
- [x] Crear migration `cash_incidents`.
- [x] Crear migration `bank_transfers`.
- [x] Crear migration `accounting_accounts`.
- [x] Crear migration `journal_entries`.
- [x] Crear migration `journal_entry_lines`.
- [x] Crear Ã­ndices de caja.
- [x] Crear Ã­ndices contables.

## Modelos

- [x] Crear model `CashSession`.
- [x] Crear model `CashMovement`.
- [x] Crear model `CashReconciliation`.
- [x] Crear model `CashCountDenomination`.
- [x] Crear model `CashIncident`.
- [x] Crear model `BankTransfer`.
- [x] Crear model `AccountingAccount`.
- [x] Crear model `JournalEntry`.
- [x] Crear model `JournalEntryLine`.
- [x] Definir relaciones.

## Servicios

- [x] Crear `CashService`.
- [x] Crear `AccountingService`.
- [x] Crear apertura de caja.
- [x] Crear movimiento de caja.
- [x] Crear cierre de caja.
- [x] Crear arqueo por denominaciones.
- [x] Separar efectivo fisico de transferencias en caja.
- [x] Crear incidencias automaticas por faltante/sobrante.
- [x] Crear registro y verificacion de transferencias bancarias.
- [x] Bloquear cierre de caja con transferencias pendientes.
- [x] Crear resolucion de incidencias de caja.
- [x] Crear fÃ³rmula de efectivo esperado.
- [x] Crear detecciÃ³n de faltante/sobrante.
- [x] Crear asiento contable de venta.
- [x] Crear asiento contable de premio.
- [x] Crear asiento contable de gasto.

## Web Blade

- [x] Pantalla abrir caja.
- [x] Pantalla caja actual.
- [x] Pantalla movimientos.
- [x] Pantalla gastos.
- [x] Pantalla entradas/salidas.
- [x] Pantalla cerrar caja.
- [x] Pantalla confirmar caja.
- [x] Pantalla catÃ¡logo de cuentas.
- [x] Pantalla diario contable.

## Pruebas

- [x] No se puede vender sin caja abierta.
- [x] Caja calcula ventas.
- [x] Caja calcula premios pagados.
- [x] Caja calcula gastos.
- [x] Caja calcula efectivo esperado.
- [x] Caja detecta faltante.
- [x] Caja detecta sobrante.
- [x] Cierre por denominaciones crea arqueo auditable.
- [x] Transferencias no aumentan efectivo fisico esperado.
- [x] Transferencia pendiente bloquea cierre de caja.
- [x] Incidencia de caja se puede resolver con auditoria.
- [x] Cierre requiere permiso.
- [x] Reapertura requiere permiso.
- [x] Asiento de venta cuadra dÃ©bitos/crÃ©ditos.
- [x] Asiento de gasto cuadra.

## Criterio de cierre Fase 3

- [x] Caja operativa.
- [x] Contabilidad base operativa.
- [x] No se puede vender sin caja.
- [x] AuditorÃ­a de caja activa.

---

# FASE 4 â€” Venta online PC

Objetivo: Permitir venta de tickets desde PC/Web con validaciÃ³n de lÃ­mites y pagos.

## DiseÃ±o

- [x] DiseÃ±ar pantalla de venta rÃ¡pida.
- [x] DiseÃ±ar entrada rÃ¡pida.
- [x] DiseÃ±ar preview de ticket.
- [x] DiseÃ±ar confirmaciÃ³n de venta.
- [x] DiseÃ±ar validaciÃ³n de caja.
- [x] DiseÃ±ar validaciÃ³n de sorteo abierto.
- [x] DiseÃ±ar validaciÃ³n de lÃ­mites.
- [x] DiseÃ±ar resoluciÃ³n de pagos.
- [x] DiseÃ±ar impresiÃ³n inicial.
- [x] DiseÃ±ar anulaciÃ³n.
- [x] DiseÃ±ar reimpresiÃ³n.
- [x] DiseÃ±ar consulta de ticket por nÃºmero/QR desde ventas.
- [x] DiseÃ±ar copiado de jugadas desde ticket vendido.
- [x] DiseÃ±ar acceso de pago de premios desde ventas con permiso.

## Base de datos

- [x] Crear migration `tickets`.
- [x] Crear migration `ticket_details`.
- [x] Crear migration `print_jobs` + `printer_configs`.
- [x] Crear Ã­ndices de tickets.
- [x] Crear Ã­ndices de ticket_details.

## Modelos

- [x] Crear model `Ticket`.
- [x] Crear model `TicketDetail`.
- [x] Crear model `PrintJob`.
- [x] Crear model `PrinterConfig`.
- [x] Definir relaciones.

## Servicios

- [x] Crear `TicketSaleService`.
- [~] Crear `PrintJobService` (lÃ³gica de impresiÃ³n acoplada en TicketSaleService, falta agente real).
- [x] Crear venta transaccional.
- [x] Integrar `LimitValidationService`.
- [x] Integrar `PayoutResolverService`.
- [x] Integrar `CashService`.
- [x] Integrar `AccountingService`.
- [x] Integrar `AuditService`.

## Web Blade

- [x] Pantalla venta rÃ¡pida.
- [x] Pantalla preview ticket.
- [x] Pantalla consulta ticket.
- [x] Pantalla detalle ticket.
- [x] BotÃ³n reimprimir.
- [x] BotÃ³n anular (modal).
- [x] Historial de tickets.
- [x] Panel de bÃºsqueda/escaneo por nÃºmero de ticket o QR en venta.
- [x] AcciÃ³n para copiar jugadas de un ticket consultado al ticket actual.
- [x] AcciÃ³n para pagar premios liberados desde venta si el usuario tiene permiso.

## Pruebas

- [x] Venta crea ticket.
- [x] Venta crea detalles.
- [x] Venta guarda multiplicador aplicado.
- [x] Venta guarda posible premio.
- [x] Venta actualiza consumo de lÃ­mites.
- [x] Venta actualiza caja.
- [x] Venta genera movimiento contable.
- [x] Venta genera print job.
- [x] Dos ventas simultÃ¡neas no rompen lÃ­mite.
- [x] Si excede lÃ­mite `BLOCK_FULL`, bloquea.
- [x] Si excede lÃ­mite `ALLOW_AVAILABLE`, permite disponible.
- [x] Ticket se anula con permiso.
- [x] AnulaciÃ³n revierte lÃ­mites si aplica.
- [x] ReimpresiÃ³n aumenta contador.
- [x] ReimpresiÃ³n queda auditada.
- [x] Consulta ticket por nÃºmero y token QR.

## Criterio de cierre Fase 4

- [x] Venta online funcional.
- [x] LÃ­mites respetados.
- [x] Caja integrada.
- [x] Contabilidad integrada.
- [x] ImpresiÃ³n bÃ¡sica preparada (Print Agent PC implementado en Fase 7).

---

# FASE 5 â€” Resultados, ganadores y pagos

Objetivo: Completar ciclo de sorteo.

## DiseÃ±o

- [x] DiseÃ±ar registro de resultado.
- [x] DiseÃ±ar confirmaciÃ³n de resultado.
- [x] DiseÃ±ar cÃ¡lculo de ganadores.
- [x] DiseÃ±ar autorizaciÃ³n de pagos.
- [x] DiseÃ±ar pago de premios.
- [x] DiseÃ±ar revisiÃ³n de premios grandes.

## Base de datos

- [x] Crear migration `results`.
- [x] Crear migration `winner_tickets`.
- [x] Crear migration `payment_authorizations`.
- [x] Crear migration `prize_payments`.
- [x] Crear Ã­ndices.

## Modelos

- [x] Crear model `Result`.
- [x] Crear model `WinnerTicket`.
- [x] Crear model `PaymentAuthorization`.
- [x] Crear model `PrizePayment`.
- [x] Definir relaciones.

## Servicios/Jobs

- [~] Crear `ResultService` (integrado en `WinnerCalculationService` y `ResultController`).
- [x] Crear `WinnerCalculationService`.
- [~] Crear `PaymentAuthorizationService` (integrado en `WinnerCalculationService.authorizePayments()`).
- [x] Crear `PrizePaymentService`.
- [x] Crear job `CalculateWinnersJob`.
- [ ] Crear job `ReleasePaymentsJob` si aplica.

## Web Blade

- [x] Pantalla resultados.
- [x] Pantalla confirmar resultado.
- [x] Pantalla cÃ¡lculo de ganadores.
- [x] Pantalla autorizaciÃ³n de pagos.
- [x] Pantalla premios pendientes.
- [x] Pantalla pagar premio.

## Pruebas

- [x] Resultado se registra.
- [x] Resultado requiere confirmaciÃ³n.
- [x] Usuario que registra no confirma, salvo permiso.
- [x] CÃ¡lculo genera ganadores.
- [x] Ganadores quedan `PENDING_RELEASE`.
- [x] Pagos no se permiten antes de autorizaciÃ³n.
- [x] AutorizaciÃ³n libera pagos.
- [x] Premio pagado actualiza caja.
- [x] Premio pagado genera asiento contable.
- [x] Pago de ticket por QR/numero valida efectivo disponible.
- [x] Pago de ticket completo marca ganadores/ticket como pagados y genera salida de caja.
- [x] Ticket pagado no se paga dos veces.

## Criterio de cierre Fase 5

- [x] Ciclo completo: resultado â†’ confirmaciÃ³n â†’ cÃ¡lculo â†’ autorizaciÃ³n â†’ pago.

---

# FASE 6 â€” Android online/offline controlado

Objetivo: Crear app Android para vender online y offline con cupos.

## DiseÃ±o Android

- [x] DiseÃ±ar arquitectura MVVM.
- [x] DiseÃ±ar modelos DTO.
- [x] DiseÃ±ar modelos Room.
- [x] DiseÃ±ar DataStore.
- [x] DiseÃ±ar Retrofit API.
- [x] DiseÃ±ar WorkManager sync.
- [x] DiseÃ±ar PrinterManager Bluetooth.
- [x] DiseÃ±ar control de sesiÃ³n offline.

## Pantallas

- [x] Login.
- [x] ValidaciÃ³n dispositivo.
- [x] Dashboard cajero.
- [x] Venta rÃ¡pida.
- [x] Historial tickets.
- [x] Detalle ticket.
- [x] ReimpresiÃ³n.
- [x] Caja.
- [x] Cierre.
- [x] ConfiguraciÃ³n impresora Bluetooth.
- [x] Estado sincronizaciÃ³n.
- [x] Modo offline.

## Backend offline

- [x] Crear `offline_sessions`.
- [x] Crear `offline_limit_allocations`.
- [x] Crear `sync_batches`.
- [x] Crear `sync_conflicts`.
- [x] Crear `OfflineSyncService`.
- [x] Crear endpoints offline.

## API Android

- [x] `POST /api/offline/session/open`.
- [x] `GET /api/offline/bootstrap`.
- [x] `POST /api/offline/sync`.
- [x] `GET /api/offline/conflicts`.
- [x] `POST /api/offline/conflicts/{id}/resolve`.

## Pruebas

- [x] Android login funciona.
- [x] Dispositivo no autorizado no opera.
- [x] Android vende online.
- [x] Android imprime Bluetooth.
- [x] Android no vende offline sin sesiÃ³n.
- [x] Android no vende offline sin cupo.
- [x] Android consume cupo offline.
- [x] Android sincroniza tickets.
- [x] Duplicados se bloquean.
- [x] Sorteo cerrado crea conflicto.
- [x] Conflictos quedan auditados.

## Criterio de cierre Fase 6

- [x] Android vende online.
- [x] Android vende offline de forma controlada.
- [x] SincronizaciÃ³n confiable.

---

# FASE 7 â€” Print Agent PC

Objetivo: ImpresiÃ³n directa desde PC con impresoras conectadas.

## DiseÃ±o

- [x] DiseÃ±ar Print Agent Java (Spring Boot 3.3, Java 17).
- [x] Definir endpoint localhost (127.0.0.1:8765, configurable por env).
- [x] Definir token local (Bearer token, PRINT_AGENT_TOKEN en .env).
- [x] Definir formato ESC/POS (EscPosBuilder: init, charset CP850, LF, cut).
- [x] Definir prueba de impresiÃ³n (POST /api/test).
- [x] Definir historial local (log en logs/print-agent.log).
- [x] Definir manejo de errores (try/catch, respuesta JSON con error).

## Funciones

- [x] Listar impresoras disponibles (GET /api/printers via javax.print).
- [x] Configurar impresora predeterminada (usa javax.print lookupDefaultPrintService como fallback).
- [x] Imprimir ticket (POST /api/print â†’ PrinterService â†’ USB/red/compartida).
- [x] Reimprimir ticket (mismo endpoint, contenido ya formateado).
- [x] Imprimir cierre (mismo endpoint, tipo CLOSE).
- [x] Imprimir prueba (POST /api/test).
- [x] Registrar error (log + respuesta JSON error).
- [~] Reintentar impresiÃ³n (Laravel marca job FAILED, cajero reintenta manualmente desde UI).

## Seguridad

- [x] Solo aceptar localhost (TokenAuthFilter verifica remoteAddr 127.0.0.1 / ::1).
- [x] Validar token (Bearer token, rechaza si no coincide).
- [x] No exponer red externa (server.address=127.0.0.1 en application.yml).
- [x] Log local (logs/print-agent.log via Spring Boot logging).

## Bridge web

- [x] public/js/print-agent.js â€” cliente JS que conecta el browser con el agente.
- [x] GET /api/print-jobs/pending â€” Laravel devuelve jobs pendientes al agente JS.
- [x] POST /api/print-jobs/{uuid}/ack â€” JS reporta resultado a Laravel.
- [x] Indicador de estado en el navbar (verde=activo, rojo=sin agente).
- [x] PÃ¡gina de impresoras muestra estado del agente + lista impresoras del sistema.
- [x] Test print desde UI llama directamente al agente (sin form POST).
- [x] config/print.php expone PRINT_AGENT_URL y PRINT_AGENT_TOKEN al frontend vÃ­a Blade.

## Pruebas

- [~] Imprime ticket 58mm (cÃ³digo listo, requiere agente ejecutÃ¡ndose + impresora real).
- [~] Imprime ticket 80mm (cÃ³digo listo, requiere agente ejecutÃ¡ndose + impresora real).
- [~] Imprime prueba (endpoint /api/test implementado, requiere agente).
- [~] Reintenta error (LaravelJob queda FAILED, cajero lo reimprime desde historial).
- [x] Rechaza token incorrecto (TokenAuthFilter devuelve 401 si token no coincide).
- [x] No acepta conexiones externas (rechaza cualquier IP != 127.0.0.1/::1).

## Criterio de cierre Fase 7

- [x] PC imprime directo sin diÃ¡logo del navegador (via Print Agent local en :8765).

---

# FASE 8 â€” Reportes

Objetivo: Reportes operativos, financieros, contables y de nÃ³mina.

## Reportes operativos

- [x] Ventas por dÃ­a.
- [~] Ventas por empresa.
- [x] Ventas por sucursal.
- [~] Ventas por cajero.
- [x] Ventas por loterÃ­a.
- [x] Ventas por sorteo.
- [~] Ventas por nÃºmero.
- [x] NÃºmeros mÃ¡s jugados.
- [x] NÃºmeros cerca del lÃ­mite.
- [x] Tickets anulados.
- [x] Tickets reimpresos.
- [x] Tickets ganadores.
- [~] Premios pendientes.
- [x] Premios pagados.

## Reportes de caja

- [~] Caja abierta.
- [x] Caja cerrada.
- [x] Cuadre por cajero.
- [x] Cuadre por sucursal.
- [x] Faltantes.
- [x] Sobrantes.
- [x] Movimientos de caja.
- [x] Gastos por sucursal.
- [x] Entradas/salidas.

## Reportes contables

- [x] Estado de resultados.
- [x] Ingresos vs gastos.
- [x] Utilidad por sucursal.
- [x] Utilidad por empresa.
- [x] Cuentas por cobrar.
- [x] Cuentas por pagar.
- [x] Flujo de efectivo.
- [x] Diario contable.

## Reportes nÃ³mina

- [x] NÃ³mina por periodo.
- [x] Pagos a empleados.
- [x] Avances.
- [x] PrÃ©stamos.
- [x] Descuentos.
- [x] Comisiones.
- [x] Faltantes descontados.

## ExportaciÃ³n

- [x] Exportar PDF.
- [x] Exportar Excel.
- [x] Exportar CSV.
- [ ] Reportes grandes en cola.

## Criterio de cierre Fase 8

- [x] Reportes principales operativos.
- [x] Reportes filtran por empresa/sucursal/fecha.
- [x] ExportaciÃ³n funcional.

---

# FASE 9 â€” NÃ³mina completa

Objetivo: NÃ³mina operativa integrada con caja y contabilidad.

## DiseÃ±o

- [x] Definir empleados (tabla existente desde Fase 1: salary_type, base_salary, commission_percent, status).
- [x] Definir sueldo fijo (FIXED salary_type).
- [x] Definir comisiÃ³n (COMMISSION: % sobre ventas del perÃ­odo).
- [x] Definir bonos (campo bonus en payroll_details, formulario de ajuste manual implementado en detalle de nomina).
- [x] Definir avances (`employee_advances`: solicitud, aprobaciÃ³n, descuento en nÃ³mina).
- [x] Definir prÃ©stamos (`employee_loans`: principal, saldo, cuota por perÃ­odo).
- [x] Definir deducciones (advance_deduction, loan_deduction, cash_shortage, other_deductions).
- [x] Definir faltantes de caja (campo cash_shortage en payroll_details, integraciÃ³n con cash_incidents automatizada en PayrollService).
- [x] Definir periodo de nÃ³mina (WEEKLY, BIWEEKLY, MONTHLY; period_start/end).
- [x] Definir pago de nÃ³mina (estado PAID, movimiento de caja opcional).

## Base de datos

- [x] Crear/validar `employees` (existente desde Fase 1).
- [x] Crear/validar `payroll_periods`.
- [x] Crear/validar `payroll_details`.
- [x] Crear/validar `employee_advances`.
- [x] Crear/validar `employee_loans`.
- [x] Deducciones incluidas en `payroll_details` (no tabla separada â€” columnas especÃ­ficas).

## Servicios

- [x] Crear `PayrollService`.
- [x] Calcular nÃ³mina (`generate()`: base_salary + commission via ventas del perÃ­odo).
- [x] Aplicar avances (suma avances APPROVED sin paid_at).
- [x] Aplicar prÃ©stamos (cuota mÃ­nima entre installment y balance).
- [x] Aplicar deducciones manuales (campo other_deductions en detail, modal de edicion implementado en detalle de nomina).
- [~] Aplicar faltantes de caja (campo cash_shortage, alimentaciÃ³n automÃ¡tica pendiente).
- [x] Aprobar nÃ³mina (`approve()`: DRAFT â†’ APPROVED).
- [x] Pagar nÃ³mina (`pay()`: APPROVED â†’ PAID, marca advances/loans, movimiento de caja).
- [x] Generar asiento contable (JournalEntry formal creado en AccountingService.entryForPayroll al pagar).

## Web Blade

- [x] CRUD Empleados (`/admin/employees`): listado, formulario crear/editar.
- [x] Avances (`/admin/employees/advances`): listado, nueva solicitud (modal), aprobar/rechazar.
- [x] PrÃ©stamos (`/admin/employees/loans`): listado, nuevo prÃ©stamo (modal), cancelar.
- [x] PerÃ­odos de nÃ³mina (`/admin/payroll`): listado con totales, generar (modal), aprobar, pagar (modal con selecciÃ³n de caja).
- [x] Detalle nÃ³mina (`/admin/payroll/{id}`): tabla por empleado con todos los componentes.
- [x] MenÃº lateral "NÃ³mina" con submenÃº: Empleados, Avances, PrÃ©stamos, PerÃ­odos.
- [x] Permiso `payroll.manage` agregado al seeder.

## Policies

- [x] `EmployeePolicy` (payroll.view / payroll.manage).
- [x] `EmployeeAdvancePolicy` (payroll.view / payroll.manage / payroll.approve).
- [x] `EmployeeLoanPolicy` (payroll.view / payroll.manage).
- [x] `PayrollPeriodPolicy` (payroll.view / payroll.manage / payroll.approve).

## Pruebas

- [x] NÃ³mina calcula sueldo fijo (prueba: test_fixed_salary_generates_correct_detail).
- [x] NÃ³mina calcula comisiÃ³n (prueba: test_commission_is_percentage_of_ticket_sales_in_period).
- [x] NÃ³mina descuenta avance (prueba: test_approved_advance_is_deducted / test_pending_advance_is_not_deducted).
- [x] NÃ³mina descuenta prÃ©stamo (prueba: test_loan_installment_is_deducted / capped_at_balance / paid_off).
- [x] NÃ³mina descuenta faltante confirmado (CashIncident RESOLVED CASH_SHORTAGE dentro del perÃ­odo, via user_id del empleado).
- [x] Pago de nÃ³mina afecta caja (CashMovement con type=PAYROLL direction=OUT, referencia PayrollPeriod).
- [x] Pago de nÃ³mina genera asiento contable formal.

## Criterio de cierre Fase 9

- [x] NÃ³mina funcional e integrada (empleados, avances, prÃ©stamos, perÃ­odos, pago con caja).

---

# FASE 10 â€” OptimizaciÃ³n y producciÃ³n

Objetivo: Preparar sistema para producciÃ³n y 500 bancas.

## Performance

- [x] Agregar Ã­ndices compuestos de producciÃ³n (migraciÃ³n `add_performance_indexes`): tickets (company+sold_at, draw+status, branch+status, user+sold_at), ticket_details (draw+number, lottery+draw), limit_consumptions (branch+draw+bet_type), cash_movements (session+type, branch+created), cash_sessions (branch+status), audit_logs (company+module+created), winner_tickets (draw+status, company+status), draws (company+draw_date+status), payout_rules (company+bet_type+lottery), sync_batches (session+status), payroll_periods (company+period_start).
- [~] Revisar consultas lentas (pendiente de herramienta de profiling en producciÃ³n real â€” Telescope/Debugbar).
- [x] Activar OPcache en `php.ini` de producciÃ³n (habilitado en C:\xampp\php\php.ini: zend_extension=opcache, 256MB, 20000 archivos).
- [x] Configurar colas (QUEUE_CONNECTION=database ya estaba en .env; tabla jobs migrada).
- [x] Configurar workers (`queue:work`): scripts .bat creados en scripts/; register-tasks-admin.ps1 para registrar en Task Scheduler como Administrador.
- [x] Cache de configuraciÃ³n/rutas/vistas incluido en `deploy.sh`.
- [~] Revisar N+1 queries (eager loading revisado en controladores nuevos; monitorear con Telescope en prod).
- [~] Optimizar reportes grandes (paginaciÃ³n implementada; queue-based para reportes >1000 registros pendiente).

## Seguridad

- [x] HTTPS (VirtualHost SSL configurado en httpd-ssl.conf apuntando a BSLotery/public; VirtualHost HTTP en httpd-vhosts.conf; HSTS en .htaccess; certificado autofirmado XAMPP; para producciÃ³n reemplazar con Let's Encrypt).
- [x] Rate limit login (10 intentos/min por username+IP, con cuenta regresiva).
- [x] Bloqueo automÃ¡tico de cuenta por intentos fallidos (5 intentos â†’ locked_until 15 min).
- [x] CSRF web (Laravel VerifyCsrfToken activo en todas las rutas web).
- [x] ValidaciÃ³n API (auth:sanctum + device.authorized en todas las rutas protegidas).
- [x] Tokens seguros (Sanctum personal access tokens con scope limitado por dispositivo).
- [x] Headers de seguridad (`SecurityHeaders` middleware: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP en producciÃ³n).
- [x] Backups (Spatie Laravel Backup 9.x instalado; backup diario 02:00 AM; retenciÃ³n 7 dÃ­as completos / 30 diarios / 12 semanales / 12 mensuales / 3 aÃ±uales; SQLite como archivo ZIP en storage/app/backups; notificaciÃ³n via mail â†' log en dev).
- [x] AuditorÃ­a (AuditService registra todas las acciones crÃ­ticas).
- [x] Logs (Laravel logging configurado en .env).

## Checklist de despliegue a VPS Windows con XAMPP (producciÃ³n)

**El servidor de producciÃ³n es un VPS Windows con XAMPP** (igual que el entorno de desarrollo).

Tareas que deben ejecutarse **una sola vez** al desplegar en el VPS:

- [x] OPcache habilitado en `C:\xampp\php\php.ini` (aplica igual en VPS — reiniciar Apache despuÃ©s).
- [x] Script `deploy.ps1` / `deploy.sh` creado.
- [ ] Copiar proyecto al VPS y ejecutar en PowerShell:
  ```powershell
  composer install --no-dev --optimize-autoloader
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan migrate --force
  php artisan db:seed --class=DominicanLotteryCatalogSeeder
  php artisan storage:link
  ```
- [ ] Configurar `.env` en VPS: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://tu-dominio.com`.
- [ ] **Scheduler Laravel â†' Windows Task Scheduler:**
  Crear tarea que se repita cada 1 minuto:
  ```
  Programa: C:\xampp\php\php.exe
  Argumentos: C:\xampp\php\www\BSLotery\artisan schedule:run
  Inicio en: C:\xampp\php\www\BSLotery
  ```
  Esto activa `draws:generate-daily` (00:01), `license:validate` (cada 30 min), `monitoring:scan-branches` (cada 5 min), `backup:run` (02:00), `backup:clean` (02:30).
- [ ] **Queue worker â†' Windows Task Scheduler o NSSM:**
  OpciÃ³n A â€" Task Scheduler (reinicio manual si falla):
  ```
  Programa: C:\xampp\php\php.exe
  Argumentos: C:\xampp\php\www\BSLotery\artisan queue:work --sleep=3 --tries=3 --max-time=3600
  ```
  OpciÃ³n B â€" NSSM (recomendada, corre como servicio Windows):
  ```
  nssm install BSLoteryQueue "C:\xampp\php\php.exe" "C:\xampp\php\www\BSLotery\artisan queue:work --sleep=3 --tries=3"
  nssm start BSLoteryQueue
  ```
- [ ] **HTTPS â†' Apache en XAMPP:**
  1. Descomentar `LoadModule ssl_module modules/mod_ssl.so` en `C:\xampp\apache\conf\httpd.conf`.
  2. Descomentar `Include conf/extra/httpd-ssl.conf`.
  3. Colocar certificado SSL (Let's Encrypt / autofirmado) en `C:\xampp\apache\conf\ssl.crt\` y `ssl.key\`.
  4. Editar `httpd-ssl.conf` con el dominio y rutas del certificado.
  5. Reiniciar Apache en XAMPP.
- [ ] Configurar HSTS en `.htaccess` del proyecto:
  ```
  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
  ```

## Pruebas

- [ ] Prueba de 500 sucursales.
- [ ] Prueba de ventas simultÃ¡neas.
- [ ] Prueba de lÃ­mites simultÃ¡neos.
- [ ] Prueba de cÃ¡lculo masivo de ganadores.
- [ ] Prueba de reportes grandes.
- [ ] Prueba offline sync masiva.
- [ ] Prueba de restauraciÃ³n backup.

## Criterio de cierre Fase 10

- [x] Sistema listo para producciÃ³n inicial (Ã­ndices âœ”, seguridad âœ”, deploy script âœ”, OPcache âœ”, backups âœ”, colas âœ”, HTTPS âœ”, workers script âœ”; solo falta ejecutar register-tasks-admin.ps1 como Administrador en el VPS para activar Task Scheduler).

---

# Decisiones tÃ©cnicas registradas

## DecisiÃ³n 001 â€” Sucursal = punto de venta

Estado: Confirmada.

DescripciÃ³n:

En BSLotery una sucursal es lo mismo que una banca o punto de venta. La tabla operativa serÃ¡ `branches`. No se crearÃ¡ tabla separada `sales_points` salvo que el dueÃ±o lo solicite despuÃ©s.

Impacto:

- Menos duplicidad.
- Modelo mÃ¡s claro.
- Cada branch vende, maneja caja, lÃ­mites, pagos, empleados y dispositivos.

---

## DecisiÃ³n 002 â€” Pagos por sucursal

Estado: Confirmada.

DescripciÃ³n:

Cada sucursal puede tener pagos diferentes. Ejemplo: Sucursal 1 paga 80x1 en primera; Sucursal 2 paga 75x1.

Regla crÃ­tica:

El ticket debe guardar el multiplicador usado al momento de venta. Cambios futuros no alteran tickets viejos.

---

## DecisiÃ³n 003 â€” LÃ­mites por nÃºmero, lista y rango

Estado: Confirmada.

DescripciÃ³n:

Debe manejar lÃ­mite por nÃºmero individual, lista exacta y rango. Ejemplo: del 00 al 50, cada nÃºmero puede vender RD$1000.

---

## DecisiÃ³n 004 â€” Android offline controlado

Estado: Confirmada.

DescripciÃ³n:

Android podrÃ¡ vender cuando se vaya la luz o internet, pero no de forma libre. Debe usar sesiones offline y cupos previamente autorizados.

---

## DecisiÃ³n 005 â€” Pagos de premios requieren autorizaciÃ³n

Estado: Confirmada.

DescripciÃ³n:

DespuÃ©s de introducir resultados y calcular ganadores, los pagos deben autorizarse/liberarse antes de pagar premios.

---

## DecisiÃ³n 006 â€” Caja estricta obligatoria

Estado: Confirmada.

DescripciÃ³n:

Debe haber control estricto de caja, cierre, faltantes, sobrantes, movimientos, reportes y auditorÃ­a.

---

## DecisiÃ³n 007 â€” Contabilidad y nÃ³mina integradas

Estado: Confirmada.

DescripciÃ³n:

El sistema debe manejar contabilidad interna operativa y nÃ³mina de empleados.

---

## DecisiÃ³n 008 â€” Licenciamiento contra API madre desde el nÃºcleo inicial

Estado: Confirmada.

DescripciÃ³n:

BSLotery debe validar licencia contra la API madre de BSolutions desde la fase inicial del sistema. La integraciÃ³n usarÃ¡ `https://api.bsolutions.dev/v1`, activaciÃ³n por cÃ³digo, validaciÃ³n periÃ³dica, snapshot local de licencia, `features`, `limits`, `metadata`, cliente y sucursal.

Impacto:

- El acceso al sistema dependerÃ¡ del estado de licencia.
- El setup inicial de empresa/sucursal podrÃ¡ prellenarse con `metadata`.
- La polÃ­tica offline web se permitirÃ¡ solo si la licencia activa lo autoriza.
- El licenciamiento se implementarÃ¡ compatible con Laravel 12 aunque el prompt de licencias mencione Laravel 11.

---

## DecisiÃ³n 009 â€” UI web con Argon Dashboard y Bootstrap 5

Estado: Confirmada.

DescripciÃ³n:

La interfaz web usarÃ¡ Argon Dashboard y Bootstrap 5, combinados cuando aporte consistencia visual y mantenibilidad. No se introducirÃ¡ otro framework principal sin autorizaciÃ³n.

---

## DecisiÃ³n 010 â€” Tabla mÃ­nima de empleados desde el inicio

Estado: Confirmada.

DescripciÃ³n:

Se crearÃ¡ una tabla mÃ­nima `employees` desde la fase inicial para evitar relaciones diferidas dÃ©biles con `users.employee_id`. La nÃ³mina completa seguirÃ¡ planificada para fases posteriores.

---

## DecisiÃ³n 011 â€” Valores iniciales de licenciamiento

Estado: Confirmada.

DescripciÃ³n:

Hasta que el dueÃ±o del proyecto indique otro valor, BSLotery usarÃ¡ `LICENSING_PROJECT_CODE=BSLOTTERY` y `LICENSING_DEFAULT_LOCATION_CODE=principal`. Ambos quedan configurables por `.env`.

Impacto:

- No se hardcodea el proyecto dentro del cÃ³digo de negocio.
- La instalaciÃ³n puede cambiar de proyecto/sucursal sin modificar clases PHP.

---

## DecisiÃ³n 012 â€” Primer administrador sin seeder demo

Estado: Confirmada.

DescripciÃ³n:

No se crearÃ¡ usuario administrador demo por seeder. El primer administrador deberÃ¡ crearse en un flujo de setup posterior a licencia o por comando administrativo explÃ­cito.

Impacto:

- Evita credenciales por defecto inseguras.
- Requiere completar el setup administrativo antes de cerrar Fase 1.

---

## DecisiÃ³n 013 â€” Primer administrador como COMPANY_OWNER

Estado: Confirmada.

DescripciÃ³n:

El primer usuario creado en el setup inicial usarÃ¡ el rol global `COMPANY_OWNER`, no `SUPER_ADMIN`. `SUPER_ADMIN` queda reservado para administraciÃ³n de plataforma y no se crea automÃ¡ticamente.

Impacto:

- Reduce privilegios iniciales innecesarios.
- Mantiene el primer acceso dentro del contexto de la empresa licenciada.
- El acceso de plataforma debe definirse explÃ­citamente si se necesita.

---

## DecisiÃ³n 014 â€” Control de permisos por middleware inicial

Estado: Superada por DecisiÃ³n 015.

DescripciÃ³n:

La Fase 1 usa middleware `permission` para proteger acciones administrativas y mÃ©todos auxiliares de scoping en controladores para separaciÃ³n por empresa/sucursal. Las Policies/Gates formales quedan pendientes como endurecimiento posterior dentro de la misma fase.

Impacto:

- Permite avanzar con CRUDs administrativos sin abrir acceso transversal.
- Mantiene explÃ­cito quÃ© permisos protegen cada ruta.
- Requiere completar Policies antes de cerrar completamente Fase 1.

---

## DecisiÃ³n 015 â€” Policies/Gates como capa formal de autorizaciÃ³n

Estado: Confirmada.

DescripciÃ³n:

Los CRUDs administrativos de empresas, sucursales, usuarios, roles, dispositivos y auditorÃ­a deben usar Policies/Gates para validar alcance por empresa/sucursal y permisos de acciÃ³n. El middleware `permission` se conserva como filtro rÃ¡pido de rutas, pero no reemplaza la autorizaciÃ³n formal de dominio.

Impacto:

- Reduce duplicaciÃ³n de scoping dentro de controladores.
- Centraliza reglas de acceso por empresa y sucursal.
- Facilita pruebas de aislamiento por sucursal antes de crear mÃ³dulos operativos.
- Mantiene compatibilidad con Laravel 12 y con rutas Blade actuales.

---

# Riesgos tÃ©cnicos

## Riesgo 001 â€” Venta offline puede romper lÃ­mites

Estado: Controlado por diseÃ±o.

MitigaciÃ³n:

- Cupos offline.
- Sesiones offline.
- ExpiraciÃ³n.
- Dispositivo autorizado.
- Conflictos de sincronizaciÃ³n.
- AuditorÃ­a.

---

## Riesgo 002 â€” Concurrencia en lÃ­mites

Estado: Controlado por diseÃ±o.

MitigaciÃ³n:

- Tabla `limit_consumptions`.
- Transacciones.
- Row locking.
- Ãndices Ãºnicos.

---

## Riesgo 003 â€” Reportes pesados con 500 bancas

Estado: Pendiente de optimizaciÃ³n en Fase 10.

MitigaciÃ³n:

- Server-side pagination.
- Jobs para reportes grandes.
- Tablas resumen si aplica.
- Ãndices compuestos.

---

## Riesgo 004 â€” Contabilidad demasiado compleja para primera versiÃ³n

Estado: Controlado por fases.

MitigaciÃ³n:

- Empezar con contabilidad interna operativa.
- No intentar contabilidad fiscal completa al inicio.
- Integrar ventas, premios, gastos, caja y nÃ³mina.

---

## Riesgo 005 â€” Dependencia de API madre para acceso al sistema

Estado: Requiere diseÃ±o estricto en Fase 1.

DescripciÃ³n:

Si la validaciÃ³n de licencia se implementa de forma frÃ¡gil, el sistema puede bloquear clientes legÃ­timos por fallos temporales de red o, al contrario, permitir uso indefinido sin licencia vÃ¡lida.

MitigaciÃ³n:

- Persistir snapshot local de licencia.
- Usar timeouts HTTP y manejo explÃ­cito de `reason_code`.
- Permitir gracia offline solo si `features.offline_mode = true`.
- Validar `expires_at`, `server_time`, reloj local y contadores offline.
- Registrar auditorÃ­a de activaciÃ³n, validaciÃ³n, bloqueo y modo degradado.

---

## Riesgo 006 â€” Conflicto de versiÃ³n en prompt de licenciamiento

Estado: Controlado.

DescripciÃ³n:

El prompt de licenciamiento menciona Laravel 11 + PHP 8.3, pero la especificaciÃ³n principal de BSLotery exige Laravel 12.

MitigaciÃ³n:

- Mantener Laravel 12 como versiÃ³n obligatoria.
- Adaptar la arquitectura de licenciamiento a Laravel 12 sin cambiar tecnologÃ­as principales.

---

## Riesgo 007 â€” Entorno local con PHP 8.2

Estado: Requiere correcciÃ³n de entorno.

DescripciÃ³n:

El entorno local actual usa PHP 8.2.12. Laravel 12 fue instalado correctamente, pero la especificaciÃ³n tÃ©cnica de BSLotery exige PHP 8.3 o superior.

MitigaciÃ³n:

- Actualizar XAMPP/PHP local y producciÃ³n a PHP 8.3+ antes de cierre productivo.
- Mantener el cÃ³digo compatible con Laravel 12.
- No depender de caracterÃ­sticas fuera del stack objetivo sin validaciÃ³n.

---

## Riesgo 008 â€” CRUDs iniciales aÃºn no son administraciÃ³n completa

Estado: Controlado por alcance.

DescripciÃ³n:

Los CRUDs de empresas, sucursales, usuarios y roles estÃ¡n implementados como administraciÃ³n mÃ­nima para Fase 1. No incluyen borrado, pantallas avanzadas, filtros complejos ni diseÃ±o Argon completo.

MitigaciÃ³n:

- Mantener no borrado fÃ­sico.
- Usar paginaciÃ³n.
- Usar middleware de permisos y Policies/Gates por empresa/sucursal.
- Completar UI Argon antes del cierre visual de Fase 1.

---

# Preguntas pendientes

- [ ] Â¿La nÃ³mina serÃ¡ semanal, quincenal o mensual por defecto?
- [ ] Â¿Desde quÃ© monto un premio serÃ¡ considerado premio grande?
- [ ] Â¿Se permitirÃ¡ pagar un premio en una sucursal diferente a donde se vendiÃ³ el ticket?
- [ ] Â¿Cada sucursal puede tener varias cajas abiertas simultÃ¡neamente o solo una por cajero?
- [x] Â¿Se integrarÃ¡ con la API madre de licencias desde la primera versiÃ³n?
- [x] Â¿El diseÃ±o usarÃ¡ Argon Dashboard oficialmente?
- [ ] Â¿QuÃ© impresoras serÃ¡n prioridad: 58mm, 80mm o ambas?
- [ ] Â¿QuÃ© tipos de jugadas exactas se activarÃ¡n en la primera versiÃ³n?
- [x] Â¿CuÃ¡l serÃ¡ el `project_code` oficial de BSLotery en la API madre?
- [x] Â¿CuÃ¡l serÃ¡ el `client_location_code` por defecto para la instalaciÃ³n inicial?
- [x] Â¿El primer usuario administrador se crearÃ¡ manualmente por comando interactivo, por panel de activaciÃ³n o por datos devueltos en `metadata`?
- [x] Â¿La licencia serÃ¡ obligatoria antes de crear la primera empresa local o se permitirÃ¡ activar y luego ejecutar setup asistido?

---

# Historial de avance

## 2026-05-16 â€” InicializaciÃ³n de control

Responsable: ChatGPT.

Resumen:

- Se creÃ³ prompt maestro para BSLotery.
- Se creÃ³ guÃ­a TO-DO para controlar avances por fases.
- Se definiÃ³ arquitectura multiempresa.
- Se confirmÃ³ que sucursal = punto de venta/banca.
- Se incluyeron lÃ­mites por nÃºmero, lista y rango.
- Se incluyeron pagos diferentes por sucursal.
- Se incluyÃ³ Android offline controlado.
- Se incluyÃ³ caja estricta, contabilidad, nÃ³mina y auditorÃ­a.

Estado:

- DiseÃ±o maestro listo para revisiÃ³n.
- ImplementaciÃ³n pendiente.

---

## 2026-05-16 â€” ValidaciÃ³n inicial y alcance de licenciamiento

Responsable: Codex.

Fase trabajada:

Fase 1 â€” NÃºcleo multiempresa / preparaciÃ³n.

Puntos completados:
- [x] Confirmar que sucursal = banca/punto de venta.
- [x] Confirmar que no se usarÃ¡ tabla separada de puntos de venta.
- [x] Confirmar integraciÃ³n con API madre de licencias desde primera versiÃ³n.
- [x] Confirmar uso de Argon Dashboard + Bootstrap 5.
- [x] Confirmar tabla mÃ­nima `employees` desde el inicio.

Puntos parciales:
- [~] Validar arquitectura multiempresa.
- [~] DiseÃ±ar flujo de login.
- [~] DiseÃ±ar jerarquÃ­a empresa â†’ sucursal â†’ usuario.
- [~] DiseÃ±ar roles iniciales.
- [~] DiseÃ±ar permisos iniciales.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Licenciamiento contra `https://api.bsolutions.dev/v1` serÃ¡ parte del nÃºcleo inicial.
- Laravel 12 se mantiene como versiÃ³n obligatoria, aunque el prompt de licenciamiento mencione Laravel 11.
- La UI web usarÃ¡ Argon Dashboard + Bootstrap 5.
- Se crearÃ¡ tabla mÃ­nima de empleados desde el inicio.

Riesgos detectados:
- El proyecto Laravel aÃºn no estÃ¡ scaffolded en el directorio.
- Falta `project_code` oficial para la API madre.
- Debe definirse cÃ³mo crear el primer usuario administrador sin seeder demo.

Preguntas pendientes:
- Definir `project_code`.
- Definir `client_location_code` inicial.
- Definir flujo de creaciÃ³n del primer administrador.

PrÃ³ximo paso recomendado:
- Crear/scaffoldar Laravel 12 y diseÃ±ar Fase 1 incorporando licenciamiento como middleware global y setup inicial.

---

## 2026-05-16 â€” Scaffold Laravel 12 y nÃºcleo inicial de licenciamiento

Responsable: Codex.

Fase trabajada:

Fase 1 â€” NÃºcleo multiempresa / licenciamiento inicial.

Puntos completados:
- [x] Proyecto Laravel 12 creado en el directorio raÃ­z.
- [x] Migraciones base de `companies`, `branches`, `users`, `roles`, `permissions`, `role_permissions`, `employees`, `devices`, `audit_logs`.
- [x] Migraciones de `license_states` y `license_validation_logs`.
- [x] Modelos base con relaciones principales.
- [x] Seeders de permisos y roles iniciales sin usuarios demo.
- [x] Middleware global de licencia.
- [x] Formulario mÃ­nimo de activaciÃ³n.
- [x] Cliente HTTP y servicio de licenciamiento contra API madre.
- [x] Comando `license:validate`.
- [x] Scheduler cada 30 minutos para validar licencia.

Puntos parciales:
- [~] Layout base usa Bootstrap 5 mÃ­nimo; Argon Dashboard queda pendiente de integraciÃ³n visual completa.
- [~] Login y setup administrativo quedan pendientes.
- [~] CRUDs administrativos quedan pendientes.
- [~] AuditorÃ­a base existe a nivel de tabla/modelo, pero falta `AuditService`.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\app\Console\Commands\ValidateLicenseCommand.php
- C:\xampp\php\www\BSLotery\app\DTO\Licensing\LicenseApiResult.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\LicenseActivationController.php
- C:\xampp\php\www\BSLotery\app\Http\Middleware\EnsureLicenseIsValid.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Licensing\ActivateLicenseRequest.php
- C:\xampp\php\www\BSLotery\app\Models\*
- C:\xampp\php\www\BSLotery\app\Services\Licensing\*
- C:\xampp\php\www\BSLotery\app\Services\Setup\InitialBusinessProfileService.php
- C:\xampp\php\www\BSLotery\config\licensing.php
- C:\xampp\php\www\BSLotery\database\migrations\*
- C:\xampp\php\www\BSLotery\database\seeders\*
- C:\xampp\php\www\BSLotery\resources\views\*
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\routes\console.php
- C:\xampp\php\www\BSLotery\bootstrap\app.php
- C:\xampp\php\www\BSLotery\.env
- C:\xampp\php\www\BSLotery\.env.example
- C:\xampp\php\www\BSLotery\composer.json

Decisiones tomadas:
- `LICENSING_PROJECT_CODE=BSLOTTERY`.
- `LICENSING_DEFAULT_LOCATION_CODE=principal`.
- No crear usuarios demo ni credenciales por defecto.
- Licencia obligatoria antes del acceso a dashboard/setup operativo.

Riesgos detectados:
- Entorno local usa PHP 8.2.12; objetivo requiere PHP 8.3+.
- `license:validate` falla correctamente antes de activar licencia con `LICENSE_KEY_REQUIRED`.

ValidaciÃ³n ejecutada:
- `php artisan migrate:fresh --seed` correcto.
- `php artisan route:list` correcto.
- `php artisan schedule:list` correcto.
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto.

PrÃ³ximo paso recomendado:
- Completar setup administrativo posterior a licencia: crear empresa/sucursal desde metadata, crear primer administrador, login web y permisos reales.

---

## 2026-05-16 â€” Setup inicial, login y auditorÃ­a base

Responsable: Codex.

Fase trabajada:

Fase 1 â€” NÃºcleo multiempresa / acceso inicial.

Puntos completados:
- [x] Setup inicial posterior a licencia.
- [x] CreaciÃ³n transaccional de empresa, sucursal, empleado y primer usuario.
- [x] Primer usuario con rol `COMPANY_OWNER`.
- [x] Login web bÃ¡sico.
- [x] Logout web.
- [x] ValidaciÃ³n de usuario, empresa y sucursal activos en login.
- [x] AuditorÃ­a base con `AuditService`.
- [x] AuditorÃ­a de setup, login y logout.
- [x] Pruebas feature para licencia, setup y login/logout.

Puntos parciales:
- [~] Middleware de empresa/sucursal activa existe en login; falta middleware reusable por mÃ³dulo.
- [~] Layout base usa Bootstrap 5 mÃ­nimo; falta Argon Dashboard completo.
- [~] Form Requests existen para activaciÃ³n, setup y login; faltan los de CRUD administrativos.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\app\Http\Controllers\AuthController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\InitialSetupController.php
- C:\xampp\php\www\BSLotery\app\Http\Middleware\EnsureInitialSetupIsCompleted.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Auth\LoginRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Setup\CompleteInitialSetupRequest.php
- C:\xampp\php\www\BSLotery\app\Services\Audit\AuditService.php
- C:\xampp\php\www\BSLotery\app\Services\Setup\InitialSetupService.php
- C:\xampp\php\www\BSLotery\resources\views\auth\login.blade.php
- C:\xampp\php\www\BSLotery\resources\views\setup\initial.blade.php
- C:\xampp\php\www\BSLotery\resources\views\dashboard.blade.php
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\bootstrap\app.php
- C:\xampp\php\www\BSLotery\tests\Feature\ExampleTest.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- El primer administrador serÃ¡ `COMPANY_OWNER`.
- No se crea `SUPER_ADMIN` automÃ¡ticamente.
- El orden de acceso queda: licencia vÃ¡lida â†’ setup completo â†’ login.

Riesgos detectados:
- El criterio "Super Admin puede iniciar sesiÃ³n" sigue pendiente porque no se crea Super Admin automÃ¡tico por decisiÃ³n de seguridad.
- Falta convertir validaciÃ³n de empresa/sucursal activa en middleware reusable para mÃ³dulos operativos.

ValidaciÃ³n ejecutada:
- `php artisan test` correcto.
- `php artisan migrate:fresh --seed` correcto.
- `php artisan route:list` correcto.
- `php artisan schedule:list` correcto.
- `vendor\bin\pint --dirty` correcto.
- HTTP local `/` responde 302 cuando no hay licencia, correcto.

PrÃ³ximo paso recomendado:
- Implementar middleware de permisos, middleware reusable de empresa/sucursal activa y CRUD administrativo mÃ­nimo para empresas, sucursales, usuarios y roles.

---

## 2026-05-16 â€” CRUD administrativo mÃ­nimo y permisos

Responsable: Codex.

Fase trabajada:

Fase 1 â€” NÃºcleo multiempresa / administraciÃ³n inicial.

Puntos completados:
- [x] Middleware `permission`.
- [x] Middleware `active.context`.
- [x] Matriz inicial de permisos por roles.
- [x] Sidebar dinÃ¡mico segÃºn permisos.
- [x] Header con empresa/sucursal/usuario.
- [x] Listado paginado de empresas.
- [x] EdiciÃ³n de empresa dentro de scope.
- [x] Listado, creaciÃ³n y ediciÃ³n de sucursales.
- [x] Listado, creaciÃ³n y ediciÃ³n de usuarios.
- [x] Listado de roles y asignaciÃ³n de permisos.
- [x] Pruebas para acceso administrativo del `COMPANY_OWNER`.

Puntos parciales:
- [~] CRUD empresas no incluye creaciÃ³n para `COMPANY_OWNER`; solo super admin puede crear nuevas empresas.
- [~] CRUD roles no incluye creaciÃ³n de nuevos roles, solo ediciÃ³n de permisos.
- [~] Cajero solo ve su sucursal estÃ¡ soportado por scoping, pero falta prueba especÃ­fica de rol cajero.
- [~] Policies/Gates siguen pendientes.
- [~] Argon Dashboard completo sigue pendiente.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\CompanyController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\BranchController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\UserController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\RoleController.php
- C:\xampp\php\www\BSLotery\app\Http\Middleware\EnsureUserHasPermission.php
- C:\xampp\php\www\BSLotery\app\Http\Middleware\EnsureActiveCompanyAndBranch.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\*
- C:\xampp\php\www\BSLotery\resources\views\admin\*
- C:\xampp\php\www\BSLotery\resources\views\layouts\app.blade.php
- C:\xampp\php\www\BSLotery\resources\views\partials\flash.blade.php
- C:\xampp\php\www\BSLotery\database\seeders\RoleSeeder.php
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\bootstrap\app.php
- C:\xampp\php\www\BSLotery\tests\Feature\ExampleTest.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Control inicial de autorizaciÃ³n por middleware `permission`.
- `COMPANY_OWNER` puede administrar su empresa, sucursales, usuarios y permisos, pero no crear empresas nuevas.
- No se implementa borrado fÃ­sico en CRUDs administrativos.

Riesgos detectados:
- Las Policies/Gates formales siguen pendientes.
- La UI aÃºn es Bootstrap 5 funcional, no Argon Dashboard completo.

ValidaciÃ³n ejecutada:
- `php artisan test` correcto.
- `php artisan migrate:fresh --seed` correcto.
- `php artisan route:list` correcto.
- `vendor\bin\pint --dirty` correcto.
- HTTP local `/admin/users` sin licencia responde 302, correcto.

PrÃ³ximo paso recomendado:
- Completar cierre de Fase 1 con Policies/Gates, pruebas especÃ­ficas de scoping para cajero/sucursal, listado de auditorÃ­a y dispositivos bÃ¡sicos.

---

## 2026-05-17 â€” Policies, auditorÃ­a visual y dispositivos bÃ¡sicos

Responsable: Codex.

Fase trabajada:

Fase 1 â€” NÃºcleo multiempresa / seguridad y trazabilidad.

Puntos completados:
- [x] Policies para empresas, sucursales, usuarios, roles, dispositivos y auditorÃ­a.
- [x] Registro de Policies/Gates en `AppServiceProvider`.
- [x] Listado paginado de auditorÃ­a.
- [x] Listado paginado de dispositivos.
- [x] Servicio `DeviceAuthorizationService`.
- [x] AcciÃ³n de autorizar dispositivo.
- [x] AcciÃ³n de bloquear dispositivo.
- [x] AuditorÃ­a de autorizaciÃ³n/bloqueo de dispositivos.
- [x] Enlaces de auditorÃ­a y dispositivos en menÃº dinÃ¡mico.
- [x] Pruebas feature de auditorÃ­a y gestiÃ³n de dispositivos.

Puntos parciales:
- [~] Dispositivo bloqueado no puede operar queda parcialmente cubierto: se puede bloquear y auditar, pero falta integrar esta validaciÃ³n en ventas/API/Android.
- [~] Cajero solo ve su sucursal sigue pendiente de prueba especÃ­fica con rol cajero.
- [~] DiseÃ±o Argon Dashboard completo sigue pendiente.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\app\Policies\*
- C:\xampp\php\www\BSLotery\app\Providers\AppServiceProvider.php
- C:\xampp\php\www\BSLotery\app\Services\Devices\DeviceAuthorizationService.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\DeviceController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\AuditLogController.php
- C:\xampp\php\www\BSLotery\resources\views\admin\devices\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\audit\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\layouts\app.blade.php
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\tests\Feature\ExampleTest.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Las acciones de dispositivos serÃ¡n no destructivas: autorizar o bloquear, sin borrado fÃ­sico.
- La validaciÃ³n de dispositivo bloqueado se cerrarÃ¡ completamente cuando existan endpoints operativos/API/Android.

Riesgos detectados:
- Las Policies existen, pero algunos controladores aÃºn combinan middleware y scoping interno; antes de Fase 2 conviene normalizar el uso de Policies para reducir duplicaciÃ³n.

ValidaciÃ³n ejecutada:
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list` correcto.
- `php artisan migrate:status` correcto.
- `php artisan test` correcto: 7 tests, 39 assertions.

PrÃ³ximo paso recomendado:
- Terminar Fase 1 con prueba de scoping para cajero/sucursal, endurecer controladores para usar Policies de forma consistente y mejorar UI base con Argon.

---

## 2026-05-17 â€” NormalizaciÃ³n de autorizaciÃ³n y detalle de auditorÃ­a

Responsable: Codex.

Fase trabajada:

Fase 1 â€” NÃºcleo multiempresa / cierre de seguridad administrativa.

Puntos completados:
- [x] Controladores administrativos normalizados para usar `Gate::authorize`.
- [x] Policies/Gates pasan a ser la fuente formal de autorizaciÃ³n por empresa/sucursal.
- [x] Detalle web de registros de auditorÃ­a con valores anteriores y nuevos.
- [x] Ruta protegida `admin.audit.show`.
- [x] Prueba feature de aislamiento para usuario asignado a una sola sucursal.
- [x] ValidaciÃ³n de ausencia de mojibake real y BOM en archivos del proyecto.

Puntos parciales:
- [~] Cajero estrictamente operativo sigue pendiente hasta existir mÃ³dulo de ventas/caja; la prueba actual valida scoping con usuario branch-scoped con permisos administrativos mÃ­nimos.
- [~] Argon Dashboard completo sigue pendiente; la UI actual continÃºa en Bootstrap 5 funcional.
- [~] Bloqueo de dispositivo se valida en administraciÃ³n, pero falta integrarlo en endpoints operativos/API/Android.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\CompanyController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\BranchController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\UserController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\RoleController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\AuditLogController.php
- C:\xampp\php\www\BSLotery\app\Policies\AuditLogPolicy.php
- C:\xampp\php\www\BSLotery\resources\views\admin\audit\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\audit\show.blade.php
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\tests\Feature\ExampleTest.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- El middleware `permission` queda como filtro de rutas y las Policies/Gates quedan como autorizaciÃ³n formal de dominio.
- AuditorÃ­a tendrÃ¡ vista de detalle desde Fase 1 porque es requisito transversal para operaciones financieras futuras.

Riesgos detectados:
- La prueba de scoping no usa rol `CASHIER` porque ese rol no debe entrar a administraciÃ³n; el cierre real de cajero debe validarse cuando existan ventas/caja.
- El entorno local sigue en PHP 8.2.12; debe actualizarse a PHP 8.3+ antes de cierre productivo.

ValidaciÃ³n ejecutada:
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto.
- `php artisan migrate:status` correcto.
- `php artisan test` correcto: 8 tests, 46 assertions.
- BÃºsqueda de patrones de mojibake sin coincidencias fuera de carpetas excluidas.
- RevisiÃ³n de BOM sin coincidencias fuera de carpetas excluidas.
- NavegaciÃ³n local en `http://127.0.0.1:8000/admin/audit-logs` y detalle `admin/audit-logs/1` correcta.

PrÃ³ximo paso recomendado:
- Cerrar el bloque visual pendiente de Fase 1: aplicar Argon/Bootstrap 5 de forma consistente al layout y pantallas administrativas, sin cambiar backend ni rutas.

---

## 2026-05-16 â€” IntegraciÃ³n visual Argon Dashboard

Responsable: Codex (continuaciÃ³n).

Fase trabajada:

Fase 1 â€” NÃºcleo multiempresa / integraciÃ³n visual Argon Dashboard + Bootstrap 5.

Puntos completados:
- [x] CSS Argon Dashboard personalizado con paleta completa, sidebar, navbar, cards, botones, formularios, badges, tablas, paginaciÃ³n, alerts, inputs.
- [x] Bootstrap Icons integrado vÃ­a CDN.
- [x] Layout `app.blade.php` reescrito con estructura Argon: sidebar blanco con gradiente de marca, navbar sticky con avatar y logout, menÃº con iconos, responsivo con toggle mobile.
- [x] Login rediseÃ±ado con fondo gradiente Argon, tarjeta centrada, inputs con iconos.
- [x] Dashboard con tarjetas estadÃ­sticas de empresas/sucursales/usuarios/dispositivos, iconos de colores por mÃ©trica.
- [x] Flash messages con iconos contextuales (success, warning, danger).
- [x] Ãndices admin actualizados: iconos en headers, badges de estado por color (ACTIVE=success, BLOCKED=danger, PENDING=warning), iconos en botones de acciÃ³n.
- [x] Formularios admin actualizados: iconos en headers, labels consistentes, botones con iconos.
- [x] Detalle de auditorÃ­a refinado con layout Argon.
- [x] TipografÃ­a Open Sans importada desde Bunny Fonts.
- [x] Variables CSS centralizadas para colores y dimensiones Argon.
- [x] Sidebar responsivo con toggle para mobile.

Puntos parciales:
- [~] Colapso completo del sidebar para desktop sigue pendiente (ahora es fijo 250px).

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\public\css\argon.css
- C:\xampp\php\www\BSLotery\resources\views\layouts\app.blade.php
- C:\xampp\php\www\BSLotery\resources\views\auth\login.blade.php
- C:\xampp\php\www\BSLotery\resources\views\dashboard.blade.php
- C:\xampp\php\www\BSLotery\resources\views\partials\flash.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\companies\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\companies\form.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\branches\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\branches\form.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\users\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\users\form.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\roles\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\roles\permissions.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\devices\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\audit\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\audit\show.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Argon Dashboard se implementa como CSS personalizado sobre Bootstrap 5, no como paquete npm/composer, para mantener simplicidad y evitar dependencias pesadas.
- Bootstrap Icons v1.11.3 como librerÃ­a de iconos principal para sidebar y acciones.
- Paleta Argon: primary #5e72e4, success #2dce89, info #11cdef, warning #fb6340, danger #f5365c.
- Sidebar fijo a 250px con toggle mobile; colapso desktop queda para iteraciÃ³n futura.
- Layout autenticado y no autenticado comparten el mismo archivo base.

Riesgos detectados:
- El CSS de Argon estÃ¡ en `public/css/argon.css` y no pasa por Vite; no es cache-busted. Aceptable para entorno local/desarrollo.
- Las vistas de licenciamiento y setup inicial no se actualizaron con el nuevo estilo Argon; se heredan del layout base que ya incluye el CSS.

ValidaciÃ³n ejecutada:
- `php artisan test` correcto: 8 tests, 46 assertions.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 32 rutas.

PrÃ³ximo paso recomendado:
- Cerrar Fase 1 con los endpoints API pendientes (login, companies, branches, users, roles, devices) o declarar Fase 1 cerrada y avanzar a Fase 2 (catÃ¡logos de loterÃ­a, pagos y lÃ­mites).

---

## 2026-05-16 â€” Fase 2: CatÃ¡logos de loterÃ­a, pagos y lÃ­mites

Responsable: Codex (continuaciÃ³n).

Fase trabajada:

Fase 2 â€” CatÃ¡logos de loterÃ­a, pagos y lÃ­mites.

Puntos completados:
- [x] Migraciones: `lotteries`, `draws`, `bet_types`, `payout_rules`, `limit_rules`, `limit_consumptions` con Ã­ndices compuestos y restricciones Ãºnicas.
- [x] Modelos con relaciones: `Lottery`, `Draw`, `BetType`, `PayoutRule`, `LimitRule`, `LimitConsumption`.
- [x] Policies registradas: `LotteryPolicy`, `DrawPolicy`, `BetTypePolicy`, `PayoutRulePolicy`, `LimitRulePolicy`.
- [x] `PayoutResolverService` con prioridad de 6 niveles (sucursal+sorteo â†’ empresa+jugada).
- [x] `LimitValidationService` con soporte para GLOBAL, SINGLE_NUMBER, NUMBER_RANGE, NUMBER_LIST.
- [x] `LimitValidationService` con polÃ­ticas BLOCK_FULL, ALLOW_AVAILABLE, REQUEST_AUTHORIZATION.
- [x] `LimitValidationService` con consumo transaccional vÃ­a `consume()` y `upsert`.
- [x] ExpansiÃ³n de rangos (ej. 00-50) y listas exactas de nÃºmeros.
- [x] Controladores admin: `LotteryController`, `DrawController`, `BetTypeController`, `PayoutRuleController`, `LimitRuleController`.
- [x] Form Requests con validaciÃ³n para store/update de cada entidad (10 clases).
- [x] Vistas Blade completas: Ã­ndices paginados con bÃºsqueda y filtros, formularios con Alpine.js para reglas dinÃ¡micas.
- [x] Pantalla de consumo de lÃ­mites (`limit-consumptions`) con tabla paginada.
- [x] Copia de reglas de pago entre sucursales.
- [x] Copia de reglas de lÃ­mite entre sucursales.
- [x] Cierre manual de sorteos con botÃ³n dedicado.
- [x] AprobaciÃ³n de reglas de pago (DRAFT â†’ ACTIVE).
- [x] Sidebar actualizado con secciÃ³n "LoterÃ­a" y sub-secciÃ³n "Sistema".
- [x] Permisos Fase 2 asignados a roles COMPANY_OWNER y ADMIN.
- [x] 30 nuevas rutas web registradas (total: 62 rutas).

Puntos pendientes:
- [ ] Endpoints API REST Fase 2.
- [ ] ImportaciÃ³n/exportaciÃ³n Excel de lÃ­mites.
- [ ] Pruebas unitarias de `PayoutResolverService` y `LimitValidationService`.
- [ ] Pruebas de integraciÃ³n para el flujo completo de catÃ¡logos.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\database\migrations\2026_05_16_000003_create_lottery_core_tables.php
- C:\xampp\php\www\BSLotery\app\Models\Lottery.php
- C:\xampp\php\www\BSLotery\app\Models\Draw.php
- C:\xampp\php\www\BSLotery\app\Models\BetType.php
- C:\xampp\php\www\BSLotery\app\Models\PayoutRule.php
- C:\xampp\php\www\BSLotery\app\Models\LimitRule.php
- C:\xampp\php\www\BSLotery\app\Models\LimitConsumption.php
- C:\xampp\php\www\BSLotery\app\Services\Lottery\PayoutResolverService.php
- C:\xampp\php\www\BSLotery\app\Services\Lottery\LimitValidationService.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\LotteryController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\DrawController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\BetTypeController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\PayoutRuleController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\LimitRuleController.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\LotteryStoreRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\LotteryUpdateRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\DrawStoreRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\DrawUpdateRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\BetTypeStoreRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\BetTypeUpdateRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\PayoutRuleStoreRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\PayoutRuleUpdateRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\LimitRuleStoreRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\LimitRuleUpdateRequest.php
- C:\xampp\php\www\BSLotery\app\Policies\LotteryPolicy.php
- C:\xampp\php\www\BSLotery\app\Policies\DrawPolicy.php
- C:\xampp\php\www\BSLotery\app\Policies\BetTypePolicy.php
- C:\xampp\php\www\BSLotery\app\Policies\PayoutRulePolicy.php
- C:\xampp\php\www\BSLotery\app\Policies\LimitRulePolicy.php
- C:\xampp\php\www\BSLotery\app\Providers\AppServiceProvider.php
- C:\xampp\php\www\BSLotery\resources\views\admin\lotteries\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\lotteries\form.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\draws\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\draws\form.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\bet-types\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\bet-types\form.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\payout-rules\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\payout-rules\form.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\limit-rules\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\limit-rules\form.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\limit-rules\consumptions.blade.php
- C:\xampp\php\www\BSLotery\resources\views\layouts\app.blade.php
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\database\seeders\RoleSeeder.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Los permisos de `BetType` se reutilizan con los de `lotteries.*` (lotteries.view, lotteries.create, lotteries.update).
- `LimitValidationService.consume()` usa `upsert` de Laravel para el consumo transaccional de lÃ­mites, compatible con MySQL.
- Las reglas de lÃ­mite soportan Alpine.js para mostrar/ocultar campos segÃºn el tipo de regla seleccionado.
- El formulario de lista de nÃºmeros acepta entrada por coma (ej. "00,12,25,38,49") y se almacena como JSON.
- El cierre de sorteo es irreversible por UI; solo cambia estado a CLOSED.

Riesgos detectados:
- `LimitValidationService.consume()` usa `DB::raw()` en upsert; funciona en MySQL pero podrÃ­a necesitar ajuste para otros motores.
- Las vistas de payout-rules y limit-rules usan Alpine.js CDN inline; si no se incluye en el layout, los campos condicionales no funcionarÃ¡n. Se incluyÃ³ Alpine.js en el `<head>` del layout.

ValidaciÃ³n ejecutada:
- `php artisan migrate --seed` correcto.
- `php artisan test` correcto: 8 tests, 46 assertions.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 62 rutas.

PrÃ³ximo paso recomendado:
- Continuar con Fase 3: Caja y contabilidad base (`cash_sessions`, `cash_movements`, `accounting_accounts`, `journal_entries`, `CashService`, `AccountingService`).

---

## 2026-05-16 â€” Fase 3: Caja y contabilidad base

Responsable: Codex (continuaciÃ³n).

Fase trabajada:

Fase 3 â€” Caja y contabilidad base.

Puntos completados:
- [x] Migraciones: `cash_sessions`, `cash_movements`, `accounting_accounts`, `journal_entries`, `journal_entry_lines` con Ã­ndices.
- [x] Modelos con relaciones: `CashSession`, `CashMovement`, `AccountingAccount`, `JournalEntry`, `JournalEntryLine`.
- [x] Policies: `CashSessionPolicy`, `JournalEntryPolicy` registradas.
- [x] `CashService`: open, recordMovement (SALE, CANCELLATION, PRIZE_PAYMENT, CASH_IN, CASH_OUT, EXPENSE, PAYROLL_PAYMENT, ADJUSTMENT), close, confirm, reopen, getActiveSession, recalculateExpectedCash.
- [x] FÃ³rmula de efectivo esperado: `opening + sales + cash_in âˆ’ cancellations âˆ’ prizes_paid âˆ’ cash_out âˆ’ expenses`.
- [x] DetecciÃ³n automÃ¡tica de faltante/sobrante al cerrar caja.
- [x] `AccountingService`: createEntry genÃ©rico, entryForSale, entryForPrizePayment, entryForExpense, entryForShortage, entryForPayroll.
- [x] NumeraciÃ³n automÃ¡tica de asientos (`JE-YYYYMM-NNNN`).
- [x] `AccountingAccountSeeder` con 12 cuentas contables iniciales por empresa.
- [x] Controladores: `CashController` (index, current, open, movement, close, confirm, reopen), `AccountingController` (accounts, journal, showEntry).
- [x] Form Requests: `CashOpenRequest`, `CashMovementRequest`, `CashCloseRequest`.
- [x] Vistas Blade: Ã­ndice de sesiones, caja actual (8 tarjetas de mÃ©tricas + tabla de movimientos), caja vacÃ­a, abrir caja, movimiento, cerrar caja (con fÃ³rmula visible), catÃ¡logo de cuentas, diario contable, detalle de asiento.
- [x] Sidebar con secciÃ³n "Operaciones" (Caja, Contabilidad).
- [x] Permisos de caja y contabilidad asignados a COMPANY_OWNER y ADMIN.
- [x] 13 nuevas rutas web (total: 75 rutas).

Puntos pendientes:
- [ ] Pruebas unitarias de `CashService` y `AccountingService`.
- [ ] IntegraciÃ³n de validaciÃ³n "no vender sin caja abierta" en Fase 4.
- [ ] APIs REST de caja y contabilidad.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\database\migrations\2026_05_16_000004_create_cash_and_accounting_tables.php
- C:\xampp\php\www\BSLotery\app\Models\CashSession.php
- C:\xampp\php\www\BSLotery\app\Models\CashMovement.php
- C:\xampp\php\www\BSLotery\app\Models\AccountingAccount.php
- C:\xampp\php\www\BSLotery\app\Models\JournalEntry.php
- C:\xampp\php\www\BSLotery\app\Models\JournalEntryLine.php
- C:\xampp\php\www\BSLotery\app\Services\Cash\CashService.php
- C:\xampp\php\www\BSLotery\app\Services\Accounting\AccountingService.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\CashController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\AccountingController.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\CashOpenRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\CashMovementRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\CashCloseRequest.php
- C:\xampp\php\www\BSLotery\app\Policies\CashSessionPolicy.php
- C:\xampp\php\www\BSLotery\app\Policies\JournalEntryPolicy.php
- C:\xampp\php\www\BSLotery\app\Providers\AppServiceProvider.php
- C:\xampp\php\www\BSLotery\resources\views\admin\cash\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\cash\current.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\cash\empty.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\cash\open.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\cash\movement.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\cash\close.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\accounting\accounts.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\accounting\journal.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\accounting\entry.blade.php
- C:\xampp\php\www\BSLotery\resources\views\layouts\app.blade.php
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\database\seeders\DatabaseSeeder.php
- C:\xampp\php\www\BSLotery\database\seeders\AccountingAccountSeeder.php
- C:\xampp\php\www\BSLotery\database\seeders\RoleSeeder.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- `CashService.recordMovement()` actualiza los totales de la sesiÃ³n vÃ­a `DB::raw()` en una transacciÃ³n, luego recalcula `expected_cash`.
- La validaciÃ³n "no vender sin caja abierta" se integrarÃ¡ en `TicketSaleService` en Fase 4.
- Las cuentas contables se crean por empresa vÃ­a seeder; cada empresa recibe las 12 cuentas base.
- El cierre de caja guarda automÃ¡ticamente `shortage_amount` y `surplus_amount` segÃºn diferencia entre contado y esperado.

Riesgos detectados:
- `CashService.recordMovement()` usa `DB::raw()` para actualizar totales; compatible con MySQL.
- El `AccountingService` requiere que existan cuentas contables con los cÃ³digos esperados (1100, 4000, 5000, 5100, etc.); el seeder garantiza esto.

ValidaciÃ³n ejecutada:
- `php artisan migrate --seed` correcto.
- `php artisan test` correcto: 8 tests, 46 assertions.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 75 rutas.

PrÃ³ximo paso recomendado:
- Continuar con Fase 4: Venta online PC (tickets, ticket_details, `TicketSaleService`, integraciÃ³n con caja, lÃ­mites y contabilidad).

---

## 2026-05-16 â€” Fase 4: Venta online PC

Responsable: Codex (continuaciÃ³n).

Fase trabajada:

Fase 4 â€” Venta online PC.

Puntos completados:
- [x] Migraciones: `tickets`, `ticket_details`, `print_jobs`, `printer_configs` con Ã­ndices compuestos y claves Ãºnicas.
- [x] Modelos: `Ticket`, `TicketDetail`, `PrintJob`, `PrinterConfig` con relaciones completas.
- [x] `TicketSaleService` â€” servicio central de ventas:
  - `preview()`: vista previa sin guardar, resuelve pagos y calcula premios posibles.
  - `sell()`: venta transaccional completa con DB::transaction:
    1. Valida sucursal activa, venta online habilitada.
    2. Valida sorteo abierto, loterÃ­a activa, hora de cierre.
    3. Valida caja abierta (si aplica control de caja).
    4. Resuelve regla de pago por prioridad (PayoutResolverService).
    5. Valida lÃ­mites con row locking (LimitValidationService).
    6. Crea ticket + ticket_details.
    7. Actualiza limit_consumptions (consume).
    8. Actualiza cash_session vÃ­a CashService.
    9. Crea asiento contable vÃ­a AccountingService.
    10. Crea print_job con contenido ESC/POS.
    11. AuditorÃ­a de venta.
    12. Rollback total si algo falla.
  - `cancel()`: anula ticket, revierte lÃ­mites (cancelled_amount), registra movimiento de caja, audita.
  - `reprint()`: incrementa print_count, crea nuevo print_job, audita.
- [x] NumeraciÃ³n automÃ¡tica de tickets: `{branch_code}-{YYMMDD}-{NNNN}`.
- [x] Contenido de impresiÃ³n ESC/POS generado automÃ¡ticamente.
- [x] `TicketController`: index, create (venta rÃ¡pida con JS dinÃ¡mico), preview, store, show, cancel (modal), reprint.
- [x] Form Requests: `TicketPreviewRequest`, `TicketSaleRequest`.
- [x] `TicketPolicy` registrada.
- [x] Vistas: venta rÃ¡pida (formulario dinÃ¡mico con Alpine/JS para agregar/quitar jugadas), preview (tabla con totales y confirmaciÃ³n), historial (Ã­ndice paginado), detalle (jugadas + impresiones + datos + modal anulaciÃ³n).
- [x] Sidebar: secciÃ³n "Operaciones" con "Vender" y "Tickets".
- [x] Permisos de ventas asignados a COMPANY_OWNER, ADMIN y CASHIER.
- [x] 7 nuevas rutas web (total: 82 rutas).

Puntos pendientes:
- [ ] Pruebas de integraciÃ³n de venta (requiere datos de loterÃ­as, sorteos, jugadas y reglas).
- [ ] Print Agent local para impresiÃ³n real.
- [ ] API REST de tickets.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\database\migrations\2026_05_16_000005_create_tickets_and_printing_tables.php
- C:\xampp\php\www\BSLotery\app\Models\Ticket.php
- C:\xampp\php\www\BSLotery\app\Models\TicketDetail.php
- C:\xampp\php\www\BSLotery\app\Models\PrintJob.php
- C:\xampp\php\www\BSLotery\app\Models\PrinterConfig.php
- C:\xampp\php\www\BSLotery\app\Services\Sales\TicketSaleService.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\TicketController.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\TicketPreviewRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\TicketSaleRequest.php
- C:\xampp\php\www\BSLotery\app\Policies\TicketPolicy.php
- C:\xampp\php\www\BSLotery\app\Providers\AppServiceProvider.php
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\preview.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\show.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\no-branch.blade.php
- C:\xampp\php\www\BSLotery\resources\views\layouts\app.blade.php
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\database\seeders\RoleSeeder.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- La venta se ejecuta en una sola transacciÃ³n DB; si cualquier paso falla, se hace rollback completo.
- `TicketSaleService` inyecta `PayoutResolverService`, `LimitValidationService`, `CashService` y `AccountingService`; cada uno es responsable de su dominio.
- La anulaciÃ³n no elimina registros; marca `CANCELLED` y revierte lÃ­mites vÃ­a `cancelled_amount`.
- El contenido de impresiÃ³n se genera en formato texto ESC/POS (32 columnas) y se almacena en `print_jobs.content`.
- No se creÃ³ `PrintJobService` separado; la lÃ³gica de impresiÃ³n estÃ¡ en `TicketSaleService` dado que es simple (crear registro PENDING).

Riesgos detectados:
- La concurrencia en lÃ­mites se mitiga con `SELECT FOR UPDATE` (lockForUpdate) en `LimitValidationService`.
- Si no hay reglas de pago configuradas, el multiplicador por defecto es 1.0 (podrÃ­a causar confusiÃ³n).
- La validaciÃ³n de hora de cierre usa `now()->format('H:i')` comparaciÃ³n de strings; funciona para este contexto.

ValidaciÃ³n ejecutada:
- `php artisan migrate --seed` correcto.
- `php artisan test` correcto: 8 tests, 46 assertions.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 82 rutas.

PrÃ³ximo paso recomendado:
- Continuar con Fase 5: Resultados, ganadores y pagos (ciclo completo de sorteo).

---

## 2026-05-16 â€” Fase 5: Resultados, ganadores y pagos

Responsable: Codex (continuaciÃ³n).

Fase trabajada:

Fase 5 â€” Resultados, ganadores y pagos.

Puntos completados:
- [x] Migraciones: `results`, `winner_tickets`, `payment_authorizations`, `prize_payments` con Ã­ndices y restricciones Ãºnicas.
- [x] Modelos: `Result`, `WinnerTicket`, `PaymentAuthorization`, `PrizePayment` con relaciones.
- [x] `WinnerCalculationService`:
  - `calculate()`: busca jugadas activas, compara con nÃºmeros ganadores (1ro, 2do, 3ro), crea `winner_tickets` en PENDING_RELEASE, marca tickets/detalles como WINNER/LOSER, crea `payment_authorization` pendiente, transaccional.
  - `authorizePayments()`: cambia autorizaciÃ³n a AUTHORIZED, libera todos los winner_tickets a RELEASED, actualiza sorteo a PAYMENTS_RELEASED.
- [x] `PrizePaymentService`:
  - `pay()`: valida estado RELEASED, crea `prize_payment`, actualiza winner_ticket/ticket/detalle a PAID, registra movimiento de caja, genera asiento contable, transaccional.
- [x] `ResultController`: index, create (con selecciÃ³n dinÃ¡mica loterÃ­aâ†’sorteo), store (registro de resultado + actualizaciÃ³n de sorteo), confirm (validaciÃ³n de mismo usuario), calculateWinners, authorizePayments.
- [x] `PrizeController`: pending (lista de premios RELEASED/PENDING_RELEASE), pay (pago de premio individual), history (pagos realizados).
- [x] Vistas: Ã­ndice de resultados con acciones contextuales (confirmar/calcular/autorizar segÃºn estado), formulario de registro con Alpine.js, premios pendientes con botÃ³n pagar, historial de pagos.
- [x] Policies: `ResultPolicy`, `WinnerTicketPolicy`.
- [x] Sidebar: "Resultados" y "Premios" en secciÃ³n Operaciones.
- [x] 9 nuevas rutas web (total: 91 rutas).

Puntos pendientes:
- [ ] RevisiÃ³n de premios grandes (alerta/configuraciÃ³n de umbral).
- [ ] Jobs asÃ­ncronos (`CalculateWinnersJob`, `ReleasePaymentsJob`) para sorteos con alto volumen.
- [ ] Pruebas de integraciÃ³n del ciclo completo.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\database\migrations\2026_05_16_000006_create_results_and_prizes_tables.php
- C:\xampp\php\www\BSLotery\app\Models\Result.php
- C:\xampp\php\www\BSLotery\app\Models\WinnerTicket.php
- C:\xampp\php\www\BSLotery\app\Models\PaymentAuthorization.php
- C:\xampp\php\www\BSLotery\app\Models\PrizePayment.php
- C:\xampp\php\www\BSLotery\app\Services\Results\WinnerCalculationService.php
- C:\xampp\php\www\BSLotery\app\Services\Results\PrizePaymentService.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\ResultController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\PrizeController.php
- C:\xampp\php\www\BSLotery\app\Policies\ResultPolicy.php
- C:\xampp\php\www\BSLotery\app\Policies\WinnerTicketPolicy.php
- C:\xampp\php\www\BSLotery\app\Providers\AppServiceProvider.php
- C:\xampp\php\www\BSLotery\resources\views\admin\results\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\results\form.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\prizes\pending.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\prizes\history.blade.php
- C:\xampp\php\www\BSLotery\resources\views\layouts\app.blade.php
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- El cÃ¡lculo de ganadores es sÃ­ncrono para esta fase; se moverÃ¡ a Jobs en Fase 10 cuando el volumen lo requiera.
- La validaciÃ³n "mismo usuario no registra y confirma" se implementa en el controlador verificando `registered_by !== auth()->id()`.
- El pago de premio requiere caja abierta (usa `getActiveSession`); si no hay caja, se permite el pago sin registrar movimiento de caja.

Riesgos detectados:
- El cÃ¡lculo de ganadores recorre todos los ticket_details del sorteo en memoria; con 500 bancas activas podrÃ­a ser pesado. MitigaciÃ³n: chunking y Jobs en Fase 10.
- No hay umbral configurable para "premios grandes"; se asume que la autorizaciÃ³n de pagos es suficiente por ahora.

ValidaciÃ³n ejecutada:
- `php artisan migrate --seed` correcto.
- `php artisan test` correcto: 8 tests, 46 assertions.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 91 rutas.

PrÃ³ximo paso recomendado:
- Fase 6 (Android offline) requiere entorno Kotlin/Android Studio separado. Recomiendo saltar a Fase 8 (Reportes) o Fase 9 (NÃ³mina) si se prefiere seguir en backend web.

---

## 2026-05-17 â€” POS: bÃºsqueda, copiado, pago y submenÃº de impresora

Responsable: Codex.

Fase trabajada:

Fase 4 â€” Venta online PC / integraciÃ³n operativa tipo TicketPro.

Puntos completados:
- [x] Se revisÃ³ el estado del proyecto: Fases 1 a 5 tienen implementaciÃ³n web sustancial; Fase 4 ya tenÃ­a venta, caja, lÃ­mites, contabilidad y print jobs.
- [x] Se revisÃ³ la app existente `C:\xampp\php\www\BSLotteryApp`: venta mÃ³vil tipo TicketPro, carrusel de loterÃ­as, teclado rÃ¡pido, venta por mÃºltiples sorteos y configuraciÃ³n Bluetooth.
- [x] Se corrigiÃ³ el envÃ­o multi-sorteo en la pantalla de venta web: ahora vende secuencialmente por `fetch` y no pierde ventas por navegaciÃ³n del primer formulario.
- [x] Se agregÃ³ endpoint web `admin.tickets.lookup` para buscar tickets por `ticket_number`, `uuid`, URL o token `ticket:{uuid}`.
- [x] Se agregÃ³ panel en pantalla de venta para buscar/leer ticket por nÃºmero o QR.
- [x] Se agregÃ³ copiado de jugadas desde ticket consultado al ticket actual usando las loterÃ­as abiertas seleccionadas.
- [x] Se agregÃ³ acceso para pagar premios liberados desde la pantalla de venta si el usuario tiene `prizes.pay`.
- [x] Se agregÃ³ soporte de escaneo QR por `BarcodeDetector` cuando el navegador lo soporte y fallback para lector tipo pistola/teclado.
- [x] Se agregÃ³ token QR textual `ticket:{uuid}` al contenido de impresiÃ³n y botones para copiar nÃºmero/QR en detalle de ticket.
- [x] El enlace de impresora dejÃ³ de estar suelto: ahora queda bajo submenÃº `Impresora > ConfiguraciÃ³n`.
- [x] Se agregÃ³ prueba feature para lookup por nÃºmero y token QR.

Puntos parciales:
- [~] QR fÃ­sico impreso queda preparado como token en contenido de impresiÃ³n; la generaciÃ³n grÃ¡fica real del QR debe resolverse en Print Agent/ESC-POS.
- [~] Escaneo por cÃ¡mara depende de soporte del navegador (`BarcodeDetector`); el flujo robusto para bancas sigue siendo lector QR fÃ­sico que pega el cÃ³digo en el campo.
- [~] Pago desde venta usa el flujo existente de `PrizePaymentService`; pago masivo por ticket completo queda pendiente.
- [~] La migraciÃ³n `2026_05_17_070755_create_personal_access_tokens_table` aparece pendiente en DB local.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\TicketController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\PrizeController.php
- C:\xampp\php\www\BSLotery\app\Services\Sales\TicketSaleService.php
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\show.blade.php
- C:\xampp\php\www\BSLotery\resources\views\layouts\app.blade.php
- C:\xampp\php\www\BSLotery\tests\Feature\ExampleTest.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- La pantalla de venta serÃ¡ tambiÃ©n punto rÃ¡pido de consulta/copiado/pago, pero el pago sigue protegido por permiso `prizes.pay`.
- Copiar un ticket no reutiliza el sorteo viejo cerrado; copia las jugadas hacia las loterÃ­as/sorteos abiertos seleccionados en el POS actual.
- El QR operativo usa `ticket:{uuid}` para no depender de nÃºmeros visibles que puedan cambiar de formato.
- El submenÃº se llama `Impresora` en singular y contiene `ConfiguraciÃ³n`.

Riesgos detectados:
- Hay servicios con parÃ¡metros monetarios tipados como `float`; aunque la base usa `DECIMAL`, conviene normalizar a strings/objetos de dinero en una refactorizaciÃ³n corta antes de endurecer ventas concurrentes.
- La generaciÃ³n grÃ¡fica de QR no debe depender de servicios externos; debe implementarse en Print Agent o con librerÃ­a local aprobada.

ValidaciÃ³n ejecutada:
- `php -l` correcto en `TicketController` y `PrizeController`.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 116 rutas.
- `php artisan test` correcto: 32 tests, 304 assertions.
- BÃºsqueda de patrones de mojibake sin coincidencias fuera de carpetas excluidas.
- RevisiÃ³n de BOM sin coincidencias fuera de carpetas excluidas.

PrÃ³ximo paso recomendado:
- Probar manualmente la pantalla `admin/tickets/create` con sesiÃ³n real: vender en mÃºltiples sorteos, buscar un ticket, copiar jugadas y pagar un premio liberado. DespuÃ©s cerrar Print Agent/QR grÃ¡fico.

---

## 2026-05-17 â€” POS: jugadas separadas por loterÃ­a seleccionada

Responsable:
- Codex

Fase trabajada:
- Fase operativa de ventas / compatibilidad con flujo TicketPro.

Puntos completados:
- [x] La pantalla de venta ya no agrega varias loterÃ­as en una sola fila con el texto `2 loterÃ­as`.
- [x] Cada jugada agregada con mÃºltiples loterÃ­as seleccionadas crea una fila independiente por sorteo con su propio `draw_id`.
- [x] El cobro agrupa las jugadas por sorteo y envÃ­a a backend solo las jugadas correspondientes a cada `draw_id`.
- [x] La copia de jugadas desde un ticket existente replica cada jugada hacia las loterÃ­as abiertas seleccionadas, manteniendo filas independientes.
- [x] Al desmarcar una loterÃ­a se remueven del ticket actual las jugadas asociadas a ese sorteo para evitar ventas ocultas.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- El POS mantiene una fila por combinaciÃ³n `draw_id + bet_type_id + number_value`; si se repite la misma jugada en la misma loterÃ­a, se acumula el monto en esa fila.
- La vista previa usa el primer sorteo con jugadas porque el flujo actual de preview acepta un solo `draw_id`; el cobro real sÃ­ procesa todos los sorteos agrupados.

Riesgos detectados:
- Si existen sorteos con el mismo nombre visible, el usuario puede ver etiquetas repetidas aunque sean `draw_id` distintos. Conviene diferenciar con hora/cÃ³digo de sorteo cuando el catÃ¡logo quede final.

ValidaciÃ³n ejecutada:
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto: 32 tests, 304 assertions.
- VerificaciÃ³n en navegador: con dos sorteos seleccionados y entrada `1020 100`, el carrito mostrÃ³ dos filas separadas `P 10-20`, cada una con monto RD$ 100.00 y premio estimado RD$ 150000.00.

PrÃ³ximo paso recomendado:
- Probar una venta real en dos sorteos desde el POS y confirmar en historial que se generan tickets/jugadas con `draw_id` correcto.

---

## 2026-05-17 â€” CatÃ¡logo inicial de loterÃ­as dominicanas y extranjeras vendidas en RD

Responsable:
- Codex

Fase trabajada:
- Fase operativa de loterÃ­as/sorteos para POS.

Puntos completados:
- [x] Se verificÃ³ que el POS repetÃ­a `LoterÃ­a Nacional` porque existÃ­an 4 sorteos abiertos demo ligados a una sola loterÃ­a.
- [x] Se agregÃ³ seeder idempotente `DominicanLotteryCatalogSeeder` con catÃ¡logo inicial basado en Conectate: loterÃ­as dominicanas, extranjeras y sorteos millonarios usados en el mercado local.
- [x] Se cargaron 32 loterÃ­as activas y 32 sorteos abiertos del dÃ­a para la empresa local.
- [x] Se cerraron sorteos demo duplicados sin borrado fÃ­sico.
- [x] Se integrÃ³ el seeder al `DatabaseSeeder` para nuevas instalaciones.

Puntos parciales:
- [x] El seeder crea sorteos para la fecha actual cuando se ejecuta; falta un comando/scheduler diario para generar automÃ¡ticamente los sorteos de cada dÃ­a. (Resuelto 2026-05-20 con `DrawGenerationService` + scheduler `dailyAt('00:01')`).
- [~] Los horarios pÃºblicos pueden variar por feriados o cambios de operador; deben quedar editables desde administraciÃ³n.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\database\seeders\DominicanLotteryCatalogSeeder.php
- C:\xampp\php\www\BSLotery\database\seeders\DatabaseSeeder.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Se modelÃ³ cada producto vendible del POS como una loterÃ­a operativa con su sorteo diario, para evitar que el POS muestre una misma loterÃ­a repetida sin contexto claro.
- No se borraron sorteos demo; se cerraron para mantener trazabilidad y evitar efectos colaterales.
- El cÃ³digo histÃ³rico `LOTNAC` se reutilizÃ³ para `LoterÃ­a Nacional` real, y el borrador local `NAC-NOCHE` quedÃ³ inactivo.

Riesgos detectados:
- Algunos productos como PowerBall, Mega Millions, Loto 5 o Super Kino no se comportan igual que quiniela/palÃ©/tripleta; antes de venderlos de verdad hay que definir sus tipos de jugada y reglas de pago especÃ­ficas.
- La pantalla de catÃ¡logo pagina 20 registros; el total activo supera esa cantidad y aparece repartido en varias pÃ¡ginas.

ValidaciÃ³n ejecutada:
- Fuente revisada: https://www.conectate.com.do/loterias/
- `php artisan db:seed --class=DominicanLotteryCatalogSeeder` correcto.
- Conteo local: 32 loterÃ­as activas, 32 sorteos abiertos de hoy, 0 duplicados abiertos por loterÃ­a/fecha/hora.
- VerificaciÃ³n en navegador: el POS mostrÃ³ 32 checkboxes de loterÃ­as abiertas.
- `php -l` correcto en el seeder.
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto: 32 tests, 304 assertions.

PrÃ³ximo paso recomendado:
- Crear comando `draws:generate-daily` con scheduler para generar sorteos diarios por empresa/sucursal y ajustar reglas especiales por producto antes de habilitar loterÃ­as millonarias.

---

## 2026-05-17 â€” Cierre/cancelaciÃ³n de sorteos con resoluciÃ³n de tickets activos

Responsable:
- Codex

Fase trabajada:
- Fase operativa de sorteos, ventas y auditorÃ­a financiera.

Puntos completados:
- [x] Se agregÃ³ operaciÃ³n explÃ­cita para cerrar sorteos abiertos desde `LoterÃ­a > Sorteos`.
- [x] Se agregÃ³ operaciÃ³n explÃ­cita para cancelar sorteos abiertos desde `LoterÃ­a > Sorteos`.
- [x] Si el sorteo tiene jugadas activas, el cierre permite decidir entre mantener tickets en el sorteo actual, transferirlos al prÃ³ximo sorteo o anularlos.
- [x] Si se cancela un sorteo con jugadas activas, se exige decidir entre transferir tickets al prÃ³ximo sorteo o anularlos.
- [x] La transferencia mueve las jugadas al prÃ³ximo sorteo de la misma loterÃ­a y conserva el ticket activo.
- [x] La anulaciÃ³n usa el flujo existente de `TicketSaleService::cancel`, registra caja, lÃ­mites y auditorÃ­a por ticket.
- [x] Se agregaron metadatos de cancelaciÃ³n/transferencia sin borrado fÃ­sico.

Puntos parciales:
- [~] Si no existe prÃ³ximo sorteo, el servicio crea el siguiente sorteo de la misma loterÃ­a para el dÃ­a siguiente con el mismo horario. Conviene reemplazar esto por el comando diario `draws:generate-daily`.
- [~] Las jugadas transferidas conservan el multiplicador aplicado al momento de venta, como exige la regla del sistema; si el negocio quiere recalcular por nuevo sorteo, debe definirse como cambio funcional separado.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\database\migrations\2026_05_17_120000_add_draw_lifecycle_and_ticket_transfer_fields.php
- C:\xampp\php\www\BSLotery\app\Services\Lottery\DrawLifecycleService.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\DrawController.php
- C:\xampp\php\www\BSLotery\app\Models\Draw.php
- C:\xampp\php\www\BSLotery\app\Models\TicketDetail.php
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\resources\views\admin\draws\index.blade.php
- C:\xampp\php\www\BSLotery\tests\Feature\SaleAndPrizeCycleTest.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- La operaciÃ³n se implementÃ³ sobre `draws` porque operativamente se cierra o cancela el sorteo vendible, no el catÃ¡logo maestro de loterÃ­as.
- El cierre normal mantiene compatibilidad con el flujo existente de resultados: los tickets pueden quedar vÃ¡lidos para el mismo sorteo cerrado.
- Cancelar un sorteo exige motivo y polÃ­tica de resoluciÃ³n de tickets.

Riesgos detectados:
- Si un ticket llegara a mezclar jugadas activas de mÃ¡s de un sorteo, la transferencia automÃ¡tica se bloquea para evitar modificar importes parcialmente sin una regla contable clara.
- La generaciÃ³n automÃ¡tica del siguiente sorteo debe formalizarse para evitar depender de creaciÃ³n bajo demanda.

ValidaciÃ³n ejecutada:
- `php artisan migrate --path=database/migrations/2026_05_17_120000_add_draw_lifecycle_and_ticket_transfer_fields.php` correcto.
- `php -l` correcto en servicio, controlador y modelos modificados.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 117 rutas.
- `php artisan test` correcto: 34 tests, 321 assertions.
- VerificaciÃ³n en navegador: `admin/draws?status=OPEN` muestra acciones de cierre y cancelaciÃ³n para sorteos abiertos.

PrÃ³ximo paso recomendado:
- Implementar `draws:generate-daily` y revisar lÃ­mites/anulaciÃ³n para normalizar definitivamente los consumos de lÃ­mites cuando se cancelan tickets.

---

## 2026-05-17 â€” POS: deseleccionar loterÃ­as no borra jugadas del ticket actual

Responsable:
- Codex

Fase trabajada:
- Fase operativa de ventas POS.

Puntos completados:
- [x] Desmarcar una loterÃ­a en el POS ya no elimina las jugadas previamente agregadas para esa loterÃ­a.
- [x] El botÃ³n de deseleccionar todas las loterÃ­as ya no limpia el ticket actual.
- [x] El cobro toma los sorteos desde las filas existentes del ticket actual, no desde la selecciÃ³n vigente de loterÃ­as.
- [x] La vista previa usa el primer sorteo con jugadas existentes aunque estÃ© desmarcado.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- La selecciÃ³n de loterÃ­as controla solo dÃ³nde se agregarÃ¡n nuevas jugadas.
- El ticket actual es la fuente de verdad para cobrar; cada fila conserva su `draw_id`.

Riesgos detectados:
- El usuario puede dejar todas las loterÃ­as desmarcadas y aun asÃ­ cobrar jugadas ya cargadas. Esto es intencional para preservar el ticket, pero conviene mostrar un indicador visual futuro de sorteos incluidos en el ticket.

ValidaciÃ³n ejecutada:
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto: 34 tests, 321 assertions.
- RevisiÃ³n de cÃ³digo: no quedan filtros que eliminen `plays` al desmarcar una loterÃ­a o al usar `deselectAll()`.

PrÃ³ximo paso recomendado:
- Mejorar el resumen del POS para mostrar `Sorteos en ticket` ademÃ¡s de `LoterÃ­as seleccionadas`.

---

## 2026-05-17 â€” LÃ­mites globales por jugada y soporte de lÃ­mites por sucursal

Responsable:
- Codex

Fase trabajada:
- Fase operativa de lÃ­mites de venta.

Puntos completados:
- [x] Se creÃ³ seeder idempotente para lÃ­mites por defecto en todas las empresas existentes.
- [x] Se creÃ³/activÃ³ el tipo de jugada `SUPER_PALE` cuando no exista.
- [x] Se creÃ³ lÃ­mite global `QUINIELA` por RD$ 3,000.00.
- [x] Se creÃ³ lÃ­mite global `PALE` por RD$ 200.00.
- [x] Se creÃ³ lÃ­mite global `TRIPLETA` por RD$ 50.00.
- [x] Se creÃ³ lÃ­mite global `SUPER_PALE` por RD$ 100.00.
- [x] Las reglas quedaron con `branch_id`, `lottery_id` y `draw_id` en `NULL`, aplicando a toda la empresa, todas las sucursales, loterÃ­as y sorteos.
- [x] Se verificÃ³ que el formulario permite definir lÃ­mites por sucursal mediante el campo `Sucursal`.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\database\seeders\DefaultLimitRuleSeeder.php
- C:\xampp\php\www\BSLotery\database\seeders\DatabaseSeeder.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Como `limit_rules.company_id` es obligatorio, â€œglobal para todas las empresasâ€ se implementa creando la misma regla por cada empresa existente.
- La regla por defecto usa `rule_type = GLOBAL`, `policy = BLOCK_FULL` y campos de sucursal/loterÃ­a/sorteo en `NULL`.

Riesgos detectados:
- `SUPER_PALE` queda disponible como tipo de jugada y lÃ­mite, pero su flujo completo de venta cross-lottery debe revisarse en POS antes de habilitarlo operativamente en producciÃ³n.
- Si se crean empresas nuevas despuÃ©s de correr el seeder, hay que volver a ejecutar `DefaultLimitRuleSeeder` o enganchar estos defaults al flujo de setup/creaciÃ³n de empresa.

ValidaciÃ³n ejecutada:
- `php artisan db:seed --class=DefaultLimitRuleSeeder` correcto.
- Consulta de base de datos confirmÃ³ 4 lÃ­mites globales activos: `QUINIELA=3000`, `PALE=200`, `TRIPLETA=50`, `SUPER_PALE=100`.
- VerificaciÃ³n en navegador: `admin/limit-rules/create` muestra selector de sucursal y `Super Pale` en jugadas.
- `php -l` correcto en seeders modificados.
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto: 34 tests, 321 assertions.

PrÃ³ximo paso recomendado:
- Integrar defaults de lÃ­mites al flujo de creaciÃ³n de nueva empresa y revisar venta real de `SUPER_PALE` cross-lottery.

---

## 2026-05-17 â€” POS: monto opcional y nuevos montos rÃ¡pidos

Responsable:
- Codex

Fase trabajada:
- Fase operativa de ventas POS.

Puntos completados:
- [x] El POS ya no inicia con monto preseleccionado.
- [x] Si se intenta agregar una jugada sin monto seleccionado ni monto escrito, el sistema solicita el monto al presionar Enter.
- [x] Si el usuario escribe nÃºmero y monto juntos, se respeta el monto escrito.
- [x] Se ampliaron los montos rÃ¡pidos a RD$ 5, 10, 20, 25, 50, 100, 200, 500, 1000 y 2000.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- El Ãºltimo monto usado se guarda solo cuando el usuario lo selecciona, lo escribe junto a la jugada o lo confirma en el prompt.
- Se mantiene la interpretaciÃ³n automÃ¡tica de 2, 4 y 6 dÃ­gitos para quiniela, pale y tripleta.

Riesgos detectados:
- Una entrada como `1020` sin monto se interpreta como pale `10-20`; para quiniela `10` con monto `20`, el usuario debe escribir `10 20`.

ValidaciÃ³n ejecutada:
- VerificaciÃ³n en navegador: el POS muestra `Sin monto` y los botones `$5`, `$10`, `$20`, `$25`, `$50`, `$100`, `$200`, `$500`, `$1000`, `$2000`.
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto: 34 tests, 321 assertions.
- BÃºsqueda de mojibake sin coincidencias en archivos tocados.

PrÃ³ximo paso recomendado:
- Agregar una ayuda visual breve en el POS para diferenciar `1020` como pale de `10 20` como quiniela con monto.

---

## 2026-05-17 â€” POS e impresiÃ³n: resumen de cobro con formato de ticket

Responsable:
- Codex

Fase trabajada:
- Fase operativa de ventas POS e impresiÃ³n.

Puntos completados:
- [x] El botÃ³n Cobrar ya no confirma solo con cantidad de jugadas/sorteos.
- [x] El resumen de cobro muestra cabecera, fecha, ticket pendiente, bloque `VENTA DE LOTERIA`, jugadas y total.
- [x] Las jugadas del resumen se agrupan por loterÃ­a/sorteo.
- [x] El contenido de `print_jobs` se alineÃ³ al formato tipo TicketPro agrupado por loterÃ­a.
- [x] Se muestran cÃ³digos cortos `q`, `pl`, `tri`, `sp` en el ticket.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\app\Services\Sales\TicketSaleService.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- El resumen previo a vender usa `Ticket No: PENDIENTE` porque el nÃºmero real se genera dentro de la transacciÃ³n de venta.
- Se conserva QR, cajero y datos operativos al final del contenido de impresiÃ³n.

Riesgos detectados:
- El `confirm()` nativo no permite tipografÃ­a monoespaciada real; si se requiere presentaciÃ³n idÃ©ntica visualmente al ticket, conviene sustituirlo por modal propio del POS.

ValidaciÃ³n ejecutada:
- `php -l app/Services/Sales/TicketSaleService.php` correcto.
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto: 40 tests, 379 assertions.

PrÃ³ximo paso recomendado:
- Probar en POS una venta con varias jugadas y varias loterÃ­as para confirmar el agrupado visual antes de cobrar.

---

## 2026-05-17 â€” CorrecciÃ³n POS: validar jugada antes de pedir monto

Responsable:
- Codex

Fase trabajada:
- Fase operativa de ventas POS.

Puntos completados:
- [x] La validaciÃ³n de formato de jugada corre antes de pedir monto.
- [x] `100` se rechaza inmediatamente y no abre el campo de monto.
- [x] `100 50` se rechaza como jugada invÃ¡lida y no agrega monto al flujo.
- [x] Solo jugadas vÃ¡lidas (`01`, `1214`, `121314`) pueden pasar al pedido de monto.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- El parser del POS valida primero pares de dos dÃ­gitos y resuelve tipo de jugada; el monto se procesa despuÃ©s.

Riesgos detectados:
- Ninguno nuevo.

ValidaciÃ³n ejecutada:
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto: 40 tests, 379 assertions.

PrÃ³ximo paso recomendado:
- Recargar POS y probar `100`, `100 50`, `01`, `1214`, `121314`.

---

## 2026-05-17 â€” CorrecciÃ³n POS: tripleta solo con pares de 2 dÃ­gitos

Responsable:
- Codex

Fase trabajada:
- Fase operativa de ventas POS.

Puntos completados:
- [x] Se eliminÃ³ la interpretaciÃ³n de 3 dÃ­gitos como tripleta.
- [x] Se bloquean entradas impares como `100` para evitar jugadas ambiguas.
- [x] Se mantiene `01` como quiniela.
- [x] Se mantiene `1214` como pale `12-14`.
- [x] Se mantiene `121314` como tripleta `12-13-14`.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Las jugadas del POS se interpretan exclusivamente por pares de dos dÃ­gitos para evitar errores de caja.

Riesgos detectados:
- Si un cliente dicta `1` como quiniela, el cajero debe escribir `01`.

ValidaciÃ³n ejecutada:
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto: 40 tests, 379 assertions.

PrÃ³ximo paso recomendado:
- Probar manualmente en POS: `100` debe rechazarse, `01` debe pedir monto/agregar quiniela, `1214` pale, `121314` tripleta.

---

## 2026-05-17 â€” CorrecciÃ³n POS: limpiar monto rÃ¡pido y pedir monto inline

Responsable:
- Codex

Fase trabajada:
- Fase operativa de ventas POS.

Puntos completados:
- [x] Se agregÃ³ botÃ³n explÃ­cito `Sin monto` debajo del input principal.
- [x] Presionar otra vez el mismo monto rÃ¡pido deja el POS sin monto fijo.
- [x] Escribir una jugada sin monto, por ejemplo `50`, y presionar Enter muestra un campo de monto dentro del POS.
- [x] Enter en el campo de monto agrega la jugada con el monto indicado.
- [x] Escape o Cancelar descarta el pedido de monto sin agregar jugada.
- [x] Los montos escritos manualmente ya no quedan fijados como monto rÃ¡pido para futuras jugadas.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Se reemplazÃ³ `window.prompt()` por un campo inline en el POS porque los prompts del navegador embebido son frÃ¡giles y poco claros para caja.
- Solo los botones rÃ¡pidos controlan el monto fijo persistente; los montos manuales aplican Ãºnicamente a la jugada actual.

Riesgos detectados:
- Si el navegador conserva la vista anterior, hay que recargar `admin/tickets/create` para tomar el Blade actualizado.

ValidaciÃ³n ejecutada:
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto: 40 tests, 379 assertions.
- `php artisan route:list --except-vendor` correcto: 117 rutas.
- `php -l` correcto en PHP tocados durante la sesiÃ³n.

PrÃ³ximo paso recomendado:
- Validar manualmente en POS: RD$10 dos veces, botÃ³n `Sin monto`, `50` + Enter + monto, `25 35`, `10-20 50`.

---

## 2026-05-17 â€” Endurecimiento nÃºcleo operativo de ventas, lÃ­mites, caja, pagos y resultados

Responsable:
- Codex

Fase trabajada:
- Fases 2, 3, 4 y 5: nÃºcleo operativo antes de Android, reportes y nÃ³mina.

Puntos completados:
- [x] POS: un monto rÃ¡pido activo se deselecciona al presionar el mismo botÃ³n otra vez.
- [x] POS: se mantiene el flujo de pedir monto al presionar Enter cuando no hay monto activo ni monto escrito.
- [x] POS: `25 35`, `10-20 50` y `123 20` respetan el monto escrito manualmente.
- [x] POS: se eliminÃ³ el cÃ¡lculo de premio estimado con multiplicadores quemados en JavaScript.
- [x] Venta: si no existe regla de pago activa aplicable, la venta no continÃºa.
- [x] Venta: cada detalle guarda `payout_rule_id`, `payout_multiplier`, `possible_prize` y `limit_rule_id` cuando aplica.
- [x] LÃ­mites: el consumo por rango se valida por `number_value`, no como bolsa compartida entre todos los nÃºmeros del rango.
- [x] LÃ­mites: el consumo vendido considera anulaciones al calcular disponibilidad.
- [x] Caja/premios: no se permite pagar premios sin caja abierta.
- [x] Premio grande: se agregÃ³ umbral `companies.big_prize_threshold`; premios iguales o superiores quedan `HELD` y no se liberan para pago directo.
- [x] Resultados: se creÃ³ `CalculateWinnersJob` y el cÃ¡lculo procesa detalles con `chunkById(500)`.
- [x] Dinero: se agregÃ³ `App\Support\Money` y se normalizaron montos crÃ­ticos en venta, lÃ­mites, caja y contabilidad.
- [x] Pruebas: se agregaron coberturas de artefactos de venta, regla de pago obligatoria, lÃ­mites por rango, simulaciÃ³n transaccional de lÃ­mite, caja requerida para premios y premio grande retenido.

Puntos parciales:
- [~] La normalizaciÃ³n decimal quedÃ³ aplicada al camino operativo crÃ­tico, pero todavÃ­a existen firmas heredadas con `float` en mÃ³dulos no tocados en esta sesiÃ³n.
- [~] La vista previa del POS sigue trabajando sobre el primer sorteo con jugadas. Se conserva para no cambiar la decisiÃ³n de negocio de tickets separados por sorteo.
- [~] En producciÃ³n con `QUEUE_CONNECTION=database`, el cÃ¡lculo de ganadores requiere un worker de cola activo para terminar fuera de la peticiÃ³n web.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\app\Support\Money.php
- C:\xampp\php\www\BSLotery\app\Services\Sales\TicketSaleService.php
- C:\xampp\php\www\BSLotery\app\Services\Lottery\PayoutResolverService.php
- C:\xampp\php\www\BSLotery\app\Services\Lottery\LimitValidationService.php
- C:\xampp\php\www\BSLotery\app\Services\Cash\CashService.php
- C:\xampp\php\www\BSLotery\app\Services\Accounting\AccountingService.php
- C:\xampp\php\www\BSLotery\app\Services\Results\PrizePaymentService.php
- C:\xampp\php\www\BSLotery\app\Services\Results\WinnerCalculationService.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\ResultController.php
- C:\xampp\php\www\BSLotery\app\Jobs\CalculateWinnersJob.php
- C:\xampp\php\www\BSLotery\app\Models\Company.php
- C:\xampp\php\www\BSLotery\database\migrations\2026_05_17_130000_add_big_prize_threshold_to_companies.php
- C:\xampp\php\www\BSLotery\tests\Feature\SaleAndPrizeCycleTest.php
- C:\xampp\php\www\BSLotery\tests\Feature\TicketProCalculationTest.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- La venta no usa fallback `payout_multiplier = 1.0`; una banca sin regla de pago activa debe corregir configuraciÃ³n antes de vender.
- El POS no muestra premio estimado local cuando no viene del backend; es preferible mostrar vacÃ­o antes que inventar pagos incorrectos por sucursal.
- Para vender jugadas con pagos por posiciÃ³n, debe existir una regla base sin `position`; las reglas por posiciÃ³n quedan para cÃ¡lculo de ganadores.
- Los premios grandes quedan retenidos con estado `HELD`; no se liberan con la autorizaciÃ³n normal de pagos.

Riesgos detectados:
- Si no se ejecuta worker de cola en producciÃ³n, los sorteos pueden quedar en `CALCULATING_WINNERS` hasta que se procese la cola.
- Falta pantalla/configuraciÃ³n administrativa para editar `big_prize_threshold`; por ahora es columna preparada y usable por backend.
- AÃºn conviene reemplazar docblocks `@test` por atributos de PHPUnit para evitar deprecaciones futuras.
- El helper `Money` centraliza normalizaciÃ³n, pero no sustituye una librerÃ­a decimal arbitraria; aceptable para esta fase, pendiente endurecimiento completo de dinero.

ValidaciÃ³n ejecutada:
- `php artisan migrate --force` correcto.
- `php artisan test` correcto: 40 tests, 379 assertions.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 117 rutas.
- `php -l` correcto en todos los PHP modificados.
- VerificaciÃ³n Browser intentada en `admin/tickets/create`; bloqueada por sesiÃ³n expirada y error del runtime de clipboard del navegador embebido, sin impacto en validaciones backend.

PrÃ³ximo paso recomendado:
- Ejecutar manualmente en navegador el flujo POS autenticado: seleccionar/deseleccionar monto rÃ¡pido, agregar `25` sin monto, `25 35`, `10-20 50` y `123 20`; luego arrancar worker de cola y validar cÃ¡lculo real de ganadores en entorno local.

---

## 2026-05-17 â€” Formato profesional de tickets 58MM y 88MM

Responsable:
- Codex

Fase trabajada:
- NÃºcleo operativo de ventas e impresiÃ³n tÃ©rmica.

Puntos completados:
- [x] Se creÃ³ `TicketPrintFormatterService` para separar el formato de impresiÃ³n de la lÃ³gica de venta.
- [x] El ticket 58MM usa formato compacto agrupado por loterÃ­a/sorteo, cÃ³digos Q/P/T/SP, totales, QR textual y cÃ³digo VAL determinÃ­stico.
- [x] El ticket 88MM usa formato ancho/tabular con columnas de loterÃ­a, sorteo, tipo, jugada, monto y premio.
- [x] `80MM` y `88MM` se procesan como formato ancho; `58MM` queda como formato compacto por defecto.
- [x] La venta genera `print_jobs.content` usando `printer_configs.paper_width` y guarda `printer_config_id` cuando aplica.
- [x] La reimpresiÃ³n usa el mismo formatter e incluye encabezado `REIMPRESION`.
- [x] Los tickets anulados muestran `ANULADO` y motivo cuando existe.
- [x] El formulario y la validaciÃ³n de impresoras aceptan `58MM`, `80MM` y `88MM`.
- [x] Se agregaron pruebas especÃ­ficas del formatter para 58MM, 88MM, reimpresiÃ³n, anulaciÃ³n y uso de premios guardados.
- [x] Se agregÃ³ prueba de integraciÃ³n que vende con impresora `88MM`, valida `print_jobs.printer_config_id`, contenido tabular, QR/VAL y reimpresiÃ³n con el mismo formatter.

Puntos parciales:
- [~] El QR se imprime como texto `QR: ticket:{uuid}`; queda pendiente generar comando ESC/POS de QR grÃ¡fico cuando se implemente el driver fÃ­sico.
- [~] El formato es texto plano compatible con tÃ©rmica; todavÃ­a no hay selecciÃ³n de tamaÃ±o de fuente ESC/POS ni corte automÃ¡tico.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\app\Services\Printing\TicketPrintFormatterService.php
- C:\xampp\php\www\BSLotery\app\Services\Sales\TicketSaleService.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\PrinterController.php
- C:\xampp\php\www\BSLotery\resources\views\admin\printers\form.blade.php
- C:\xampp\php\www\BSLotery\tests\Feature\SaleAndPrizeCycleTest.php
- C:\xampp\php\www\BSLotery\tests\Feature\TicketPrintFormatterTest.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- No se creÃ³ migraciÃ³n para `paper_width` porque la columna actual es `string(10)` y ya soporta `88MM`; el bloqueo estaba en validaciÃ³n y formulario.
- El formatter no recalcula premios ni multiplicadores; usa `ticket_details.possible_prize` y los datos persistidos del ticket.
- Si no existe configuraciÃ³n de impresora aplicable, la venta usa `58MM` por defecto para mantener compatibilidad.
- La configuraciÃ³n de impresora se resuelve primero por `branches.default_printer_id`; si no existe, se toma una impresora activa de la sucursal o global de la empresa.

Riesgos detectados:
- En 58MM el texto `QR: ticket:{uuid}` supera 32 caracteres, pero se deja completo porque el requisito exige imprimir el UUID real para validaciÃ³n.
- Los montos se formatean desde DECIMAL persistido; no se cambia todavÃ­a a una librerÃ­a decimal arbitraria para renderizado.
- Las advertencias de PHPUnit por docblocks `@test` siguen existiendo en pruebas heredadas y deben migrarse a atributos antes de PHPUnit 12.

ValidaciÃ³n ejecutada:
- `php -l` correcto en PHP modificados.
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto: 46 tests, 421 assertions.
- `php artisan route:list --except-vendor` correcto: 117 rutas.

PrÃ³ximo paso recomendado:
- Probar manualmente con una impresora o visor de cola: crear ticket con impresora `58MM`, repetir con `88MM`, reimprimir y verificar que el `print_jobs.content` conserva agrupaciÃ³n, total, QR y VAL.

---

## 2026-05-18 â€” POS con reloj en vivo y apertura/cierre de sorteos

Responsable:
- Codex

Fase trabajada:
- NÃºcleo operativo de ventas POS y administraciÃ³n de sorteos.

Puntos completados:
- [x] La pantalla de ventas muestra fecha de hoy y hora en tiempo real.
- [x] Cada sorteo/loterÃ­a en POS muestra cuenta regresiva hasta cierre o apertura.
- [x] La cuenta regresiva cambia de color: azul para tiempo amplio/en espera, verde cuando queda una hora o menos, rojo cuando quedan 15 minutos o menos o estÃ¡ cerrado.
- [x] Cuando se agota el tiempo, el sorteo queda visualmente como `Cerrada` y no se puede seleccionar para vender.
- [x] Se agregÃ³ `draws.open_time` para controlar desde quÃ© hora acepta ventas cada sorteo.
- [x] El backend de venta bloquea ventas antes de `open_time` y despuÃ©s de `close_time`.
- [x] El formulario de sorteos permite configurar hora de apertura por sorteo.
- [x] La pantalla de sorteos permite actualizar masivamente la hora de apertura de todos los sorteos de la empresa.
- [x] Se regenerÃ³ el catÃ¡logo dominicano para crear sorteos de hoy `2026-05-18`.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\database\migrations\2026_05_18_090000_add_open_time_to_draws.php
- C:\xampp\php\www\BSLotery\app\Models\Draw.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\DrawStoreRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Requests\Admin\DrawUpdateRequest.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\DrawController.php
- C:\xampp\php\www\BSLotery\app\Services\Sales\TicketSaleService.php
- C:\xampp\php\www\BSLotery\app\Services\Lottery\DrawLifecycleService.php
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\draws\form.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\draws\index.blade.php
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\database\seeders\DemoDataSeeder.php
- C:\xampp\php\www\BSLotery\database\seeders\DominicanLotteryCatalogSeeder.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- La hora de apertura por defecto para sorteos existentes es `00:00`, para mantener compatibilidad y no bloquear operaciones actuales.
- Los sorteos siguen usando `status = OPEN` como estado operativo, pero la ventana real de venta ahora depende tambiÃ©n de `open_time` y `close_time`.
- El POS no elimina jugadas existentes cuando un sorteo se cierra por reloj; bloquea el cobro hasta que el cajero elimine esas jugadas.
- La actualizaciÃ³n masiva de apertura queda restringida por permiso `draws.update` y registrada en auditorÃ­a.

Riesgos detectados:
- Los sorteos importados desde catÃ¡logo usan la hora pÃºblica como cierre; si una banca necesita cerrar ventas minutos antes del sorteo, debe ajustar `close_time`.
- El reloj del POS depende del reloj del servidor/navegador local. Para operaciÃ³n distribuida futura conviene exponer hora de servidor por endpoint o inyectarla con drift controlado.
- La pantalla POS todavÃ­a contiene textos heredados con caracteres mojibake en algunas etiquetas; no se corrigiÃ³ en esta tarea para no mezclar alcance.

ValidaciÃ³n ejecutada:
- `php artisan migrate --force` correcto.
- `php artisan db:seed --class=DominicanLotteryCatalogSeeder --force` correcto.
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto: 46 tests, 421 assertions.
- `php artisan route:list --except-vendor` correcto: 118 rutas.
- `php -l` correcto en PHP modificados.

PrÃ³ximo paso recomendado:
- Probar en navegador `admin/tickets/create`: verificar hora en vivo, colores de cuenta regresiva, selecciÃ³n automÃ¡tica solo de sorteos disponibles y bloqueo visual de sorteos cerrados.

---

## 2026-05-18 â€” Ajuste visual POS: loterÃ­as en columnas y texto corregido

Responsable:
- Codex

Fase trabajada:
- NÃºcleo operativo de ventas POS.

Puntos completados:
- [x] Se corrigieron textos daÃ±ados en la pantalla de venta: `Tipea los dÃ­gitos`, reglas Q/P/T, `NÃºmero` y `LoterÃ­as`.
- [x] El panel de loterÃ­as ahora usa tarjetas en grid de 2 columnas; en pantallas anchas pasa a 3 columnas.
- [x] Se ampliÃ³ el Ã¡rea de loterÃ­as y se redujo el ancho visual del bloque central.
- [x] Las tarjetas de loterÃ­a tienen altura estable, texto truncado con elipsis y evitan distorsiÃ³n de nombres largos.
- [x] El cuadro de entrada de nÃºmeros se redujo en tamaÃ±o de fuente y padding.
- [x] Se corrigiÃ³ una duplicaciÃ³n accidental del Blade y quedÃ³ una sola vista vÃ¡lida.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Se usaron entidades HTML (`&iacute;`, `&uacute;`) en textos visibles puntuales para evitar que vuelvan a aparecer caracteres mojibake en el navegador.
- El panel de loterÃ­as queda en `520px` por defecto, baja a `430px` en pantallas medianas y sube a `640px` con 3 columnas en pantallas grandes.
- Los textos largos de loterÃ­a/sorteo se truncaron con elipsis para preservar el tamaÃ±o de tarjeta.

Riesgos detectados:
- No se pudo verificar visualmente en navegador porque la sesiÃ³n actual redirige a `/login`; requiere iniciar sesiÃ³n y recargar `admin/tickets/create`.
- Quedan textos heredados con acentos simplificados en algunos mensajes internos de JavaScript para evitar problemas de codificaciÃ³n.

ValidaciÃ³n ejecutada:
- `php -l resources/views/admin/tickets/sale.blade.php` correcto.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 118 rutas.
- `php artisan test` correcto: 46 tests, 421 assertions.

PrÃ³ximo paso recomendado:
- Probar visualmente el POS autenticado en `admin/tickets/create`, verificando 2 columnas de loterÃ­as, textos limpios y que el input no domine la pantalla.

---

## 2026-05-18 â€” Ordenamiento POS por columnas AM/PM

Responsable:
- Codex

Fase trabajada:
- NÃºcleo operativo de ventas POS.

Puntos completados:
- [x] Las loterÃ­as del POS se separan en tres columnas fijas.
- [x] Primera columna: sorteos AM.
- [x] Segunda columna: sorteos PM normales, desde 12:00 hasta antes de 20:00.
- [x] Tercera columna: sorteos PM tarde, desde 20:00 en adelante.
- [x] Dentro de cada columna se ordenan por nombre de loterÃ­a, luego hora y nombre de sorteo.
- [x] El panel de loterÃ­as se ampliÃ³ para sostener las 3 columnas sin deformar tarjetas.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Se usa `close_time` como referencia para clasificar AM/PM porque es la hora operativa visible para cierre de venta.
- El corte para `PM tarde` queda en `20:00`, de modo que sorteos como Anguila 6:00 PM quedan en la segunda columna y Anguila 9:00 PM en la tercera.
- La agrupaciÃ³n se calcula en Blade/PHP para que el DOM ya nazca ordenado y no dependa de JavaScript.

Riesgos detectados:
- Si una banca considera 7:30 PM como `PM tarde`, habrÃ¡ que ajustar el umbral de 20:00 a 19:00 o hacerlo configurable.

ValidaciÃ³n ejecutada:
- `php -l resources/views/admin/tickets/sale.blade.php` correcto.
- `vendor\bin\pint --dirty` correcto.
- `php artisan test` correcto: 46 tests, 421 assertions.

PrÃ³ximo paso recomendado:
- Validar visualmente en `admin/tickets/create` que Anguila 10:00 AM quede en la primera columna, Anguila 6:00 PM en la segunda y Anguila 9:00 PM en la tercera.

---

## 2026-05-18 - POS: bloques verticales AM/PM/NOCHE

Responsable:
- Codex

Fase trabajada:
- Nucleo operativo de ventas POS.

Puntos completados:
- [x] Se cambio el selector de loterias de columnas laterales a bloques verticales.
- [x] El orden visual queda como `AM`, debajo `PM`, debajo `NOCHE`.
- [x] Cada bloque muestra sus sorteos en tarjetas horizontales con wrapping automatico.
- [x] La entrada inteligente queda debajo de los bloques de loterias.
- [x] El ticket actual y el resumen/cobro quedan debajo de la entrada inteligente.
- [x] Se conserva la logica existente de seleccion de sorteos, cuenta regresiva, cobro, preview, busqueda, copiado y pago de tickets.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Se mantiene `close_time` como referencia de agrupacion operativa.
- `AM` contiene sorteos antes de `12:00`.
- `PM` contiene sorteos desde `12:00` hasta antes de `20:00`.
- `NOCHE` contiene sorteos desde `20:00` en adelante.
- La agrupacion se calcula en Blade/PHP y Alpine solo conserva el estado interactivo.

Riesgos detectados:
- No se pudo validar visualmente la pantalla porque el navegador local redirige a `/login`; requiere sesion activa para confirmar el layout final.
- Si el negocio considera que la noche inicia antes de `20:00`, el umbral debe moverse o convertirse en configuracion.

Validacion ejecutada:
- `php -l resources/views/admin/tickets/sale.blade.php` correcto.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 118 rutas.
- `php artisan test` correcto: 46 tests, 421 assertions.

Proximo paso recomendado:
- Iniciar sesion, abrir `admin/tickets/create` y confirmar visualmente que el flujo queda: AM, PM, NOCHE, entrada inteligente, ticket/resumen.

---

## 2026-05-18 - POS compacto sin scroll de pagina

Responsable:
- Codex

Fase trabajada:
- Nucleo operativo de ventas POS.

Puntos completados:
- [x] Se fijo la altura del POS al viewport para evitar scroll general de pagina durante venta.
- [x] Los bloques `AM`, `PM` y `NOCHE` vuelven a una distribucion horizontal compacta.
- [x] Las tarjetas de sorteos se redujeron en altura, padding y tipografia para caber mejor.
- [x] La entrada inteligente se redujo en alto y mantiene los montos rapidos disponibles.
- [x] El area inferior conserva ticket actual y resumen/cobro como paneles horizontales.
- [x] Se mantuvo sin cambios la logica de venta, seleccion, preview, busqueda, copiado y pago.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Se elimina el scroll vertical del contenedor principal del POS usando altura fija de viewport y `overflow: hidden`.
- La zona de loterias usa tres bloques horizontales porque el flujo vertical completo consume demasiado alto para operar caja rapido.
- Se conserva scroll interno solo en la tabla de jugadas si el ticket crece mucho; ocultar jugadas vendidas seria peor para control de caja.

Riesgos detectados:
- En pantallas muy pequenas o con zoom alto, si hay demasiados sorteos abiertos, algunas tarjetas pueden quedar demasiado compactas. La solucion robusta futura es modo compacto configurable o filtros rapidos por grupo.
- La validacion visual completa sigue requiriendo sesion activa en navegador; el intento local redirige a `/login`.

Validacion ejecutada:
- `php -l resources/views/admin/tickets/sale.blade.php` correcto.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 118 rutas.
- `php artisan test` correcto: 46 tests, 421 assertions.

Proximo paso recomendado:
- Probar autenticado en `admin/tickets/create` con el zoom normal del navegador y confirmar que no aparece scroll de pagina durante una venta real.

---

## 2026-05-18 - RediseÃ±o POS operativo compacto

Responsable:
- Codex

Fase trabajada:
- Nucleo operativo de ventas POS.

Puntos completados:
- [x] Se agrego header operativo compacto con sistema/sucursal, caja activa, cajero, fecha, hora y ventas del dia.
- [x] La seccion de loterias se compacto con filtros: Todas, AM, PM, Noche, Abiertas, Cerradas y Seleccionadas.
- [x] Las tarjetas de loteria muestran solo datos operativos esenciales: seleccion, loteria, sorteo/hora, estado y cuenta regresiva.
- [x] La entrada inteligente se reorganizo en una sola fila operativa con tipos de jugada, input y montos rapidos.
- [x] `Ticket actual` ahora es una tabla real de jugadas con loteria, sorteo, tipo, jugada, monto, premio posible y accion de eliminar.
- [x] El panel derecho destaca el total y muestra jugadas, loterias, subtotal y premio posible.
- [x] `Buscar ticket / QR` se movio a un acordeon controlado por Alpine para no ocupar espacio siempre.
- [x] Se mantuvo la compatibilidad con seleccion multiple, cobro por draw_id, preview, busqueda, copiado y pago.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- El acordeon de busqueda se controla con Alpine porque el layout global no carga Bootstrap JS.
- El premio posible del POS se muestra como `-` cuando no hay preview/backend cargado en memoria; la venta real sigue guardando premios desde backend.
- Se dejo scroll interno en loterias y tabla de jugadas, no scroll general de pagina.
- Las ventas del dia se calculan en la vista con tickets no cancelados de la sucursal activa para dar contexto inmediato al cajero.

Riesgos detectados:
- No se pudo validar visualmente autenticado en el navegador porque el login local con `admin` / `Password1234` no entro y la pagina quedo en `/login`.
- En pantallas muy pequenas o con zoom alto, los filtros de loteria pueden ocupar dos lineas; si molesta, conviene convertirlos a dropdown compacto.
- La consulta de ventas del dia en Blade es aceptable por alcance actual, pero a mediano plazo debe venir del controlador o de un ViewModel para mantener la vista limpia.

Validacion ejecutada:
- `php -l resources/views/admin/tickets/sale.blade.php` correcto.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 118 rutas.
- `php artisan test` correcto: 46 tests, 421 assertions.

Proximo paso recomendado:
- Validar visualmente con sesion real en `admin/tickets/create`: filtros de loteria, ausencia de scroll general, tabla de jugadas y acordeon de busqueda.

---

## 2026-05-18 - POS sin scroll interno en loterias

Responsable:
- Codex

Fase trabajada:
- Nucleo operativo de ventas POS.

Puntos completados:
- [x] Se elimino el scroll interno de la seccion de loterias cambiando `overflow: auto` por `overflow: hidden`.
- [x] Se densificaron las tarjetas de loterias para que entren mas sorteos por fila cuando el filtro esta en `Todas`.
- [x] Se redujo el ancho minimo de tarjetas, padding, altura y tipografia de los elementos de loteria.
- [x] Se mantuvieron los filtros, seleccion multiple, estados visuales y cuenta regresiva.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Se priorizo eliminar el scroll visible de loterias por encima de tarjetas grandes.
- Se mantuvo el maximo de altura de la seccion para no empujar la entrada inteligente ni la tabla de jugadas.

Riesgos detectados:
- Si una empresa tiene muchos mas sorteos abiertos simultaneos que el catalogo actual, `overflow: hidden` podria ocultar tarjetas. En ese caso conviene un modo `Todas` plano sin grupos o un filtro por busqueda.
- La validacion visual exacta debe hacerse autenticado en el POS con datos reales.

Validacion ejecutada:
- `php -l resources/views/admin/tickets/sale.blade.php` correcto.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 118 rutas.
- `php artisan test` correcto: 46 tests, 421 assertions.

Proximo paso recomendado:
- Probar filtro `Todas` en `admin/tickets/create` con zoom normal y confirmar que no aparece barra de scroll en loterias.

---

## 2026-05-18 - POS loterias legibles y apertura 8 AM

Responsable:
- Codex

Fase trabajada:
- Nucleo operativo de ventas POS.

Puntos completados:
- [x] Se actualizaron los sorteos actuales/futuros en base de datos para abrir a las `08:00`.
- [x] Se ajustaron los seeders para que los nuevos sorteos demo/catalogo nazcan con `open_time = 08:00`.
- [x] El formulario y accion masiva de sorteos ahora usan `08:00` como valor por defecto visible.
- [x] La seccion AM ya no ocupa un tercio fijo cuando tiene pocas loterias.
- [x] Las columnas AM/PM/NOCHE ahora reparten ancho proporcionalmente segun cantidad de sorteos.
- [x] Las tarjetas de loterias se hicieron mas grandes y legibles manteniendo el contenedor sin scroll.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\draws\form.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\draws\index.blade.php
- C:\xampp\php\www\BSLotery\database\seeders\DemoDataSeeder.php
- C:\xampp\php\www\BSLotery\database\seeders\DominicanLotteryCatalogSeeder.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Cambiar `open_time` a `08:00` no debe reabrir sorteos cuyo `close_time` ya paso; el cierre sigue siendo la regla operativa fuerte.
- El ancho de cada bloque de loterias usa un factor proporcional a la cantidad de sorteos del grupo para evitar que AM desperdicie espacio.
- Se mantuvo `overflow: hidden` en loterias para no mostrar barra de scroll durante venta.

Riesgos detectados:
- A las `21:22` solo quedan 4 sorteos vendibles porque los demas ya cerraron por `close_time`; esto es correcto segun reglas actuales.
- Si se necesita probar todos los sorteos de noche, hay que ajustar temporalmente `close_time` o crear sorteos de prueba con cierre posterior.

Validacion ejecutada:
- Se actualizaron 32 sorteos actuales/futuros a `open_time = 08:00`.
- Verificacion operativa: hora actual `21:22`, sorteos vendibles restantes: 4.
- `php -l` correcto en los archivos PHP/Blade modificados.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto: 118 rutas.
- `php artisan test` correcto: 46 tests, 421 assertions.

Proximo paso recomendado:
- Probar el POS autenticado con filtro `Todas` y `Abiertas`; si se desea probar venta en todas las loterias de noche, crear un set de sorteos de prueba con cierre `23:59`.

---

## 2026-05-18 - Correccion fecha local POS sorteos cerrados

Responsable:
- Codex

Fase trabajada:
- Nucleo operativo de ventas POS.

Puntos completados:
- [x] Se verifico que backend tenia 4 sorteos vendibles a la hora actual.
- [x] Se identifico que el POS parseaba `draw_date` con zona UTC cuando Laravel serializa fechas con `T...Z`.
- [x] Se corrigio `parseDrawDate()` para usar solo `YYYY-MM-DD` y construir fecha local sin conversion UTC.
- [x] Se conserva la regla de cierre por `close_time`.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- El POS no debe usar `new Date('YYYY-MM-DDT00:00:00Z')` para un sorteo local, porque en zona America/Santo_Domingo puede caer en el dia anterior.
- La fecha del sorteo se interpreta como fecha operativa local, no como instante UTC.

Riesgos detectados:
- A la hora actual solo quedan sorteos cuyo cierre es posterior a la hora del servidor. Los sorteos con cierre vencido seguiran apareciendo cerrados correctamente.

Validacion ejecutada:
- Backend: `server_now=2026-05-18 21:26:35`, sorteos vendibles: 4, sorteos del dia: 32.
- `php -l resources/views/admin/tickets/sale.blade.php` correcto.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto.
- `php artisan test` correcto: 46 tests, 421 assertions.

Proximo paso recomendado:
- Recargar `admin/tickets/create` con cache limpia; en filtro `Abiertas` deben verse los sorteos con cierre posterior a la hora actual.

---

## 2026-05-18 - Verificacion cierre Anguila y pre-ticket

Responsable:
- Codex

Fase trabajada:
- Nucleo operativo de ventas POS.

Puntos completados:
- [x] Se verifico que Anguila 6:00 PM tiene `status = OPEN`, `open_time = 08:00` y `close_time = 18:00`.
- [x] Se confirmo que el bloqueo de Anguila 6:00 PM ocurre por hora operativa vencida, no por cierre manual.
- [x] Se cambio el texto de confirmacion previa para que no parezca ticket real: ahora dice `PREVISUALIZACION VENTA`.
- [x] Se reemplazo `Ticket No: PENDIENTE` por `Ticket No: se genera al cobrar`.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\admin\tickets\sale.blade.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- No se reabrio Anguila 6:00 PM automaticamente porque su hora de cierre real ya paso.
- El numero real de ticket solo puede existir despues de guardar la venta en backend.

Riesgos detectados:
- Hay una decision de negocio pendiente si se desea permitir ventas despues de `close_time` mientras el sorteo siga `status = OPEN`; eso contradice el bloqueo por horario implementado previamente.

Validacion ejecutada:
- `php -l resources/views/admin/tickets/sale.blade.php` correcto.
- `vendor\bin\pint --dirty` correcto.
- `php artisan route:list --except-vendor` correcto.
- `php artisan test` correcto: 46 tests, 421 assertions.

Proximo paso recomendado:
- Definir si `close_time` debe bloquear automaticamente la venta o si solo debe bloquear el cierre manual del sorteo.

---

## 2026-05-18 - Correccion venta POS sin posicion de pago

Responsable:
- Codex

Fase trabajada:
- Nucleo operativo de ventas POS, reglas de pago y pruebas de ticket.

Puntos completados:
- [x] Se reprodujo el fallo de venta desde backend usando caja abierta, sucursal Central y sorteo vendible.
- [x] Se identifico que el POS enviaba jugadas sin `position` y el resolver no encontraba reglas configuradas como `FIRST`, `EXACT`, etc.
- [x] Se corrigio `PayoutResolverService` para resolver posicion primaria de venta sin quemar multiplicadores.
- [x] Se agrego prueba automatizada para venta sin posicion que debe usar la regla primaria `FIRST`.
- [x] Se agrego regla demo de pago para `SUPER_PALE` con posicion `FIRST` y se creo la regla local faltante para poder vender Super Pale.
- [x] Se crearon tickets reales de prueba:
  - `principal-260518-0001`: Quiniela 25 por RD$10.00 en PowerBall.
  - `principal-260518-0002`: Quiniela, Pale, Tripleta y Super Pale en New York 11:30.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\app\Services\Lottery\PayoutResolverService.php
- C:\xampp\php\www\BSLotery\database\seeders\DemoDataSeeder.php
- C:\xampp\php\www\BSLotery\tests\Feature\SaleAndPrizeCycleTest.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Si una venta POS no trae posicion, la posicion primaria se infiere por tipo: `QUINIELA`, `PALE` y `SUPER_PALE` usan `FIRST`; `TRIPLETA` usa `EXACT`.
- El multiplicador sigue saliendo exclusivamente de `payout_rules`; no se agregaron multiplicadores quemados al flujo de venta.
- La venta despues de `close_time` sigue bloqueada. Para pruebas nocturnas se deben usar sorteos cuyo cierre no haya vencido o ajustar horarios de prueba.

Riesgos detectados:
- La pantalla de login impidio prueba manual con navegador porque la sesion actual no estaba autenticada; la validacion se hizo por servicio y pruebas Feature.
- Si se desea probar Anguila 6 PM despues de las 18:00, hay que decidir explicitamente si se permite ignorar `close_time`; hoy el sistema lo bloquea por diseno operativo.

Validacion ejecutada:
- Creacion real por servicio: ticket `principal-260518-0001`, con `ticket_details` y `print_job`.
- Creacion real por servicio: ticket `principal-260518-0002`, con Quiniela, Pale, Tripleta y Super Pale.
- `php artisan test --filter=SaleAndPrizeCycleTest`: 15 tests, 180 assertions.
- `php -l app/Services/Lottery/PayoutResolverService.php`: correcto.
- `php -l database/seeders/DemoDataSeeder.php`: correcto.
- `php -l tests/Feature/SaleAndPrizeCycleTest.php`: correcto.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 118 rutas.
- `php artisan test`: correcto, 47 tests, 427 assertions.

Proximo paso recomendado:
- Autenticarse en el navegador y repetir venta manual en `admin/tickets/create` usando un sorteo abierto por hora, por ejemplo New York 11:30 si aun no vence. Si se quiere probar cualquier loteria sin depender de la hora real, crear sorteos de prueba con cierre `23:59`.

---

## 2026-05-18 - Confirmacion segura de resultados y configuracion

Responsable:
- Codex

Fase trabajada:
- Fase 5: resultados, auditoria operativa y configuracion.

Puntos completados:
- [x] Se confirmo que `results` ya registra `registered_by`, `registered_at`, `confirmed_by` y `confirmed_at`.
- [x] Se endurecio la confirmacion: el mismo usuario que registra un resultado no puede confirmarlo.
- [x] Se agrego configuracion por empresa para decidir si los resultados requieren confirmacion.
- [x] Si la confirmacion esta apagada, el resultado queda `CONFIRMED` al registrarse y se guardan usuario/hora de registro y confirmacion.
- [x] Se agrego pantalla `Configuracion de resultados`.
- [x] Se agrego acceso al menu lateral para Configuracion.
- [x] Se ajusto el bloque de Impresora en el menu lateral para evitar distorsion por usar un `nav-link` no navegable como contenedor.
- [x] La lista de resultados ahora muestra nombre y hora de registro, y nombre y hora de confirmacion.
- [x] Se agregaron pruebas para confirmacion por segundo admin y confirmacion automatica por configuracion.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\database\migrations\2026_05_18_000004_create_system_settings_table.php
- C:\xampp\php\www\BSLotery\app\Models\SystemSetting.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\SettingsController.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\ResultController.php
- C:\xampp\php\www\BSLotery\resources\views\admin\settings\results.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\results\index.blade.php
- C:\xampp\php\www\BSLotery\resources\views\layouts\app.blade.php
- C:\xampp\php\www\BSLotery\public\css\argon.css
- C:\xampp\php\www\BSLotery\routes\web.php
- C:\xampp\php\www\BSLotery\tests\Feature\SaleAndPrizeCycleTest.php
- C:\xampp\php\www\BSLotery\tests\Feature\TicketProCalculationTest.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- La configuracion `results.require_confirmation` queda por empresa, no global del sistema.
- El valor por defecto es exigir confirmacion para proteger el flujo financiero.
- `results.modify_confirmed` no permite saltarse la regla de doble control; el mismo usuario nunca confirma su propio resultado cuando la confirmacion esta activa.
- Cuando la confirmacion se desactiva, se considera una decision administrativa explicita y se audita el cambio de configuracion.

Riesgos detectados:
- Hay textos antiguos con mojibake en algunas vistas heredadas; se corrigio la vista de resultados y se redujo el problema en el menu nuevo, pero queda pendiente una limpieza completa de encoding en vistas antiguas.
- La prueba visual del menu requiere sesion web autenticada; la validacion ejecutada fue por rutas, tests y sintaxis.

Validacion ejecutada:
- `php artisan migrate --force`: migracion `system_settings` aplicada correctamente.
- `php artisan test --filter=SaleAndPrizeCycleTest`: 16 tests, 197 assertions.
- `php artisan test --filter=TicketProCalculationTest`: 18 tests, 186 assertions.
- `php -l` en PHP modificados: correcto.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 120 rutas.
- `php artisan test`: correcto, 48 tests, 462 assertions.

Proximo paso recomendado:
- Probar manualmente con dos usuarios administradores reales: uno registra el resultado y otro lo confirma. Luego probar apagar la confirmacion en Configuracion y verificar que el resultado quede confirmado automaticamente.

---

## 2026-05-18 - Limpieza de encoding en UI y mensajes operativos

Responsable:
- Codex

Fase trabajada:
- Estabilizacion visual y mensajes operativos.

Puntos completados:
- [x] Se corrigio mojibake visible en layout principal del menu lateral.
- [x] Se corrigio mojibake en el formulario de registro de resultados.
- [x] Se corrigieron mensajes operativos con acentos rotos en controladores y servicios.
- [x] Se verifico que no quedan secuencias `Ãƒ`, `Ã‚` o `Ã¢` en `resources/views`, `app/Http` ni `app/Services`.

Archivos creados/modificados:
- C:\xampp\php\www\BSLotery\resources\views\layouts\app.blade.php
- C:\xampp\php\www\BSLotery\resources\views\admin\results\form.blade.php
- C:\xampp\php\www\BSLotery\app\Http\Controllers\Admin\ResultController.php
- C:\xampp\php\www\BSLotery\app\Services\Sales\TicketSaleService.php
- C:\xampp\php\www\BSLotery\app\Services\Results\PrizePaymentService.php
- C:\xampp\php\www\BSLotery\app\Services\Results\WinnerCalculationService.php
- C:\xampp\php\www\BSLotery\02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- La correccion se limito a vistas, controladores y servicios que impactan UI, errores o auditoria visible.
- No se hizo una reescritura masiva de historiales antiguos ni comentarios de documentacion fuera del runtime para evitar ruido innecesario.

Riesgos detectados:
- Pueden quedar textos antiguos con encoding incorrecto en documentacion o seeders no visibles; no bloquean operacion.

Validacion ejecutada:
- `rg 'Ãƒ|Ã‚|Ã¢' resources/views app/Http app/Services -n`: sin resultados.
- `php -l` en PHP modificados: correcto.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 120 rutas.
- `php artisan test`: correcto, 48 tests, 462 assertions.

Proximo paso recomendado:
- Avanzar con pruebas manuales guiadas de resultados y cierre de ciclo operativo: registrar resultado, confirmar con otro admin, calcular ganadores, autorizar pagos y pagar premio con caja abierta.

---

## 2026-05-18 - Prueba operativa completa de venta a pago

Responsable:
- Codex

Fase trabajada:
- Cierre del ciclo operativo Fases 2, 4 y 5.

Puntos completados:
- [x] Se verifico caja abierta local para la sucursal principal.
- [x] Se vendio un ticket real de prueba.
- [x] Se registro resultado ganador con usuario registrador.
- [x] Se confirmo resultado con un segundo administrador distinto.
- [x] Se calcularon ganadores.
- [x] Se autorizo el pago de premios.
- [x] Se pago premio con caja abierta.
- [x] Se verifico movimiento de caja y asiento contable del pago.

Evidencia de prueba:
- Ticket: `principal-260518-0003`
- Sorteo: `New York 11:30`
- Jugada: Quiniela `77` por RD$1.00
- Resultado registrado: 77 / 12 / 34
- Registrado por: Wailan Brea
- Confirmado por: Admin Confirmador Prueba
- Ganadores: 1
- Premio total: RD$72.00
- Estado final del ticket: `PAID`
- Estado final del sorteo: `PAYMENTS_RELEASED`
- Pago creado: `prize_payments.id = 1`
- Movimiento de caja: verificado
- Asiento contable: verificado

Decisiones tomadas:
- La prueba se ejecuto sobre datos locales de desarrollo y no sobre Android ni reportes.
- Se uso un segundo administrador local de prueba para validar la regla de doble control.

Riesgos detectados:
- La prueba se ejecuto por servicio/modelo en entorno local; queda pendiente repetirla desde navegador con dos sesiones/usuarios reales para validar la experiencia visual completa.
- El sorteo usado queda con pagos liberados en la base local de prueba.

Validacion ejecutada:
- Prueba operativa por `php artisan tinker --execute`: correcta.
- Verificacion de `cash_movement`: correcta.
- Verificacion de `journal_entry`: correcta.

Proximo paso recomendado:
- Corregir y formalizar el flujo visual de resultados/premios si al probar en navegador aparecen fricciones; luego avanzar a reportes operativos de ventas, premios y caja.

---

## 2026-05-18 - Usuarios operativos y roles minimos

Responsable:
- Codex

Fase trabajada:
- Seguridad operativa, permisos y licenciamiento.

Puntos completados:
- [x] Se verifico que existian 2 usuarios antes del cambio: `brea` y `confirmador-prueba`.
- [x] Se bloqueo el usuario temporal `confirmador-prueba` porque tenia rol `COMPANY_OWNER` y solo fue creado para una prueba anterior.
- [x] Se creo rol `BRANCH_CASHIER_PAYER` para cajero/pagador de sucursal.
- [x] Se creo rol `RESULT_CONFIRM_ONLY` para confirmador exclusivo de resultados.
- [x] Se creo usuario `cajero-central` para sucursal Central.
- [x] Se creo usuario `confirmador-resultados` para confirmar resultados.
- [x] Se verifico que el cajero solo tenga menus operativos: vender, tickets, premios y caja.
- [x] Se verifico que el confirmador solo tenga resultados y confirmacion.
- [x] Se verifico licencia local: no existe limite `max_users`; la licencia actual limita dispositivos/offline, no usuarios.

Usuarios creados:
- `cajero-central` / rol `BRANCH_CASHIER_PAYER` / sucursal Central.
- `confirmador-resultados` / rol `RESULT_CONFIRM_ONLY` / sucursal Central.

Permisos del cajero:
- `dashboard.view`
- `sales.create`
- `sales.preview`
- `sales.reprint`
- `tickets.view`
- `tickets.reprint`
- `prizes.pay`
- `cash.open`
- `cash.view`
- `cash.movement`
- `cash.close`

Permisos del confirmador:
- `dashboard.view`
- `results.view`
- `results.confirm`

Decisiones tomadas:
- No se otorgo permiso de anulacion al cajero para evitar que un usuario operativo pueda anular ventas sin control.
- No se otorgo permiso de registrar resultados al confirmador, solo confirmarlos.
- No se otorgaron menus administrativos, reportes, configuracion, loterias, usuarios ni reglas a estos roles.
- No se requieren licencias adicionales para estos usuarios con la licencia actual; si la API madre envia `limits.max_users` en el futuro, se debe validar el conteo de usuarios activos contra ese limite.

Riesgos detectados:
- La aplicacion aun no aplica automaticamente un limite `max_users` porque la licencia actual no lo trae. Debe implementarse si el plan comercial empieza a enviarlo.
- Las claves iniciales deben cambiarse manualmente despues de probar, porque aun no existe flujo de cambio obligatorio de password.

Validacion ejecutada:
- `php artisan db:seed --class=RoleSeeder --force`: correcto.
- Verificacion de permisos por usuario via Tinker: correcta.
- `php -l database/seeders/RoleSeeder.php`: correcto.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan test`: correcto, 48 tests, 462 assertions.

Proximo paso recomendado:
- Probar login manual con `cajero-central` y `confirmador-resultados` para validar que el menu lateral refleje exactamente los permisos.

---

## 2026-05-18 - Validacion visual de roles operativos y ajuste de dashboard/menu

Responsable:
- Codex

Fase trabajada:
- Seguridad operativa, permisos y experiencia de usuarios por rol.

Puntos completados:
- [x] Se valido en navegador el login de `cajero-central`.
- [x] Se confirmo que `cajero-central` no puede acceder a `admin/users` y recibe 403.
- [x] Se corrigio el dashboard para no mostrar metricas administrativas a usuarios sin permisos.
- [x] Se corrigio el menu lateral para ocultar secciones vacias cuando el usuario no tiene permisos.
- [x] Se valido en navegador el login de `confirmador-resultados`.
- [x] Se corrigio el acceso a premios para que `RESULT_CONFIRM_ONLY` no vea ni acceda a pagos de premios.
- [x] Se confirmo que `confirmador-resultados` puede ver resultados y confirmar cuando aplica.
- [x] Se confirmo que `confirmador-resultados` no puede registrar resultados ni ver premios.

Puntos parciales:
- [~] Falta flujo de cambio obligatorio de password para usuarios creados con clave inicial.

Archivos creados/modificados:
- `resources/views/dashboard.blade.php`
- `resources/views/layouts/app.blade.php`
- `routes/web.php`
- `app/Policies/WinnerTicketPolicy.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- El dashboard ahora calcula tarjetas segun permisos del usuario y filtra por empresa/sucursal activa cuando aplica.
- El rol `RESULT_CONFIRM_ONLY` conserva `results.view` y `results.confirm`, pero no hereda acceso a premios.
- Las rutas de historial y pendientes de premios requieren `prizes.pay`, no `results.view`.
- Las secciones del menu lateral solo se muestran si tienen al menos una opcion visible para el usuario.

Riesgos detectados:
- Las claves iniciales de usuarios operativos siguen siendo temporales; antes de produccion se debe implementar cambio obligatorio o cambio manual controlado.
- Si en el futuro la licencia madre envia `limits.max_users`, debe agregarse validacion de usuarios activos contra ese limite.

Validacion ejecutada:
- Navegador: login `cajero-central`, menu operativo, dashboard operativo y bloqueo 403 en usuarios.
- Navegador: login `confirmador-resultados`, menu solo de resultados, bloqueo 403 en registro de resultados y premios.
- `php -l app/Policies/WinnerTicketPolicy.php`: correcto.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan test`: correcto, 48 tests, 462 assertions.
- `php artisan route:list --except-vendor`: correcto, 120 rutas.
- `rg "Ãƒ|Ã‚|Ã¢" resources/views app/Http app/Services routes database -n`: sin coincidencias.

Proximo paso recomendado:
- Implementar cambio obligatorio de password para usuarios nuevos y luego avanzar a reportes operativos minimos de ventas, premios y caja.

Estado posterior:
- [x] Resuelto en avance `Cambio obligatorio de password para usuarios nuevos`.

---

## 2026-05-18 - Cambio obligatorio de password para usuarios nuevos

Responsable:
- Codex

Fase trabajada:
- Seguridad operativa y endurecimiento de autenticacion.

Puntos completados:
- [x] Se agrego persistencia local para exigir cambio de password por usuario.
- [x] Se agrego fecha de ultimo cambio de password.
- [x] Se creo pantalla protegida para cambio de password.
- [x] Se agrego middleware global para bloquear acceso a modulos hasta cambiar la clave inicial.
- [x] Se marco automaticamente `must_change_password` al crear usuarios desde administracion.
- [x] Se marca nuevamente `must_change_password` cuando un administrador restablece la clave de un usuario.
- [x] Se audita el cambio de password del propio usuario.
- [x] Se marcaron `cajero-central` y `confirmador-resultados` para cambio obligatorio en el proximo acceso.
- [x] Se agregaron pruebas de integracion del flujo.

Archivos creados/modificados:
- `database/migrations/2026_05_18_220000_add_password_change_fields_to_users_table.php`
- `app/Http/Requests/Auth/ChangePasswordRequest.php`
- `app/Http/Middleware/EnsurePasswordWasChanged.php`
- `resources/views/auth/change-password.blade.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/Admin/UserController.php`
- `app/Models/User.php`
- `bootstrap/app.php`
- `routes/web.php`
- `tests/Feature/PasswordChangeFlowTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- La proteccion se implemento como middleware global de `web`, no solo como ocultamiento visual, para impedir acceso directo por URL.
- El setup inicial no fuerza cambio de password porque el administrador define su propia clave durante instalacion.
- Los usuarios creados o reseteados por administracion deben cambiar su clave al iniciar sesion.
- La nueva clave debe tener minimo 10 caracteres con letras y numeros, y debe ser distinta a la actual.

Riesgos detectados:
- No existe aun pantalla de perfil para cambio voluntario de password despues del primer cambio; no bloquea operacion, pero conviene agregarla antes de produccion.
- Las advertencias de PHPUnit por metadata en doc-comments siguen pendientes en pruebas existentes y deberian migrarse a atributos antes de PHPUnit 12.

Validacion ejecutada:
- `php artisan migrate`: correcto.
- Marcado local de `cajero-central` y `confirmador-resultados` con `must_change_password = true`: correcto.
- Navegador: acceso a POS redirige a `/password/change`: correcto.
- `php -l` en archivos PHP modificados: correcto.
- `php artisan test tests/Feature/PasswordChangeFlowTest.php`: correcto, 3 tests, 12 assertions.
- `php artisan test`: correcto, 51 tests, 474 assertions.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 122 rutas.
- `rg "Ãƒ|Ã‚|Ã¢" app/Http/Requests/Auth app/Http/Middleware app/Http/Controllers/AuthController.php resources/views/auth tests/Feature/PasswordChangeFlowTest.php -n`: sin coincidencias.

Proximo paso recomendado:
- Avanzar a reportes operativos minimos de ventas, premios y caja, manteniendo filtros por empresa/sucursal y paginacion.

---

## 2026-05-19 - Reportes operativos minimos con filtros seguros

Responsable:
- Codex

Fase trabajada:
- Fase 8: reportes operativos de ventas, premios y caja.

Puntos completados:
- [x] Se endurecio el dashboard de reportes con resumen de ventas, anulaciones, premios y cajas.
- [x] Se agregaron filtros consistentes por fecha desde/hasta, sucursal, usuario, loteria, sorteo, estado y tamano de pagina.
- [x] Se corrigio el alcance por sucursal: usuarios limitados a una banca no pueden consultar datos de otra banca aunque manipulen `branch_id`.
- [x] Se mantuvo alcance por empresa en todas las consultas.
- [x] Se agrego paginacion en reportes agrupados y listados.
- [x] Se mejoraron reportes de ventas por dia, sucursal, loteria/sorteo y numeros mas jugados.
- [x] Se mejoraron reportes de tickets anulados, ganadores y premios pagados.
- [x] Se mejoro cuadre de caja con apertura, cierre, ventas, premios, esperado, contado y diferencia.
- [x] Se agregaron pruebas de integracion para filtros, aislamiento por sucursal y cuadre de caja.

Puntos parciales:
- [~] Ventas por cajero se cubre como filtro en reportes existentes; no se creo pantalla separada.
- [~] Premios pendientes se cubre filtrando estado en ganadores; no se creo pantalla separada nueva.
- [~] Caja abierta se cubre filtrando estado en cuadre de caja; no se creo pantalla separada nueva.
- [~] Exportacion PDF/Excel/CSV sigue pendiente.
- [~] Reportes grandes en cola sigue pendiente para volumen alto.

Archivos creados/modificados:
- `app/Http/Controllers/Admin/ReportController.php`
- `resources/views/admin/reports/partials/filters.blade.php`
- `resources/views/admin/reports/index.blade.php`
- `resources/views/admin/reports/sales-by-day.blade.php`
- `resources/views/admin/reports/sales-by-branch.blade.php`
- `resources/views/admin/reports/sales-by-lottery.blade.php`
- `resources/views/admin/reports/top-numbers.blade.php`
- `resources/views/admin/reports/cancelled.blade.php`
- `resources/views/admin/reports/cash-summary.blade.php`
- `resources/views/admin/reports/winners.blade.php`
- `resources/views/admin/reports/prizes-paid.blade.php`
- `tests/Feature/OperationalReportsTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Los reportes se mantienen dentro del modulo existente y no se agregan nuevos modulos.
- Los administradores con permisos de empresa/sucursales pueden consultar todas las sucursales de su empresa.
- Los usuarios operativos con `branch_id` y sin permisos administrativos quedan forzados a su sucursal.
- `active_branch_id` no se usa como filtro silencioso para administradores, porque puede ocultar sucursales sin que el usuario lo note.
- Se priorizo paginacion y filtros indexables sobre consultas masivas.

Riesgos detectados:
- Los reportes agregados por `DATE(sold_at)` son aceptables para rangos operativos pequenos/medianos; para volumen alto conviene crear tablas resumen o jobs de reportes.
- No se implemento exportacion todavia; debe hacerse con jobs si el rango puede ser grande.
- Quedan advertencias de PHPUnit por metadata en doc-comments heredados.

Validacion ejecutada:
- `php -l app/Http/Controllers/Admin/ReportController.php`: correcto.
- `php -l tests/Feature/OperationalReportsTest.php`: correcto.
- `php artisan test tests/Feature/OperationalReportsTest.php`: correcto, 4 tests, 18 assertions.
- `php artisan test`: correcto, 55 tests, 492 assertions.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 122 rutas.
- `rg "Ãƒ|Ã‚|Ã¢" app/Http/Controllers/Admin/ReportController.php resources/views/admin/reports tests/Feature/OperationalReportsTest.php -n`: sin coincidencias.

Proximo paso recomendado:
- Implementar exportacion controlada de reportes operativos o avanzar a reportes de movimientos de caja detallados, antes de nomina o Android.

---

## 2026-05-19 - Correccion autorizacion al cerrar caja

Responsable:
- Codex

Fase trabajada:
- Fase 3: caja y control operativo.

Puntos completados:
- [x] Se corrigio `CashSessionPolicy::update()` para aceptar autorizacion de clase cuando aun no se ha resuelto una caja concreta.
- [x] Se mantiene validacion de empresa cuando la autorizacion recibe una instancia real de `CashSession`.
- [x] Se agrego prueba de regresion para abrir la pantalla de cierre de caja sin `ArgumentCountError`.

Archivos creados/modificados:
- `app/Policies/CashSessionPolicy.php`
- `tests/Feature/SaleAndPrizeCycleTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- No se cambio el flujo de cierre ni la ruta; se corrigio la policy para soportar los dos usos reales de Laravel Gate: clase e instancia.
- La autorizacion de clase valida permisos (`cash.movement`, `cash.close`, `cash.confirm`, `cash.reopen`); la autorizacion de instancia ademas valida pertenencia a la empresa.

Riesgos detectados:
- Siguen existiendo advertencias de PHPUnit por doc-comment metadata heredada.

Validacion ejecutada:
- `php -l app/Policies/CashSessionPolicy.php`: correcto.
- `php -l tests/Feature/SaleAndPrizeCycleTest.php`: correcto.
- `php artisan test --filter=cash_close_form_authorizes_without_policy_argument_error`: correcto.
- `php artisan test`: correcto, 56 tests, 495 assertions.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 122 rutas.

Proximo paso recomendado:
- Reintentar cierre de caja desde navegador. Si el flujo visual queda correcto, avanzar a exportacion controlada de reportes o movimientos de caja detallados.

---

## 2026-05-19 - Cierre de caja con contado fisico y faltante/sobrante visible

Responsable:
- Codex

Fase trabajada:
- Fase 3: caja, cierre y cuadre operativo.

Puntos completados:
- [x] Se reviso el flujo de cierre de caja completo: formulario, servicio, controlador e historial.
- [x] Se confirmo que `counted_cash`, `shortage_amount` y `surplus_amount` se guardaban, pero no se mostraban claramente en el historial.
- [x] Se agrego resultado visual en el formulario de cierre para ver faltante/sobrante antes de cerrar.
- [x] Se agrego aviso posterior al cierre con esperado, contado fisico, diferencia, faltante o sobrante.
- [x] Se agregaron columnas `Contado` y `Diferencia` al historial de caja.
- [x] Se cambio el calculo de diferencia de cierre para usar centavos enteros mediante `Money::subtract()`, `Money::absolute()` y comparadores.
- [x] Se agrego prueba de regresion para cierre con faltante.

Archivos creados/modificados:
- `app/Support/Money.php`
- `app/Services/Cash/CashService.php`
- `app/Http/Controllers/Admin/CashController.php`
- `resources/views/admin/cash/close.blade.php`
- `resources/views/admin/cash/index.blade.php`
- `tests/Feature/SaleAndPrizeCycleTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- El sistema no bloquea el cierre si hay faltante o sobrante; lo registra y lo muestra claramente para posterior confirmacion/control.
- El faltante/sobrante queda en la misma `cash_session`, sin crear movimientos artificiales adicionales.
- Se usa el valor contado por el cajero como dato operativo auditable del cierre.

Riesgos detectados:
- Aun falta una pantalla de confirmacion administrativa mas detallada para decidir tratamiento del faltante/sobrante.
- `Money::normalize()` todavia usa conversion float internamente; se redujo riesgo en diferencia de cierre usando centavos, pero conviene endurecer todo el helper en una fase de dinero dedicada.
- Siguen existiendo advertencias de PHPUnit por doc-comment metadata heredada.

Validacion ejecutada:
- `php -l app/Support/Money.php`: correcto.
- `php -l app/Services/Cash/CashService.php`: correcto.
- `php -l app/Http/Controllers/Admin/CashController.php`: correcto.
- `php -l tests/Feature/SaleAndPrizeCycleTest.php`: correcto.
- `php artisan test --filter=cash_close_persists_counted_cash_and_shortage_summary`: correcto.
- `php artisan test`: correcto, 57 tests, 504 assertions.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 122 rutas.
- `rg "Ãƒ|Ã‚|Ã¢" app/Support/Money.php app/Services/Cash/CashService.php app/Http/Controllers/Admin/CashController.php resources/views/admin/cash tests/Feature/SaleAndPrizeCycleTest.php -n`: sin coincidencias.

Proximo paso recomendado:
- Probar cierre de caja desde navegador con contado menor, igual y mayor al esperado. Luego avanzar a confirmacion administrativa de faltantes/sobrantes o movimientos de caja detallados.

---

## 2026-05-19 - Arqueo profesional de caja con denominaciones e incidencias

Responsable:
- Codex

Fase trabajada:
- Fase 3: caja, cierre, transferencias e incidencias.

Puntos completados:
- [x] Se agrego persistencia para arqueos de caja por sesion.
- [x] Se agrego conteo por denominaciones de billetes y monedas.
- [x] Se agrego tabla/modelo de incidencias de caja para faltantes, sobrantes y reaperturas.
- [x] Se agrego tabla/modelo base de transferencias bancarias.
- [x] `cash_movements` ahora guarda `payment_method` y `bank_transfer_id`.
- [x] `CashService` separa movimientos en efectivo de movimientos por transferencia.
- [x] El efectivo esperado solo considera efectivo fisico: apertura + ventas efectivo - premios efectivo - retiros/gastos/anulaciones.
- [x] El cierre crea `cash_reconciliation`, guarda denominaciones y abre incidencia si hay faltante o sobrante.
- [x] El historial de caja muestra estado de arqueo e incidencias abiertas.
- [x] Se agregaron pruebas de cierre por denominaciones y transferencias fuera del efectivo fisico.

Puntos parciales:
- [~] La estructura de transferencias esta lista, pero aun falta UI completa para registrar banco, referencia, evidencia y verificacion.
- [~] Las incidencias se crean automaticamente, pero falta pantalla dedicada para gestionarlas, cerrarlas y asignar responsables.
- [~] El cierre registra la diferencia, pero aun falta flujo administrativo para aprobar tratamiento contable del faltante/sobrante.

Archivos creados/modificados:
- `database/migrations/2026_05_19_010000_create_cash_reconciliation_tables.php`
- `app/Models/BankTransfer.php`
- `app/Models/CashReconciliation.php`
- `app/Models/CashCountDenomination.php`
- `app/Models/CashIncident.php`
- `app/Models/CashSession.php`
- `app/Models/CashMovement.php`
- `app/Services/Cash/CashService.php`
- `app/Http/Requests/Admin/CashCloseRequest.php`
- `app/Http/Controllers/Admin/CashController.php`
- `resources/views/admin/cash/close.blade.php`
- `resources/views/admin/cash/index.blade.php`
- `tests/Feature/SaleAndPrizeCycleTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- El cierre no se bloquea por faltante/sobrante; se registra, se marca como pendiente de revision y se genera incidencia auditable.
- Las transferencias no modifican los totales fisicos de `cash_sessions`; se resumen en el arqueo para no contaminar el efectivo contado.
- Se conservaron campos historicos de `cash_sessions` para compatibilidad y se agrego `cash_reconciliations` como detalle profesional del arqueo.

Riesgos detectados:
- Falta una pantalla operativa de transferencias; mientras no exista, las ventas/pagos siguen entrando por efectivo salvo integraciones de servicio.
- Falta workflow formal de incidencias: revisar, aprobar, cerrar, adjuntar evidencia y escalar.
- `Money::normalize()` aun debe endurecerse en una fase dedicada para eliminar conversiones internas con float.

Validacion ejecutada:
- `php artisan migrate`: correcto.
- `php -l` en PHP modificados: correcto.
- `php artisan test --filter=cash_close`: correcto, 3 tests, 26 assertions.
- `php artisan test --filter=transfer_movements_do_not_increase_physical_expected_cash`: correcto, 1 test, 12 assertions.
- `php artisan test`: correcto, 59 tests, 530 assertions.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 122 rutas.

Proximo paso recomendado:
- Crear modulo de movimientos/transferencias detallado con banco, referencia, evidencia, verificacion y pantalla de incidencias antes de seguir ampliando reportes.

---

## 2026-05-19 - Transferencias verificadas e incidencias operativas de caja

Responsable:
- Codex

Fase trabajada:
- Fase 3: caja profesional, control de transferencias e incidencias.

Puntos completados:
- [x] Se agrego `movement_type` a transferencias para distinguir ventas, premios, entradas, salidas y gastos por transferencia.
- [x] Se creo servicio `BankTransferService` para registrar, confirmar y rechazar transferencias.
- [x] Se creo servicio `CashIncidentService` para resolver incidencias con usuario, fecha y notas.
- [x] Se crearon pantallas de transferencias: listado, filtro, creacion, confirmacion y rechazo.
- [x] Se crearon pantallas de incidencias: listado, filtros y resolucion.
- [x] Se agregaron permisos especificos para transferencias e incidencias.
- [x] Se agrego submenu de Caja con Caja actual, Historial, Transferencias e Incidencias.
- [x] Se bloqueo el cierre de caja cuando existan transferencias pendientes.
- [x] Al confirmar una transferencia se crea `cash_movement` con `payment_method = BANK_TRANSFER`, sin afectar efectivo fisico esperado.
- [x] Se agregaron pruebas de bloqueo de cierre por transferencia pendiente, confirmacion de transferencia, acceso a pantallas y resolucion de incidencias.

Puntos parciales:
- [~] La evidencia de transferencia se guarda como referencia/ruta textual; falta carga de archivo real con storage seguro.
- [~] El rechazo rapido desde listado usa una nota fija; conviene agregar modal dedicado con motivo obligatorio visible.
- [~] Falta reporte dedicado de transferencias confirmadas/pendientes/rechazadas por banco y cajero.

Archivos creados/modificados:
- `database/migrations/2026_05_19_011000_add_movement_type_to_bank_transfers_table.php`
- `app/Services/Cash/BankTransferService.php`
- `app/Services/Cash/CashIncidentService.php`
- `app/Http/Controllers/Admin/BankTransferController.php`
- `app/Http/Controllers/Admin/CashIncidentController.php`
- `app/Http/Requests/Admin/BankTransferStoreRequest.php`
- `app/Http/Requests/Admin/BankTransferRejectRequest.php`
- `app/Http/Requests/Admin/CashIncidentResolveRequest.php`
- `resources/views/admin/cash/transfers/index.blade.php`
- `resources/views/admin/cash/transfers/create.blade.php`
- `resources/views/admin/cash/incidents/index.blade.php`
- `resources/views/admin/cash/current.blade.php`
- `resources/views/layouts/app.blade.php`
- `database/seeders/PermissionSeeder.php`
- `database/seeders/RoleSeeder.php`
- `routes/web.php`
- `app/Models/BankTransfer.php`
- `app/Services/Cash/CashService.php`
- `tests/Feature/SaleAndPrizeCycleTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Una transferencia pendiente no crea movimiento de caja; solo al confirmarse se registra el movimiento financiero.
- Las transferencias verificadas quedan en el balance operativo, pero no modifican el efectivo fisico esperado.
- Cajeros y pagadores pueden crear transferencias, pero la verificacion queda para admin, supervisor o contador.
- Las incidencias no se borran; se resuelven con nota, usuario y fecha.

Riesgos detectados:
- Falta adjuntar evidencia real y validar archivos para transferencias.
- Falta flujo de aprobacion contable de faltantes/sobrantes despues de resolver la incidencia.
- Falta pantalla de detalle de incidencia/transferencia con timeline completo.
- Siguen existiendo advertencias de PHPUnit por metadata en doc-comments heredadas.

Validacion ejecutada:
- `php artisan migrate`: correcto.
- `php artisan db:seed --class=PermissionSeeder --force`: correcto.
- `php artisan db:seed --class=RoleSeeder --force`: correcto.
- `php -l` en PHP modificados: correcto.
- `php artisan test --filter='cash_transfer_and_incident_pages|pending_bank_transfer|cash_incident_can_be_resolved'`: correcto, 3 tests, 37 assertions.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan test`: correcto, 62 tests, 567 assertions.
- `php artisan route:list --except-vendor`: correcto, 129 rutas.
- Browser: las rutas nuevas redirigieron a login al no haber sesion activa; las vistas se validaron por pruebas HTTP autenticadas.

Proximo paso recomendado:
- Crear carga segura de evidencias y reporte detallado de transferencias/incidencias, o avanzar al tratamiento contable formal de faltantes y sobrantes.

---

## 2026-05-19 - Pago de ticket por QR/numero con validacion de efectivo

Responsable:
- Codex

Fase trabajada:
- Fase 5: pagos de premios integrados con caja.

Puntos completados:
- [x] Se agrego pago de ticket completo desde numero de ticket o QR cuando existen premios liberados.
- [x] La busqueda de ticket ahora devuelve `pay_url`, `released_prize_total` y `can_pay_released_prizes`.
- [x] El POS muestra boton "Pagar ticket completo" cuando el ticket tiene premios liberados pendientes.
- [x] La pantalla de detalle del ticket muestra boton de pago completo cuando aplica.
- [x] Antes de pagar premio se recalcula caja y se valida efectivo disponible.
- [x] Si no hay efectivo suficiente, el pago se bloquea y no crea `prize_payment`.
- [x] El administrador puede registrar un refuerzo de caja como `CASH_IN`; luego el pago queda permitido.
- [x] El pago genera salida de caja `PRIZE_PAYMENT`, asiento contable y auditoria.
- [x] Se agrego prueba de ticket por busqueda QR/numero, bloqueo por efectivo insuficiente, refuerzo de caja y pago final.

Archivos creados/modificados:
- `app/Support/Money.php`
- `app/Services/Cash/CashService.php`
- `app/Services/Results/PrizePaymentService.php`
- `app/Http/Controllers/Admin/PrizeController.php`
- `app/Http/Controllers/Admin/TicketController.php`
- `app/Policies/TicketPolicy.php`
- `resources/views/admin/tickets/sale.blade.php`
- `resources/views/admin/tickets/show.blade.php`
- `routes/web.php`
- `tests/Feature/SaleAndPrizeCycleTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- No se permite marcar un ticket como `PAID` manualmente si no hay premios liberados; el pago debe venir de `winner_tickets`.
- La validacion de efectivo usa `expected_cash` recalculado y bloqueo de fila de `cash_sessions` dentro de la transaccion de pago.
- El refuerzo de caja para poder pagar se registra como movimiento `CASH_IN`, no como transferencia bancaria, porque el pago en efectivo requiere efectivo fisico disponible.

Riesgos detectados:
- Falta pantalla dedicada de "refuerzo de caja" con aprobacion de administrador; hoy se usa el formulario de movimientos de caja.
- Falta separar permisos para que cajero no pueda registrar cualquier `CASH_IN` si se decide restringirlo solo a administradores.
- `Money::normalize()` aun usa conversion interna a float y debe endurecerse luego.
- Siguen existiendo advertencias PHPUnit por metadata en doc-comments heredadas.

Validacion ejecutada:
- `php -l` en PHP modificados: correcto.
- `php artisan test --filter=released_ticket_prize_cannot_be_paid_when_cash_is_insufficient_until_admin_funds_cash`: correcto.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan test`: correcto, 63 tests, 590 assertions.
- `php artisan route:list --except-vendor`: correcto, 130 rutas.

Proximo paso recomendado:
- Crear flujo dedicado de refuerzo/traslado de efectivo entre admin y caja con permiso propio, aprobacion y comprobante, para no depender del movimiento generico `CASH_IN`.

---

## 2026-05-19 - Monitoreo por sucursal y notificaciones operativas

Responsable:
- Codex

Fase trabajada:
- Monitoreo operativo transversal sobre ventas, premios y caja.

Puntos completados:
- [x] Se creo tabla `system_notifications` para alertas persistentes por empresa/sucursal.
- [x] Se creo modelo `SystemNotification`.
- [x] Se creo `BranchMonitoringService` para consolidar ventas, premios, neto, caja estimada y jugada mas alta por sucursal.
- [x] Se creo `SystemNotificationService` para crear/actualizar alertas sin duplicarlas y marcarlas como leidas.
- [x] Se creo pantalla `admin/monitoring` con vista por sucursal.
- [x] Se creo pantalla `admin/notifications` para revisar y marcar alertas como leidas.
- [x] Se agrego menu `Monitoreo` y badge de notificaciones sin leer.
- [x] Se agregaron permisos `monitoring.view`, `notifications.view`, `notifications.manage`.
- [x] Se genera alerta critica `BRANCH_LOSS` cuando premios superan ventas o caja estimada queda negativa.
- [x] Se muestra la jugada con mayor monto por sucursal: numero, monto, tipo, loteria y sorteo.
- [x] Se agrego prueba de integracion que valida perdida por sucursal, alerta visible y notificacion persistente.

Archivos creados/modificados:
- `database/migrations/2026_05_19_012000_create_system_notifications_table.php`
- `app/Models/SystemNotification.php`
- `app/Services/Monitoring/SystemNotificationService.php`
- `app/Services/Monitoring/BranchMonitoringService.php`
- `app/Http/Controllers/Admin/MonitoringController.php`
- `app/Http/Controllers/Admin/SystemNotificationController.php`
- `resources/views/admin/monitoring/index.blade.php`
- `resources/views/admin/notifications/index.blade.php`
- `resources/views/layouts/app.blade.php`
- `database/seeders/PermissionSeeder.php`
- `database/seeders/RoleSeeder.php`
- `routes/web.php`
- `tests/Feature/BranchMonitoringTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- La primera regla de perdida es operativa y directa: premios pagados del dia mayores que ventas del dia, o caja activa con efectivo esperado negativo.
- Las notificaciones usan `fingerprint` diario por empresa/sucursal para evitar duplicados mientras sigan sin leer.
- El monitoreo se calcula con agregados agrupados por sucursal y filtros de fecha/sucursal, evitando consultas masivas sin filtros.

Riesgos detectados:
- El monitoreo se recalcula al abrir la pantalla; para alertas proactivas reales falta job programado que ejecute el escaneo cada pocos minutos.
- Falta umbral configurable por empresa/sucursal para alertar antes de llegar a perdida.
- Falta canal de envio externo: email, WhatsApp, push o websocket.
- Siguen existiendo advertencias PHPUnit por metadata en doc-comments heredadas.

Validacion ejecutada:
- `php artisan migrate`: correcto.
- `php artisan db:seed --class=PermissionSeeder --force`: correcto.
- `php artisan db:seed --class=RoleSeeder --force`: correcto.
- `php -l` en PHP modificados: correcto.
- `php artisan test tests/Feature/BranchMonitoringTest.php`: correcto, 1 test, 9 assertions.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan test`: correcto, 64 tests, 599 assertions.
- `php artisan route:list --except-vendor`: correcto, 133 rutas.

Proximo paso recomendado:
- Convertir el monitoreo en proceso proactivo con comando Artisan + scheduler y agregar umbrales configurables de alerta por empresa/sucursal.

---

## 2026-05-19 - Monitoreo proactivo con umbrales configurables

Responsable:
- Codex

Fase trabajada:
- Monitoreo operativo transversal sobre ventas, premios y caja.

Puntos completados:
- [x] Se creo tabla `branch_monitoring_settings` para umbrales por empresa y por sucursal.
- [x] Se creo modelo `BranchMonitoringSetting`.
- [x] Se agrego permiso `monitoring.configure`.
- [x] Se creo pantalla `admin/monitoring/settings` para configurar perdida critica, caja minima y jugada acumulada alta.
- [x] Se extendio `BranchMonitoringService` para alertas `BRANCH_LOW_CASH` y `BRANCH_TOP_PLAY_HIGH`.
- [x] Se creo comando Artisan `monitoring:scan-branches`.
- [x] Se programo el escaneo cada 5 minutos en `routes/console.php`.
- [x] Se agregaron pruebas de comando proactivo y configuracion de umbrales.

Archivos creados/modificados:
- `database/migrations/2026_05_19_014000_create_branch_monitoring_settings_table.php`
- `app/Models/BranchMonitoringSetting.php`
- `app/Http/Requests/Admin/MonitoringSettingsRequest.php`
- `app/Console/Commands/ScanBranchMonitoringCommand.php`
- `app/Services/Monitoring/BranchMonitoringService.php`
- `app/Http/Controllers/Admin/MonitoringController.php`
- `resources/views/admin/monitoring/index.blade.php`
- `resources/views/admin/monitoring/settings.blade.php`
- `database/seeders/PermissionSeeder.php`
- `database/seeders/RoleSeeder.php`
- `routes/web.php`
- `routes/console.php`
- `tests/Feature/BranchMonitoringTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Los umbrales se guardan en tabla dedicada, no en `system_settings`, porque necesitan alcance por sucursal, validacion de columnas monetarias e indices claros.
- El comando usa el mismo `BranchMonitoringService` que la pantalla para evitar reglas duplicadas.
- La configuracion por sucursal sobrescribe la configuracion por defecto de la empresa.
- Las alertas se siguen creando con `fingerprint` diario para evitar duplicados mientras esten sin leer.

Riesgos detectados:
- Falta canal externo de entrega de notificaciones: email, WhatsApp, push o websocket.
- Falta flujo dedicado de refuerzo/traslado de efectivo; hoy el refuerzo sigue dependiendo del movimiento de caja.
- `Money::normalize()` aun usa conversion interna a float y debe endurecerse en una fase de precision monetaria.
- Siguen existiendo advertencias PHPUnit por metadata en doc-comments heredadas.

Validacion ejecutada:
- `php -l` en PHP modificados: correcto.
- `php artisan migrate`: correcto.
- `php artisan db:seed --class=PermissionSeeder`: correcto.
- `php artisan db:seed --class=RoleSeeder`: correcto.
- `php artisan test tests/Feature/BranchMonitoringTest.php`: correcto, 3 tests, 16 assertions.
- `php artisan monitoring:scan-branches`: correcto.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan test`: correcto, 66 tests, 606 assertions.
- `php artisan route:list --except-vendor`: correcto, 135 rutas.
- `php artisan schedule:list`: correcto; `monitoring:scan-branches` queda cada 5 minutos.

Proximo paso recomendado:
- Crear flujo dedicado de refuerzo/traslado de efectivo desde administrador hacia sucursal/caja, con permiso propio, aprobacion, comprobante y auditoria.

---

## 2026-05-19 - Refuerzo de efectivo para cajas abiertas

Responsable:
- Codex

Fase trabajada:
- Caja, pagos de premios y control financiero operativo.

Puntos completados:
- [x] Se creo tabla `cash_funding_transfers` para registrar refuerzos de efectivo autorizados.
- [x] Se creo modelo `CashFundingTransfer`.
- [x] Se creo request `CashFundingTransferRequest` con permiso y validacion de monto.
- [x] Se creo `CashFundingTransferController` con listado, formulario y registro de refuerzo.
- [x] Se agrego metodo `CashService::fundCashSession()` con transaccion, bloqueo de caja, movimiento `CASH_IN` y recalculo de `expected_cash`.
- [x] Se agregaron permisos `cash.funding.view` y `cash.funding.create`.
- [x] Se agregaron rutas `admin/cash/funding`.
- [x] Se agregaron pantallas de refuerzos y acceso desde Caja.
- [x] Se agrego prueba de integracion que valida que el refuerzo aumenta el efectivo esperado y crea movimiento financiero.

Archivos creados/modificados:
- `database/migrations/2026_05_19_015000_create_cash_funding_transfers_table.php`
- `app/Models/CashFundingTransfer.php`
- `app/Http/Requests/Admin/CashFundingTransferRequest.php`
- `app/Http/Controllers/Admin/CashFundingTransferController.php`
- `app/Services/Cash/CashService.php`
- `resources/views/admin/cash/funding/index.blade.php`
- `resources/views/admin/cash/funding/create.blade.php`
- `resources/views/admin/cash/current.blade.php`
- `resources/views/admin/cash/index.blade.php`
- `database/seeders/PermissionSeeder.php`
- `database/seeders/RoleSeeder.php`
- `routes/web.php`
- `tests/Feature/BranchMonitoringTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- El refuerzo se registra como entidad propia y tambien como movimiento `CASH_IN`, porque debe aumentar efectivo fisico disponible y quedar trazable.
- Solo se permite reforzar cajas abiertas de la empresa activa.
- El registro bloquea la fila de `cash_sessions` para evitar inconsistencias de concurrencia al recalcular efectivo esperado.

Riesgos detectados:
- El flujo actual registra el refuerzo como completado inmediatamente; si se quiere manejar solicitud/aprobacion/recepcion por separado, debe agregarse estado `PENDING/APPROVED/RECEIVED`.
- Falta comprobante adjunto o evidencia de entrega fisica.
- Falta notificacion automatica al cajero cuando el admin registra el refuerzo.
- `Money::normalize()` aun usa conversion interna a float y debe endurecerse en una fase de precision monetaria.

Validacion ejecutada:
- `php -l` en PHP modificados: correcto.
- `php artisan migrate`: correcto.
- `php artisan db:seed --class=PermissionSeeder`: correcto.
- `php artisan db:seed --class=RoleSeeder`: correcto.
- `php artisan test tests/Feature/BranchMonitoringTest.php`: correcto, 4 tests, 23 assertions.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan test`: correcto, 67 tests, 613 assertions.
- `php artisan route:list --except-vendor`: correcto, 138 rutas.
- `php artisan schedule:list`: correcto.

Proximo paso recomendado:
- Agregar estados de recepcion del refuerzo y notificacion al cajero; luego endurecer `Money::normalize()` para eliminar conversiones internas a float.

---

## 2026-05-19 â€” ExportaciÃ³n PDF/Excel en todos los reportes

Responsable:
- Claude Sonnet 4.6

Fase trabajada:
- Fase 8: reportes operativos.

Puntos completados:
- [x] Paquetes instalados: `maatwebsite/excel` v3.1 y `barryvdh/laravel-dompdf` v3.1.
- [x] Clase genÃ©rica `ReportExport` (`app/Exports/ReportExport.php`) con cabecera estilizada.
- [x] Plantilla PDF genÃ©rica (`resources/views/admin/reports/exports/pdf.blade.php`) â€” horizontal A4, logo-free, empresa/perÃ­odo en encabezado.
- [x] Botones Excel y PDF en el partial de filtros (`partials/filters.blade.php`) â€” visibles solo en subvistas de reporte (no en el dashboard de Ã­ndice).
- [x] Los 8 mÃ©todos del `ReportController` soportan `?export=excel` y `?export=pdf` respetando los mismos filtros activos.
- [x] MÃ¡ximo 5000 filas por exportaciÃ³n (lÃ­mite operativo razonable).
- [x] 73 tests, 632 assertions â€” todo pasa.

Archivos creados/modificados:
- `app/Exports/ReportExport.php` (nuevo)
- `resources/views/admin/reports/exports/pdf.blade.php` (nuevo)
- `app/Http/Controllers/Admin/ReportController.php` (export en 8 mÃ©todos)
- `resources/views/admin/reports/partials/filters.blade.php` (botones Excel/PDF)
- `composer.json` / `composer.lock` (nuevas dependencias)

Decisiones tomadas:
- Export genÃ©rico (un solo `ReportExport` y una sola vista PDF) en lugar de 8 clases separadas; las columnas y datos se construyen inline en el controlador.
- Los filtros activos se preservan en la URL de export (`array_merge(request()->query(), ['export' => 'excel'])`).
- OrientaciÃ³n landscape en PDF para tablas anchas.

PrÃ³ximo paso recomendado:
- ValidaciÃ³n de dispositivo bloqueado en endpoints operativos/API.
- API REST para Android (Fase 6).

---

## 2026-05-19 â€” Rate limit en login + sorteos diarios automÃ¡ticos + etiquetas en espaÃ±ol + atajos de teclado en POS

Responsable:
- Claude Sonnet 4.6

Fase trabajada:
- Fase 1 (seguridad), Fase 3 (sorteos), Fase 8 (UX/POS).

Puntos completados:
- [x] Rate limit en login: `RateLimiter` por username+IP, bloqueo tras 10 intentos/min con cuenta regresiva en segundos.
- [x] Bloqueo automÃ¡tico de cuenta: tras 5 intentos fallidos se setea `locked_until = now() + 15 min`.
- [x] Reset automÃ¡tico en login exitoso: `failed_login_attempts = 0`, `locked_until = null`, `RateLimiter::clear()`.
- [x] Test `LoginSecurityTest` con 6 casos (todos pasan).
- [x] Comando `draws:generate-daily` creado (`app/Console/Commands/GenerateDailyDrawsCommand.php`).
- [x] Seeder `DominicanLotteryCatalogSeeder` refactorizado: nuevo mÃ©todo pÃºblico `runForDate(string $date)`.
- [x] Comando registrado en scheduler `routes/console.php` con `dailyAt('00:01')`.
- [x] Sorteos de hoy creados ejecutando el seeder manualmente.
- [x] Componente Blade `StatusBadge` creado: `app/View/Components/StatusBadge.php` + `resources/views/components/status-badge.blade.php`.
- [x] Todos los estados en la UI ahora se muestran en espaÃ±ol (mapeados desde el componente).
- [x] 18 vistas actualizadas para usar `<x-status-badge :status="..." />`.
- [x] Atajos de teclado POS: `Ctrl+L` limpiar jugadas, `Ctrl+T` limpiar jugadas + loterÃ­as seleccionadas.

Archivos creados/modificados:
- `app/Http/Controllers/AuthController.php` (rate limit + auto-lock)
- `tests/Feature/LoginSecurityTest.php` (nuevo)
- `app/Console/Commands/GenerateDailyDrawsCommand.php` (nuevo)
- `database/seeders/DominicanLotteryCatalogSeeder.php` (mÃ©todo `runForDate`)
- `routes/console.php` (schedule)
- `app/View/Components/StatusBadge.php` (nuevo)
- `resources/views/components/status-badge.blade.php` (nuevo)
- `resources/views/admin/tickets/sale.blade.php` (atajos Ctrl+L y Ctrl+T)
- 17 vistas admin actualizadas con `<x-status-badge>`

Decisiones tomadas:
- Etiquetas de estado en la UI en espaÃ±ol sin cambiar valores internos de BD; mapeado centralizado en `StatusBadge` para mantener consistencia.
- Sorteos generados diariamente a las 00:01; el scheduler de Laravel debe estar corriendo (`php artisan schedule:run` en cron o task scheduler de Windows).
- `Ctrl+T` deselecciona tambiÃ©n las loterÃ­as, no solo las jugadas; es el "limpiar todo" del POS.

Riesgos detectados:
- El scheduler de Laravel (`php artisan schedule:run`) debe estar configurado en el cron/task scheduler del servidor para que `draws:generate-daily` se ejecute automÃ¡ticamente cada dÃ­a.

PrÃ³ximo paso recomendado:
- ExportaciÃ³n PDF/Excel en reportes operativos (Fase 8).
- ValidaciÃ³n de dispositivo bloqueado en endpoints operativos/API.

---

## 2026-05-19 - Redirect obligatorio a cambio de password en login

Responsable:
- Claude Sonnet 4.6

Fase trabajada:
- Fase 1: autenticaciÃ³n y seguridad operativa.

Puntos completados:
- [x] Se agregÃ³ redirect a `password.edit` en `AuthController::store()` cuando `must_change_password` es verdadero.
- [x] El redirect ocurre despuÃ©s del login exitoso y antes del `redirect()->intended(dashboard)`.
- [x] El flujo queda: credenciales vÃ¡lidas â†’ sesiÃ³n regenerada â†’ actualizar last_login_at â†’ auditar login â†’ si `must_change_password` â†’ redirigir a cambio de clave, sino â†’ dashboard.

Archivos creados/modificados:
- `app/Http/Controllers/AuthController.php`

Decisiones tomadas:
- El redirect usa `route('password.edit')` directo porque ya existe el middleware `EnsurePasswordWasChanged` como capa adicional de protecciÃ³n; el redirect en login es solo la experiencia fluida para que el usuario llegue directamente al formulario.
- No se necesita middleware adicional; la doble protecciÃ³n ya existe: middleware global + redirect en login.

Riesgos detectados:
- Ninguno nuevo. El flujo ya era correcto desde el middleware; solo faltaba la redirecciÃ³n explÃ­cita post-login para no mostrar el dashboard brevemente antes de la redirecciÃ³n del middleware.

PrÃ³ximo paso recomendado:
- Revisar los puntos pendientes del control de seguridad: rate limit en login, bloqueo automÃ¡tico por intentos fallidos y validaciÃ³n de dispositivos bloqueados en endpoints operativos.

---

## 2026-05-19 â€” Exportaciones PDF/Excel, seguridad dispositivos, reporte lÃ­mites y API offline

Responsable: Claude Sonnet 4.6

Fases trabajadas: Fase 6 (API Android backend), Fase 8 (reportes), Seguridad.

Puntos completados:
- [x] ExportaciÃ³n PDF corregida: `Pdf::view()` â†’ `Pdf::loadView()` (barryvdh/laravel-dompdf).
- [x] Reporte "NÃºmeros cerca del lÃ­mite" implementado con filtro de umbral configurable (default 70%).
- [x] ValidaciÃ³n de dispositivos bloqueados en API REST (middleware `EnsureDeviceIsAuthorized`).
- [x] API login actualizado: registra dispositivo (PENDING), bloquea login si BLOCKED, retorna `device_status`.
- [x] Rutas API protegidas con `auth:sanctum` + `device.authorized`.
- [x] Alias `device.authorized` registrado en `bootstrap/app.php`.
- [x] Fase 6 backend: tablas `offline_sessions`, `offline_limit_allocations`, `sync_batches`, `sync_conflicts`.
- [x] Modelos `OfflineSession`, `OfflineLimitAllocation`, `SyncBatch`, `SyncConflict`.
- [x] `OfflineSyncService` con `openSession()`, `buildBootstrap()`, `processBatch()`, `resolveConflict()`.
- [x] `OfflineController` con todos los endpoints: `/api/bootstrap`, `/api/offline/session/open`, `/api/offline/bootstrap`, `/api/offline/sync`, `/api/offline/conflicts`, `/api/offline/conflicts/{id}/resolve`.
- [x] `BetType` faltaba en imports de `TicketController` â†’ corregido.
- [x] Browser check: 52/52 OK incluyendo exportaciones Excel y PDF.

Archivos creados/modificados:
- `app/Http/Middleware/EnsureDeviceIsAuthorized.php` (nuevo)
- `app/Http/Controllers/ApiController.php` (login con registro de dispositivo)
- `app/Http/Controllers/Api/OfflineController.php` (nuevo)
- `app/Http/Controllers/Admin/ReportController.php` (numbersNearLimit, loadView fix)
- `app/Http/Controllers/Admin/TicketController.php` (BetType import)
- `app/Services/Offline/OfflineSyncService.php` (nuevo)
- `app/Models/OfflineSession.php`, `OfflineLimitAllocation.php`, `SyncBatch.php`, `SyncConflict.php` (nuevos)
- `database/migrations/2026_05_19_100000..100003_*` (4 migraciones offline)
- `resources/views/admin/reports/numbers-near-limit.blade.php` (nuevo)
- `resources/views/admin/reports/index.blade.php` (enlace nuevo reporte)
- `routes/api.php` (sanctum + device middleware + rutas offline)
- `routes/web.php` (ruta reports/numbers-near-limit)
- `bootstrap/app.php` (alias device.authorized)
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Dispositivos con status PENDING (reciÃ©n registrados) pueden operar; solo BLOCKED es rechazado.
- HAVING no soportado por SQLite en subqueries de paginaciÃ³n â†’ reemplazado con `whereRaw` + `orderByRaw`.
- El sync offline procesa tickets con el mismo `TicketSaleService` que el online; si el sorteo cerrÃ³, genera conflicto.

Riesgos detectados:
- La app Android (Kotlin/Compose) es la Ãºnica parte de Fase 6 que queda pendiente; el backend estÃ¡ completo.

PrÃ³ximo paso recomendado:
- Fase 7 (Print Agent PC) o continuar con pruebas unitarias pendientes de Fase 5.

---

## 2026-05-19 â€” Fase 7: Print Agent PC completo

Responsable: Claude Sonnet 4.6

Fase trabajada: Fase 7 â€” Print Agent PC.

Puntos completados:
- [x] Java Print Agent (Spring Boot 3.3, Java 17) en `print-agent/`.
- [x] `pom.xml` con dependencias spring-boot-starter-web + security.
- [x] `application.yml`: bind a 127.0.0.1:8765, PRINT_AGENT_TOKEN configurable.
- [x] `PrintAgentApplication.java` + `AgentProperties.java` (record @ConfigurationProperties).
- [x] `TokenAuthFilter.java`: verifica IP localhost + Bearer token.
- [x] `SecurityConfig.java`: CORS localhost-only, stateless, filtro de token.
- [x] `EscPosBuilder.java`: init, text (charset configurable), feed, cut.
- [x] `PrinterService.java`: `listPrinters()` via javax.print, `print()` USB/WINDOWS_SHARED/NETWORK.
- [x] `PrintController.java`: GET /api/status (pÃºblico), GET /api/printers, POST /api/print, POST /api/test.
- [x] DTOs `PrintRequest.java` y `PrinterInfo.java`.
- [x] `print-agent/README.md` con instrucciones de compilaciÃ³n, configuraciÃ³n y autostart Windows.
- [x] `config/print.php` con PRINT_AGENT_URL y PRINT_AGENT_TOKEN.
- [x] `.env` + `.env.example` con PRINT_AGENT_TOKEN y PRINT_AGENT_URL.
- [x] `public/js/print-agent.js`: JS bridge que conecta browser â†’ agente â†’ Laravel.
- [x] Layout `app.blade.php`: carga print-agent.js + inyecta bsPrintAgentUrl/Token, indicador en navbar.
- [x] `Api/PrintJobController.php`: GET /api/print-jobs/pending, POST /api/print-jobs/{uuid}/ack.
- [x] `routes/api.php`: rutas print-jobs registradas bajo sanctum.
- [x] Vista impresoras: panel estado agente, botÃ³n ver impresoras del sistema, test print via JS (sin POST form).

Archivos creados:
- `print-agent/pom.xml`
- `print-agent/src/main/resources/application.yml`
- `print-agent/src/main/java/.../PrintAgentApplication.java`
- `print-agent/src/main/java/.../config/AgentProperties.java`
- `print-agent/src/main/java/.../config/TokenAuthFilter.java`
- `print-agent/src/main/java/.../config/SecurityConfig.java`
- `print-agent/src/main/java/.../controller/PrintController.java`
- `print-agent/src/main/java/.../dto/PrintRequest.java`
- `print-agent/src/main/java/.../dto/PrinterInfo.java`
- `print-agent/src/main/java/.../service/EscPosBuilder.java`
- `print-agent/src/main/java/.../service/PrinterService.java`
- `print-agent/README.md`
- `config/print.php`
- `public/js/print-agent.js`
- `app/Http/Controllers/Api/PrintJobController.php`

Archivos modificados:
- `routes/api.php` (rutas print-jobs)
- `resources/views/layouts/app.blade.php` (JS bridge + indicador navbar)
- `resources/views/admin/printers/index.blade.php` (panel agente + test via JS)
- `.env` (PRINT_AGENT_TOKEN=bslottery-dev-token-2026)
- `.env.example` (documentar variables)
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- El agente escucha en 127.0.0.1 (no 0.0.0.0) â€” rechazo a nivel de bind + filtro IP redundante.
- ESC/POS no requiere librerÃ­a externa: `javax.print` (JDK estÃ¡ndar) para USB/Windows, TCP socket para red.
- El token del agente se inyecta al JS vÃ­a `window.bsPrintAgentToken` desde Blade (exposiciÃ³n controlada, solo localhost).
- Jobs con error quedan en status FAILED en Laravel; reimpresiÃ³n es manual desde historial.
- La app Android usa su propio PrinterManager Bluetooth; el Print Agent PC es solo para impresoras del cajero de PC.

PrÃ³ximo paso recomendado:
- Fase 9 (NÃ³mina: empleados, periodos, cÃ¡lculo, pago integrado con caja).

---

## 2026-05-19 â€” Fase 9: NÃ³mina completa

Responsable: Claude Sonnet 4.6

Fase trabajada: Fase 9 â€” NÃ³mina.

Puntos completados:
- [x] MigraciÃ³n `payroll_tables`: `employee_advances`, `employee_loans`, `payroll_periods`, `payroll_details`.
- [x] Modelos: `EmployeeAdvance`, `EmployeeLoan`, `PayrollPeriod`, `PayrollDetail`.
- [x] `PayrollService::generate()` â€” calcula salario fijo + comisiÃ³n (% sobre ventas del perÃ­odo via BCMath) + avances + cuotas de prÃ©stamos.
- [x] `PayrollService::approve()` â€” DRAFT â†’ APPROVED.
- [x] `PayrollService::pay()` â€” APPROVED â†’ PAID, marca advances/loans, CashMovement categorÃ­a PAYROLL.
- [x] `EmployeeController` (CRUD) con validaciÃ³n.
- [x] `EmployeeAdvanceController` (listado, store, aprobar/rechazar).
- [x] `EmployeeLoanController` (listado, store, cancelar).
- [x] `PayrollController` (index, show, generate, approve, pay con selecciÃ³n de caja).
- [x] Policies: Employee, EmployeeAdvance, EmployeeLoan, PayrollPeriod.
- [x] Permiso `payroll.manage` agregado al PermissionSeeder.
- [x] Vistas: employees/index, employees/form, employees/advances, employees/loans, payroll/index, payroll/show.
- [x] MenÃº lateral "NÃ³mina" con submenÃº: Empleados, Avances, PrÃ©stamos, PerÃ­odos.
- [x] Rutas registradas en web.php (16 rutas: employees resource + advances + loans + payroll).
- [x] Migraciones ejecutadas correctamente.

Pendientes menores:
- Asiento contable formal al pagar nÃ³mina (CashMovement existe, falta JournalEntry de nÃ³mina).
- AutomatizaciÃ³n de faltantes de caja â†’ cash_shortage en payroll_details.
- Formulario de ajuste manual de bonus y other_deductions en detalle de nÃ³mina.
- Pruebas unitarias de PayrollService.

Archivos creados:
- `database/migrations/2026_05_19_200000_create_payroll_tables.php`
- `app/Models/PayrollPeriod.php`, `PayrollDetail.php`, `EmployeeAdvance.php`, `EmployeeLoan.php`
- `app/Services/Payroll/PayrollService.php`
- `app/Http/Controllers/Admin/EmployeeController.php`
- `app/Http/Controllers/Admin/EmployeeAdvanceController.php`
- `app/Http/Controllers/Admin/EmployeeLoanController.php`
- `app/Http/Controllers/Admin/PayrollController.php`
- `app/Policies/EmployeePolicy.php`, `EmployeeAdvancePolicy.php`, `EmployeeLoanPolicy.php`, `PayrollPeriodPolicy.php`
- `resources/views/admin/employees/index.blade.php`
- `resources/views/admin/employees/form.blade.php`
- `resources/views/admin/employees/advances.blade.php`
- `resources/views/admin/employees/loans.blade.php`
- `resources/views/admin/payroll/index.blade.php`
- `resources/views/admin/payroll/show.blade.php`

Archivos modificados:
- `database/seeders/PermissionSeeder.php` (payroll.manage)
- `routes/web.php` (16 rutas nÃ³mina)
- `resources/views/layouts/app.blade.php` (menÃº NÃ³mina)
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

PrÃ³ximo paso recomendado:
- Fase 10: OptimizaciÃ³n para producciÃ³n (Ã­ndices, OPcache, queues, HTTPS, backups).
- O pruebas unitarias pendientes de Fases 5, 9.

---

## 2026-05-19 â€” Fase 10: OptimizaciÃ³n para producciÃ³n

Responsable: Claude Sonnet 4.6

Fase trabajada: Fase 10 â€” OptimizaciÃ³n y producciÃ³n.

Puntos completados:
- [x] MigraciÃ³n de Ã­ndices de producciÃ³n: 11 tablas con 18 Ã­ndices compuestos para queries crÃ­ticas de ventas, lÃ­mites, caja, auditorÃ­a, ganadores, sincronizaciÃ³n offline, nÃ³mina.
- [x] Middleware `SecurityHeaders`: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP en producciÃ³n, remociÃ³n de X-Powered-By/Server.
- [x] Script `deploy.sh`: composer install --no-dev, config/route/view/event:cache, migrate --force, seed opcional, storage:link, queue:restart, chown/chmod.
- [x] Ãndices ejecutados en SQLite local sin errores.
- [x] 168 rutas en total verificadas sin errores de registro.

Archivos creados:
- `database/migrations/2026_05_19_300000_add_performance_indexes.php`
- `app/Http/Middleware/SecurityHeaders.php`
- `deploy.sh`

Archivos modificados:
- `bootstrap/app.php` (SecurityHeaders en web middleware stack)
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Pendientes para producciÃ³n real:
- HTTPS (configuraciÃ³n en nginx/Apache).
- OPcache en php.ini.
- Configurar worker de colas con supervisor.
- Backups automÃ¡ticos.
- Ejecutar `bash deploy.sh --seed` en primer despliegue.

---

## 2026-05-19 â€” Correccion compilacion Android TicketDetailViewModel

Responsable:
- Codex

Fase trabajada:
- Android / detalle de ticket e impresion Bluetooth.

Puntos completados:
- [x] Se corrigio error Kotlin `Unresolved reference 'update'` en `TicketDetailViewModel`.
- [x] Se agrego import faltante `kotlinx.coroutines.flow.update`.
- [x] Se valido compilacion Kotlin debug del modulo Android.

Archivos modificados:
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketDetailViewModel.kt`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Se mantuvo el patron existente del proyecto: otros ViewModels ya usan `MutableStateFlow.update`.
- No se cambio arquitectura ni flujo de impresion; el error era de import faltante.

Riesgos detectados:
- Android Gradle Plugin 8.5.2 advierte que fue probado hasta `compileSdk = 34`, mientras el proyecto usa `compileSdk = 35`.
- Hay warnings de iconos Compose deprecados en `SaleScreen.kt` y `TicketDetailScreen.kt`.
- Gradle reporta features deprecadas que seran incompatibles con Gradle 10.

Validacion ejecutada:
- `.\gradlew.bat :app:compileDebugKotlin`: correcto, `BUILD SUCCESSFUL`.

Proximo paso recomendado:
- Corregir warnings Android de iconos `AutoMirrored` y revisar compatibilidad AGP/compileSdk antes de generar APK de produccion.

---

## 2026-05-19 â€” Limpieza warnings Android y APK debug

Responsable:
- Codex

Fase trabajada:
- Android / estabilidad de build antes de APK.

Puntos completados:
- [x] Se reemplazaron iconos direccionales deprecados por `Icons.AutoMirrored.Filled`.
- [x] Se corrigieron usos de `ArrowBack`, `ArrowForward` y `Backspace` en pantalla de venta Android.
- [x] Se corrigio `ArrowBack` en detalle de ticket Android.
- [x] Se genero APK debug correctamente.

Archivos modificados:
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/sale/SaleScreen.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketDetailScreen.kt`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Se uso `AutoMirrored` porque respeta correctamente layouts LTR/RTL y elimina deprecaciones de Compose.
- No se actualizo Android Gradle Plugin en esta pasada; requiere una actualizacion controlada de toolchain para no romper build/Gradle.

Riesgos detectados:
- Persiste advertencia de compatibilidad: AGP `8.5.2` fue probado hasta `compileSdk = 34`, mientras el proyecto usa `compileSdk = 35`.
- El build debug compila, pero antes de release conviene actualizar AGP/Kotlin/Gradle de forma controlada o bajar temporalmente `compileSdk`.

Validacion ejecutada:
- `.\gradlew.bat :app:compileDebugKotlin`: correcto, `BUILD SUCCESSFUL`.
- `.\gradlew.bat :app:assembleDebug`: correcto, `BUILD SUCCESSFUL`.

Proximo paso recomendado:
- Actualizar Android Gradle Plugin de forma controlada y luego ejecutar `assembleDebug`/`assembleRelease`; si no se actualiza, documentar oficialmente la advertencia de `compileSdk 35`.

---

## 2026-05-19 â€” Actualizacion controlada Android Gradle Plugin

Responsable:
- Codex

Fase trabajada:
- Android / toolchain de compilacion.

Puntos completados:
- [x] Se actualizo Android Gradle Plugin de `8.5.2` a `8.9.0`.
- [x] Se elimino advertencia de compatibilidad entre AGP y `compileSdk = 35`.
- [x] Se migro `kotlinOptions { jvmTarget = "17" }` a `kotlin { compilerOptions { jvmTarget.set(JvmTarget.JVM_17) } }`.
- [x] Se corrigieron iconos Compose deprecados restantes con `Icons.AutoMirrored.Filled`.
- [x] Se valido compilacion Kotlin debug y APK debug.

Archivos modificados:
- `android/gradle/libs.versions.toml`
- `android/app/build.gradle.kts`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/dashboard/DashboardScreen.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/settings/SettingsScreen.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/sync/SyncScreen.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketsScreen.kt`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Se eligio AGP `8.9.0` en vez de AGP `9.x` para evitar cambios mayores del toolchain.
- Se mantuvo Gradle Wrapper `9.0.0`, que ya estaba funcionando en el proyecto.

Riesgos detectados:
- Persiste warning de Moshi: `Kapt support in Moshi Kotlin Code Gen is deprecated`; aunque el proyecto usa `ksp(libs.moshi.codegen)`, Gradle lo reporta durante `hiltJavaCompileDebug`.
- `assembleDebug` muestra aviso de librerias nativas no strippeadas (`libandroidx.graphics.path.so`, `libdatastore_shared_counter.so`), no bloqueante en debug.
- Antes de release conviene ejecutar `assembleRelease` y revisar R8/ProGuard.

Validacion ejecutada:
- `.\gradlew.bat :app:compileDebugKotlin`: correcto, `BUILD SUCCESSFUL`.
- `.\gradlew.bat :app:assembleDebug`: correcto, `BUILD SUCCESSFUL`.

Proximo paso recomendado:
- Revisar warning de Moshi codegen y luego ejecutar `assembleRelease` para validar build de produccion Android.

---

## 2026-05-19 â€” Validacion Android release y Moshi codegen

Responsable:
- Codex

Fase trabajada:
- Android / build release.

Puntos completados:
- [x] Se actualizo Moshi de `1.15.1` a `1.15.2`.
- [x] Se confirmo que el proyecto usa `ksp(libs.moshi.codegen)` y no tiene configuracion `kapt`.
- [x] Se valido que Moshi genera adaptadores KSP para los DTOs con `@JsonClass(generateAdapter = true)`.
- [x] Se ejecuto `assembleDebug` y `assembleRelease`.
- [x] Se genero APK release sin firmar.

Archivos modificados:
- `android/gradle/libs.versions.toml`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- No se elimino `moshi-kotlin-codegen` porque los DTOs remotos dependen de adaptadores generados y quitarlos cambiaria rendimiento/comportamiento de serializacion.
- El warning de Moshi se dejo como riesgo de dependencia externa: aparece durante `hiltJavaCompile*`, pero el classpath confirma que Moshi esta registrado como KSP processor.

Riesgos detectados:
- Persiste warning de Moshi: `Kapt support in Moshi Kotlin Code Gen is deprecated`; no bloquea build y no proviene de una configuracion `kapt` del proyecto.
- El APK release generado es `app-release-unsigned.apk`; falta configuracion de firma para produccion.
- Release usa `BuildConfig.API_BASE_URL` de `defaultConfig`, actualmente `http://10.0.2.2:8000/api/`, no valido para dispositivos reales fuera del emulador.

Validacion ejecutada:
- `.\gradlew.bat :app:assembleDebug`: correcto, `BUILD SUCCESSFUL`.
- `.\gradlew.bat :app:assembleRelease`: correcto, `BUILD SUCCESSFUL`.
- `.\gradlew.bat :app:assembleDebug --warning-mode all`: correcto, `BUILD SUCCESSFUL`.
- `.\gradlew.bat :app:assembleRelease --warning-mode all`: correcto, `BUILD SUCCESSFUL`.

Artefactos generados:
- `android/app/build/outputs/apk/debug/app-debug.apk`
- `android/app/build/outputs/apk/release/app-release-unsigned.apk`

Proximo paso recomendado:
- Configurar firma Android release y separar `API_BASE_URL` por flavor/build type para emulador, LAN y produccion.

---

## 2026-05-19 â€” Android flavors y firma release configurable

Responsable:
- Codex

Fase trabajada:
- Android / configuracion de ambientes y preparacion de firma.

Puntos completados:
- [x] Se confirmo uso de `01_PROMPT_MAESTRO_BSLOTTERY.md` como especificacion principal y `02_GUIA_TODO_CONTROL_BSLOTTERY.md` como control de avance.
- [x] Se configuraron flavors Android `emulator`, `lan` y `production`.
- [x] Se agrego `BuildConfig.SERVER_URL` por ambiente.
- [x] Se mantuvo `BuildConfig.API_BASE_URL` por compatibilidad.
- [x] Se cambio el valor por defecto del login para usar `BuildConfig.SERVER_URL`, eliminando el servidor quemado en `LoginViewModel`.
- [x] Se agrego firma release opcional por `keystore.properties` sin exponer secretos.
- [x] Se agrego `android/.gitignore` para `local.properties`, `keystore.properties` y keystores.
- [x] Se agrego `android/keystore.properties.example`.
- [x] Se agrego `android/README_RELEASE.md` con comandos de build y firma.

Archivos creados/modificados:
- `android/app/build.gradle.kts`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/login/LoginViewModel.kt`
- `android/.gitignore`
- `android/keystore.properties.example`
- `android/README_RELEASE.md`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- `emulator` usa `http://10.0.2.2:8000` para pruebas en emulador.
- `lan` usa `BSLOTTERY_LAN_SERVER_URL` para dispositivos reales dentro de red local.
- `production` usa `BSLOTTERY_PRODUCTION_SERVER_URL` y debe apuntar a HTTPS.
- La firma release no falla si no existe `keystore.properties`; genera APK unsigned para pruebas de build, y firmado cuando se configure el keystore.
- No se guardan claves, passwords ni keystores en el proyecto.

Riesgos detectados:
- `network_security_config.xml` permite HTTP globalmente para operar en LAN; para produccion HTTPS debe endurecerse con configuracion por flavor.
- `productionRelease` sigue unsigned hasta que se cree keystore real y `keystore.properties`.
- La URL de produccion usada en validacion fue `https://bsloteria.example.com`, solo para probar build; debe cambiarse por dominio real.
- Persiste warning externo de Moshi codegen aunque el proyecto usa KSP.

Validacion ejecutada:
- `.\gradlew.bat :app:tasks --all`: correcto, variantes generadas.
- `.\gradlew.bat :app:assembleEmulatorDebug`: correcto, `BUILD SUCCESSFUL`.
- `.\gradlew.bat :app:assembleLanDebug -PBSLOTTERY_LAN_SERVER_URL=http://192.168.1.50:8000`: correcto, `BUILD SUCCESSFUL`.
- `.\gradlew.bat :app:assembleProductionRelease -PBSLOTTERY_PRODUCTION_SERVER_URL=https://bsloteria.example.com`: correcto, `BUILD SUCCESSFUL`.
- `.\gradlew.bat :app:assembleEmulatorDebug :app:assembleLanDebug :app:assembleProductionRelease ... --warning-mode all`: correcto, `BUILD SUCCESSFUL`.
- `.\gradlew.bat :app:signingReport`: correcto; debug firmado con debug keystore, releases sin firma al no existir `keystore.properties`.
- Se verifico `BuildConfig` generado:
  - `emulatorDebug`: `SERVER_URL=http://10.0.2.2:8000`.
  - `lanDebug`: `SERVER_URL=http://192.168.1.50:8000`.
  - `productionRelease`: `SERVER_URL=https://bsloteria.example.com`.

Artefactos generados:
- `android/app/build/outputs/apk/emulator/debug/app-emulator-debug.apk`
- `android/app/build/outputs/apk/lan/debug/app-lan-debug.apk`
- `android/app/build/outputs/apk/production/release/app-production-release-unsigned.apk`

Proximo paso recomendado:
- Endurecer `network_security_config` por flavor: permitir HTTP solo en `emulator`/`lan` y exigir HTTPS en `production`.

---

## 2026-05-19 â€” Android network security por flavor

Responsable:
- Codex

Fase trabajada:
- Android / seguridad de red por ambiente.

Puntos completados:
- [x] Se cambio `main/res/xml/network_security_config.xml` a configuracion segura por defecto sin cleartext.
- [x] Se agrego `network_security_config.xml` para flavor `emulator` permitiendo HTTP.
- [x] Se agrego `network_security_config.xml` para flavor `lan` permitiendo HTTP en red privada.
- [x] Se agrego `network_security_config.xml` para flavor `production` bloqueando HTTP.
- [x] Se agrego validacion Gradle para impedir `BSLOTTERY_PRODUCTION_SERVER_URL` con `http://`.
- [x] Se actualizo `android/README_RELEASE.md`.
- [x] Se aumento `MaxMetaspaceSize` de Gradle a `1g` para builds con multiples flavors.

Archivos creados/modificados:
- `android/app/src/main/res/xml/network_security_config.xml`
- `android/app/src/emulator/res/xml/network_security_config.xml`
- `android/app/src/lan/res/xml/network_security_config.xml`
- `android/app/src/production/res/xml/network_security_config.xml`
- `android/app/build.gradle.kts`
- `android/gradle.properties`
- `android/README_RELEASE.md`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- HTTP queda permitido solo en `emulator` y `lan`, porque XAMPP/LAN privada puede no tener TLS.
- `production` exige HTTPS a nivel de configuracion de red Android y tambien a nivel de build Gradle.
- No se aceptan URLs HTTP para `productionRelease`; el build falla temprano con mensaje claro.

Riesgos detectados:
- Los warnings `crunchPngs/useProguard/wearAppUnbundled` vienen de AGP con Gradle 9 y apuntan a compatibilidad futura con Gradle 10; no son de codigo de la app.
- Persiste warning externo de Moshi codegen aunque el proyecto usa KSP.
- Falta configurar keystore real para generar APK production firmado.

Validacion ejecutada:
- `.\gradlew.bat :app:assembleEmulatorDebug :app:assembleLanDebug :app:assembleProductionRelease -PBSLOTTERY_LAN_SERVER_URL=http://192.168.1.50:8000 -PBSLOTTERY_PRODUCTION_SERVER_URL=https://bsloteria.example.com --warning-mode all`: correcto, `BUILD SUCCESSFUL`.
- `.\gradlew.bat :app:assembleProductionRelease -PBSLOTTERY_PRODUCTION_SERVER_URL=http://bsloteria.example.com`: falla correctamente con `BSLOTTERY_PRODUCTION_SERVER_URL debe usar HTTPS para production.`

Proximo paso recomendado:
- Configurar keystore real de produccion fuera del repositorio y generar `productionRelease` firmado, o continuar con pruebas funcionales Android contra servidor LAN real.

---

## 2026-05-19 â€” Android offline controlado por cupos

Responsable:
- Codex

Fase trabajada:
- Fase 6 â€” Android online/offline controlado.

Puntos completados:
- [x] Android intenta vender online primero y solo guarda offline ante caida de red/servidor.
- [x] Android bloquea venta offline si no existe sesion offline activa.
- [x] Android consume cupo local de tickets y monto antes de guardar una venta offline.
- [x] Android sincroniza tickets offline controlados contra `POST /api/offline/sync`.
- [x] Backend crea una asignacion de cupo en `offline_limit_allocations` al abrir sesion offline.
- [x] Backend valida cupo de tickets y monto durante sincronizacion offline.
- [x] Backend responde `accepted_uuids` y `failed` para marcar tickets locales como sincronizados o fallidos.

Puntos parciales:
- [~] Se salto la prueba manual en dispositivo fisico por instruccion del usuario.
- [~] `php artisan test` tiene 2 fallos existentes en `Tests\Feature\ExampleTest` por redireccion esperada a licencia/setup frente a redireccion actual a login; no esta relacionado con el cambio offline.

Archivos creados/modificados:
- `app/Http/Controllers/Api/OfflineController.php`
- `app/Services/Offline/OfflineSyncService.php`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/ApiService.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/dto/Dtos.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/local/entity/Entities.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/local/dao/OfflineSessionDao.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/local/AppDatabase.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/di/DatabaseModule.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/OfflineRepository.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/TicketRepository.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/SyncRepository.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/sync/SyncViewModel.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/sync/SyncScreen.kt`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- El cupo offline minimo se maneja como asignacion generica por sesion para tickets y monto total.
- La app no guarda ventas offline por errores 4xx de validacion; solo intenta cola offline ante red/servidor no disponible.
- Las ventas offline se sincronizan con UUID del cliente para mantener idempotencia.
- El modulo de sincronizacion Android muestra una accion explicita para activar sesion offline.

Riesgos detectados:
- Falta prueba real en dispositivo con red desconectada y reconexion.
- Falta UI avanzada para configurar ticket limit, amount limit y horas de vencimiento desde Android; por ahora usa valores por defecto.
- El endpoint legacy `/api/mobile/tickets/batch` se conserva para tickets antiguos sin `offlineSessionUuid`.

Validacion ejecutada:
- `php -l app\Http\Controllers\Api\OfflineController.php`: correcto.
- `php -l app\Services\Offline\OfflineSyncService.php`: correcto.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 171 rutas.
- `.\gradlew.bat :app:compileEmulatorDebugKotlin`: correcto.
- `.\gradlew.bat :app:assembleEmulatorDebug`: correcto.
- `php artisan test`: 71 pruebas pasan, 2 fallan en `Tests\Feature\ExampleTest` por redireccion de licencia/setup a login.

Proximo paso recomendado:
- Corregir o actualizar las 2 pruebas de redireccion inicial segun el flujo real de licencia/login, y despues hacer prueba manual Android: abrir sesion offline, desconectar red, vender dentro/fuera del cupo y sincronizar.

---

## 2026-05-20 - Bluetooth print Android, asiento nomina, reportes contables y correccion middleware

Responsable:
- Claude Sonnet 4.6

Fase trabajada:
- Fase 6: Android impresion Bluetooth.
- Fase 8: Estado de resultados y Nomina por periodo.
- Fase 9: Asiento contable formal al pagar nomina.
- Pruebas: correccion de 2 fallos en ExampleTest por orden de middleware.

Puntos completados:
- [x] Android imprime ticket Bluetooth despues de venta exitosa (boton en dialogo de exito).
- [x] Android reimprime ticket desde TicketDetailScreen (icono en TopAppBar + Snackbar de resultado).
- [x] ESC-POS 32 chars: buildTicketText() en SaleViewModel y buildDetailTicketText() en TicketDetailViewModel.
- [x] Pago de nomina genera asiento contable formal: PayrollService.pay() llama AccountingService.entryForPayroll() dentro de la transaccion.
- [x] Reporte Estado de resultados: combina ventas, premios, gastos y nomina; desglose por sucursal; PDF/Excel.
- [x] Reporte Nomina por periodo: tabla paginada de PayrollPeriod con frecuencia, empleados, bruto/deducciones/neto, estado y link a detalle.
- [x] Index de reportes actualizado: seccion Contabilidad (Estado de resultados, Diario contable) y Nomina (Nomina por periodo, Gestion de nomina).
- [x] Correccion de middleware: EnsureLicenseIsValid y EnsureInitialSetupIsCompleted movidos a middleware global (append) para ejecutarse antes que auth en Laravel 12.
- [x] shouldBypass() usa $request->is('path/*') en lugar de routeIs('route.*') porque el middleware global corre antes de la resolucion de rutas.
- [x] php artisan test: 73 pruebas pasan (632 assertions), 0 fallos.

Archivos creados/modificados:
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/sale/SaleViewModel.kt
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/sale/SaleScreen.kt
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketDetailViewModel.kt
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketDetailScreen.kt
- app/Services/Payroll/PayrollService.php
- app/Http/Controllers/Admin/ReportController.php
- routes/web.php
- resources/views/admin/reports/income-statement.blade.php (nuevo)
- resources/views/admin/reports/payroll-report.blade.php (nuevo)
- resources/views/admin/reports/index.blade.php
- bootstrap/app.php
- app/Http/Middleware/EnsureLicenseIsValid.php
- app/Http/Middleware/EnsureInitialSetupIsCompleted.php
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- El income statement usa datos operativos (tickets, prize_payments, cash_movements, payroll_periods) en lugar de journal_entries para mayor confiabilidad aunque el diario no este completo.
- El branchId del asiento de nomina se resuelve como cashSession?.branch_id ?? payer.branch_id para cubrir ambos casos.
- En Laravel 12, $middleware->web(append:[...]) ejecuta DESPUES del middleware de ruta auth; usar $middleware->append([...]) garantiza ejecucion previa.
- URL patterns ($request->is()) son obligatorios en middleware global; routeIs() falla porque la ruta aun no fue resuelta.

Riesgos detectados:
- El reporte de estado de resultados no incluye movimientos de diario contable de otras categorias; si se agregan nuevas fuentes de ingreso/gasto no habituales, hay que actualizar incomeStatement().
- El Bluetooth en Android depende de que haya un dispositivo emparejado y conectado; sin impresora, muestra "Sin impresora conectada" via Snackbar.

Validacion ejecutada:
- php artisan test: 73 passed (632 assertions), 0 fallos.
- php -l app/Services/Payroll/PayrollService.php: correcto.
- php -l app/Http/Controllers/Admin/ReportController.php: correcto.

Proximo paso recomendado:
- Pruebas unitarias de nomina (Fase 9): sueldo fijo, comision, avances, prestamos.
- Aplicar faltantes de caja automaticamente desde cash_incidents en PayrollService.
- Reportes pendientes Fase 8: Movimientos de caja, Gastos por sucursal, Entradas/salidas.

---

## 2026-05-20 - Pruebas unitarias nomina y correccion de riesgos

Responsable:
- Claude Sonnet 4.6

Fase trabajada:
- Fase 9: pruebas de nomina y correccion de bugs.

Puntos completados:
- [x] 24 pruebas de PayrollService: sueldo fijo, comision, avances (approved/pending), prestamos (installment, capped, paid_off), neto minimo 0, totales por periodo, approve/pay transitions, cash_movement, journal_entry, cash_shortage.
- [x] Riesgo corregido: automatizacion de faltante de caja en nomina. PayrollService.calculateDetail() ahora consulta CashIncident (type=CASH_SHORTAGE, status=RESOLVED, user_id=employee.user_id, created_at en el periodo) y lo aplica como cash_shortage en PayrollDetail.
- [x] Bug corregido: PayrollService.pay() usaba campos incorrectos en CashMovement (type='OUT', category='PAYROLL' — columnas inexistentes). Corregido a type='PAYROLL', direction='OUT', reference_type/reference_id segun schema real.
- [x] php artisan test: 97 pruebas pasan (686 assertions), 0 fallos.

Archivos creados/modificados:
- app/Services/Payroll/PayrollService.php
- tests/Feature/PayrollServiceTest.php (nuevo)
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- El faltante se deduce por created_at del incident dentro del periodo; esto es seguro porque los periodos no se solapan (unique constraint en company_id+period_start+period_end).
- Solo incidents RESOLVED se deducen; OPEN no se deducen hasta ser confirmados.
- Employees sin user_id saltan la deduccion de faltante.
- El setUp del test crea datos directamente con modelos (no via HTTP setup route) para evitar dependencias de sesion y ser mas rapido.

Riesgos corregidos:
- Bug de columnas en CashMovement: ya no falla silenciosamente ni viola NOT NULL constraint al pagar nomina con caja abierta.
- Automatizacion de faltante: ya no requiere intervencion manual para aplicar shortage al payroll.

Validacion ejecutada:
- php artisan test tests/Feature/PayrollServiceTest.php: 24 passed (54 assertions).
- php artisan test: 97 passed (686 assertions), 0 fallos.

Proximo paso recomendado:
- Reportes pendientes Fase 8: Movimientos de caja, Gastos por sucursal.
- Cierre formal Fase 9: marcar criterio de cierre completo.
- Fase 10: OPcache, colas/workers, HTTPS, backups.

---

## 2026-05-20 - Reportes operativos y de nomina Fase 8

Reportes implementados:

Operativos (caja):
- [x] Movimientos de caja (admin/reports/cash-movements): listado paginado con filtros por tipo, direccion y sucursal. Export PDF/Excel.
- [x] Gastos por sucursal (admin/reports/expenses-by-branch): salidas OUT agrupadas por sucursal, breakdown por tipo, excluye PAYROLL y PRIZE_PAYMENT. Export PDF/Excel.
- [x] Entradas y salidas (admin/reports/cash-in-out): resumen IN vs OUT por sucursal con balance neto. Export PDF/Excel.

Nomina:
- [x] Avances (admin/reports/payroll-advances): listado paginado de EmployeeAdvance con filtros de fecha y estado. Export PDF/Excel.
- [x] Prestamos (admin/reports/payroll-loans): listado de EmployeeLoan con barra de progreso de pago. Export PDF/Excel.
- [x] Comisiones (admin/reports/payroll-commissions): detalle de comisiones de PayrollDetail por periodo. Export PDF/Excel.

Bugs corregidos en incomeStatement():
- CashMovement: where('type','OUT') -> where('direction','OUT').
- CashMovement: whereNotIn('category',...) -> whereNotIn('type',...).
- Mismo fix aplicado en el map byBranch.

Rutas agregadas: 6 nuevas rutas GET bajo admin/reports/.
Vistas: 6 nuevas vistas en resources/views/admin/reports/.
Index actualizado con los nuevos enlaces en las tarjetas Caja y Nomina.

Pendiente Fase 8:
- Pagos a empleados (reporte).
- Descuentos (reporte).
- Faltantes descontados (reporte).
- Reportes contables: Ingresos vs gastos, Utilidad por sucursal, Utilidad por empresa, CxC, CxP, Flujo de efectivo, Diario contable.

---

## 2026-05-20 - Cierre total reportes Fase 8 (contables y nomina restantes)

Reportes implementados en esta sesion:

Nomina:
- [x] Pagos a empleados (admin/reports/payroll-payments): PayrollDetail de periodos PAID, neto por empleado, export PDF/Excel.
- [x] Descuentos (admin/reports/payroll-deductions): PayrollDetail donde total_deductions > 0, desglose por avance/prestamo/faltante/otros.
- [x] Faltantes descontados (admin/reports/payroll-shortages): PayrollDetail donde cash_shortage > 0.

Contables:
- [x] Ingresos vs gastos (admin/reports/income-vs-expenses): JournalEntryLine agrupado por cuenta INCOME/EXPENSE, credito vs debito.
- [x] Utilidad por sucursal (admin/reports/profit-by-branch): ventas - premios - gastos - nomina por sucursal, con totales y export.
- [x] Utilidad por empresa (admin/reports/profit-by-company): resumen consolidado con estado de resultados y margen neto.
- [x] Cuentas por cobrar (admin/reports/accounts-receivable): saldos de EmployeeLoan ACTIVE + EmployeeAdvance APPROVED pendientes.
- [x] Cuentas por pagar (admin/reports/accounts-payable): WinnerTicket sin paid_at (premios por cobrar).
- [x] Flujo de efectivo (admin/reports/cash-flow): CashSession agrupadas por dia con apertura, ventas, entradas, premios, salidas, gastos.
- [x] Diario contable: ya existia via AccountingController@journal; marcado como completo.

Imports agregados a ReportController: AccountingAccount, CashIncident, JournalEntry, JournalEntryLine.
Rutas: 10 nuevas rutas GET.
Vistas: 9 nuevas vistas en resources/views/admin/reports/.
Index de reportes actualizado con todos los enlaces.

Estado Fase 8: COMPLETA. Todos los reportes del tracking doc estan implementados.

Proximo paso:
- Cierre formal Fase 9.
- Fase 10: OPcache, colas/workers, HTTPS, backups automaticos.

---

## 2026-05-20 - Exportacion CSV en reportes y UI de ajuste de nomina

Responsable:
- Claude Sonnet 4.6

Fases trabajadas:
- Fase 8: exportacion CSV.
- Fase 9: UI de ajuste manual de bono y otras deducciones en detalle de nomina.

Puntos completados:
- [x] Exportacion CSV agregada a todos los reportes: `doExport()` en ReportController acepta `format='csv'` usando `Excel::download(..., \Maatwebsite\Excel\Excel::CSV)`. Reutiliza la clase `ReportExport` ya existente.
- [x] Boton CSV agregado en `partials/filters.blade.php` junto a los botones Excel y PDF, pasando `?export=csv` como parametro.
- [x] Ruta PATCH `payroll/{payrollPeriod}/details/{payrollDetail}` registrada en web.php con middleware `permission:payroll.manage` y nombre `admin.payroll.detail.update`.
- [x] Metodo `PayrollController::updateDetail()` implementado con validacion de campos `bonus` y `other_deductions`, recalculo con BCMath de `gross_pay`, `total_deductions` y `net_pay` (neto minimo 0), actualizacion del detalle y recalculo de los tres totales del periodo (`total_gross`, `total_deductions`, `total_net`). Bloquea si el periodo no esta en DRAFT.
- [x] Vista `payroll/show.blade.php` actualizada: boton de edicion (icono lapiz) por fila solo cuando el periodo es DRAFT y el usuario tiene `payroll.manage`; modal Bootstrap con inputs de bono y otras deducciones pre-poblados via atributos `data-*`; script `@push('scripts')` que conecta el boton al modal y establece el action PATCH correcto por fila. Se agrego columna de faltantes (`cash_shortage`) que estaba faltando en la tabla.
- [x] Importacion de `PayrollDetail` agregada a `PayrollController`.

Archivos creados/modificados:
- `app/Http/Controllers/Admin/ReportController.php` (caso csv en doExport)
- `app/Http/Controllers/Admin/PayrollController.php` (import PayrollDetail + metodo updateDetail)
- `resources/views/admin/reports/partials/filters.blade.php` (boton CSV)
- `resources/views/admin/payroll/show.blade.php` (columna faltantes, boton edicion, modal ajuste, @push scripts)
- `routes/web.php` (ruta PATCH detail update)

Decisiones tomadas:
- El CSV reutiliza `ReportExport` (misma clase que Excel) porque Maatwebsite Excel soporta CSV nativo; no se creo nueva clase.
- `updateDetail()` recalcula los totales del periodo cargando todos los detalles desde BD despues de actualizar; es mas simple y correcto que ajustar con delta.
- El modal usa `data-*` attributes y un script minimo vanilla JS; no se introdujo dependencia nueva.
- La columna `cash_shortage` se agrego a la tabla porque ya existia en el modelo pero no se mostraba en la vista.

Riesgos detectados:
- El CSV descargado puede tener problemas de encoding en Excel para Windows si el archivo no incluye BOM; aceptable por ahora ya que el export es operativo, no contable formal.
- Si el periodo tiene muchos detalles, el recalculo de totales carga todos en memoria; aceptable para el volumen esperado de empleados por periodo.

Validacion ejecutada:
- `php -l app/Http/Controllers/Admin/PayrollController.php`: correcto.
- `php -l app/Http/Controllers/Admin/ReportController.php`: correcto.

Proximo paso recomendado:
- Pendiente menor: pantalla de perfil para cambio voluntario de password.
- Pendiente VPS: ejecutar register-tasks-admin.ps1 como Administrador, configurar .env de produccion, reemplazar certificado autofirmado con Let's Encrypt cuando haya dominio.
- Limpieza de tracking doc: ~150 items de diseno de Fases 1-5 aun marcados `[ ]` aunque ya estan implementados.

---

## 2026-05-20 - Reset de credenciales y cierre Fase 10

Credenciales reseteadas:
- brea / Password1234! (COMPANY_OWNER)
- cajero-central / Password1234! (BRANCH_CASHIER_PAYER)
- confirmador-resultados / Password1234! (RESULT_CONFIRM_ONLY)

Fase 10 completada:
- OPcache habilitado en C:\xampp\php\php.ini: zend_extension=opcache, opcache.enable=1, memory_consumption=256MB, interned_strings_buffer=16MB, max_accelerated_files=20000. Requiere reiniciar Apache en XAMPP.
- Colas: QUEUE_CONNECTION=database ya estaba en .env; tabla jobs ya migrada. Para dev: php artisan queue:work.
- Backups: spatie/laravel-backup 9.x instalado. Backup ZIP del directorio database/ (incluye database.sqlite) sin sqlite3 CLI. Diario 02:00, cleanup 02:30, prune-failed semanal. Retencion: 7d completos / 30d diarios / 12 semanas / 12 meses / 3 anos. Almacenado en storage/app/backups/BSLottery/. Probado OK: 42 archivos, 109KB.
- Scheduler actualizado: backup:run --only-files, backup:clean, queue:prune-failed.
- Notificaciones de backup por 'mail' (MAIL_MAILER=log en dev = solo logs).

Estado del sistema: TODAS LAS FASES 1-10 COMPLETADAS.

Pendiente en VPS Windows (produccion):
- HTTPS: habilitar ssl_module en Apache de XAMPP + certificado.
- Scheduler: Windows Task Scheduler ejecutando php artisan schedule:run cada 1 minuto.
- Queue worker: NSSM como servicio Windows o Task Scheduler para queue:work.
- Copiar proyecto al VPS y ejecutar comandos de deploy (ver checklist en tracking doc).

---

## 2026-05-20 - Acceso voluntario a cambio de contrasena

Responsable:
- Codex

Fase trabajada:
- Cierre de pendiente menor posterior a Fase 10.

Puntos completados:
- [x] Se revisaron `01_PROMPT_MAESTRO_BSLOTTERY.md` y `02_GUIA_TODO_CONTROL_BSLOTTERY.md` antes de avanzar.
- [x] Se identifico como siguiente pendiente menor la pantalla/acceso de perfil para cambio voluntario de password.
- [x] Se agrego acceso directo "Clave" en el header para usuarios autenticados.
- [x] Se ajusto la vista de cambio de contrasena para diferenciar cambio obligatorio de clave temporal vs cambio voluntario.
- [x] Se agrego prueba funcional para confirmar que un usuario autenticado puede acceder al cambio voluntario desde el layout.

Archivos creados/modificados:
- `resources/views/layouts/app.blade.php`
- `resources/views/auth/change-password.blade.php`
- `tests/Feature/PasswordChangeFlowTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- No se creo un modulo de perfil nuevo porque el sistema ya tenia ruta, controlador, request, vista y auditoria para cambio de contrasena.
- Se agrego un enlace compacto en el header para no ampliar el menu lateral ni crear permisos nuevos.
- La vista conserva el cierre de sesion cuando el usuario esta obligado a cambiar contrasena; para cambio voluntario muestra retorno al dashboard.

Riesgos detectados:
- El layout aun contiene textos antiguos con encoding mojibake en varias secciones; este bloque solo corrigio la vista tocada y el nuevo acceso.
- Sigue pendiente limpieza general del tracking doc: hay items historicos `[ ]` que ya estan implementados o que corresponden a despliegue real en VPS.

Validacion ejecutada:
- `php -l app\Http\Controllers\AuthController.php`: correcto.
- `php artisan test tests\Feature\PasswordChangeFlowTest.php`: 4 passed, 18 assertions.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 189 rutas.
- `php artisan test`: 98 passed, 692 assertions.

Proximo paso recomendado:
- Limpieza tecnica del tracking doc: separar pendientes reales de produccion/VPS de items historicos ya implementados, sin borrar historial.

---

## 2026-05-20 - Reporte de tickets reimpresos

Responsable:
- Codex

Fase trabajada:
- Fase 8: Reportes operativos.

Puntos completados:
- [x] Se revisaron `01_PROMPT_MAESTRO_BSLOTTERY.md` y `02_GUIA_TODO_CONTROL_BSLOTTERY.md` antes de avanzar.
- [x] Se completo el reporte operativo de tickets reimpresos.
- [x] Se agrego ruta protegida por `permission:reports.view`.
- [x] Se agrego vista paginada con filtros existentes de reportes.
- [x] Se agrego exportacion mediante el flujo comun de `ReportController::doExport()`.
- [x] Se agrego prueba funcional del reporte.

Archivos creados/modificados:
- `app/Http/Controllers/Admin/ReportController.php`
- `resources/views/admin/reports/index.blade.php`
- `resources/views/admin/reports/reprinted.blade.php`
- `routes/web.php`
- `tests/Feature/OperationalReportsTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- La fuente auditable del reporte es `print_jobs.type = REPRINT`, no solo `tickets.print_count`, porque `PrintJob` conserva estado, intentos, fecha, impresora y dispositivo.
- El filtro principal usa `print_jobs.created_at` para reportar cuando se solicito la reimpresion. `printed_at` queda visible como dato operativo.
- El reporte conserva los filtros globales por empresa, sucursal, cajero, loteria, sorteo y estado.

Riesgos detectados:
- Sigue pendiente la eliminacion completa de uso de `float`/casts monetarios en codigo PHP; la base usa DECIMAL, pero la capa de aplicacion aun tiene conversiones para formato y calculos en varios servicios.
- API REST administrativa de empresas/sucursales/usuarios/roles sigue parcial; no marcar Fase 1 API como completa.
- Android tiene pantallas operativas principales, pero Caja/Cierre en Android siguen pendientes.
- Los reportes grandes aun no estan desacoplados a cola para alto volumen.

Validacion ejecutada:
- `php -l app\Http\Controllers\Admin\ReportController.php`: correcto.
- `php -l tests\Feature\OperationalReportsTest.php`: correcto.
- `php artisan test tests\Feature\OperationalReportsTest.php`: 5 passed, 24 assertions.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 190 rutas.
- `php artisan test`: 99 passed, 698 assertions.

Proximo paso recomendado:
- Continuar con limpieza tecnica del tracking doc y luego atacar una deuda real de alto impacto: eliminar conversiones monetarias inseguras en servicios financieros o completar Caja/Cierre Android, segun prioridad operativa.

---

## 2026-05-20 - Endurecimiento de venta real en Android

Responsable:
- Codex

Fase trabajada:
- Fase 6: Android online/offline controlado.

Puntos completados:
- [x] Se reviso la app Android contra el flujo real de venta.
- [x] Android ahora genera y conserva un `device_uuid` estable en DataStore.
- [x] Android envia `X-Device-UUID` en cada request autenticado.
- [x] Login movil registra el dispositivo Android como `PENDING` si no existe.
- [x] Las rutas `/api/mobile/*` ahora exigen `device.authorized`.
- [x] Un dispositivo Android `PENDING` o `BLOCKED` no puede sincronizar ni vender.
- [x] La venta movil online queda conectada a caja abierta cuando la sucursal tiene control de caja.
- [x] La venta movil online genera `CashMovement` y `PrintJob`, y conserva `device_id` y `cash_session_id`.
- [x] La app guarda localmente en Room el ticket vendido online para que aparezca en historial.
- [x] La entrada movil de jugadas ya no acepta longitudes ambiguas: solo 2, 4 o 6 digitos completos; Super Pale usa 4 digitos.
- [x] Se corrigio el contrato de sincronizacion movil para usar `scheduled_time`, `close_time` y `possible_prize` reales del backend.

Puntos parciales:
- [~] Caja y cierre nativos en Android siguen pendientes como pantallas propias; por ahora la venta movil requiere que la caja del usuario ya este abierta en backend/web.

Archivos creados/modificados:
- `app/Http/Controllers/Api/MobileController.php`
- `app/Http/Middleware/EnsureDeviceIsAuthorized.php`
- `app/Services/Sales/TicketSaleService.php`
- `routes/api.php`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/dto/Dtos.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/AuthInterceptor.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/AuthRepository.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/TicketRepository.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/login/LoginScreen.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/sale/SaleViewModel.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/sale/SaleScreen.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/util/SessionStore.kt`
- `tests/Feature/MobileSaleApiTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- El UUID del dispositivo se persiste localmente y no se recalcula por request, para permitir autorizacion estable desde el panel web.
- La API movil mantiene login publico, pero bloquea operacion real hasta que el dispositivo este autorizado.
- La venta movil online usa los mismos artefactos financieros minimos que la venta web: ticket, detalle, consumo de limite, movimiento de caja y trabajo de impresion.
- La venta movil requiere caja abierta si la sucursal tiene `cash_control_enabled`; vender sin caja abierta no es aceptable para operacion real.

Riesgos detectados:
- Falta construir UI Android para abrir/cerrar caja; esto limita operacion 100% movil en sucursales que no usen la web para abrir caja.
- El flujo offline sincronizado sigue necesitando una definicion contable/caja mas fina cuando la caja se cierra antes de sincronizar tickets offline.
- Sigue pendiente la deuda global de conversiones monetarias `float` en varias zonas PHP.
- PHPUnit emite warnings por metadata en doc-comments de pruebas antiguas; no rompe hoy, pero debe migrarse antes de PHPUnit 12.

Validacion ejecutada:
- `php -l app\Http\Controllers\Api\MobileController.php`: correcto.
- `php -l app\Http\Middleware\EnsureDeviceIsAuthorized.php`: correcto.
- `php -l app\Services\Sales\TicketSaleService.php`: correcto.
- `php -l tests\Feature\MobileSaleApiTest.php`: correcto.
- `php artisan test tests\Feature\MobileSaleApiTest.php`: 2 passed, 11 assertions.
- `android/gradlew.bat :app:compileEmulatorDebugKotlin`: BUILD SUCCESSFUL.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 190 rutas.
- `php artisan test`: 101 passed, 709 assertions.

Proximo paso recomendado:
- Implementar Caja y Cierre en Android para que una sucursal pueda operar desde el movil sin depender del panel web para abrir o cerrar caja.

---

## 2026-05-20 - Android sorteos abiertos, carga de tickets y Super Pale

Responsable:
- Codex

Fase trabajada:
- Fase 6: Android online/offline controlado.

Puntos completados:
- [x] Se reviso por que la app movil no mostraba sorteos abiertos.
- [x] La sincronizacion movil ahora entrega siempre los sorteos operativos abiertos actuales, aunque el telefono envie `since`.
- [x] El dashboard Android dispara sincronizacion de catalogo al entrar para evitar operar con catalogo local viejo.
- [x] La API movil ahora devuelve tipos de jugada activos globales y de la empresa, incluyendo `SUPER_PALE`.
- [x] Se agrego endpoint movil para cargar tickets por numero, UUID o texto QR `ticket:{uuid}`.
- [x] La pantalla Android de tickets ahora incluye bloque "Cargar ticket" y navega al detalle al encontrarlo.
- [x] La respuesta de tickets moviles incluye numero de ticket, sorteo, loteria, tipo de jugada y premio posible guardado.
- [x] Se agregaron pruebas para sincronizacion de sorteos abiertos, catalogo Super Pale y busqueda de ticket por numero/QR.

Puntos parciales:
- [~] La carga por QR actualmente acepta el texto/token del QR; queda pendiente escaneo con camara.
- [~] Super Pale aparece en Android cuando existe en catalogo; si el negocio requiere seleccionar dos loterias distintas para Super Pale, falta disenar esa UX especifica.
- [~] Caja y cierre nativos en Android siguen pendientes.

Archivos creados/modificados:
- `app/Http/Controllers/Api/MobileController.php`
- `routes/api.php`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/ApiService.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/dto/Dtos.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/TicketRepository.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/SyncRepository.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/dashboard/DashboardViewModel.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketsViewModel.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketsScreen.kt`
- `tests/Feature/MobileSaleApiTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Los sorteos abiertos son catalogo operativo critico y no deben depender de sincronizacion incremental; por eso se devuelven completos en cada sync movil.
- Los tipos de jugada tambien se sincronizan completos para no ocultar `SUPER_PALE` por `updated_at` o por reglas globales con `company_id` nulo.
- La busqueda de ticket se limita por empresa y sucursal del usuario movil para evitar lectura cruzada entre sucursales.
- La app acepta `ticket:{uuid}` como token QR deterministico, alineado con el formato de impresion del sistema.

Riesgos detectados:
- Falta implementar escaneo real con camara para QR; hoy el usuario puede escribir o pegar el token/numero.
- Si el telefono conserva una base local vieja con sorteos cerrados, la auto-sincronizacion al dashboard corrige el catalogo, pero debe probarse en dispositivo real con datos persistidos.
- La experiencia de Super Pale movil puede necesitar ajustes si se confirma una regla de negocio de dos loterias por jugada.
- PHPUnit sigue emitiendo warnings por metadata en doc-comments de pruebas antiguas; no bloquea Laravel hoy, pero debe corregirse antes de PHPUnit 12.

Validacion ejecutada:
- `php -l app\Http\Controllers\Api\MobileController.php`: correcto.
- `php -l routes\api.php`: correcto.
- `php -l tests\Feature\MobileSaleApiTest.php`: correcto.
- `php artisan test tests\Feature\MobileSaleApiTest.php`: 4 passed, 21 assertions.
- `php artisan test`: 103 passed, 719 assertions.
- `vendor\bin\pint --dirty`: correcto.
- `php artisan route:list --except-vendor`: correcto, 191 rutas.
- `android/gradlew.bat :app:compileEmulatorDebugKotlin`: BUILD SUCCESSFUL.

Proximo paso recomendado:
- Implementar escaneo QR con camara en Android y luego completar Caja/Cierre Android para operacion movil independiente.

---

## 2026-05-20 - Caja y cierre nativos en Android

Responsable:
- Claude

Fase trabajada:
- Fase 6: Android online/offline controlado.

Puntos completados:
- [x] Se agregaron endpoints moviles para caja: `GET /api/mobile/cash/status`, `POST /api/mobile/cash/open`, `POST /api/mobile/cash/close`.
- [x] `MobileController` ahora reutiliza `CashService` directamente para abrir y cerrar caja, registrando auditoria.
- [x] La apertura movil respeta `cash_control_enabled` de la sucursal y bloquea apertura duplicada del mismo usuario.
- [x] El cierre movil reusa la logica de conciliacion existente: calcula faltante/sobrante, persiste reconciliacion y crea incidente si aplica.
- [x] Android: nuevo `CashRepository`, DTOs Moshi (`CashStatusResponse`, `CashSessionDto`, requests/responses) y endpoints en `ApiService`.
- [x] Android: nuevas pantallas `CashScreen` + `CashViewModel` con estados: sin caja, caja abierta (totales operativos) y caja cerrada (resumen).
- [x] Android: tarjeta "Caja" agregada al `DashboardScreen` con ruta `Screen.Cash` registrada en `NavGraph`/`MainActivity`.
- [x] Pruebas Feature nuevas: status sin caja, ciclo abrir+cerrar, cierre sin caja abierta, status cuando la sucursal no usa control de caja.

Puntos parciales:
- [~] Cierre movil acepta efectivo contado en un solo campo; conteo por denominaciones (billetes/monedas) sigue disponible solo desde la web.
- [~] Si la caja queda en estado `CLOSED` para el dia, la pantalla movil pide refrescar manualmente para volver a habilitar apertura.

Archivos creados/modificados:
- `app/Http/Controllers/Api/MobileController.php`
- `routes/api.php`
- `tests/Feature/MobileSaleApiTest.php`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/dto/Dtos.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/ApiService.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/CashRepository.kt` (nuevo)
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/cash/CashViewModel.kt` (nuevo)
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/cash/CashScreen.kt` (nuevo)
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/navigation/NavGraph.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/MainActivity.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/dashboard/DashboardScreen.kt`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Se reusa `CashService` directamente desde el controlador movil en lugar de duplicar logica; la API movil queda alineada con el comportamiento de la web (mismas validaciones, mismos artefactos contables).
- La sucursal y el usuario para abrir/cerrar caja se toman del token (`user->branch`), no se envian por request, para evitar abrir caja en una sucursal ajena.
- El cierre movil acepta `counted_cash` opcional; si llega vacio, el ViewModel lo completa con `expected_cash` antes de enviar para evitar errores 422.

Riesgos detectados:
- Sigue pendiente el escaneo QR con camara para la pantalla "Cargar ticket".
- El cierre movil no permite contar denominaciones; cajas con politica estricta de arqueo siguen necesitando la web para arqueos detallados.
- PHPUnit sigue emitiendo warnings por metadata en doc-comments de pruebas antiguas; no bloquea Laravel hoy.

Validacion ejecutada:
- `php -l app\Http\Controllers\Api\MobileController.php`: correcto.
- `php -l routes\api.php`: correcto.
- `php -l tests\Feature\MobileSaleApiTest.php`: correcto.
- `php artisan test --filter=MobileSaleApiTest`: 8 passed, 48 assertions.
- `php artisan test`: 107 passed, 746 assertions.
- `vendor\bin\pint --dirty`: passed.
- `android/gradlew.bat :app:compileEmulatorDebugKotlin`: BUILD SUCCESSFUL.

Proximo paso recomendado:
- Implementar escaneo QR con camara (`CameraX`/`ML Kit`) en la pantalla "Cargar ticket" para completar la operacion movil.

---

## 2026-05-20 - Escaneo QR con camara en Android

Responsable:
- Claude

Fase trabajada:
- Fase 6: Android online/offline controlado.

Puntos completados:
- [x] Se agregaron dependencias CameraX 1.3.4 (`core`, `camera2`, `lifecycle`, `view`) y ML Kit `barcode-scanning` 17.3.0 en `libs.versions.toml` y `app/build.gradle.kts`.
- [x] Se agrego `accompanist-permissions` 0.34.0 para manejar permiso de camara en Compose.
- [x] Se anadio `uses-permission CAMERA` y `uses-feature` opcional para autofocus en `AndroidManifest.xml`.
- [x] Nuevo `QrCodeAnalyzer` (`scanner/`) que usa ML Kit BarcodeScanner para detectar QR/Code-128/EAN-13 con flag idempotente (un solo hit por sesion).
- [x] Nueva pantalla `QrScannerScreen` con `CameraX PreviewView` + `ImageAnalysis` enlazada al ciclo de vida, marco guia y overlay informativo.
- [x] Manejo de permiso runtime con `rememberPermissionState`: pide permiso al entrar, muestra pantalla de fallback si el usuario lo niega.
- [x] Se anadio ruta `Screen.ScanQr` (`scan_qr`) al `NavGraph` y a `MainActivity` con paso de resultado via `savedStateHandle` hacia el back stack de Tickets.
- [x] `TicketsScreen` ahora recibe `onScanQr`, lee `scannedToken` de `savedStateHandle` y al recibir un valor lo carga automaticamente en el buscador y dispara `lookup`.
- [x] Se anadio boton de icono "Escanear QR" en la tarjeta "Cargar ticket" junto al boton de busqueda existente.

Puntos parciales:
- [~] El analyzer corre en un `Executors.newSingleThreadExecutor` propio; en dispositivos muy debiles puede mostrar latencia al detectar QR pequenos. Para esos casos conviene aumentar la resolucion o usar luz adicional.

Archivos creados/modificados:
- `android/gradle/libs.versions.toml`
- `android/app/build.gradle.kts`
- `android/app/src/main/AndroidManifest.xml`
- `android/app/src/main/java/dev/bsolutions/bsloteria/scanner/QrCodeAnalyzer.kt` (nuevo)
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/scanner/QrScannerScreen.kt` (nuevo)
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/navigation/NavGraph.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/MainActivity.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketsScreen.kt`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Se uso ML Kit `barcode-scanning` (modelo on-device empaquetado en la APK) en vez de la variante Google Play Services para que el escaneo funcione sin depender de servicios externos; esto encaja con el modo offline.
- Se uso `accompanist-permissions` para no reescribir el flujo de permisos a mano y mantener consistencia con el estilo Compose.
- El resultado del scanner se pasa via `savedStateHandle` del `NavBackStackEntry` previo: simple, sin necesidad de un ViewModel compartido entre pantallas.
- El analyzer ignora hits posteriores al primero para evitar disparar el lookup multiples veces si la camara sigue detectando el mismo QR durante el `popBackStack`.
- Se acepta cualquier formato detectado (`rawValue`) y se delega a `MobileController::lookupTicket` la normalizacion (`ticket:{uuid}` o numero), que ya estaba implementada.

Riesgos detectados:
- ML Kit barcode incrementa el tamano del APK (~3-4 MB) por modelo on-device.
- En dispositivos sin autofocus la deteccion puede fallar; el feature de autofocus se declara `required="false"` para no excluir dispositivos.
- El permiso de camara denegado permanentemente requiere que el usuario lo habilite desde Ajustes; la pantalla informa pero no abre Ajustes automaticamente.

Validacion ejecutada:
- `android/gradlew.bat :app:compileEmulatorDebugKotlin`: BUILD SUCCESSFUL.
- `android/gradlew.bat :app:assembleEmulatorDebug`: BUILD SUCCESSFUL (APK debug generado).

Proximo paso recomendado:
- Probar el escaneo en dispositivo real con un ticket impreso con QR `ticket:{uuid}` y validar latencia. Si se requiere, agregar conteo por denominaciones al cierre movil de caja (faltante actual de Fase 6).

---

## 2026-05-20 - Cierre de caja Android por denominaciones

Responsable:
- Claude

Fase trabajada:
- Fase 6: Android online/offline controlado.

Puntos completados:
- [x] Nuevo `Denominations.kt` (`domain/model/`) con la lista alineada al backend (`bill_2000`..`coin_1`), helpers de total y mapa vacio.
- [x] `CashUiState` extendido con `closeMode: CashCloseMode` (`AMOUNT` o `DENOMINATIONS`) y `denominations: Map<String, Int>`.
- [x] `CashViewModel` con acciones `setCloseMode`, `setDenominationQty`, `incrementDenomination`, `clearDenominations` y total derivado.
- [x] `closeCash` ahora envia `denominations` cuando el modo es DENOMINATIONS (y omite `counted_cash`), o `counted_cash` cuando el modo es AMOUNT. Tras exito se limpian el monto y las cantidades.
- [x] `CashScreen`: nuevo selector tipo chips ("Monto total" / "Por denominaciones"), tabla con +/-, input numerico, subtotal por fila y bloque de resumen con total contado, esperado y diferencia (cuadrado/sobrante/faltante).
- [x] Prueba feature `test_mobile_cash_close_with_denominations_computes_counted_cash` que valida que `2 x 1000 + 5 x 100 = 2500.00` y que se calcula sobrante `500.00`.

Puntos parciales:
- (ninguno)

Archivos creados/modificados:
- `android/app/src/main/java/dev/bsolutions/bsloteria/domain/model/Denominations.kt` (nuevo)
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/cash/CashViewModel.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/cash/CashScreen.kt`
- `tests/Feature/MobileSaleApiTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Se reusa el contrato actual del backend: el backend ya tenia `denominationMap()` y `resolveCountedCash` que prioriza denominaciones cuando hay al menos una pieza > 0. La app simplemente activa ese camino enviando el mapa.
- Se omite `counted_cash` cuando se envia el mapa de denominaciones para evitar inconsistencias entre el total contado por el cajero y el calculado por el backend.
- El total se calcula localmente con `BigDecimal` en `Denominations.total()` para que el usuario vea el cuadre/faltante/sobrante en vivo antes de enviar.
- Se filtran solo las denominaciones con cantidad > 0 al enviar; el repo ya tenia este filtro pero lo reforzamos en el ViewModel para evitar request con 10 keys en cero.

Riesgos detectados:
- Si el usuario alterna entre modos (AMOUNT <-> DENOMINATIONS) durante un cierre, el otro campo conserva su valor en memoria. Esto es intencional para no perder el conteo, pero el cajero debe revisar antes de pulsar CERRAR CAJA.
- La pantalla muestra 10 denominaciones; en pantallas pequenas conviene desplazarse (la pantalla ya usa `verticalScroll`).

Validacion ejecutada:
- `php -l tests\Feature\MobileSaleApiTest.php`: correcto.
- `php artisan test --filter=MobileSaleApiTest`: 9 passed, 55 assertions.
- `php artisan test`: 108 passed, 753 assertions.
- `vendor\bin\pint --dirty`: passed.
- `android/gradlew.bat :app:compileEmulatorDebugKotlin`: BUILD SUCCESSFUL.

Proximo paso recomendado:
- Probar todo el ciclo movil real en dispositivo: login -> abrir caja -> venta -> cargar ticket por QR -> pagar premio (si aplica) -> cerrar caja con conteo por denominaciones. Con esto la Fase 6 queda 100% cerrada para operacion movil independiente.

---

## 2026-05-20 - Pago de premios desde Android (cierre del ciclo movil)

Responsable:
- Claude

Fase trabajada:
- Fase 6: Android online/offline controlado.

Puntos completados:
- [x] Nuevos endpoints moviles: `GET /api/mobile/tickets/{uuid}/winners` (lista winners con estado RELEASED/PAID/HELD y total liberado) y `POST /api/mobile/tickets/{uuid}/pay-prize` (paga todos los winners RELEASED del ticket).
- [x] `MobileController` ahora inyecta `PrizePaymentService` y reusa `payReleasedWinnersForTicket`, conservando todas las validaciones existentes (caja abierta obligatoria, suficiencia de efectivo, asiento contable, movimiento PRIZE_PAYMENT, status PAID).
- [x] Cuando la sucursal tiene `cash_control_enabled`, el endpoint exige caja abierta del cajero antes de pagar.
- [x] Android: DTOs `WinnerDto`, `TicketWinnersResponse`, `TicketSummaryDto`, `PrizePaymentEntry`, `PayPrizeResponse` en `Dtos.kt`.
- [x] Nuevo `PrizeRepository` con `getWinners(uuid)` y `payPrize(uuid)`, parseo de `ApiError` y manejo unificado de excepciones.
- [x] `TicketDetailViewModel` carga automaticamente los winners al abrir el detalle (excepto si el ticket esta pendiente de sync), expone `payPrize()` y refresca tras pago.
- [x] `TicketDetailScreen`: nueva tarjeta "Premios" con lista de winners (numero, posicion matched, monto y chip de estado por color), total liberado y boton "PAGAR PREMIO" + dialogo de confirmacion con monto y mensaje sobre asiento contable.
- [x] Snackbar de exito tras pago, refresh manual con icono y manejo de errores con surface error-container.
- [x] Tests Feature nuevos: `test_mobile_can_pay_released_prize_for_ticket` (creacion full → pago → verificacion PrizePayment + CashMovement + winner PAID + segunda llamada falla) y `test_mobile_pay_prize_requires_open_cash_session`.

Puntos parciales:
- (ninguno)

Archivos creados/modificados:
- `app/Http/Controllers/Api/MobileController.php`
- `routes/api.php`
- `tests/Feature/MobileSaleApiTest.php`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/dto/Dtos.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/ApiService.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/PrizeRepository.kt` (nuevo)
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketDetailViewModel.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketDetailScreen.kt`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Se reusa `PrizePaymentService::payReleasedWinnersForTicket` en lugar de duplicar la logica de transaccion + caja + asiento. Esto garantiza paridad de comportamiento entre web y movil (mismas auditorias, mismos asientos contables, mismo bloqueo de doble pago).
- Se decidio pagar TODOS los winners liberados de un ticket en una sola operacion (con confirmacion previa) en lugar de uno a uno. La transaccion DB es atomica: si falla cualquier pago, ninguno persiste.
- El endpoint solo paga `RELEASED`, nunca `HELD` (premios grandes pendientes de autorizacion). Esto preserva la decision 005 del prompt maestro (premios grandes requieren autorizacion).
- La pantalla de detalle no consulta winners si el ticket esta sync pending (offline), porque el server aun no lo conoce; en su lugar muestra la seccion una vez se sincronice.

Riesgos detectados:
- Si el cajero pulsa "PAGAR" justo cuando se esta acabando el efectivo de caja, el backend lanza error y el winner queda en RELEASED. El test confirma este camino pero conviene monitorear en operacion real.
- El UI hoy no permite pagar por partes (parcial). Si surge ese caso de negocio, se requeriria una nueva variante de endpoint y de payReleasedWinnersForTicket.

Validacion ejecutada:
- `php -l app\Http\Controllers\Api\MobileController.php`: correcto.
- `php -l routes\api.php`: correcto.
- `php -l tests\Feature\MobileSaleApiTest.php`: correcto.
- `php artisan test --filter=MobileSaleApiTest`: 11 passed, 72 assertions.
- `php artisan test`: 110 passed, 770 assertions.
- `vendor\bin\pint --dirty`: passed.
- `android/gradlew.bat :app:compileEmulatorDebugKotlin`: BUILD SUCCESSFUL.

Ciclo movil completo (Fase 6 100% cerrada):
- Login -> registrar dispositivo (autorizar PENDING desde web) -> Caja: abrir -> Venta rapida (quiniela/pale/tripleta/super pale) -> Historial -> Cargar ticket por QR (camara) o numero -> Detalle: ver winners, Pagar premio si esta RELEASED -> Caja: cerrar con monto total o por denominaciones. Toda la operacion movil ahora es viable sin tocar el panel web (exceptuando autorizacion inicial del dispositivo y resultados/declaracion de ganadores que son administrativos).

Proximo paso recomendado:
- Probar el ciclo completo en dispositivo Android real (login -> caja -> venta -> escaneo QR -> pago premio -> cierre denominaciones) y compilar APK release firmado.

---

## 2026-05-20 - Sincronizacion Android-Sistema y cache offline de caja

Responsable:
- Claude

Fase trabajada:
- Fase 6: Android online/offline controlado.

Puntos completados:
- [x] `GET /api/mobile/cash/status` ahora retorna los ultimos 30 movimientos de la caja abierta del usuario (orden desc por created_at) y `server_time` para indicar "ultima actualizacion".
- [x] Si el cajero abrio caja desde el panel web y luego se pasa al telefono, la app ve la caja, sus totales y todos los movimientos hechos en el sistema.
- [x] `CashRepository` ahora persiste en DataStore propio (`cash_cache`) la ultima respuesta exitosa de status (JSON + timestamp). Si la siguiente llamada falla por IOException, retorna el cache con `fromCache = true` y `cachedAtMillis`.
- [x] `CashScreen` muestra banner "Sin conexion - mostrando datos guardados" cuando se sirve del cache, con la hora de la ultima actualizacion. Cuando llega del servidor, muestra "Sincronizado a las HH:mm".
- [x] Nueva tarjeta "Movimientos recientes" en `CashScreen` con lista IN/OUT, monto signado, tipo legible (Venta/Cancelacion/Pago de premio/Entrada/Salida/Gasto/Pago nomina), descripcion y hora.
- [x] Auto-refresh en `CashScreen` cuando vuelve a primer plano via `Lifecycle.Event.ON_START` + `DisposableEffect`.
- [x] Nueva pantalla `StartupSyncScreen` que aparece tras login (y al abrir la app si ya estaba logueado) antes del Dashboard. Sincroniza en paralelo: catalogo, estado de caja del usuario, tickets pendientes. Muestra cada paso con icono de estado (running/OK/offline/error) y detalle ("Caja abierta - fondo RD$ X, esperado RD$ Y").
- [x] Si la sincronizacion no tiene errores, auto-continua al Dashboard tras 500ms; si hay errores, muestra "Continuar de todos modos".
- [x] Prueba feature `test_mobile_cash_status_returns_session_and_recent_movements_opened_from_web` que valida: caja abierta desde el sistema + 2 movimientos (SALE 1200 + CASH_IN 500) -> la app ve la caja con expected_cash 6700 y los 2 movimientos via API.

Puntos parciales:
- (ninguno)

Archivos creados/modificados:
- `app/Http/Controllers/Api/MobileController.php`
- `tests/Feature/MobileSaleApiTest.php`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/dto/Dtos.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/CashRepository.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/cash/CashViewModel.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/cash/CashScreen.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/startup/StartupSyncViewModel.kt` (nuevo)
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/startup/StartupSyncScreen.kt` (nuevo)
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/navigation/NavGraph.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/MainActivity.kt`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- El estado de la caja es la fuente de verdad del servidor (los totales viven en la fila `cash_sessions` y se actualizan por cada movimiento). El movil no calcula totales aparte: simplemente refleja lo que el backend retorna. Esto evita divergencias entre web y movil.
- El cache offline es READ-ONLY: solo se usa para mostrar la ultima vista del estado. Las acciones de abrir/cerrar caja siguen requiriendo conexion (el banner lo indica).
- La pantalla de sincronizacion inicial corre las 3 tareas en paralelo con `async` para minimizar latencia. Cada una reporta su estado individual: si solo falla una (ej. tickets offline), las otras siguen mostrando OK.
- Al volver el teclado de venta o de cualquier otra pantalla a Caja, `Lifecycle.Event.ON_START` dispara `refresh()` automaticamente, asegurando que el cajero ve totales actualizados sin tener que tocar el icono de refresh.

Riesgos detectados:
- El cache offline puede mostrar datos viejos si el usuario abrio la app, perdio conexion y mantuvo la pantalla abierta horas; el banner con timestamp es la mitigacion para que el cajero sepa cuando fue la ultima sync real.
- El auto-refresh ON_START dispara una request por cada vuelta a la pantalla; en redes muy lentas esto puede sentirse. Si surge ese caso, se podria debounce con un minimo de 30s entre refreshes.

Validacion ejecutada:
- `php -l app\Http\Controllers\Api\MobileController.php`: correcto.
- `php artisan test --filter='MobileSaleApiTest::test_mobile_cash_status_returns_session_and_recent_movements_opened_from_web'`: 1 passed, 15 assertions.
- `php artisan test`: 111 passed, 785 assertions.
- `vendor\bin\pint --dirty`: passed.
- `android/gradlew.bat :app:compileEmulatorDebugKotlin`: BUILD SUCCESSFUL (1 warning de deprecacion en LocalLifecycleOwner, no bloquea).

Proximo paso recomendado:
- Probar en dispositivo real: abrir caja desde web, vender 2-3 tickets en web, abrir la app movil, validar que la pantalla de Caja muestra los totales correctos y la lista de movimientos hechos desde la web. Luego desconectar wifi y confirmar el banner "Sin conexion".

---

## 2026-05-20 - Bugfix Android: X-Device-UUID faltante y recuperacion automatica

Responsable:
- Claude

Fase trabajada:
- Fase 6: Android online/offline controlado.

Sintoma detectado en logs reales del emulador:
- Logs Logcat: `GET /api/mobile/cash/status` y `GET /api/mobile/sync/data` retornan 403 con `{"message":"Dispositivo requerido para operar desde Android.","code":"DEVICE_UUID_REQUIRED"}` aunque el `Authorization: Bearer ...` esta presente.
- Causa raiz: el `AuthInterceptor` leia `deviceUuidFlow.firstOrNull()` y solo agregaba el header si no era blank; cuando el DataStore tenia un token pero no `device_uuid` (escenario tras upgrade del APK o sesion antigua), el header se omitia y el middleware backend rechazaba.

Puntos completados:
- [x] `AuthInterceptor` ahora llama `getOrCreateDeviceUuid()` (que genera y persiste si falta), garantizando que TODO request autenticado lleve `X-Device-UUID`. Si tuvo que generar uno nuevo, se loggea con Timber.warn.
- [x] `ApiError` DTO extendido con campo opcional `code` para que Android pueda distinguir errores de dispositivo.
- [x] `Result.Error` ahora incluye `code: String?` para propagar el codigo desde el repo hasta el ViewModel.
- [x] `CashRepository.parseError` reemplazado por `parseApiError` que retorna el ApiError completo (message + code).
- [x] `StartupSyncViewModel` detecta los codigos `DEVICE_UUID_REQUIRED`, `DEVICE_NOT_FOUND`, `DEVICE_NOT_AUTHORIZED` y `DEVICE_BLOCKED` y dispara `triggerForceLogout()`, que limpia sesion (`AuthRepository.logout()`) y cache (`cashRepository.clearCache()`).
- [x] `StartupSyncScreen` muestra mensaje rojo "Sesion invalida - regresando al login" + razon y navega a Login tras 800ms; `MainActivity` registra el nuevo callback `onForceLogout`.

Puntos parciales:
- (ninguno)

Archivos modificados:
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/AuthInterceptor.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/dto/Dtos.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/util/Result.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/CashRepository.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/startup/StartupSyncViewModel.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/startup/StartupSyncScreen.kt`
- `android/app/src/main/java/dev/bsolutions/bsloteria/ui/MainActivity.kt`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Se prefiere generar el UUID en el interceptor (en vez de fallar) porque la peor experiencia es que el cajero quede atrapado en una pantalla de error sin saber que hacer. Con la generacion automatica + auto-logout, la app se auto-recupera: si el UUID generado no corresponde a un dispositivo registrado, el backend retorna `DEVICE_NOT_FOUND` y la app limpia sesion + va a login para que el dispositivo se registre como PENDING.
- El timeout de 800ms antes de navegar a login da tiempo a que el usuario vea el mensaje rojo "Sesion invalida" y entienda por que esta yendo al login.
- Se conserva el `device_uuid` durante `session.clear()` para que la siguiente vez que el usuario logee desde el mismo dispositivo se reutilice el mismo UUID.

Riesgos detectados:
- Si el usuario tiene token valido + UUID nuevo (auto-generado por el bugfix), la primera request retornara `DEVICE_NOT_FOUND` y se forzara logout. Tras login nuevo, el dispositivo se registra como PENDING y requiere autorizacion desde el panel web (comportamiento esperado, ya documentado).
- El interceptor ahora hace un `runBlocking { ... getOrCreateDeviceUuid() }` que en el peor caso escribe al DataStore. En la practica solo ocurre una vez (en la primera request post-upgrade), tras eso firstOrNull() ya retorna el valor en memoria.

Validacion ejecutada:
- `android/gradlew.bat :app:compileEmulatorDebugKotlin`: BUILD SUCCESSFUL.

Proximo paso recomendado:
- En el emulador: reinstalar el APK con esta version, abrir la app. Si tenia sesion stale -> debe ir a StartupSync, detectar el error de dispositivo y volver a login automaticamente. Tras login, autorizar el dispositivo desde el panel web y reiniciar el flujo.

---

## 2026-05-20 - Bugfix BranchDto: company_id requerido pero ausente en cash/status

Responsable:
- Claude

Fase trabajada:
- Fase 6: Android online/offline controlado.

Sintoma detectado:
- Tras login exitoso + dispositivo AUTHORIZED, el `/api/mobile/cash/status` retorna 200 OK pero Moshi falla al deserializar la respuesta:
  `JsonDataException: Required value 'companyId' (JSON name 'company_id') missing at $.branch`
- En la respuesta, el backend retornaba `"branch":{"id":1,"name":"Central"}` sin `company_id`, pero el DTO compartido `BranchDto` lo declaraba como propiedad obligatoria (`val companyId: Long`).
- Resultado: CashRepository propagaba un error generico al StartupSync y la pantalla de Caja no podia mostrarse.

Puntos completados:
- [x] `BranchDto.companyId` ahora tiene default `0L`. Otros endpoints (login) siguen rellenandolo; los que no lo envien (cash/status) no rompen Moshi.
- [x] `MobileController::cashStatus` ahora incluye `company_id` en el objeto `branch` (tanto cuando `cash_control_enabled` como cuando no), para consistencia con el resto de endpoints.
- [x] Compilacion Kotlin BUILD SUCCESSFUL y `php artisan test --filter=MobileSaleApiTest`: 12 passed, 87 assertions.

Archivos modificados:
- `android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/dto/Dtos.kt`
- `app/Http/Controllers/Api/MobileController.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- Se hizo defensivo el DTO (default 0L) ademas de retornar el campo en el backend. Asi, si en el futuro otro endpoint comparte el DTO sin enviar company_id, el cliente no falla.
- `company_id` es informacion no sensible (esta en el token), por lo que retornarlo no introduce riesgo.

Riesgos detectados:
- Existen otros endpoints donde el contrato Moshi podria no coincidir con cambios futuros del backend. Mitigar exigiendo defaults en DTOs que se compartan entre endpoints.

Validacion ejecutada:
- `php -l app/Http/Controllers/Api/MobileController.php`: correcto.
- `php artisan test --filter=MobileSaleApiTest`: 12 passed, 87 assertions.
- `android/gradlew.bat :app:compileEmulatorDebugKotlin`: BUILD SUCCESSFUL.

Proximo paso recomendado:
- Recompilar e instalar el APK en el emulador. Validar que la pantalla de StartupSync ahora muestra "Caja abierta - fondo RD$ 5000.00, esperado RD$ 5135.00" (datos reales del log) en lugar de un error de Moshi.

---

## 2026-05-20 - Generacion diaria de sorteos: servicio dedicado, TZ por empresa y tests

Responsable:
- Claude

Fase trabajada:
- Fase 2 (Catalogo de loterias) - cierre del pendiente "comando/scheduler diario".

Contexto del problema:
- El comando `draws:generate-daily` ya existia (creado en sesion 2026-05-17) pero delegaba directamente a `DominicanLotteryCatalogSeeder::runForDate`. Tres problemas reales:
  1. Mezclaba responsabilidades (un seeder se usa para inicializar BD, no como job diario).
  2. Cada noche re-upserteaba TODO el catalogo de loterias y corria limpiezas legacy innecesarias.
  3. NO respetaba la zona horaria de la empresa: usaba `Carbon::today()` que toma `config/app.php` timezone (UTC). En produccion, a las 00:01 UTC son las 20:01 RD del dia anterior; los sorteos se generaban con la fecha UTC, no la fecha local del cajero.

Puntos completados:
- [x] Extraido el catalogo de 32 loterias a `app/Support/DominicanLotteryCatalog.php` para compartirlo entre seeder y servicio sin duplicacion. Incluye `entries()`, `retiredCodes()`, `findByCode()`.
- [x] Refactor del seeder `DominicanLotteryCatalogSeeder` para consumir el catalogo compartido (sin cambios de comportamiento en su uso original de inicializacion de BD).
- [x] Creado servicio `app/Services/Lottery/DrawGenerationService` con metodo `generate(?string $forDate, int $days)`:
  - Itera solo empresas con `status = ACTIVE`.
  - Por cada empresa, calcula "hoy" en la `companies.timezone` correcta (default `America/Santo_Domingo`).
  - Solo crea sorteos para loterias con `status = ACTIVE` (ignora loterias inactivas).
  - Idempotente: si ya existe sorteo para (company, lottery, date, time), lo cuenta como skipped y no duplica.
  - Soporta `$days >= 1` para generar varios dias adelante (margen si el scheduler falla un dia).
  - Retorna estadisticas: `{companies_processed, draws_created, draws_skipped, days_covered}`.
- [x] Refactor del comando `GenerateDailyDrawsCommand`:
  - Nuevas opciones: `--date=YYYY-MM-DD` (preserva compatibilidad) y `--days=N` (default 1).
  - Imprime tabla con estadisticas del run.
  - Validacion: rechaza `--days` < 1.
  - Inyecta `DrawGenerationService` via container.
- [x] Test feature `tests/Feature/Console/GenerateDailyDrawsCommandTest.php` con 6 casos: creacion basica, idempotencia, exclusion de empresas SUSPENDED, respeto de timezone con `Carbon::setTestNow` (servidor UTC 02:00 21-may = 22:00 RD 20-may -> sorteos para el 20), opcion `--days=3` crea 3 dias, y loterias INACTIVE se ignoran.

Puntos parciales:
- (ninguno)

Archivos creados/modificados:
- app/Support/DominicanLotteryCatalog.php (nuevo)
- app/Services/Lottery/DrawGenerationService.php (nuevo)
- app/Console/Commands/GenerateDailyDrawsCommand.php (reescrito)
- database/seeders/DominicanLotteryCatalogSeeder.php (refactor a catalogo compartido)
- tests/Feature/Console/GenerateDailyDrawsCommandTest.php (nuevo)
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Catalogo compartido en `app/Support` (no migracion a columna `lotteries.scheduled_time`) porque tocar el esquema requeriria migracion + backfill + cambios en otras pantallas, y el alcance autorizado era el comando, no el modelo de loterias. Anotado como mejora futura: si una empresa quiere horarios distintos por loteria, mover `scheduled_time` a la tabla `lotteries`.
- La timezone se lee de `companies.timezone` (default `America/Santo_Domingo`). NO se cambia el `config/app.php` global (UTC), porque ese cambio afecta `now()`/`toDateString()` en ~12 lugares mas (accounting, payroll, reportes, monitoring) y requiere una sesion dedicada con backfill de datos historicos. Esta sesion solo aisla el comando del bug global.
- El scheduler en `routes/console.php:13` ya estaba en `dailyAt('00:01')`; se conserva. Tras el fix, a las 00:01 UTC (= 20:01 RD del dia anterior) el comando ya calcula la fecha correcta (21 RD = 21 RD, no 21 UTC).
- Se mantiene `open_time` hardcodeado a '08:00' como antes; si se necesita configurable por loteria, agregar columna en `lotteries`.

Riesgos detectados:
- El bug global de `config('app.timezone') = 'UTC'` sigue afectando otros 12+ puntos del codigo (`DashboardController`, `ApiController`, `ReportController`, `AccountingService`, `PayrollService`, `OfflineSyncService`, `BranchMonitoring*`, `LicenseService`). Cualquier consulta tipo `now()->toDateString()` en RD despues de las 20:00 va a usar la fecha UTC del dia siguiente. Esta sesion no lo toca; queda como deuda tecnica priorizada para una sesion dedicada de timezone.
- Si `companies.timezone` queda en NULL o string invalido en alguna fila, `Carbon::now()` lanza excepcion. El servicio aplica fallback con `?: 'America/Santo_Domingo'` para NULL, pero no valida strings invalidos. Asume que la BD esta consistente.
- Los smoke tests CLI dejaron 64 sorteos para 2026-05-25 y 2026-05-26 en la BD local (durante validacion `--date=2026-05-25 --days=2`). No son destructivos (son sorteos futuros validos), pero si se desea limpiar: `Draw::whereDate('draw_date', '>=', '2026-05-25')->delete();`.

Validacion ejecutada:
- `php -l` sobre los 5 archivos nuevos/modificados: correcto.
- `php artisan test --filter=GenerateDailyDrawsCommandTest`: 6 passed, 18 assertions.
- `php artisan test`: 117 passed, 803 assertions (era 111/770 antes; +6 tests nuevos, sin regresiones).
- `vendor/bin/pint --dirty`: passed.
- Smoke CLI: `php artisan draws:generate-daily --date=2026-05-25 --days=2` creo 64 sorteos en empresa local; segunda corrida reporto 0 creados / 64 ya existentes (idempotencia confirmada).

Proximo paso recomendado:
- Sesion dedicada a timezone global: cambiar `config/app.php` a `America/Santo_Domingo` (o introducir helper `companyToday($company)`) y auditar los ~12 puntos donde se usa `now()->toDateString()` para evitar el desfase nocturno en accounting, reportes y dashboard. Esto sale del scope del comando pero es el bug operativo mas grande aun no resuelto.

---

## 2026-05-20 - App Android: impresion persistente, reconexion BT al arrancar y auto-print

Responsable:
- Claude

Fase trabajada:
- Fase 6 / Fase 7: completar el flujo operativo de venta + impresion para que la app sea usable en banca real sin friccion.

Contexto del problema:
- La app ya tenia venta + impresion ESC/POS via Bluetooth implementadas, pero en uso operativo tenia tres deficiencias criticas:
  1. La impresora seleccionada NO se persistia. Cada cierre/reinicio de la app perdia la conexion Y la seleccion -> el cajero tenia que ir a Settings, presionar "Actualizar dispositivos", elegir su impresora y conectar manualmente cada vez.
  2. No habia reconexion automatica al arrancar. Aun si la impresora siguiera pareada en el sistema, la app no intentaba reconectar.
  3. El cajero tenia que tocar manualmente "Imprimir" en el dialog post-venta. En operacion de banca con ritmo alto, esto es un paso de mas que retrasa la atencion.

Puntos completados:
- [x] `SessionStore` extendido con `printerAddressFlow`, `printerNameFlow`, `autoPrintFlow` + metodos `savePrinter()`, `clearPrinter()`, `setAutoPrint()`. La impresora elegida y el toggle de auto-print ahora se persisten en DataStore.
- [x] `SessionStore.clear()` (llamado en logout y re-login) ahora preserva `printerAddress`, `printerName`, `serverUrl`, `autoPrint` y `deviceUuid`. Solo borra token + datos del usuario. Asi el cambio de cajero en la misma terminal mantiene la impresora y la URL del servidor sin reconfigurar.
- [x] `BluetoothPrinterManager.connectByAddress(address)` nuevo: reconecta a una impresora previamente conocida usando solo su MAC, sin requerir que el usuario reabra la lista de dispositivos.
- [x] `StartupSyncViewModel` ahora corre un cuarto paso "Impresora Bluetooth" en paralelo a catalog/cash/tickets. Si hay address guardada y el adapter esta disponible, intenta `connectByAddress()` en `Dispatchers.IO`. Si falla, queda en OFFLINE (no ERROR) para no bloquear la entrada al Dashboard -- el cajero puede vender sin impresora y reconectar despues.
- [x] `SettingsViewModel` reescrito:
  - `connectPrinter()` corre en IO y guarda address+name en DataStore al exito.
  - `disconnectPrinter()` ("Olvidar") llama `clearPrinter()` y limpia el estado.
  - `reconnectSaved()` nuevo: reintenta conexion con la impresora guardada sin tener que re-elegirla.
  - `testPrint()` ahora corre en `Dispatchers.IO` con feedback ("Imprimiendo..."/"Prueba enviada"/"Sin impresora conectada") y auto-clear tras 2.5s.
  - `setAutoPrint(enabled)` para el toggle.
  - Estado expone tanto `connectedDeviceName` (conexion viva) como `rememberedDeviceName` (impresora guardada aunque desconectada).
- [x] `SettingsScreen` actualizado:
  - Iconos diferenciados: BluetoothConnected (verde) / BluetoothSearching (terciario, "guardada pero desconectada") / Bluetooth (sin nada).
  - Boton "Reconectar" aparece cuando hay impresora guardada pero la conexion esta caida.
  - Boton "Desconectar" renombrado a "Olvidar" -- es mas claro que ademas borra la persistencia.
  - Switch "Imprimir tras vender" (default ON) controla auto-print post-venta.
  - Feedback de test print inline con auto-dismiss.
- [x] `SaleViewModel.sell()` ahora, tras venta exitosa, lee `sessionStore.autoPrintFlow.firstOrNull()` y dispara `printLastTicket()` automaticamente si esta ON. El dialog de exito sigue mostrando el boton manual "Imprimir" como fallback (utilidad: reintentar si el primer print fallo).

Puntos parciales:
- (ninguno)

Archivos modificados:
- android/app/src/main/java/dev/bsolutions/bsloteria/util/SessionStore.kt
- android/app/src/main/java/dev/bsolutions/bsloteria/printer/BluetoothPrinterManager.kt
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/settings/SettingsViewModel.kt
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/settings/SettingsScreen.kt
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/startup/StartupSyncViewModel.kt
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/sale/SaleViewModel.kt
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- `clear()` preserva mas que el deviceUuid (agregue serverUrl, printer*, autoPrint). Razon: en banca real la terminal es FIJA -- siempre la misma URL, misma impresora. Lo que rota es el cajero. Borrar la config de hardware en cada logout obliga a re-pegar la IP del servidor y re-conectar BT cada cambio de turno, lo cual produce friccion y errores. Solo se limpia token + datos del usuario.
- Impresora desconectada NO bloquea el flujo de venta (paso "printer" en StartupSync queda OFFLINE en lugar de ERROR). El cajero puede vender, los tickets se guardan, y puede ir a Settings a reconectar despues. Bloquear la entrada al Dashboard por falta de impresora seria peor UX -- a veces el rollo de papel se acaba a media jornada.
- Auto-print ON por default. La mayoria de bancas quieren impresion inmediata; el cajero que quiera revisar antes puede apagar el toggle.
- El boton manual "Imprimir" en el dialog post-venta se conserva incluso con auto-print activo: sirve como reintento si el primer print fallo (papel atascado, BT cayendose, etc.).
- "Olvidar" en vez de "Desconectar" para el boton, porque la accion semantica es borrar la persistencia, no solo soltar la conexion.

Riesgos detectados:
- `connectByAddress()` puede fallar silenciosamente si el adapter BT esta en estado intermedio (encendiendose) al momento del startup sync. Si el cajero ve "Guardada: X (desconectada)" puede confundirse; el boton "Reconectar" mitiga esto.
- La reconexion al startup corre con `Dispatchers.IO` pero puede tardar varios segundos en BT 2.0 lentos. Como va en paralelo con catalog/cash/tickets, no extiende el tiempo total visible (es bounded por el peor caso entre los 4 pasos).
- Si dos terminales comparten un mismo APK con el mismo MAC de impresora guardado y la impresora solo permite una conexion SPP a la vez, una de las dos perdera la conexion en cada arranque. No es escenario comun (cada banca tiene su impresora propia), pero anotado.
- Test print + auto-clear de 2.5s usa `kotlinx.coroutines.delay` dentro de un `LaunchedEffect` con key=msg. Si la API de impresion retorna mas rapido que el render, podria haber un breve flash. Acceptable para uso operativo.

Validacion ejecutada:
- Imposible compilar Kotlin desde este entorno (instruccion explicita del usuario [[no-compilar]]); revision estatica de tipos e imports en los 6 archivos.
- Flujo end-to-end revisado mentalmente:
  1. Primera vez: Login -> StartupSync ("Sin impresora configurada") -> Dashboard -> Settings -> elegir impresora -> auto-guarda -> toggle ON.
  2. Vender: success -> auto-print -> dialog muestra estado de impresion -> "Nueva venta" para limpiar.
  3. Cerrar app y reabrir: Login -> StartupSync intenta reconectar -> si la impresora esta encendida y en rango, queda conectada antes de entrar al Dashboard.
  4. Logout y nuevo cajero: impresora + URL preservadas; solo el token se borra.
- No se modifico ningun archivo PHP; sin necesidad de correr suite PHP.

Proximo paso recomendado:
- Usuario debe compilar APK debug, instalar en el equipo de prueba con impresora ESC/POS pareada y validar:
  (a) Tras cerrar y reabrir la app, el paso "Impresora Bluetooth" del StartupSync muestra "Conectada: <nombre>".
  (b) Con toggle ON, al vender se imprime sin tocar nada.
  (c) Con toggle OFF, al vender aparece el dialog y solo imprime si se presiona "Imprimir".
  (d) Tras logout y login con otro usuario, la impresora sigue ahi.

---

## 2026-05-20 - Selector de conexion (Emulador / LAN) en LoginScreen y Settings

Responsable:
- Claude

Fase trabajada:
- Fase 6: facilitar uso simultaneo de la app en emulador AVD y celular fisico sin reinstalar APK ni escribir la URL manualmente.

Contexto del problema:
- El usuario reporto que el cel fisico daba error "failed to connect to /10.0.2.2 (port 8000)". Diagnostico:
  1. `php artisan serve` corria en `127.0.0.1:8000` (loopback), no en `0.0.0.0`. Se relanzo con `--host=0.0.0.0 --port=8000` y se confirmo HTTP 200 desde la IP LAN 192.168.100.159.
  2. El APK instalado en el cel era la variante `emulator`, cuyo `BuildConfig.SERVER_URL` esta hardcoded a `http://10.0.2.2:8000` (alias del AVD, no IP real).
  3. El usuario tenia que editar el campo URL manualmente cada vez para cambiar entre emulador (10.0.2.2) y celular fisico (192.168.100.159).
- Solucion solicitada: poder alternar entre las dos URLs con un toque, sin recompilar ni escribir nada.

Puntos completados:
- [x] `build.gradle.kts` agrega 2 buildConfigField NUEVOS en `defaultConfig` (independientes del flavor):
  - `EMULATOR_URL` = `"http://10.0.2.2:8000"` (fijo, alias estandar del AVD).
  - `LAN_URL` viene de la propiedad Gradle `BSLOTTERY_LAN_SERVER_URL`. Default `http://192.168.1.100:8000` si la propiedad no esta seteada.
- [x] `android/gradle.properties` ahora incluye `BSLOTTERY_LAN_SERVER_URL=http://192.168.100.159:8000` (la IP real del PC del usuario). Comentario explica que se debe cambiar al mover la app a otro router.
- [x] Nuevo `util/ServerPresets.kt` expone una lista `ServerPresets.ALL` (Emulador + LAN) leida desde BuildConfig. Tambien tiene `ServerPresets.match(url)` que devuelve el preset si la URL ingresada coincide (case-insensitive a slash final), o null para custom.
- [x] `LoginScreen` muestra un composable privado `ServerPresetChips` arriba del campo URL:
  - "Conexion rapida" label.
  - Row de `FilterChip` (Emulador / LAN) que al tocarse autocompleta el TextField via `viewModel::onServerUrlChange`.
  - Linea de status: "Emulador: http://..." o "Custom: http://..." dependiendo de si coincide con algun preset.
- [x] `SettingsScreen` -> seccion "Servidor" tiene la misma fila de chips arriba del TextField. Misma logica.
- [x] El campo URL OutlinedTextField sigue siendo editable manualmente (custom URL), los chips son atajo, no reemplazo.

Puntos parciales:
- (ninguno)

Archivos modificados:
- android/app/build.gradle.kts (defaultConfig + buildConfigField EMULATOR_URL/LAN_URL)
- android/gradle.properties (BSLOTTERY_LAN_SERVER_URL=http://192.168.100.159:8000)
- android/app/src/main/java/dev/bsolutions/bsloteria/util/ServerPresets.kt (nuevo)
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/login/LoginScreen.kt (chips + composable privado)
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/settings/SettingsScreen.kt (chips inline en seccion Servidor)
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Presets como buildConfigField en `defaultConfig` (no como flavor-specific). Asi UN solo APK (cualquier variante: emulator, lan, production) lleva AMBAS URLs y el cajero puede alternar. Antes se necesitaba elegir variante en Build Variants para tener una URL u otra.
- `LAN_URL` parametrizable via `gradle.properties` y no hardcoded en build.gradle.kts: cada PC tiene IP distinta y mover la app entre oficinas solo requiere editar 1 linea en gradle.properties + reinstalar.
- `EMULATOR_URL` se queda hardcoded a `10.0.2.2:8000` porque ese alias es estandar de Android (no cambia segun host).
- TextField sigue editable: si el usuario necesita una URL custom (ej. ngrok temporal o un VPS de staging), puede escribirla y el chip status muestra "Custom: ...".
- Composable `ServerPresetChips` esta privado en LoginScreen y duplicado inline en SettingsScreen. Razon: el patron es chico y duplicarlo evita tener que crear un widget compartido que cargara dependencias UI cruzadas. Si se agrega una 3ra pantalla con presets, se promueve a `ui/common/`.
- No se cambio el flavor `emulator` ni `lan`; los presets son adicionales y compatibles. La variante "lan" sigue util para builds de produccion preconfigurados que NO permiten cambiar URL.

Riesgos detectados:
- Si `gradle.properties` esta versionado y otro developer clona el repo, vera la IP 192.168.100.159 del PC original, que no aplica en su red. Mitigacion: agregar `gradle.properties` a `.gitignore` y proveer `gradle.properties.example` (no implementado en esta sesion -- el proyecto actual NO usa git, asi que el riesgo es bajo).
- Si la propiedad `BSLOTTERY_LAN_SERVER_URL` no esta seteada, cae al default `http://192.168.1.100:8000` que probablemente no existe en la red del usuario. El chip "LAN" apuntaria a una IP muerta y daria timeout, pero al menos la app no crashea.
- El chip "Emulador" tiene utilidad cero en un cel fisico (10.0.2.2 no resuelve). Mostrar el chip ahi puede confundir, pero es preferible a tener 2 builds distintos para emulador vs fisico.

Validacion ejecutada:
- Imposible compilar Kotlin desde este entorno (memoria [[no-compilar]]). Revision estatica de imports y tipos en los 5 archivos modificados/nuevos.
- Flujo verificado:
  1. Cel fisico abre app -> LoginScreen muestra chips "Emulador" (selected porque BuildConfig.SERVER_URL = 10.0.2.2 default) + "LAN" (no selected).
  2. Cajero toca "LAN" -> campo URL se autocompleta a http://192.168.100.159:8000, chip "LAN" queda selected, status dice "LAN: http://...".
  3. Cajero loguea -> AuthRepository persiste la URL en DataStore (linea 18 ya hace updateServerUrl ANTES del request HTTP).
  4. Proxima vez que abra Login: state.serverUrl viene del DataStore via DynamicBaseUrlInterceptor (o queda en el default de BuildConfig si DataStore esta vacio). Si guardo "LAN" antes, los chips reflejan eso.
- Server PHP confirmado escuchando en 0.0.0.0:8000 y respondiendo HTTP 200 desde 192.168.100.159.

Proximo paso recomendado:
- Recompilar APK debug e instalar en el cel. Validar que:
  (a) LoginScreen muestra los dos chips "Emulador" y "LAN" con el de "Emulador" preseleccionado.
  (b) Tocar "LAN" autocompleta el campo URL a http://192.168.100.159:8000 sin escribir nada.
  (c) Login funciona y se guarda la preferencia.
  (d) Settings -> Servidor muestra los mismos chips para cambiar despues del login.
- Si la oficina cambia de router, basta editar 1 linea de `gradle.properties` y reinstalar.

---

## 2026-05-21 - Discovery activo de impresoras Bluetooth en Settings

Responsable:
- Claude

Fase trabajada:
- Fase 6 / Fase 7: la pantalla de Settings solo listaba `bondedDevices` (pareados desde el sistema). Si la impresora estaba encendida pero NO pareada todavia, no aparecia.

Contexto del problema:
- El cajero reporto: "debe leer los dispositivos de impresora que esten encendido o ver los mismo que ve el celular para seleccionarlo".
- Diagnostico: `BluetoothPrinterManager.pairedPrinters()` usa `adapter.bondedDevices`, lo cual solo lista los emparejados a nivel sistema. Para impresoras nuevas (sin parear) o para ver lo mismo que ve el cel en Bluetooth -> Buscar, hay que usar `BluetoothAdapter.startDiscovery()` con BroadcastReceiver dinamico.

Puntos completados:
- [x] `AndroidManifest.xml` actualizado:
  - `BLUETOOTH_SCAN` con `usesPermissionFlags="neverForLocation"` (API 31+: evita pedir ubicacion, que asusta a cajeros).
  - `ACCESS_FINE_LOCATION` con `maxSdkVersion="30"` (requerido por discovery en Android 6-11).
- [x] `BluetoothPrinterManager` extendido:
  - Nueva sealed class `ScanEvent` (`DeviceFound`, `Finished`, `Error`).
  - Metodo `scanForDevices(): Flow<ScanEvent>` con `callbackFlow`:
    - Registra `BroadcastReceiver` dinamico para `ACTION_FOUND` y `ACTION_DISCOVERY_FINISHED`.
    - Filtra duplicados con un `seenAddresses: Set<String>`.
    - Usa `getParcelableExtra(EXTRA_DEVICE, BluetoothDevice::class.java)` en API 33+ y la version deprecated en API < 33.
    - Auto-cancela discovery y desregistra receiver al cerrar el Flow (via `awaitClose`).
    - Valida `adapter != null`, `adapter.isEnabled`, y `hasScanPermission()` antes de empezar.
  - Metodo `cancelScan()` para cancelacion manual.
  - `hasScanPermission()` chequea `BLUETOOTH_SCAN` en API 31+, `ACCESS_FINE_LOCATION` en < 31.
- [x] `SettingsViewModel`:
  - Estado agregado: `discoveredDevices`, `isScanning`, `scanMessage`.
  - `scanForDevices()` arranca un Job que colecta el Flow del manager y actualiza el state progresivamente. Filtra devices que ya estan en `pairedDevices`.
  - `cancelScan()` cancela el Job + llama `manager.cancelScan()`.
  - `clearScanMessage()` para limpiar el mensaje tras unos segundos.
- [x] `SettingsScreen`:
  - Imports nuevos: `Manifest`, `Build`, `accompanist.permissions.rememberMultiplePermissionsState`.
  - `rememberMultiplePermissionsState` con permisos condicionales por API level.
  - Variable local `pendingScanAfterGrant` para auto-arrancar scan tras grant.
  - Funcion `startScanWithPermission()`: si tiene permisos, scan; si no, los pide y queda pendiente.
  - Boton "Buscar nuevos" reemplaza al solitario "Actualizar dispositivos". Row de 2 botones: "Pareados (N)" y "Buscar nuevos" / "Cancelar" (segun isScanning).
  - Indicador de progreso (`CircularProgressIndicator`) mientras scanning.
  - Mensaje de estado (`scanMessage`) inline.
  - Dos secciones separadas con headers: "PAREADOS EN EL SISTEMA" y "ENCONTRADOS CERCA (NO PAREADOS)" (color tertiary para diferenciar).
  - `BluetoothDeviceCard` ahora acepta `accentColor` para variar el icono entre pareados (primary) y descubiertos (tertiary).

Puntos parciales:
- (ninguno)

Archivos modificados:
- android/app/src/main/AndroidManifest.xml (BLUETOOTH_SCAN neverForLocation + ACCESS_FINE_LOCATION)
- android/app/src/main/java/dev/bsolutions/bsloteria/printer/BluetoothPrinterManager.kt (ScanEvent + scanForDevices Flow)
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/settings/SettingsViewModel.kt (state + scanForDevices/cancelScan)
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/settings/SettingsScreen.kt (permisos runtime + UI buscar/cancelar + 2 listas separadas)
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- `scanForDevices` retorna `Flow<ScanEvent>` con `callbackFlow` en vez de callbacks o suspend. Razon: el Flow se cancela automaticamente cuando el ViewModelScope muere (rotacion, navegacion), liberando el BroadcastReceiver y cancelando el discovery sin leaks.
- `neverForLocation` en BLUETOOTH_SCAN: en uso comercial el cajero ve el dialogo de permiso para Bluetooth (ya conocido) y NO el de "Acceso a ubicacion precisa" (que genera dudas y rechazos). Solo aplica API 31+. En API <=30 se sigue necesitando location.
- Filtrado de pareados en `discoveredDevices`: si un device aparece en ambos, solo aparece en "Pareados" para no duplicar la card.
- Cancelacion manual (`Cancelar`) reemplaza al boton "Buscar nuevos" mientras esta scanning. Mejor UX que un boton fijo deshabilitado.
- Color terciario para devices descubiertos: visual cue de que son nuevos (no pareados todavia). Al tocar uno, el sistema Android lanza dialogo de pairing automaticamente la primera vez (Bluetooth standard).
- Permisos runtime via accompanist-permissions (ya estaba en build.gradle.kts:210). Alternativa era usar `rememberLauncherForActivityResult` directo, pero accompanist abstrae bien el flow multi-permission.

Riesgos detectados:
- En Android <= 11, el usuario debe otorgar `ACCESS_FINE_LOCATION` para que aparezcan los nuevos dispositivos. Si rechaza, el scan retornara "Permiso denegado". No hay fallback automatico.
- `startDiscovery()` puede interferir con conexiones SPP activas. En `connect()` ya hay `adapter.cancelDiscovery()` para mitigar, pero si el usuario empieza un scan justo despues de conectar la impresora, podria caerse la conexion. Mitigado: el boton "Buscar nuevos" se deshabilita mientras isScanning, y el scan completo dura ~12s.
- Discovery clasico Bluetooth (SPP) NO ve dispositivos BLE-only (Bluetooth Low Energy). Las impresoras termicas comerciales usan SPP, asi que esta bien para el caso de uso. Si en futuro se agrega impresora BLE, hay que usar `BluetoothLeScanner`.
- El scan dura ~12 segundos fijos (limite del sistema). Si no aparece la impresora, el usuario puede tocar "Buscar nuevos" otra vez.
- Si el usuario habia parado el `php artisan serve` y reinicia el PC, el server muere y la app cae a offline. NO esta relacionado con esta sesion pero queda como recordatorio.

Validacion ejecutada:
- Imposible compilar (memoria [[no-compilar]]). Revision estatica de imports, sintaxis, manejo de permisos por API level.
- Flujo verificado mentalmente:
  1. Cajero entra a Settings con la impresora encendida pero no pareada.
  2. Toca "Buscar nuevos" -> primera vez ve el dialogo de permiso BLUETOOTH_SCAN/CONNECT (API 31+) o ACCESS_FINE_LOCATION (API <=30).
  3. Acepta -> scan arranca automaticamente.
  4. Aparece la seccion "ENCONTRADOS CERCA" con la impresora en color tertiary.
  5. Toca la impresora -> Android pide pairing -> tras parear, `connect()` se ejecuta y guarda en DataStore.
  6. La proxima vez la impresora aparece en "PAREADOS EN EL SISTEMA" (color primary).

Proximo paso recomendado:
- Recompilar e instalar. Probar el flujo: con la impresora termica encendida pero NO pareada al cel, ir a Settings -> Impresora Bluetooth -> "Buscar nuevos". Debe aparecer en la lista "Encontrados". Tocar para parear + conectar.
- Si la impresora no aparece tras 12s: verificar que este en modo discoverable (algunos modelos requieren mantener boton de feed presionado al encender) y que el cel tenga BT encendido.

---

## 2026-05-21 - QR real en ticket + consulta publica /t/{uuid} + columna Origen WEB/APP

Responsable:
- Claude

Fase trabajada:
- Fase 4 (web POS) + Fase 6 (app movil) + Fase 7 (impresion): cerrar el ciclo "QR del ticket es escaneable y lleva a una vista publica del ticket".

Contexto del problema:
- El usuario pidio: "verifica que se genere el codigo QR del ticket tanto en la web como en la app, luego debemos poder consultar ese ticket en un link de consulta de la web". Diagnostico: NADA generaba QR real -- el formatter PHP y el buildTicketText Android emitian solo la linea de texto "QR: ticket:<uuid>" que la impresora rendereaba como texto plano, no como QR. Tampoco existia ruta publica para consultar el ticket sin login.
- Segundo pedido (en mitad de la sesion): "cuando una sucursal hace un ticket la app movil debe quedar registrado que fue desde la app y se debe ver a la hora de entrar a ticket en la web una columna que siga si fue web o app".

Puntos completados:

Bloque A - QR real + consulta publica:
- [x] Nueva ruta `GET /t/{uuid}` -> `TicketPublicLookupController@show`, sin auth. Constraint regex en UUID (36 hex con guiones).
- [x] `TicketPublicLookupController::show` carga ticket con relaciones minimas (company, branch, details, winnerTickets, results). NO carga user, device, cashSession ni datos administrativos.
- [x] Vista `resources/views/public/ticket/show.blade.php`: mobile-first, Bootstrap CDN, agrupa jugadas por sorteo, muestra resultado si existe, badge de status (ACTIVE/CANCELLED/WINNER/etc), total ganado destacado si hay premios.
- [x] Vista `public/ticket/not_found.blade.php` para 404.
- [x] Bypass agregado en `EnsureInitialSetupIsCompleted` y `EnsureLicenseIsValid` para `/t/*` -- la consulta publica debe ser accesible incluso si la banca esta sin licencia activa o sin setup (el cliente que escanea el QR no es admin).
- [x] `TicketPrintFormatterService::format58mm/88mm` ahora emite `[[QR:<url>]]` en lugar de `QR: ticket:<uuid>`. Nuevo metodo `publicTicketUrl(Ticket)` que retorna `route('ticket.public', ...)`.
- [x] `print-agent/EscPosBuilder.java`: nuevo metodo `qrCode(data)` que emite secuencia ESC/POS `GS ( k` (modelo 2, modulo 6, EC=M). `text()` ahora parsea `[[QR:<data>]]` en cada linea y lo reemplaza por bytes de QR centrados. Compatible con compiler Spring Boot existente.
- [x] `android/.../printer/BluetoothPrinterManager.kt`: nuevo `appendQrCode(buf, data)` con la misma secuencia ESC/POS. `buildEscPos()` ahora parsea `[[QR:...]]` por linea con regex `\[\[QR:(.+?)]]`.
- [x] `android/.../ui/screen/sale/SaleViewModel.kt`: `buildTicketText()` ahora recibe `serverUrl` (leido de DataStore) y emite `[[QR:<server>/t/<uuid>]]` + linea "Escanea para ver tu ticket".
- [x] `android/.../ui/screen/tickets/TicketDetailViewModel.kt`: inyecta `SessionStore`, `buildDetailTicketText()` recibe `serverUrl` y agrega marcador QR.

Bloque B - Columna Origen WEB/APP:
- [x] Backend YA guardaba el origen: `TicketSaleService.sell() -> sale_mode='ONLINE'` y `sellFromMobile() -> sale_mode='MOBILE'`. No habia que tocar el servicio.
- [x] `admin/tickets/index.blade.php` -> nueva columna "Origen" con badge codificado por sale_mode:
  - `MOBILE` -> "App" (verde con icono `bi-phone`)
  - `ONLINE` -> "Web" (azul con icono `bi-pc-display`)
  - `OFFLINE` -> "Offline" (amarillo con icono `bi-cloud-slash`, para futuro)
  - default -> muestra valor crudo o "—"

Bloque C - Tests:
- [x] `tests/Feature/TicketPublicLookupTest.php` con 5 casos:
  (a) consulta publica sin auth muestra empresa + sucursal + numero + tipo de jugada;
  (b) UUID no existente retorna 404 con vista amigable;
  (c) UUID malformado lo rechaza el regex de la ruta antes del controller;
  (d) ticket cancelado muestra leyenda "ANULADO" + motivo;
  (e) la ruta es accesible sin sesion activa.
- [x] Tests existentes actualizados:
  - `TicketPrintFormatterTest::test_58mm_ticket_contains_core_data_grouped_plays_and_validation` ahora valida `[[QR:` + `/t/<uuid>` en vez de `QR: ticket:<uuid>`.
  - `SaleAndPrizeCycleTest::sale_and_reprint_use_configured_ticket_print_formatter` igual.

Puntos parciales:
- (ninguno)

Archivos creados/modificados:
- app/Http/Controllers/TicketPublicLookupController.php (nuevo)
- resources/views/public/ticket/show.blade.php (nuevo)
- resources/views/public/ticket/not_found.blade.php (nuevo)
- routes/web.php (ruta `/t/{uuid}` con constraint UUID)
- app/Http/Middleware/EnsureInitialSetupIsCompleted.php (bypass `t/*`)
- app/Http/Middleware/EnsureLicenseIsValid.php (bypass `t/*`)
- app/Services/Printing/TicketPrintFormatterService.php (marcador `[[QR:<url>]]` + metodo publicTicketUrl)
- print-agent/src/main/java/dev/bsolutions/printagent/service/EscPosBuilder.java (qrCode + parse en text)
- android/app/src/main/java/dev/bsolutions/bsloteria/printer/BluetoothPrinterManager.kt (appendQrCode + parse en buildEscPos)
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/sale/SaleViewModel.kt (serverUrl en buildTicketText)
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketDetailViewModel.kt (idem)
- resources/views/admin/tickets/index.blade.php (columna Origen con badge)
- tests/Feature/TicketPublicLookupTest.php (nuevo, 5 casos)
- tests/Feature/TicketPrintFormatterTest.php (assertion QR actualizada)
- tests/Feature/SaleAndPrizeCycleTest.php (assertion QR actualizada)
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Marcador `[[QR:<data>]]` en vez de bytes ESC/POS directos en el formatter PHP. Razon: el formatter retorna String que viaja por HTTP al print agent / por Bluetooth al BTPrinterManager. Bytes binarios crudos romperian UTF-8 en transito; un marcador legible es atomico, debugeable y permite que el rendering del QR ocurra en la capa que conoce la impresora especifica.
- QR codifica la URL completa de consulta (`http://server/t/<uuid>`), no solo el UUID. Asi cualquier cel con camara puede escanear y abrir la pagina inmediatamente sin app especial.
- URL se construye desde `route('ticket.public', ...)` (web) y `serverUrl + /t/<uuid>` (Android, leyendo DataStore). En LAN apunta a `http://192.168.x.x:8000/t/<uuid>`; en produccion con dominio apunta al dominio.
- Limitacion conocida: en LAN, el QR solo es escaneable si el cel del cliente esta en el mismo wifi que el server. Para uso comercial real con clientes externos se requiere dominio publico (config `APP_URL`). Anotado como futuro.
- Modulo QR de tamano 6 + EC nivel M: balance entre tamano fisico (~21mm @203dpi, escaneable a 30cm) y robustez (15% de tolerancia a danos). Suficiente para impresion termica.
- Bypass `t/*` en middleware de licencia/setup: el cliente que escanea el QR es publico y NO debe ver pagina de "licencia bloqueada" si la banca tiene licencia vencida. Decision deliberada: el ticket sigue siendo consultable para el cliente aunque la banca tenga problemas administrativos.
- Vista publica NO muestra cajero ni cash session: data minimamente expuesta, suficiente para que el cliente verifique su ticket pero sin info operativa interna.
- Columna "Origen" con valores ya existentes en BD (ONLINE/MOBILE/OFFLINE). NO se hizo migracion ni rename porque romperia datos historicos. La UI traduce a "Web"/"App"/"Offline" via match() en Blade.
- Iconos Bootstrap Icons (ya cargados via app.blade.php): `bi-pc-display` para Web, `bi-phone` para App, `bi-cloud-slash` para Offline.

Riesgos detectados:
- El QR en impresoras termicas muy economicas puede salir con tamano modulo 6 mal alineado por baja resolucion mecanica. Si pasa, bajar a modulo 5 en EscPosBuilder.java + BluetoothPrinterManager.kt.
- Si la URL del server cambia (otra IP de LAN, dominio nuevo), los tickets viejos impresos siguen apuntando a la URL vieja -- el QR es estatico en el papel. Solucion: en produccion fijar `APP_URL` a un dominio estable antes de imprimir tickets reales.
- La vista publica no requiere captcha ni rate limit -- alguien podria scrappear UUIDs aleatorios. Pero los UUIDs son 128-bit, asi que adivinarlos a fuerza bruta es inviable. Si en el futuro hay preocupacion de scraping legitimo (privacidad de jugadas), agregar throttle middleware.
- El bypass `t/*` agrega una ruta sin licencia. Una banca con licencia vencida queda con UN endpoint operativo (consulta de ticket). Esto es intencional: protege al cliente final que ya pago, no a la banca administradora.
- Columna "Origen" depende de que el campo `sale_mode` este poblado correctamente en filas existentes. Filas pre-migracion sin valor mostraran "—". No requiere backfill obligatorio.

Validacion ejecutada:
- `php -l` sobre controller + tests + middleware: correcto.
- `php artisan route:list --path=t/`: ruta registrada como `ticket.public`.
- `php artisan test --filter="TicketPrintFormatterTest|TicketPublicLookupTest"`: 10 passed, 38 assertions.
- `php artisan test`: 122 passed, 819 assertions (subimos +5 nuevos + 2 actualizados; previo era 117).
- `vendor/bin/pint --dirty`: passed.
- Imposible compilar Kotlin/Java (memoria [[no-compilar]]). Revision estatica de tipos e imports en los 3 archivos Kotlin + 1 Java.

Proximo paso recomendado:
- Recompilar APK + reiniciar print-agent Java. Validar:
  (a) Vender un ticket desde la web; al imprimir, el papel debe tener QR real al pie (no la linea de texto). Escanear con cualquier cel -> abrir vista publica.
  (b) Mismo flujo desde la app movil con impresora Bluetooth: imprime QR; escanear; abre la vista publica con `http://192.168.100.159:8000/t/<uuid>`.
  (c) En `admin.tickets.index`, ver columna "Origen": tickets de la web aparecen como "Web" (badge azul), tickets de la app aparecen como "App" (badge verde).
  (d) Para uso comercial real con clientes externos al wifi: configurar APP_URL en .env con dominio publico (o ngrok temporal).

---

## 2026-05-21 - App Android: filtros de fecha en historial + rediseño detalle del ticket

Responsable:
- Claude

Fase trabajada:
- Fase 6: UX del historial y detalle de tickets en la app movil.

Contexto del problema:
- El usuario pidio:
  1. En el historial de tickets, columnas: Ver, Numero, Jugada, Monto, Hora, Fecha.
  2. Filtro de fecha: Hoy, Ayer, Personalizada.
  3. Vista de detalle mas legible y clara.

Puntos completados:

A - Modelo de datos:
- [x] `TicketEntity` extendido con `ticketNumber: String? = null` y `soldAtEpoch: Long = 0L`. El primero permite mostrar el numero asignado por el backend; el segundo evita reparsear el string de fecha cada vez que se filtra.
- [x] `AppDatabase` version 2 -> 3. `fallbackToDestructiveMigration` recrea la tabla local (cache, no critico perder).
- [x] `TicketRepository.cacheOnlineTicket()` ahora guarda `ticketNumber` desde el DTO y parsea `soldAt` (ISO o "yyyy-MM-dd HH:mm:ss") a epoch millis con `parseIsoToEpoch()`.
- [x] `TicketDao.observeAllDetails()` nuevo para alimentar el preview de jugadas en la lista.

B - ViewModel con filtros:
- [x] `TicketsViewModel` rewrite:
  - Nuevo `enum DateFilter { TODAY, YESTERDAY, CUSTOM }`.
  - `TicketFilterState` con dateFilter (default TODAY) + customStartMs/customEndMs.
  - `tickets` ahora es `combine(synced, offline, details, filter)`; aplica `applyDateFilter()` con rangos calculados via `Calendar`.
  - `TicketDisplayItem` extendido con `ticketNumber`, `timeLabel` ("h:mm a"), `dateLabel` ("dd/MM/yyyy"), `jugadasPreview` ("25Q, 47P, 1234Pl") y `jugadasCount`.
  - `buildJugadasPreview()` arma string compacto con max 3 jugadas + "…" si hay mas.
  - Funciones publicas `setDateFilter(filter)` y `setCustomRange(startMs, endMs)`.

C - UI historial:
- [x] `TicketsScreen` rediseñado:
  - Nueva `DateFilterRow` con icono CalendarMonth + 3 chips (Hoy/Ayer/Personalizada). El chip Personalizada muestra el rango actual (ej. "20/05 — 23/05").
  - `CustomDateRangeDialog` con flujo de dos pasos (fecha inicial -> fecha final) usando `DatePicker` material3. Al confirmar llama `setCustomRange()`.
  - `EmptyState` ahora muestra mensaje contextual segun filtro ("Sin tickets hoy", "Sin tickets ayer", "Sin tickets en el rango seleccionado").
  - `TicketCard` rediseñada:
    - Leading: icono ChevronRight (botón Ver implícito por click en la card).
    - Centro: numero de ticket en mono + icono sync compacto, preview de jugadas, contador de jugadas.
    - Derecha: monto grande, hora ("8:35 PM"), fecha ("21/05/2026") -- cumple los 4 datos pedidos (monto, hora, fecha + número).

D - Rediseño TicketDetailScreen:
- [x] Header card prominente: "Número de ticket" en grande (22sp mono bold) + StatusChip a la derecha (Sincronizado/Pendiente con color e icono).
- [x] MetaRow uniforme para Loteria/Sorteo/Fecha (label gris a la izquierda, valor bold a la derecha).
- [x] Nuevo `TotalCard` con fondo tintado primary, "Total apostado" + monto 26sp mono bold + icono Payments.
- [x] `SectionLabel` con contador: "JUGADAS (3)" en color primary.
- [x] Cada jugada en su propio Card (en vez de filas apretadas):
  - Caja izquierda con numero grande (22sp mono) sobre fondo tintado.
  - Centro: chip "Quiniela"/"Palé"/"Tripleta"/"Súper Palé" (label completo, no abreviado) + premio posible si existe.
  - Derecha: label "Monto" + monto mono bold.
- [x] UUID al pie en gris claro para soporte/debug.
- [x] `TicketDetailViewModel.load()` ahora pone `ticketNumber` desde DB (con fallback a UUID corto si offline).

Puntos parciales:
- (ninguno)

Archivos modificados:
- android/app/src/main/java/dev/bsolutions/bsloteria/data/local/entity/Entities.kt (ticketNumber + soldAtEpoch)
- android/app/src/main/java/dev/bsolutions/bsloteria/data/local/AppDatabase.kt (version 3)
- android/app/src/main/java/dev/bsolutions/bsloteria/data/local/dao/TicketDao.kt (observeAllDetails)
- android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/TicketRepository.kt (parseIsoToEpoch + caching ticketNumber)
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketsViewModel.kt (rewrite con filtros)
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketsScreen.kt (rewrite UI)
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketDetailViewModel.kt (ticketNumber en state)
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/tickets/TicketDetailScreen.kt (rewrite layout)
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Default `DateFilter.TODAY` en vez de "Todos". Razon: en operacion real el cajero ve los tickets del dia 95% del tiempo; bajar el ruido visual cargando 100 tickets historicos por defecto.
- Migración Room destructiva (drop tabla cache + recrear) en vez de `addColumn`. Razon: la app ya usaba `fallbackToDestructiveMigration` -- las tablas son cache local que se reconstruye del backend en la proxima sync. No hay datos del usuario que se pierdan.
- `buildJugadasPreview` muestra max 3 jugadas. 3 caben en 1 linea en pantallas estandar; mas seria scroll horizontal. Numero total de jugadas se muestra debajo ("4 jugadas").
- `TicketCard` usa click en toda la card como "Ver" (con icono ChevronRight como hint), en vez de un boton "Ver" separado. Razon: optimiza el area tactil para dedos en mobile y reduce ruido visual.
- Custom date picker en 2 pasos (start -> end) en vez de DateRangePicker Material3. Razon: DateRangePicker requiere mas espacio vertical y mejor scroll; el wizard de 2 pasos funciona mejor en pantallas pequeñas.
- En TicketDetailScreen, cada jugada es ahora un Card propio (no fila). Costo: mas vertical scroll si hay muchas jugadas. Beneficio: cada jugada respira y se lee de un vistazo, especialmente el numero. Para tickets con 1-5 jugadas (caso comun) la UX mejora notablemente.
- Tipos de jugada se muestran completos ("Quiniela", "Palé") en detalle y abreviados ("Q", "P") en lista. Decision: detalle tiene espacio y debe ser claro; lista necesita compactness.

Riesgos detectados:
- `parseIsoToEpoch` puede retornar 0 si el formato del backend cambia. En ese caso el filtro de "Hoy" no incluiria ese ticket. Mitigado: si epoch=0 el ticket queda fuera de TODOS los rangos -> el usuario lo veria al cambiar a Personalizada con rango amplio.
- `observeAllDetails()` retorna TODOS los detalles cacheados (no solo los de tickets visibles). Si la cache crece a miles de tickets, podria volverse pesado. Mitigado: el repository solo cachea los 100 mas recientes (ya existia ese limite en `observeRecent`); los detalles se borran tambien al limpiar tickets.
- Migracion destructiva borra la cache local del usuario. Tras instalar la nueva version, el historial empieza vacio hasta que la app sincronice con el backend (StartupSync ya lo hace). En oficina sin internet esto puede generar confusion temporal.
- DatePicker material3 requiere version compose-bom reciente (2024.x ya esta).

Validacion ejecutada:
- Imposible compilar (memoria [[no-compilar]]). Revision estatica de imports, tipos, lambdas en los 8 archivos.
- Verificacion mental del flujo:
  1. Cajero abre Historial -> ve solo tickets de HOY por default.
  2. Toca "Ayer" -> filtra a tickets de ayer.
  3. Toca "Personalizada" -> dialog 2-step picker -> elige rango -> aplica.
  4. Cada card muestra: ChevronRight, Numero, jugadas preview, monto, hora, fecha.
  5. Tap en card -> Detalle: numero gigante arriba, total destacado, cada jugada en mini-card legible.

Proximo paso recomendado:
- Recompilar APK + instalar. Validar:
  (a) Historial abre filtrando "Hoy"; cambiar a "Ayer" trae los tickets de ayer; "Personalizada" abre el dialog.
  (b) Card de ticket muestra los 4 datos pedidos (numero, jugada preview, monto, hora, fecha) + chevron de ver.
  (c) Detalle: numero de ticket grande, total bien visible, cada jugada en su card individual.
  (d) Tras la migracion Room, la primera apertura tiene cache vacia hasta StartupSync.

---

## 2026-05-21 - Web: dashboard TZ + limpieza mojibake + estados en espanol

Responsable:
- Claude

Fase trabajada:
- Fase 8 (reportes/dashboard) + transversal: bugs visuales reportados por el usuario.

Contexto del problema:
- El usuario reporto 3 problemas simultaneos:
  1. El dashboard mostraba "Sin sorteos abiertos hoy" aunque si existian. Si los abria desde "Ver todos" si aparecian.
  2. La vista de reportes tenia "mucho texto mal escrito" (mojibake — caracteres acentuados rotos por double-encoding UTF-8).
  3. Los estados (ACTIVE, CANCELLED, PAID, etc.) salian en ingles en muchas vistas, en lugar de espanol.

Puntos completados:

A - Dashboard respeta TZ de la empresa:
- [x] `DashboardController` ahora calcula `$today`/`$yesterday` en la timezone de la empresa (`companies.timezone`, default `America/Santo_Domingo`), no en UTC del servidor:
  ```php
  $timezone = Company::find($companyId)?->timezone ?: config('app.timezone', 'America/Santo_Domingo');
  $today = Carbon::now($timezone)->toDateString();
  ```
- [x] Mismo fix aplicado al grafico de 7 dias (`whereBetween('sold_at', ...)` y labels `Carbon::parse($d, $timezone)`).
- [x] Causa raiz del bug: el comando `draws:generate-daily` corre a las 00:01 hora local (TZ empresa). Crea sorteos con `draw_date = hoy RD`. Pero el dashboard consultaba con `now()->toDateString()` UTC, que tras las 8 PM RD vale "manana" -> ningun sorteo del dia coincide. Ahora ambos lados (creacion y consulta) hablan la misma TZ.
- [x] Esta fix se suma a la sesion previa del comando `draws:generate-daily`. Aun queda deuda en otros 10 puntos del codigo que usan `now()->toDateString()` global (accounting, payroll, reportes), pero el dashboard, que es la primera pantalla, ya esta arreglado.

B - Mojibake en reportes:
- [x] Script `_fix_mojibake.php` (temporal, ya borrado) recorrio `resources/views/admin/reports/*.blade.php` y reemplazo 15 secuencias double-encoded por su equivalente UTF-8 correcto:
  - `Ã­` -> `í`, `Ã³` -> `ó`, `Ã©` -> `é`, `Ã¡` -> `á`, `Ãº` -> `ú`, `Ã±` -> `ñ`, `Ã‘` -> `Ñ`
  - `Â¿` -> `¿`, `Â¡` -> `¡`, `Â°` -> `°`
  - Mayusculas acentuadas tambien (`Ã"` -> `Ó`, `Ã‰` -> `É`, etc.)
- [x] Solo 1 archivo tenia mojibake: `reports/index.blade.php` (con "Ventas por dÃ­a", "loterÃ­a", "NÃºmeros", "NÃ³mina", "PrÃ©stamos", "perÃ­odo", "GestiÃ³n"). Tras el fix: "Ventas por día", "lotería", "Números", "Nómina", "Préstamos", "período", "Gestión".
- [x] Verificacion final con grep en TODO `resources/views/`: cero mojibake restante.

C - Estados en espanol via componente reutilizable:
- [x] `app/View/Components/StatusBadge.php` extendido con dos metodos publicos estaticos:
  - `StatusBadge::labelFor(string $status): string` -> "ACTIVE" -> "Activo"
  - `StatusBadge::cssClassFor(string $status): string` -> "ACTIVE" -> "bg-success"
- [x] El componente `<x-status-badge>` reutiliza estos metodos en su constructor. Los nombres terminan en "For" para evitar colision con las properties publicas `$label` y `$cssClass` (Blade extrae publicMethods al renderizar y un metodo con el mismo nombre que la property la sobrescribe en el contexto del template — un metodo `cssClass()` sin sufijo causaba el error "htmlspecialchars Closure given" descubierto al correr la suite).
- [x] 15 vistas migradas de `<span class="...">{{ $X->status }}</span>` (con clases ternarias custom) a `<x-status-badge :status="$X->status" />`:
  - `admin/accounting/{accounts,entry,journal}.blade.php`
  - `admin/cash/funding/index.blade.php`
  - `admin/employees/{advances,loans}.blade.php`
  - `admin/payroll/{index,show}.blade.php` (2 en show)
  - `admin/reports/{cash-summary,payroll-advances,payroll-loans,payroll-report,prizes-paid,winners}.blade.php`
  - `admin/tickets/show.blade.php`
- [x] 3 formularios (dropdowns) migrados de `<option>{{ $status }}</option>` a `<option>{{ StatusBadge::labelFor($status) }}</option>`:
  - `admin/{branches,companies,users}/form.blade.php`
- [x] Las clases CSS de los badges ahora vienen del componente (no de ternarias inline), unificando colores en todo el sistema.

Puntos parciales:
- (ninguno)

Archivos modificados:
- app/Http/Controllers/DashboardController.php (TZ-aware)
- app/View/Components/StatusBadge.php (labelFor + cssClassFor)
- resources/views/admin/reports/index.blade.php (mojibake fix)
- 15 vistas con `<x-status-badge>` (ver detalle arriba)
- 3 formularios con `StatusBadge::labelFor()` en dropdowns
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Dashboard usa TZ por-empresa (no global `config/app.php`). Razon: cambiar el TZ global afectaria ~12 puntos del codigo y requeriria backfill de datos historicos. El fix por-empresa es quirurgico y respeta la decision arquitectonica "multiempresa con TZ propia".
- Mojibake se arreglo via script PHP que trabaja a nivel de bytes UTF-8 (en vez de PowerShell que tuvo problemas con encoding de stdin). Las secuencias son exactamente las bytes que generaria un re-encoding Latin-1 -> UTF-8 de un texto que ya era UTF-8 (clasico double-encoding).
- Componente vs helper: agregue metodos estaticos al componente existente en vez de crear `App\Support\StatusLabels`. Razon: una sola fuente de verdad. Si en futuro hay mas de 3 sitios que necesitan helpers, se extrae a Support.
- "labelFor" / "cssClassFor" en vez de "label" / "cssClass" estaticos: descubri en runtime que Blade extrae los public methods del componente como callables en el contexto del template, sobrescribiendo properties con el mismo nombre. El sufijo "For" evita el clash sin renombrar las properties (que ya estan en uso por el template `<span class="{{ $cssClass }}">{{ $label }}</span>`).
- Las clases ternarias `{{ $X === 'PAID' ? 'bg-success' : 'bg-warning' }}` se reemplazaron por el badge unificado. Costo: pierdes flexibilidad de colores por vista. Beneficio: cualquier status nuevo solo se agrega al componente y todas las vistas lo respetan; ademas, los colores son consistentes en todo el sistema.

Riesgos detectados:
- Reset de assertions visuales: si algun test feature buscaba "PAID" o "ACTIVE" literal en la vista, ahora encuentra "Pagado"/"Activo". La suite paso 122/122, asi que las pruebas existentes no afectadas. Pruebas futuras deben usar `StatusBadge::labelFor('PAID')` en sus aserciones.
- El bug global de TZ sigue afectando otros 10+ puntos (`AccountingService::createEntry()` con `now()->toDateString()`, `PayrollService`, `ReportController`, etc.). Si el usuario reporta "no me aparece el asiento de hoy en accounting" tras las 8 PM RD, es esa misma deuda. Ya esta documentado como proximo paso desde la sesion del comando `draws:generate-daily`.
- Si en el futuro alguien agrega un nuevo status al sistema sin tocar `LABELS` y `CLASSES` del componente, se mostrara el codigo en ingles (fallback). Mitigado: el fallback degrada elegantemente (status crudo + `bg-secondary`).

Validacion ejecutada:
- `php -l` sobre los 3 archivos PHP modificados (Dashboard, StatusBadge, 1 form): correcto.
- `php artisan test`: 122 passed, 819 assertions. Hubo 6 fallas iniciales por el clash de nombres `cssClass()`; tras renombrar a `cssClassFor()` paso todo.
- `vendor/bin/pint --dirty`: passed.
- Grep verificacion: cero `{{ \$X->status }}` crudo restante en `admin/`, cero mojibake en `views/`.

Proximo paso recomendado:
- Sesion dedicada al timezone global (deuda heredada). Audit completo de `now()->toDateString()` / `Carbon::today()` en services y controllers. La opcion mas limpia es introducir un helper `App\Support\Tz::companyToday(Company $c)` que reemplace todos los call sites, en lugar de cambiar `config/app.php` global (cambio que afecta datos historicos).
- Si tras esta release el cajero ve los sorteos abiertos en el dashboard, validar tambien los reportes operativos (sales-by-day, cash-summary) que probablemente tienen el mismo bug de TZ.

---

## 2026-05-21 - Timezone global a America/Santo_Domingo (resuelve deuda heredada)

Responsable:
- Claude

Fase trabajada:
- Transversal: cerrar la deuda de timezone que se identifico en sesiones previas (dashboard, sorteos, accounting, payroll, reportes).

Contexto del problema:
- El servidor PHP correo en UTC. Despues de las 8 PM hora RD, `now()->toDateString()` retornaba la fecha del dia siguiente (porque ya era medianoche UTC). Esto causaba que:
  - Dashboard mostrara "Sin sorteos abiertos hoy" aunque hubiera sorteos (arreglado previamente con fix por-empresa).
  - POS/API filtros `where draw_date >= today` perdieran los sorteos del dia.
  - Asientos contables (`entry_date`) y pagos de nomina (`paid_at`) se etiquetaran con la fecha del dia siguiente.
  - Reportes "de hoy" salieran vacios.
- Sesion anterior (2026-05-21): yo habia empezado a refactorizar con un helper `App\Support\Tz::companyToday($companyId)` para hacerlo "multiempresa correcto". El usuario me detuvo y pregunto si era la mejor solucion -- justa pregunta. La realidad: 1 empresa, en RD, sin planes inmediatos de operar en otro huso. El helper era overkill.

Decision tomada:
- Cambiar `config/app.php` timezone a `America/Santo_Domingo` (parametrizado via `APP_TIMEZONE` env var). Resuelve TODOS los call sites de `now()` de un solo plumazo, sin necesidad de tocar 10+ archivos.
- Si en el futuro hay otra empresa en TZ distinto (Florida, Anguila, etc.), se introduce el helper `Tz::companyToday()` solo entonces.

Puntos completados:
- [x] `config/app.php` -> `'timezone' => env('APP_TIMEZONE', 'America/Santo_Domingo')`.
- [x] `.env` y `.env.example` -> agregado `APP_TIMEZONE=America/Santo_Domingo` explicito.
- [x] Helper `App\Support\Tz` (creado en sesion anterior) eliminado.
- [x] Revertidos los call sites que ya habia migrado al helper en 4 controllers: `TicketController`, `MobileController`, `OfflineController`, `ApiController`. Vuelven a usar `now()->toDateString()` -- ahora correcto porque la TZ global ya es RD.
- [x] `DashboardController` simplificado: vuelve a `now()->toDateString()` y `now()->subDay()` (sin parametros TZ explicitos). El fix por-empresa de la sesion anterior queda redundante pero ya correcto.

Puntos parciales:
- (ninguno)

Archivos modificados:
- config/app.php
- .env
- .env.example
- app/Http/Controllers/DashboardController.php (simplificado)
- app/Http/Controllers/Admin/TicketController.php (revertido)
- app/Http/Controllers/Api/MobileController.php (revertido)
- app/Http/Controllers/Api/OfflineController.php (revertido)
- app/Http/Controllers/ApiController.php (revertido)
- app/Support/Tz.php (eliminado)
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- TZ global via `APP_TIMEZONE` env var en lugar de hardcoded en `config/app.php`. Razon: si en el futuro se despliega en otra TZ (testing en CI, server en otro pais), basta cambiar la env -- no hay que tocar codigo.
- Default `America/Santo_Domingo` en el codigo: garantiza que si alguien clona el repo sin setear la env, no se rompa.
- No se hizo backfill de datos historicos. Razon: los timestamps en SQLite se guardan como strings sin TZ (Laravel hace el casting al leer). Tras este cambio, `now()` retorna en RD y los nuevos registros se guardan en RD. Los registros viejos que se crearon en UTC siguen siendo strings, pero al hidratarse a Carbon ahora se interpretan como RD -- lo cual los desfasa 4 horas. Decision: aceptar ese desfase historico porque (a) operativamente el negocio empezo recien, hay pocos registros viejos; (b) el desfase es predictible (4h hacia atras) y no rompe nada funcional; (c) un backfill seria peligroso si algo se sale mal.
- Se conservaron las "tareas borradas" del helper en el sistema de tareas para auditoria de la decision.

Riesgos detectados:
- Datos historicos que se guardaron pre-cambio con TZ UTC se interpretaran ahora como RD. Para un cajero leyendo "vendido a las 21:35" hoy 21-may, si ese registro era originalmente 21:35 UTC = 17:35 RD, ahora se vera como 21:35 RD. Aceptable porque la operacion ya esta marchando en RD y el usuario espera ver horas RD.
- Si en futuro hay test que asuma UTC (raro, no encontramos ninguno), fallaria. La suite paso 122/122 confirmando.
- Si Laravel hace migraciones de fechas via `migrate` o algo similar al deploy, el cambio podria interpretar mal columnas datetime. Mitigado: las migraciones no usan TZ.
- Si en el futuro hay otra empresa en Florida (UTC-5) o California (UTC-8), el helper `Tz::companyToday()` debera reintroducirse, pero ahora con la base ya correcta para la empresa default.

Validacion ejecutada:
- `php artisan test`: 122 passed, 819 assertions. Cero regresiones.
- `vendor/bin/pint --dirty`: passed.
- Verificacion mental:
  1. Cajero abre dashboard a las 9 PM RD -> `now()->toDateString()` retorna "2026-05-21" (RD), no "2026-05-22" (UTC). Los sorteos del dia se ven.
  2. AccountingService crea asiento con `entry_date = 2026-05-21` (correcto), no "2026-05-22".
  3. POS web y movil filtran `draw_date >= 2026-05-21` (correcto).

Proximo paso recomendado:
- En produccion (VPS Windows), agregar `APP_TIMEZONE=America/Santo_Domingo` al `.env` y correr `php artisan config:cache`.
- Validar visualmente despues de las 8 PM RD que (a) dashboard sigue mostrando sorteos abiertos, (b) asientos contables nuevos tienen `entry_date` del dia local, (c) reporte "Ventas por dia" trae el dia local.

---

## 2026-05-21 - Jobs asincronos: liberacion de pagos en cola

Responsable:
- Claude

Fase trabajada:
- Fase 5: cierre del pendiente "Jobs asincronos para sorteos con alto volumen".

Contexto del problema:
- `CalculateWinnersJob` ya existia (sesion previa, despachado desde `ResultController::calculateWinners`).
- Pero `ResultController::authorizePayments` aun llamaba inline a `WinnerCalculationService::authorizePayments($draw, $user)`, que en sorteos grandes hace un UPDATE masivo de `winner_tickets` (PENDING_RELEASE -> RELEASED) y bloquea la peticion del admin que aprueba.
- Riesgo: con 500 sucursales y un sorteo nacional con miles de ganadores, el request puede exceder el timeout HTTP y dejar el sorteo en estado inconsistente.

Puntos completados:
- [x] Nuevo `App\Jobs\ReleasePaymentsJob(drawId, userId, notes)` que envuelve `WinnerCalculationService::authorizePayments`.
- [x] `ResultController::authorizePayments` ahora:
  - Valida sincronamente que existe una `PaymentAuthorization` PENDING (para retornar 4xx inmediato si no, en vez de fallar silente en el worker).
  - Despacha `ReleasePaymentsJob` con `auth()->id()` + notes opcional.
  - Retorna mensaje "Autorización enviada a cola. Los pagos se liberarán en segundos."

Puntos parciales:
- (ninguno)

Archivos creados/modificados:
- app/Jobs/ReleasePaymentsJob.php (nuevo)
- app/Http/Controllers/Admin/ResultController.php (despacha job + validacion previa)
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Validacion previa del PENDING fuera del job. Razon: si no hay autorizacion pendiente, queremos devolver el error en la respuesta HTTP del admin, no que el job falle en el worker y el admin nunca se entere. El job solo se despacha si el preflight pasa.
- Job idempotente por diseno: si por alguna razon se despacha dos veces, la segunda llamada a `authorizePayments` lanzara `RuntimeException("No hay autorización pendiente")` porque el status ya cambio a AUTHORIZED. El job lo manejara como falla y no duplicara pagos.
- No se agrego retry policy explicita: usa la default de Laravel (1 intento). Si el job falla, queda en `failed_jobs` y el admin puede reintentar manualmente desde la UI.
- `CalculateWinnersJob` ya existia con la misma estructura; mantuve consistencia (publico `int` drawId / userId, sin payload encryption).

Riesgos detectados:
- **Critico para produccion**: requiere worker activo. En `.env` actual `QUEUE_CONNECTION=database`. Sin `php artisan queue:work` corriendo, los jobs se acumulan en la tabla `jobs` y nunca se procesan. El usuario debe configurar Windows Task Scheduler con `queue:work --tries=3 --timeout=120` (ya documentado en Fase 10 como pendiente de produccion).
- En tests, `QUEUE_CONNECTION=sync` corre los jobs inmediatamente (por eso pasan 122/122 sin cambios).
- Si el job falla a mitad de camino (proceso muere), el WinnerTicket podria quedar parcialmente en RELEASED y parcialmente en PENDING_RELEASE. Mitigado por: el service hace `update()` masivo (atomico SQL), no row-by-row. Si la BD garantiza atomicidad (SQLite si para single statements), no hay estado partial.
- La notes del request llega al job pero el controller no las pide explicitamente en el form actual; queda como hook para futura extension (UI para "Razon de autorizacion").

Validacion ejecutada:
- `php -l` en los 2 archivos PHP: correcto.
- `php artisan test`: 122 passed, 819 assertions. Los tests existentes de SaleAndPrizeCycleTest (`prize_cannot_be_paid_before_authorization`, etc.) siguen pasando porque PHPUnit usa QUEUE sync.
- `vendor/bin/pint --dirty`: passed.

Proximo paso recomendado:
- En VPS Windows, configurar tarea de Windows Task Scheduler con accion: `C:\xampp\php\php.exe artisan queue:work --tries=3 --timeout=120 --queue=default`. Iniciar al arrancar Windows, reiniciar si falla. NSSM es alternativa mas robusta.
- Validar end-to-end en staging con un sorteo de prueba: declarar resultado -> calcularWinners (job 1) -> authorizePayments (job 2) -> los winner_tickets pasan a RELEASED en segundos sin bloquear la UI.

---

## 2026-05-21 - Cierre faltantes caja -> nomina: fix responsable + UI clarificada

Responsable:
- Claude

Fase trabajada:
- Fase 3 (caja) <-> Fase 9 (nomina): asegurar que el faltante de caja se atribuya correctamente al cajero responsable para que el descuento posterior en nomina sea correcto.

Contexto del problema:
- La integracion ya estaba implementada: `CashService` crea `CashIncident(type=CASH_SHORTAGE)` al cerrar caja con faltante; `PayrollService::buildEmployeeDetail` (linea 205) suma las incidencias RESOLVED del cajero en el periodo y las descuenta de `gross_pay`.
- Bug detectado al revisar el flujo: `CashService::createDifferenceIncident($session, ..., $closedBy, ...)` asignaba `user_id = $closedBy->id`. En el flujo web normal cajero = closedBy (cada cajero cierra su propia caja), pero si en el futuro un admin cierra la caja de un cajero (caso programatico, supervision), la incidencia se asignaba al admin y el descuento se imputaba al admin equivocado.
- UI listado de incidencias: la columna "Creada por" decia el `user_id` (que se interpretaba como creador), pero realmente es el RESPONSABLE del faltante. Confuso. Tampoco habia indicacion clara al admin de que resolver = descontar del cajero.
- Decision tomada por el usuario: mantener el flujo MANUAL (admin revisa y marca RESOLVED; el descuento se aplica al periodo de nomina). NO auto-RESOLVER.

Puntos completados:
- [x] Fix `CashService::createDifferenceIncident`: ahora usa `$session->user_id` (cajero responsable) en vez de `$closedBy->id`. Argumento renombrado de `$user` a `$closedBy` para claridad. Fallback defensivo a `$closedBy->id` solo si `$session->user_id` es null (no deberia ocurrir en flujo normal). Comentario explica el porque.
- [x] UI `admin/cash/incidents/index.blade.php` mejorada:
  - Nueva columna "Cajero responsable" separada de "Resuelta por" (antes ambos vivian en una sola columna "Responsables" confusa).
  - El nombre del cajero se muestra con `@username` para distinguir del admin.
  - Nueva columna "Caja" con icono y `#sessionId` para correlacionar con la sesion de caja.
  - Monto en rojo cuando es CASH_SHORTAGE (indicador visual del impacto).
  - Hint educativo cuando se va a resolver un CASH_SHORTAGE: "Al resolver, se descontará en la próxima nómina del cajero." Asi el admin entiende la consecuencia antes de confirmar.
  - Placeholder del textarea mas claro: "Nota de resolución (motivo, decisión, etc.)".
- [x] Test feature `cash_shortage_incident_is_assigned_to_cashier_not_to_admin_who_closed_session`:
  - Crea un cajero (rol CASHIER) y lo asocia a la sucursal.
  - Invoca directamente `CashService::open($branch, $cashier, ...)` y `CashService::close($session, $admin, ...)`.
  - Verifica que el `CashIncident` resultante tiene `user_id = cashier->id`, NO admin->id.
  - Razon de invocar el service directamente (no via HTTP): el controller actual (`CashController::close`) usa `getActiveSession($branchId, auth()->id())` que solo permite cerrar la caja propia del usuario logueado. El test demuestra la decision arquitectonica del service, que es donde vive el fix.

Puntos parciales:
- (ninguno)

Archivos modificados:
- app/Services/Cash/CashService.php (fix responsable)
- resources/views/admin/cash/incidents/index.blade.php (UI mejorada)
- tests/Feature/SaleAndPrizeCycleTest.php (nuevo test del fix)
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- **Mantener flujo manual de RESOLVED** (decision del usuario). Razon: el faltante puede tener causas que NO ameritan descuento al cajero (robo a mano armada, error de conteo en sesion previa, problema con factura, etc.). El admin debe investigar y registrar la decision en `resolution_notes` antes de aplicar el descuento. Esto da trazabilidad legal en Republica Dominicana donde el cajero puede impugnar el descuento.
- Hint educativo en la UI ("Al resolver se descontará...") en vez de un dialogo modal. Razon: mas suave, menos disruptivo, suficiente porque el textarea ya requiere `minlength=5`.
- Mostrar `#sessionId` como texto sin link. Razon: la ruta `admin.cash.show` (detalle por sesion) no existe todavia; crear esa pantalla es trabajo de Fase 9 separada. Mejor mostrar el ID como referencia que un link roto.
- Test del fix invoca el service directo en vez del controller. Razon: el flujo web no expone "admin cierra caja de otro" como feature; pero el fix protege contra ese caso si en el futuro se agrega (o si algun job lo hace programaticamente). El test es regression-guard del comportamiento del service.

Riesgos detectados:
- Si un admin nunca abre la pantalla de incidencias, los faltantes acumulados quedan OPEN y NUNCA se descuentan en nomina. Mitigacion: agregar un widget de "incidencias OPEN pendientes" al dashboard como indicador rojo. NO se hizo en esta sesion (scope acotado al fix + UI listado).
- El monto del descuento es 1:1 con el faltante. Si el faltante fue causado por algo NO imputable al cajero, el admin debe rechazar la incidencia (estado actual: solo hay OPEN y RESOLVED; no hay "DESESTIMADA"). Para "no descontar" hoy, el admin tiene que dejar la incidencia OPEN para siempre. Mitigacion futura: agregar transition `OPEN -> DISMISSED` con motivo. Anotado pero no implementado.
- El nuevo test usa el service directamente. Si en el futuro alguien refactoriza el service y cambia el signature de `close()`, el test fallara y forzara la revision del fix. Es lo deseado.

Validacion ejecutada:
- `php artisan test`: 123 passed, 823 assertions (+1 nuevo, sin regresiones).
- `vendor/bin/pint --dirty`: passed.
- Test especifico verde: `cash_shortage_incident_is_assigned_to_cashier_not_to_admin_who_closed_session`.

Proximo paso recomendado:
- Validar visualmente la pantalla `/admin/cash/incidents` con un cierre real con faltante. Confirmar que:
  (a) "Cajero responsable" muestra el nombre + @username del cajero correcto.
  (b) "Caja" muestra el ID con icono.
  (c) Al resolver, aparece el hint amarillo "Se descontará en la próxima nómina".
  (d) Tras resolver, el siguiente calculo de nomina (PayrollService) descuenta el monto en `cash_shortage` del cajero.
- Futuras mejoras anotadas: (i) widget dashboard con incidencias OPEN; (ii) transition `DISMISSED` para incidencias no imputables.

---

## 2026-05-21 - Widget dashboard: incidencias de caja pendientes

Responsable:
- Claude

Fase trabajada:
- Fase 3 / Dashboard: cerrar el riesgo "admin nunca abre la pantalla de incidencias -> faltantes nunca se descuentan al cajero".

Contexto del problema:
- En la sesion anterior se confirmo que el flujo `Caja -> Nomina` esta completo: `CashIncident(RESOLVED)` se descuenta automaticamente del cajero en el proximo periodo de nomina.
- Pero el flujo depende de que el admin VEA la pantalla de incidencias y ACTUE. Sin un recordatorio visible en el dashboard, los faltantes pueden quedarse OPEN para siempre y nunca aplicarse.
- Decision: agregar un widget KPI mas en la fila superior del dashboard que muestre la cantidad de incidencias OPEN + el monto total de faltantes pendientes. Borde rojo si hay alguna. Link directo al listado filtrado por status=OPEN.

Puntos completados:
- [x] `DashboardController` calcula 2 valores nuevos:
  - `$openIncidentsCount`: count de `CashIncident` con status=OPEN (filtrado por company + branch activo).
  - `$openShortageAmount`: suma del campo `amount` de los OPEN tipo CASH_SHORTAGE. Diferencia faltantes (que se descontaran) de sobrantes (que no), mostrando el monto que el cajero tiene en riesgo.
- [x] Widget "Incidencias" en `dashboard.blade.php`, en la fila superior junto a Sorteos/Por pagar/Resultados/Cancelados:
  - Visible solo si el usuario tiene permiso `cash.incidents.view` (cajeros NO lo ven; admins/supervisores si).
  - Icono `bi-exclamation-triangle` en fondo rojo translucido.
  - Count grande; si > 0, en color danger con borde 2px del card en danger.
  - Texto secundario: "Faltante: RD$ X" si hay; "Sin incidencias pendientes" si no.
  - Toda la card es clickable -> `route('admin.cash.incidents.index', ['status' => 'OPEN'])`.

Puntos parciales:
- (ninguno)

Archivos modificados:
- app/Http/Controllers/DashboardController.php (queries + variables a la vista)
- resources/views/dashboard.blade.php (widget nuevo entre Cancelados e fin de fila)
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Solo se muestra el monto de CASH_SHORTAGE (no CASH_SURPLUS). Razon: el sobrante no representa dinero del cajero en riesgo; el faltante si. Mostrar ambos juntos confunde sobre cuanto se va a descontar.
- Widget oculto si no hay permiso `cash.incidents.view`. Razon: cajeros y vendedores no necesitan ver este KPI; ademas no podrian resolver las incidencias.
- Card clickable completo en vez de un boton "Ver" separado. Razon: consistente con el patron del resto del dashboard (otras cards no son clickables, pero esta amerita action inmediato).
- No se agrega filtro por fecha. Razon: las incidencias OPEN se acumulan sin importar la fecha. Si el admin no las resolvio hace 3 dias, sigue siendo dinero pendiente -- el widget debe gritarlo.

Riesgos detectados:
- Si pasan semanas sin que el admin resuelva, el contador crece y crece sin metrica de "cuanto tiempo lleva pendiente". Mitigacion futura: agregar "Incidencia mas antigua: X dias" como texto secundario, no implementado por brevedad.
- El widget agrega 1 query mas al dashboard. Es 1 query con count + 1 query con sum (ambos sobre cash_incidents con indice por company_id/status que ya deberia existir). Negligible.

Validacion ejecutada:
- `php -l app/Http/Controllers/DashboardController.php`: correcto.
- `php artisan test`: 123 passed, 823 assertions. Sin regresiones.
- `vendor/bin/pint --dirty`: passed.

Proximo paso recomendado:
- Validar visualmente: con incidencias OPEN, el dashboard debe mostrar el widget rojo prominente. Click -> listado filtrado.
- Anotado como deuda futura: estado `DISMISSED` para incidencias no imputables al cajero. Hoy la unica forma de "no descontar" es dejar la incidencia OPEN para siempre, lo cual ensucia el contador del widget.

---

## 2026-05-21 - Estado DISMISSED para incidencias no imputables al cajero

Responsable:
- Claude

Fase trabajada:
- Fase 3 / Caja: cerrar la deuda anotada en la entrada anterior. Antes de hoy solo existian 2 estados terminales utiles (OPEN -> RESOLVED). Si el faltante no era culpa del cajero (robo, error administrativo, factura mal cobrada por terceros), la unica opcion era dejar la incidencia OPEN para siempre -- ensuciando el widget del dashboard y bloqueando el cierre operativo.

Contexto del problema:
- `PayrollService` ya filtra los `cash_shortage` por `status = RESOLVED` y `user_id = cajero`. Eso significa que el filtro de "que se descuenta" estaba bien, pero faltaba un estado terminal "cerrar sin descontar".
- Necesitabamos una transicion explicita `OPEN -> DISMISSED` con motivo obligatorio para que quede auditable.

Puntos completados:
- [x] `CashIncidentService::dismiss($incident, $user, $reason)`: transicion OPEN -> DISMISSED, marca `resolved_by` + `resolved_at`, guarda `resolution_notes`. Lanza `RuntimeException` si la incidencia no esta OPEN.
- [x] `CashIncidentDismissRequest`: validacion analoga al resolve (motivo required, min:5, max:1000).
- [x] `CashIncidentController::dismiss` + ruta `POST admin.cash.incidents.dismiss` bajo permiso `cash.incidents.resolve` (mismo permiso que resolve: ambas son acciones terminales del admin).
- [x] `StatusBadge`: label "Desestimada" + clase `bg-secondary` para `DISMISSED`.
- [x] UI: segundo formulario "Desestimar" en `admin/cash/incidents/index.blade.php` (debajo del de Resolver), con motivo obligatorio. Color secondary para diferenciar visualmente del Resolver verde.
- [x] Filtro de listado: nueva opcion "Desestimada" en el select de status.
- [x] Hint amarillo en la UI: "Resolver = descontar al cajero en la proxima nomina. Desestimar = no aplica descuento." (solo visible para CASH_SHORTAGE OPEN).
- [x] Audit log: accion `incident_dismissed` con descripcion explicita "(no se descontara al cajero)".
- [x] Tests:
  - `PayrollServiceTest::test_dismissed_cash_shortage_is_not_deducted`: confirma que DISMISSED NO descuenta en nomina (cash_shortage = 0).
  - `SaleAndPrizeCycleTest::cash_incident_can_be_dismissed_without_payroll_charge`: flujo HTTP end-to-end con audit trail.
  - `SaleAndPrizeCycleTest::dismissing_a_non_open_incident_returns_validation_error`: no se puede desestimar dos veces.

Puntos parciales:
- (ninguno)

Archivos modificados:
- app/Services/Cash/CashIncidentService.php (metodo `dismiss`)
- app/Http/Requests/Admin/CashIncidentDismissRequest.php (nuevo)
- app/Http/Controllers/Admin/CashIncidentController.php (metodo `dismiss`)
- routes/web.php (ruta `admin.cash.incidents.dismiss`)
- app/View/Components/StatusBadge.php (label + class para DISMISSED)
- resources/views/admin/cash/incidents/index.blade.php (filtro + UI form dismiss + hint)
- tests/Feature/PayrollServiceTest.php (test no-descuento DISMISSED)
- tests/Feature/SaleAndPrizeCycleTest.php (2 tests HTTP)
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Reutilizar el permiso `cash.incidents.resolve` para dismiss en lugar de crear `cash.incidents.dismiss`. Razon: quien puede cerrar una incidencia descontando dinero al cajero, puede tambien cerrarla sin descontarlo. Separar los permisos abre la puerta a un rol que puede SOLO desestimar (escape facil para encubrir faltantes reales). Mantener un unico permiso fuerza coherencia.
- Hint amarillo explicito en la UI antes de las acciones. Razon: la diferencia entre Resolver y Desestimar es PLATA REAL del cajero. Si el admin elige mal, o le descuenta injustamente al cajero, o encubre un faltante real. El hint es un cinturon de seguridad.
- Motivo requerido (min 5) en dismiss. Razon: sin motivo, el audit trail es inutil; con motivo, queda evidencia de por que se decidio no imputar al cajero.
- DISMISSED tambien marca `resolved_by` + `resolved_at`. Razon: el flujo de resolucion es identico semantica-mente ("alguien la cerro en tal momento"); reusar los campos evita una migracion y simplifica el modelo.
- Color secondary (gris) para el badge DISMISSED. Razon: success (verde) implica "se resolvio bien"; secondary (gris) implica "se cerro sin accion adicional". Es la semantica correcta.

Riesgos detectados:
- Un admin malicioso puede desestimar TODOS los faltantes para encubrir robos sistematicos. Mitigacion: queda audit log con motivo, fecha, autor; el dueno puede revisar `audit_logs WHERE action='incident_dismissed'` para deteccion. Mejora futura: reporte gerencial de "incidencias desestimadas por admin/mes" con monto total no descontado.
- No hay manera de "des-desestimar" una incidencia (reabrir). Por diseno: una vez que se documento el motivo de no imputar, cambiar de opinion seria un cambio de criterio que merece una NUEVA incidencia (caso de uso raro). Si surge necesidad, agregar un metodo `reopen` con permiso elevado.

Validacion ejecutada:
- `php artisan test`: 126 passed, 844 assertions (subio de 123 con los 3 nuevos tests).
- `vendor/bin/pint --dirty`: passed.

Proximo paso recomendado:
- Validar visualmente con un cierre real con faltante:
  (a) Aparece la pantalla con el hint amarillo y los dos botones (verde "Resolver" y gris "Desestimar").
  (b) Al desestimar, la incidencia pasa a DISMISSED y el siguiente periodo de nomina del cajero NO descuenta nada (`cash_shortage = 0`).
  (c) El widget del dashboard deja de contar esa incidencia (porque ya no esta OPEN).
- Mejora futura: reporte gerencial de "incidencias desestimadas" para detectar patrones (mismo admin desestimando faltantes del mismo cajero mes tras mes seria sospechoso).

---

## 2026-05-21 - POS Web: Combinar + Voltear (paridad con TicketPro)

Responsable:
- Claude

Fase trabajada:
- Fase 4 / POS web: agregar dos features de productividad que existen en TicketPro (referencia externa) y faltaban en nuestra pantalla de ventas.

Contexto del problema:
- TicketPro tiene "Combinar" (genera pales/tripletas/super-pales automaticamente desde los numeros del cart) y "Voltear" (invierte cada chunk de 2 digitos, ej. 12-34 -> 21-43). Son las dos features que mas tiempo ahorran al cajero.
- Sin Combinar, el cajero que quiere vender 5 numeros a quiniela + pale + tripleta tiene que escribir manualmente 5 + 10 (C(5,2)) + 10 (C(5,3)) = 25 jugadas. Con Combinar, 5 jugadas + un click.

Puntos completados:
- [x] Voltear: boton toggle en toolbar del cart. Genera para cada jugada su version invertida (`12-34` -> `21-43`). Capicuas y duplicados se saltan. Marca con `_flipped: true` (badge `<->` info). Toggle off restaura.
- [x] Combinar: boton + modal con checkboxes Pale/Tripleta/Super_Pale/Quiniela + monto por tipo + monto global fallback (default = promedio).
  - Pale: C(n,2) por loteria.
  - Tripleta: C(n,3) por loteria.
  - Super Pale: cross-product entre las 2 primeras loterias con numeros.
  - Quiniela: 1 jugada por numero unico por loteria (extraida de pales/tripletas existentes).
- [x] Preview en vivo en el modal: cada checkbox muestra cuantas jugadas se generaran antes de confirmar.
- [x] Dedup: si la jugada generada ya existe en el cart, suma el monto en vez de duplicar.
- [x] Marca `_combined: true` (badge `<>` primary) para distinguir auto-generadas.
- [x] Llama `checkPlayLimit()` sobre cada generada para validar contra limites.

Archivos modificados:
- resources/views/admin/tickets/sale.blade.php (toolbar, modal Combinar, badges, metodos Alpine)

Decisiones tomadas:
- Fuente para Combinar = numeros expandidos del cart (no input separado). Razon: cubre el flujo principal del cajero ("escribo 5 quinielas -> combinar") y evita complejidad de input dual. TicketPro Android tiene input separado; lo descartamos por simplicidad web.
- Voltear es one-shot: presiona = genera; presiona de nuevo = quita. No se aplica automaticamente a jugadas nuevas que se agreguen DESPUES. Coincide con TicketPro.
- Modal de Combinar reutiliza `pos-combinar-overlay` CSS — un overlay generico para futuros dialogs del POS.

Riesgos detectados:
- Combinar con muchos numeros genera explosion: 10 numeros = 45 pales + 120 tripletas. Si las jugadas exceden limite, todas se marcan con badge rojo. Sin mitigacion automatica; el cajero ve y borra las problematicas.
- Voltear no respeta limites: puede generar jugadas que excedan disponible. Se marca igual que cualquier jugada manual via `checkPlayLimit`.

Validacion ejecutada:
- `php artisan test`: 126 passed (sin tests JS para este cambio; el flujo es 100% client-side Alpine y depende de bet types reales).
- `vendor/bin/pint --dirty`: passed.

Proximo paso recomendado:
- Validar visualmente: cargar 5 quinielas, abrir Combinar, marcar Pale + Tripleta -> ver C(5,2)=10 pales y C(5,3)=10 tripletas con badge `<>`.
- Voltear: aplicar a 3 quinielas -> ver 3 jugadas mas con badge `<->`, presionar de nuevo -> volver a 3.

---

## 2026-05-21 - POS Web + Android: pre-check sincronico de limite por jugada

Responsable:
- Claude

Fase trabajada:
- Fase 4 / POS web + Fase 5 / App Android: cuando el cajero intenta jugar un monto que excede el disponible del numero, el flujo anterior solo mostraba "rechazado por el servidor" sin permitir ajustar. Ahora se ofrece ajustar al disponible.

Contexto del problema:
- Caso de uso real: numero 12 con limite RD$3000, ya vendido RD$2800, cajero intenta jugar RD$500. Antes: bloqueo silencioso o "rechazado" sin alternativa. Ahora: dialog dice "Disponible RD$200, jugar 200 o omitir".
- Bug latente descubierto: el check no consideraba lo ya pendiente en el cart. Dos veces el mismo numero por RD$200 c/u contra RD$200 disponibles se vendia parcialmente y el backend rechazaba al final.

Puntos completados:
- [x] Backend (Laravel):
  - Endpoint `GET /api/mobile/tickets/check-limit` en `MobileController::checkLimit` (paridad con `admin.tickets.check-limit`).
  - Ruta agregada bajo `auth:sanctum + device.authorized`.
  - Inyecta `LimitValidationService` en el constructor del controller mobile.
  - 3 tests `MobileSaleApiTest`: cerca-del-cap, agotado, sin reglas.
- [x] Web (Alpine.js):
  - `addPlay()` se vuelve async, hace pre-check paralelo (`Promise.all`) por cada draw seleccionado.
  - Helper `resolveLimitConflicts()` calcula disponible efectivo = backend - pendingInCart.
  - Helper `getCartPendingFor(drawId, betTypeId, numberValue)` suma lo ya en cart.
  - Modal "Limite alcanzado" con decisiones por draw: `ok` | `adjust` | `omit`.
  - Boton "Ajustar todo al disponible" para resolver en bulk.
  - Spinner en el input mientras hace pre-check.
  - Badge amarillo `↕` en jugadas ajustadas (`_limitAdjusted = true`).
- [x] Android (Kotlin Compose):
  - DTO `CheckLimitResponse(available, maxBet, blocked)` en `Dtos.kt`.
  - Metodo `checkLimit()` en `ApiService.kt` con `@GET("mobile/tickets/check-limit")`.
  - Metodo `TicketRepository.checkLimit()` con fallback a null si offline.
  - `JugadaItem` extendido con `flipped`, `combined`, `limitAdjusted`.
  - `SaleUiState` extendido con `voltearActivo`, `preCheckLoading`, `limitDialog`, `combinarDialog`.
  - `addJugada()` ahora dispara coroutine que llama `resolveLimitConflicts()` antes de agregar.
  - `applyLimitDecisions()`, `updateLimitDecision()`, `applyAllAdjust()`, `cancelLimitDialog()`.
  - `toggleVoltear()` con helper `flipNumberValue()`.
  - `openCombinarDialog()`, `submitCombinar()`, `combinarPreview()`, `canCombinarSuper()`, `expandNumbersByDraw()`.
  - `SaleScreen.kt`: `JugadasToolbar` con AssistChips Voltear/Combinar, `LimitConflictDialog`, `CombinarDialog`, badges `VOL`/`COMB`/`AJUST` en `JugadaRow`.
- [x] Tests: 3 `SaleAndPrizeCycleTest` (admin) + 3 `MobileSaleApiTest` (mobile) cubriendo escenarios de `check-limit`. Total 132/132 passed (subio de 126 con 6 nuevos).

Archivos modificados:
- app/Http/Controllers/Api/MobileController.php (constructor + metodo `checkLimit`)
- app/Http/Controllers/Admin/TicketController.php (sin cambios; ya existia el endpoint admin)
- routes/api.php (nueva ruta mobile)
- resources/views/admin/tickets/sale.blade.php (pre-check async, modal limite, badges)
- tests/Feature/SaleAndPrizeCycleTest.php (3 tests admin check-limit)
- tests/Feature/MobileSaleApiTest.php (3 tests mobile check-limit)
- android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/dto/Dtos.kt (`CheckLimitResponse`)
- android/app/src/main/java/dev/bsolutions/bsloteria/data/remote/ApiService.kt (metodo `checkLimit`)
- android/app/src/main/java/dev/bsolutions/bsloteria/data/repository/TicketRepository.kt (`checkLimit` con fallback IO)
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/sale/SaleViewModel.kt (~250 lineas nuevas)
- android/app/src/main/java/dev/bsolutions/bsloteria/ui/screen/sale/SaleScreen.kt (toolbar, 2 dialogs, badges)
- 02_GUIA_TODO_CONTROL_BSLOTTERY.md

Decisiones tomadas:
- Pre-check sincronico (vs post-check con boton "Ajustar" en la fila). Razon: corta el problema antes de que el cajero pierda tiempo, evita basura acumulada en el cart, alinea UX entre web y Android.
- Resta lo pendiente en cart del disponible reportado por backend. Razon: cierra el bug latente donde el backend solo conoce consumo confirmado.
- Sin red -> no bloquear, anadir y dejar que backend valide al vender. Razon: resiliencia operativa, el backend es la verdad final.
- Modal por jugada (no por draw): si seleccionas 3 loterias y entras una jugada que choca en 2, el modal muestra las 3 con decisiones independientes.
- Reutilizar permiso `cash.incidents.resolve` ya existente para mobile checkLimit no — el endpoint solo lee y no muta nada; basta con `auth:sanctum + device.authorized`.
- Mantener `checkPlayLimit` async post-add en web como red de seguridad: si otro cajero vende mientras el cart esta abierto, el badge se actualiza.
- Android: en lugar de Alert con HTML, dos AlertDialog Compose con FilterChips. UX nativa.
- Paridad casi total web<->android. Unica diferencia: web tiene "Ajustar todo" como boton dentro del modal; Android lo tiene como TextButton arriba de la lista.

Riesgos detectados:
- Latencia: `addPlay()` ahora hace N requests `check-limit` (1 por draw). En la practica ~50-150ms; si la conexion es lenta, se nota. Mitigacion: el spinner indica progreso, y `Promise.all`/`async` hace paralelo.
- Race condition: entre check y sell, otro cajero puede vender y bajar el disponible. Backend rechaza al final; cajero ve alert. Misma red de seguridad que antes.
- Android offline: `checkLimit` devuelve null si no hay red. Comportamiento: deja pasar la jugada y backend valida al sincronizar. NO se garantiza que la jugada se acepte; el cajero puede vender un ticket que se rechazara al sincronizar. Mejora futura: cache local de limites en Room.
- En Android no se hizo test instrumentado (no hay infra de tests UI Compose). Solo se valido el endpoint backend.

Validacion ejecutada:
- `php artisan test`: 132 passed, 866 assertions. Subio de 126 con los 6 nuevos tests.
- `vendor/bin/pint --dirty`: passed.
- Android: NO se compila desde aqui (memoria del usuario "el usuario compila por su cuenta"). El usuario debera ejecutar `gradlew assembleDebug` o equivalente.

Proximo paso recomendado:
- Web: configurar LimitRule (quiniela #34, max=3000), vender RD$2800, intentar jugar 12 RD$500 -> debe abrir el modal con "Disponible RD$200".
- Android: usuario compila y prueba el mismo escenario. Verificar que aparece el AlertDialog "Limite alcanzado" con los FilterChips.
- Mejora futura: cache local de limites en Room para que Android pueda pre-checkear offline.
- Mejora futura: si el cajero tiene varios pales en el cart contra el mismo numero (ej. dos veces 12-34 en draw X), el dedup actual los suma. Verificar que `getCartPendingFor` ya cubre este caso correctamente (lo cubre — suma todos los matches del cart).

---

## 2026-05-21 - Disponibilidad diaria de sorteos abiertos

Responsable:
- Codex

Fase trabajada:
- Fase 3/6: nucleo operativo de venta web y sincronizacion movil.

Puntos completados:
- [x] Se diagnostico por que el POS no mostraba loterias abiertas: no existian sorteos para `2026-05-21`; la base saltaba de `2026-05-19` a `2026-05-25`.
- [x] Se genero en la base local el calendario de sorteos para `2026-05-21` y `2026-05-22`.
- [x] `DrawGenerationService` ahora expone generacion idempotente por empresa para pantallas/API operativas.
- [x] La pantalla POS genera sorteos faltantes de hoy/manana antes de consultar sorteos.
- [x] La sincronizacion movil y el bootstrap offline generan sorteos faltantes antes de devolver catalogo.
- [x] La validacion de venta ya no compara solo la hora; ahora valida contra fecha+hora del sorteo.
- [x] Se agregaron pruebas para evitar que vuelva el fallo de catalogo diario faltante y el bug de sorteo futuro tratado como cerrado.

Sorteos abiertos confirmados en la base local al cierre de esta correccion:
- Florida Noche: cierre 21:50.
- PowerBall: cierre 22:59.
- Mega Millions: cierre 23:00.
- New York 11:30: cierre 23:30.

Archivos creados/modificados:
- `app/Services/Lottery/DrawGenerationService.php`
- `app/Http/Controllers/Admin/TicketController.php`
- `app/Http/Controllers/Api/MobileController.php`
- `app/Http/Controllers/Api/OfflineController.php`
- `app/Services/Sales/TicketSaleService.php`
- `tests/Feature/MobileSaleApiTest.php`
- `tests/Feature/SaleAndPrizeCycleTest.php`
- `02_GUIA_TODO_CONTROL_BSLOTTERY.md`

Decisiones tomadas:
- El scheduler sigue siendo el flujo principal (`draws:generate-daily` diario), pero POS/API movil/offline ahora hacen una generacion idempotente como red de seguridad. Esto evita que una banca quede sin venta si el scheduler no corrio en Windows/XAMPP.
- La validacion de cierre en venta usa fecha+hora completa del sorteo. Comparar solo `now()->format('H:i')` era incorrecto porque bloqueaba sorteos futuros con hora menor que la hora actual.
- Se generan dos dias desde las entradas operativas para cubrir hoy y manana sin crear volumen excesivo.

Riesgos detectados:
- Si el scheduler de Laravel no esta corriendo en Windows, los sorteos se generaran al entrar al POS/API, pero el cierre automatico por minuto tambien depende del scheduler. Debe configurarse `php artisan schedule:work` o tarea programada de Windows para operacion real.
- Persisten warnings de PHPUnit por metadata `@test` en doc-comments de pruebas antiguas; no falla hoy, pero debe migrarse antes de PHPUnit 12.

Validacion ejecutada:
- `php artisan draws:generate-daily --date=2026-05-21 --days=2`: 64 sorteos creados.
- `php -l` en archivos PHP modificados: correcto.
- `php artisan test tests\Feature\MobileSaleApiTest.php --filter=generates_missing_today`: 1 passed, 5 assertions.
- `php artisan test tests\Feature\SaleAndPrizeCycleTest.php --filter=future_draw`: 1 passed, 2 assertions.
- `php artisan test tests\Feature\MobileSaleApiTest.php tests\Feature\SaleAndPrizeCycleTest.php`: 47 passed, 435 assertions.
- `vendor\bin\pint --dirty`: fixed/correcto.
- `php artisan route:list --except-vendor`: correcto, 200 rutas.
- `php artisan test`: 144 passed, 894 assertions.

Proximo paso recomendado:
- Configurar el scheduler real en Windows/XAMPP para que `draws:generate-daily` y `draws:auto-close` corran sin depender de que alguien abra el POS.

---

# Plantilla para futuras actualizaciones

Copiar esta plantilla cada vez que una IA o desarrollador avance.

```text
## FECHA â€” TÃ­tulo del avance

Responsable:

Fase trabajada:

Puntos completados:
- [x]
- [x]

Puntos parciales:
- [~]

Archivos creados/modificados:
- ruta/archivo
- ruta/archivo

Decisiones tomadas:
- decisiÃ³n

Riesgos detectados:
- riesgo

Preguntas pendientes:
- pregunta

PrÃ³ximo paso recomendado:
- prÃ³ximo paso
```
