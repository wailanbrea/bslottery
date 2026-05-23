# BSLotery — Reglas de Lotería: Documento de Revisión para VPS

> **Propósito:** Este documento contiene todas las reglas de negocio vigentes en el sistema local.
> Una IA debe comparar estas reglas con la configuración actual en el VPS (base de datos SQLite)
> y corregir cualquier diferencia en: tipos de jugada, multiplicadores de pago, límites de apuesta,
> loterías y sorteos.
>
> **Solo actualizar:** `bet_types`, `payout_rules`, `limit_rules`, `lotteries`, `draws`.
> No tocar tickets existentes, resultados, ganadores ni movimientos de caja.

---

## 1. TIPOS DE JUGADA (`bet_types`)

Cuatro tipos activos por empresa. `digits_count = 2` para todos.

| code | name | numbers_count | digits_count | requires_position | is_cross_lottery | status |
|------|------|:---:|:---:|:---:|:---:|:---:|
| `QUINIELA` | Quiniela | 1 | 2 | false | false | ACTIVE |
| `PALE` | Pale | 2 | 2 | false | false | ACTIVE |
| `TRIPLETA` | Tripleta | 3 | 2 | **true** | false | ACTIVE |
| `SUPER_PALE` | Super Pale | 2 | 2 | false | **true** | ACTIVE |

**Regla de validación al vender:**
- `QUINIELA`: exactamente 1 número de 2 dígitos (00–99).
- `PALE`: exactamente 2 números de 2 dígitos separados por guion o espacio.
- `TRIPLETA`: exactamente 3 números de 2 dígitos; requiere posición (`EXACT`).
- `SUPER_PALE`: exactamente 2 números; aplica a **dos loterías distintas** (cross-lottery).

---

## 2. REGLAS DE PAGO (`payout_rules`)

El multiplicador se aplica sobre el monto apostado: `premio = monto × multiplicador`.

### 2.1 Quiniela

| position | payout_multiplier | Descripción |
|----------|:-----------------:|-------------|
| `FIRST`  | **72** | Coincide con el 1.er número ganador |
| `SECOND` | **12** | Coincide con el 2.º número ganador |
| `THIRD`  | **4**  | Coincide con el 3.er número ganador |

> Si un número coincide con más de una posición en el mismo sorteo, **se acumulan** los premios (ej: 1ª + 2ª = 84×).

### 2.2 Pale

| position | payout_multiplier | Aplica a |
|----------|:-----------------:|----------|
| `FIRST`  | **1500** | Pale 1ª-2ª o Pale 1ª-3ª |
| `SECOND` | **100**  | Pale 2ª-3ª |

**Lógica de evaluación (orden):**
1. ¿Los 2 números del jugador están en {1ª, 2ª}? → paga FIRST (1500×).
2. ¿Los 2 números del jugador están en {2ª, 3ª}? → paga SECOND (100×).
3. ¿Los 2 números del jugador están en {1ª, 3ª} **y** 2ª ≠ 3ª? → paga FIRST (1500×).
4. Las tres condiciones pueden acumularse si se cumplen varias.

> **Números duplicados:** Si el jugador apostó `00-00`, necesita que tanto 1ª como 2ª sean `00`.

### 2.3 Tripleta

| position | payout_multiplier | Descripción |
|----------|:-----------------:|-------------|
| `EXACT`  | **20 000** | Los 3 números coinciden con {1ª, 2ª, 3ª} (multiset) |
| `ANY`    | **100**    | Exactamente 2 de los 3 números coinciden (tripleta pata) |

> `0` o `1` aciertos = pierde. La coincidencia es por **multiplicidad** (multiset), no por posición exacta.

### 2.4 Super Pale

| position | payout_multiplier | Descripción |
|----------|:-----------------:|-------------|
| `FIRST`  | **1500** | 1.er número = 1ª de lotería A **y** 2.º número = 1ª de lotería B |

> Requiere que ambas loterías tengan resultado `CONFIRMED` para calcular.

---

## 3. LÍMITES DE APUESTA (`limit_rules`)

Límites globales predeterminados por empresa (scope `PER_BRANCH`, `rule_type = GLOBAL`).
No están ligados a una lotería ni sorteo específico (`lottery_id = null`, `draw_id = null`).
Política: `BLOCK_FULL` (la jugada entera se rechaza si supera el límite).

| Tipo de Jugada | max_amount_per_number | policy |
|---------------|:---------------------:|--------|
| QUINIELA | **RD$ 3,000.00** | BLOCK_FULL |
| PALE | **RD$ 200.00** | BLOCK_FULL |
| TRIPLETA | **RD$ 50.00** | BLOCK_FULL |
| SUPER_PALE | **RD$ 100.00** | BLOCK_FULL |

### 3.1 Tipos de regla de límite disponibles

| rule_type | Descripción |
|-----------|-------------|
| `GLOBAL` | Aplica a todos los números del tipo de jugada |
| `SINGLE_NUMBER` | Aplica a un número exacto (ej: solo el `25`) |
| `NUMBER_RANGE` | Aplica a un rango (ej: `00` hasta `50`) |
| `NUMBER_LIST` | Aplica a una lista específica de números |

### 3.2 Políticas al exceder límite

| policy | Comportamiento |
|--------|---------------|
| `BLOCK_FULL` | Rechaza toda la jugada |
| `ALLOW_AVAILABLE` | Permite solo el monto disponible restante |
| `REQUEST_AUTHORIZATION` | No bloquea, pero crea solicitud de aprobación |

### 3.3 Cómo se calcula el consumo

```
consumo_efectivo = sold_amount + reserved_offline_amount - cancelled_amount
disponible = max_amount_per_number - consumo_efectivo
```

- **Scope `PER_BRANCH`** (default): cada sucursal tiene su propio contador.
- **Scope `COMPANY`**: suma el consumo de **todas** las sucursales de la empresa.

---

## 4. LOTERÍAS (`lotteries`)

32 loterías activas. Columnas clave: `code` (único por empresa), `name`, `country`, `status = ACTIVE`.

### 4.1 República Dominicana (country = `DO`)

| code | name | Hora cierre |
|------|------|:-----------:|
| `NAC-GANAMAS` | Gana Más | 14:30 |
| `LOTNAC` | Lotería Nacional | 21:00 |
| `LEIDSA-QUINIELA` | Quiniela Leidsa | 20:55 |
| `LEIDSA-PEGA3` | Pega 3 Más | 20:55 |
| `LEIDSA-LOTOPOOL` | Loto Pool Leidsa | 20:55 |
| `LEIDSA-SUPERKINO` | Super Kino TV | 20:55 |
| `LEIDSA-LOTO` | Loto - Loto Más | 20:55 |
| `REAL-QUINIELA` | Quiniela Real | 12:55 |
| `REAL-LOTOPOOL` | Loto Pool Real | 12:55 |
| `REAL-LOTO` | Loto Real | 12:55 |
| `LOTEKA-QUINIELA` | Quiniela Loteka | 19:55 |
| `LOTEKA-MEGACHANCES` | Mega Chances | 19:55 |
| `LOTEKA-MEGALOTTO` | Mega Lotto | 19:55 |
| `PRIMERA-DIA` | La Primera Día | 12:00 |
| `PRIMERA-NOCHE` | Primera Noche | 20:00 |
| `PRIMERA-LOTO5` | Loto 5 | 20:00 |
| `SUERTE-MD` | La Suerte MD | 12:30 |
| `SUERTE-6PM` | La Suerte 6PM | 18:00 |
| `LOTEDOM` | LoteDom | 13:55 |
| `QUEMAITO-MAYOR` | El Quemaito Mayor | 13:55 |
| `KING-1230` | King Lottery 12:30 | 12:30 |
| `KING-730` | King Lottery 7:30 | 19:30 |

### 4.2 Anguila (country = `AI`)

| code | name | Hora cierre |
|------|------|:-----------:|
| `ANGUILA-10AM` | Anguila 10:00 AM | 10:00 |
| `ANGUILA-1PM` | Anguila 1:00 PM | 13:00 |
| `ANGUILA-6PM` | Anguila 6:00 PM | 18:00 |
| `ANGUILA-9PM` | Anguila 9:00 PM | 21:00 |

### 4.3 Estados Unidos (country = `US`)

| code | name | Hora cierre |
|------|------|:-----------:|
| `FLORIDA-DIA` | Florida Día | 13:30 |
| `FLORIDA-NOCHE` | Florida Noche | 21:50 |
| `NY-330` | New York 3:30 | 15:30 |
| `NY-1130` | New York 11:30 | 23:30 |
| `MEGAMILLIONS` | Mega Millions | 23:00 |
| `POWERBALL` | PowerBall | 22:59 |

### 4.4 Loterías retiradas

| code | Acción |
|------|--------|
| `NAC-NOCHE` | Marcar `status = INACTIVE`; cerrar sorteos OPEN activos |

---

## 5. SORTEOS (`draws`)

Los sorteos se crean diariamente por el seeder `DominicanLotteryCatalogSeeder`.

**Campos clave por sorteo:**

| Campo | Valor |
|-------|-------|
| `status` | `OPEN` al crear; `CLOSED` cuando se cierra |
| `close_time` | Igual que `scheduled_time` (hora de cierre de la lotería) |
| `open_time` | `08:00` (apertura estándar) |
| `draw_date` | Fecha del sorteo (hoy) |
| `name` | Nombre descriptivo del sorteo (ej: `Gana Más 2:30 PM`) |

**Nombres de sorteo esperados** (según catálogo):

| code | draw name |
|------|-----------|
| `NAC-GANAMAS` | Gana Más 2:30 PM |
| `LOTNAC` | Nacional 9:00 PM |
| `LEIDSA-QUINIELA` | Leidsa 8:55 PM |
| `LEIDSA-PEGA3` | Pega 3 Más |
| `LEIDSA-LOTOPOOL` | Loto Pool |
| `LEIDSA-SUPERKINO` | Super Kino TV |
| `LEIDSA-LOTO` | Loto - Loto Más |
| `REAL-QUINIELA` | Real 12:55 PM |
| `REAL-LOTOPOOL` | Loto Pool Real |
| `REAL-LOTO` | Loto Real |
| `LOTEKA-QUINIELA` | Loteka 7:55 PM |
| `LOTEKA-MEGACHANCES` | Mega Chances |
| `LOTEKA-MEGALOTTO` | Mega Lotto |
| `PRIMERA-DIA` | La Primera 12:00 PM |
| `PRIMERA-NOCHE` | La Primera 8:00 PM |
| `PRIMERA-LOTO5` | Loto 5 |
| `SUERTE-MD` | La Suerte 12:30 PM |
| `SUERTE-6PM` | La Suerte 6:00 PM |
| `LOTEDOM` | LoteDom 1:55 PM |
| `QUEMAITO-MAYOR` | El Quemaito Mayor |
| `KING-1230` | King Lottery 12:30 |
| `KING-730` | King Lottery 7:30 |
| `ANGUILA-10AM` | Anguila 10:00 AM |
| `ANGUILA-1PM` | Anguila 1:00 PM |
| `ANGUILA-6PM` | Anguila 6:00 PM |
| `ANGUILA-9PM` | Anguila 9:00 PM |
| `FLORIDA-DIA` | Florida Día |
| `FLORIDA-NOCHE` | Florida Noche |
| `NY-330` | New York 3:30 |
| `NY-1130` | New York 11:30 |
| `MEGAMILLIONS` | Mega Millions |
| `POWERBALL` | PowerBall |

---

## 6. LÓGICA DE CÁLCULO DE GANADORES

### 6.1 Normalización de números

Todo número se normaliza a **2 dígitos**: `"1"` → `"01"`, `"64"` → `"64"`, `""` → `"00"`.

### 6.2 Quiniela

```
nums = [N] (1 número)
G = [1ª, 2ª, 3ª]

si N == G[0] → premio += monto × mult(FIRST)
si N == G[1] → premio += monto × mult(SECOND)
si N == G[2] → premio += monto × mult(THIRD)
si premio > 0 → WINNER, sino → LOSER
```

### 6.3 Pale

```
nums = [A, B] (2 números)
G = [1ª, 2ª, 3ª]

si {A,B} ⊆ {G[0],G[1]} (multiset) → premio += monto × mult(FIRST)    # pale 1ª-2ª
si {A,B} ⊆ {G[1],G[2]} (multiset) → premio += monto × mult(SECOND)   # pale 2ª-3ª
si G[1]≠G[2] y {A,B} ⊆ {G[0],G[2]} (multiset) → premio += monto × mult(FIRST)  # pale 1ª-3ª
si premio > 0 → WINNER, sino → LOSER
```

> Multiset: si A = B (ej: `00-00`), se necesitan **dos** ocurrencias del mismo número en {G[0],G[1]}.

### 6.4 Tripleta

```
nums = [A, B, C] (3 números)
G = [1ª, 2ª, 3ª]

coincidencias = intersección_multiset(nums, G)  # cuenta duplicados

si coincidencias == 3 → WINNER, premio = monto × mult(EXACT)   # tripleta exacta
si coincidencias == 2 → WINNER, premio = monto × mult(ANY)     # tripleta pata
si coincidencias <= 1 → LOSER
```

### 6.5 Super Pale

```
nums = [A, B] (2 números)
G_loteria1 = [1ª, 2ª, 3ª]  # resultado de la 1ª lotería
G_loteria2 = [1ª, ...]      # resultado de la 2ª lotería

si A == G_loteria1[0] y B == G_loteria2[0] → WINNER, premio = monto × mult(FIRST)
(o si B == G_loteria1[0] y A == G_loteria2[0])
sino → LOSER

Requisito: ambas loterías deben tener resultado CONFIRMED
```

---

## 7. ESTADOS Y FLUJOS

### 7.1 Estados de un sorteo (`draws.status`)

```
OPEN → CLOSED → (resultado) REGISTERED → CONFIRMED
                                        ↓
                              CALCULATING_WINNERS
                                        ↓
                              WINNERS_CALCULATED
                                        ↓
                              PAYMENTS_RELEASED
                                        ↓
                                   FINALIZED
```

### 7.2 Estados de un ticket detail (`ticket_details.status`)

| status | Significado |
|--------|-------------|
| `ACTIVE` | Jugada activa, esperando resultado |
| `WINNER` | Ganó |
| `LOSER` | Perdió |
| `CANCELLED` | Anulado |

### 7.3 Premio retenido (HELD)

Si `prize_amount >= company.big_prize_threshold` → `winner_tickets.status = HELD` (requiere aprobación manual antes de liberar).

---

## 8. RESOLUCIÓN DE REGLAS DE PAGO (prioridad)

El sistema busca la regla más específica en este orden (de mayor a menor prioridad):

1. Sucursal + Sorteo + Tipo de jugada + Posición
2. Sucursal + Lotería + Tipo de jugada + Posición
3. Sucursal + Tipo de jugada + Posición
4. Empresa + Sorteo + Tipo de jugada + Posición
5. Empresa + Lotería + Tipo de jugada + Posición
6. **Empresa + Tipo de jugada + Posición** ← regla base/global

Si no existe ninguna regla activa para ese tipo de jugada → error de venta.

---

## 9. INSTRUCCIONES PARA LA IA REVISORA

### Lo que debes hacer en el VPS:

1. **Verificar `bet_types`:** Confirmar que existen los 4 tipos (`QUINIELA`, `PALE`, `TRIPLETA`, `SUPER_PALE`) con los valores correctos de la sección 1. Crear o corregir si difieren.

2. **Verificar `payout_rules`:** Para cada empresa en el VPS, confirmar que existen reglas de pago activas con los multiplicadores exactos de la sección 2. Si faltan o tienen valores distintos, corregir. Las reglas deben tener:
   - `status = ACTIVE`
   - `effective_from` = fecha pasada (no futura)
   - `effective_to = null` (sin vencimiento)
   - `branch_id = null`, `lottery_id = null`, `draw_id = null` (globales)

3. **Verificar `limit_rules`:** Para cada empresa, confirmar que existen reglas globales con los límites de la sección 3. Crear o corregir si difieren.

4. **Verificar `lotteries`:** Confirmar que las 32 loterías de la sección 4 existen con `status = ACTIVE` y los nombres correctos. La lotería `NAC-NOCHE` debe estar `INACTIVE`.

5. **Verificar `draws` de hoy:** Si hay sorteos OPEN con `close_time` incorrecto o nombre incorrecto, actualizar según la sección 5.

### Lo que NO debes tocar:

- Tablas: `tickets`, `ticket_details`, `results`, `winner_tickets`, `payment_authorizations`
- Tablas: `cash_sessions`, `cash_movements`, `accounting_entries`
- Tablas: `users`, `companies`, `branches`, `employees`
- Tablas: `limit_consumptions` (se recalcula automáticamente)
- Cualquier dato con `status = CANCELLED` o que tenga tickets asociados

### Cómo verificar que está correcto:

```sql
-- Verificar bet_types
SELECT code, name, numbers_count, requires_position, is_cross_lottery, status
FROM bet_types WHERE status = 'ACTIVE';

-- Verificar payout_rules globales
SELECT bt.code, pr.position, pr.payout_multiplier, pr.status
FROM payout_rules pr
JOIN bet_types bt ON bt.id = pr.bet_type_id
WHERE pr.branch_id IS NULL AND pr.lottery_id IS NULL AND pr.draw_id IS NULL
  AND pr.status = 'ACTIVE'
ORDER BY bt.code, pr.position;

-- Verificar limit_rules globales
SELECT bt.code, lr.rule_type, lr.max_amount_per_number, lr.policy, lr.status
FROM limit_rules lr
JOIN bet_types bt ON bt.id = lr.bet_type_id
WHERE lr.branch_id IS NULL AND lr.lottery_id IS NULL AND lr.draw_id IS NULL
  AND lr.rule_type = 'GLOBAL' AND lr.status = 'ACTIVE'
ORDER BY bt.code;

-- Verificar loterías
SELECT code, name, country, status FROM lotteries ORDER BY country, code;
```

### Valores esperados tras la corrección:

```
payout_rules (globales, sin branch/lottery/draw):
  QUINIELA  FIRST   → 72
  QUINIELA  SECOND  → 12
  QUINIELA  THIRD   → 4
  PALE      FIRST   → 1500
  PALE      SECOND  → 100
  TRIPLETA  EXACT   → 20000
  TRIPLETA  ANY     → 100
  SUPER_PALE FIRST  → 1500

limit_rules (globales, GLOBAL, BLOCK_FULL):
  QUINIELA   → 3000.00
  PALE       → 200.00
  TRIPLETA   → 50.00
  SUPER_PALE → 100.00
```

---

*Generado el 2026-05-22 desde el código fuente local de `C:\xampp\php\www\BSLotery`.*
*Archivos fuente: `DominicanLotteryCatalog.php`, `DefaultLimitRuleSeeder.php`, `DemoDataSeeder.php`,*
*`WinnerCalculationService.php`, `PayoutResolverService.php`, `LimitValidationService.php`.*
