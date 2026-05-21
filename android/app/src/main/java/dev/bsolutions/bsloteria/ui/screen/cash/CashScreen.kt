package dev.bsolutions.bsloteria.ui.screen.cash

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.AccountBalanceWallet
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.CloudOff
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.LockOpen
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Remove
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import dev.bsolutions.bsloteria.data.remote.dto.CashMovementDto
import dev.bsolutions.bsloteria.data.remote.dto.CashSessionDto
import dev.bsolutions.bsloteria.domain.model.Denomination
import dev.bsolutions.bsloteria.domain.model.DenominationKind
import dev.bsolutions.bsloteria.domain.model.Denominations
import java.math.BigDecimal
import java.math.RoundingMode
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CashScreen(
    viewModel: CashViewModel,
    onBack: () -> Unit,
) {
    val state by viewModel.state.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    val lifecycleOwner = LocalLifecycleOwner.current

    LaunchedEffect(state.successMessage) {
        state.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearSuccessMessage()
        }
    }

    // Auto-refresh cuando la pantalla vuelve a primer plano (ej. cajero
    // alterna entre Caja y Venta o sale a otra app y regresa).
    DisposableEffect(lifecycleOwner) {
        val observer = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_START) {
                viewModel.refresh()
            }
        }
        lifecycleOwner.lifecycle.addObserver(observer)
        onDispose { lifecycleOwner.lifecycle.removeObserver(observer) }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Caja", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, null)
                    }
                },
                actions = {
                    IconButton(onClick = viewModel::refresh, enabled = !state.isLoading) {
                        Icon(Icons.Default.Refresh, "Refrescar")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    navigationIconContentColor = MaterialTheme.colorScheme.onPrimary,
                    actionIconContentColor = MaterialTheme.colorScheme.onPrimary,
                )
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { padding ->
        Column(
            Modifier
                .fillMaxSize()
                .padding(padding)
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            if (state.isLoading && !state.initialLoadDone) {
                Box(Modifier.fillMaxWidth().padding(top = 48.dp), Alignment.Center) {
                    CircularProgressIndicator()
                }
                return@Column
            }

            BranchHeader(branchName = state.branchName)

            SyncStatusBanner(
                isOfflineCache = state.isOfflineCache,
                cachedAtMillis = state.cachedAtMillis,
                lastSyncedMillis = state.lastSyncedMillis,
            )

            state.error?.let { err ->
                ErrorBanner(err, onDismiss = viewModel::clearError)
            }

            when {
                !state.cashControlEnabled -> NoCashControlCard()
                state.session?.status == "OPEN" -> {
                    OpenSessionCard(
                        session = state.session!!,
                        countedCashInput = state.countedCashInput,
                        notesInput = state.notesInput,
                        isSubmitting = state.isSubmitting,
                        closeMode = state.closeMode,
                        denominations = state.denominations,
                        onCountedChange = viewModel::onCountedCashChange,
                        onNotesChange = viewModel::onNotesChange,
                        onClose = viewModel::closeCash,
                        onCloseModeChange = viewModel::setCloseMode,
                        onDenominationChange = viewModel::setDenominationQty,
                        onDenominationIncrement = viewModel::incrementDenomination,
                        onClearDenominations = viewModel::clearDenominations,
                    )
                    MovementsCard(movements = state.movements)
                }
                state.session != null -> ClosedSessionCard(session = state.session!!)
                else -> OpenCashForm(
                    openingAmount = state.openingAmountInput,
                    notes = state.notesInput,
                    isSubmitting = state.isSubmitting,
                    onAmountChange = viewModel::onOpeningAmountChange,
                    onNotesChange = viewModel::onNotesChange,
                    onSubmit = viewModel::openCash,
                )
            }
        }
    }
}

@Composable
private fun BranchHeader(branchName: String?) {
    Card(elevation = CardDefaults.cardElevation(2.dp)) {
        Row(
            Modifier.fillMaxWidth().padding(12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Icon(
                Icons.Default.AccountBalanceWallet, null,
                tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(28.dp)
            )
            Column {
                Text("Sucursal", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Text(
                    branchName ?: "—",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
            }
        }
    }
}

@Composable
private fun ErrorBanner(message: String, onDismiss: () -> Unit) {
    Surface(
        color = MaterialTheme.colorScheme.errorContainer,
        modifier = Modifier.fillMaxWidth()
    ) {
        Row(
            Modifier.padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                message,
                Modifier.weight(1f),
                color = MaterialTheme.colorScheme.onErrorContainer,
                style = MaterialTheme.typography.bodySmall
            )
            TextButton(onClick = onDismiss) { Text("OK") }
        }
    }
}

@Composable
private fun NoCashControlCard() {
    Card {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
            Text(
                "Esta sucursal no tiene control de caja habilitado.",
                fontWeight = FontWeight.SemiBold
            )
            Text(
                "Puedes vender directamente sin abrir caja. Si necesitas usar caja, contacta a tu administrador.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
private fun OpenCashForm(
    openingAmount: String,
    notes: String,
    isSubmitting: Boolean,
    onAmountChange: (String) -> Unit,
    onNotesChange: (String) -> Unit,
    onSubmit: () -> Unit,
) {
    Card {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Default.LockOpen, null, tint = MaterialTheme.colorScheme.primary)
                Spacer(Modifier.width(6.dp))
                Text(
                    "Abrir caja",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
            }
            Text(
                "Indica el monto inicial de efectivo (fondo) con el que abres la caja.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )

            OutlinedTextField(
                value = openingAmount,
                onValueChange = onAmountChange,
                label = { Text("Monto inicial (RD$)") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                modifier = Modifier.fillMaxWidth(),
                enabled = !isSubmitting
            )

            OutlinedTextField(
                value = notes,
                onValueChange = onNotesChange,
                label = { Text("Notas (opcional)") },
                modifier = Modifier.fillMaxWidth(),
                minLines = 2,
                maxLines = 4,
                enabled = !isSubmitting
            )

            Button(
                onClick = onSubmit,
                enabled = !isSubmitting && openingAmount.isNotBlank(),
                modifier = Modifier.fillMaxWidth().height(48.dp)
            ) {
                if (isSubmitting) {
                    CircularProgressIndicator(
                        Modifier.size(20.dp), strokeWidth = 2.dp,
                        color = MaterialTheme.colorScheme.onPrimary
                    )
                } else {
                    Text("ABRIR CAJA", fontWeight = FontWeight.Bold, letterSpacing = 1.sp)
                }
            }
        }
    }
}

@Composable
private fun OpenSessionCard(
    session: CashSessionDto,
    countedCashInput: String,
    notesInput: String,
    isSubmitting: Boolean,
    closeMode: CashCloseMode,
    denominations: Map<String, Int>,
    onCountedChange: (String) -> Unit,
    onNotesChange: (String) -> Unit,
    onClose: () -> Unit,
    onCloseModeChange: (CashCloseMode) -> Unit,
    onDenominationChange: (String, Int) -> Unit,
    onDenominationIncrement: (String, Int) -> Unit,
    onClearDenominations: () -> Unit,
) {
    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer)) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Default.CheckCircle, null, tint = MaterialTheme.colorScheme.primary)
                Spacer(Modifier.width(6.dp))
                Text(
                    "Caja abierta",
                    fontWeight = FontWeight.Bold,
                    style = MaterialTheme.typography.titleMedium,
                    color = MaterialTheme.colorScheme.onPrimaryContainer
                )
            }
            AmountRow("Fondo inicial", session.openingAmount)
            AmountRow("Ventas", session.salesTotal)
            AmountRow("Premios pagados", "-" + session.prizesPaidTotal)
            AmountRow("Cancelaciones", "-" + session.cancellationsTotal)
            AmountRow("Entradas (CASH IN)", session.cashInTotal)
            AmountRow("Salidas / Gastos", "-" + addAmounts(session.cashOutTotal, session.expensesTotal))
            HorizontalDivider()
            AmountRow(
                "Efectivo esperado",
                session.expectedCash,
                emphasized = true
            )
        }
    }

    Card {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Text(
                "Cerrar caja",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )

            CloseModeSelector(selected = closeMode, onChange = onCloseModeChange, enabled = !isSubmitting)

            when (closeMode) {
                CashCloseMode.AMOUNT -> {
                    Text(
                        "Indica el efectivo físico en caja. Si lo dejas vacío se usa el efectivo esperado.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    OutlinedTextField(
                        value = countedCashInput,
                        onValueChange = onCountedChange,
                        label = { Text("Efectivo contado (RD$)") },
                        placeholder = { Text(session.expectedCash) },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !isSubmitting
                    )
                }
                CashCloseMode.DENOMINATIONS -> {
                    DenominationsCounter(
                        denominations = denominations,
                        expected = session.expectedCash,
                        enabled = !isSubmitting,
                        onChange = onDenominationChange,
                        onIncrement = onDenominationIncrement,
                        onClear = onClearDenominations,
                    )
                }
            }

            OutlinedTextField(
                value = notesInput,
                onValueChange = onNotesChange,
                label = { Text("Notas / motivo (opcional)") },
                modifier = Modifier.fillMaxWidth(),
                minLines = 2,
                maxLines = 4,
                enabled = !isSubmitting
            )
            FilledTonalButton(
                onClick = onClose,
                enabled = !isSubmitting,
                modifier = Modifier.fillMaxWidth().height(48.dp)
            ) {
                if (isSubmitting) {
                    CircularProgressIndicator(Modifier.size(20.dp), strokeWidth = 2.dp)
                } else {
                    Text("CERRAR CAJA", fontWeight = FontWeight.Bold, letterSpacing = 1.sp)
                }
            }
        }
    }
}

@Composable
private fun CloseModeSelector(
    selected: CashCloseMode,
    onChange: (CashCloseMode) -> Unit,
    enabled: Boolean,
) {
    val options = listOf(
        CashCloseMode.AMOUNT to "Monto total",
        CashCloseMode.DENOMINATIONS to "Por denominaciones",
    )
    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        options.forEach { (mode, label) ->
            val isSelected = mode == selected
            FilterChip(
                selected = isSelected,
                onClick = { if (enabled) onChange(mode) },
                label = { Text(label, fontSize = 12.sp, fontWeight = FontWeight.SemiBold) },
                enabled = enabled,
            )
        }
    }
}

@Composable
private fun DenominationsCounter(
    denominations: Map<String, Int>,
    expected: String,
    enabled: Boolean,
    onChange: (String, Int) -> Unit,
    onIncrement: (String, Int) -> Unit,
    onClear: () -> Unit,
) {
    val total = Denominations.total(denominations)
    val expectedBd = expected.toBigDecimalOrNull() ?: BigDecimal.ZERO
    val diff = total.subtract(expectedBd).setScale(2, RoundingMode.HALF_UP)

    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Text(
            "Indica cuántas piezas tienes de cada denominación. El total se calcula automáticamente.",
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )

        Text(
            "Billetes",
            style = MaterialTheme.typography.labelMedium,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Denominations.BILLS.forEach { d ->
            DenominationRow(
                denomination = d,
                qty = denominations[d.key] ?: 0,
                enabled = enabled,
                onChange = { qty -> onChange(d.key, qty) },
                onIncrement = { delta -> onIncrement(d.key, delta) },
            )
        }

        Text(
            "Monedas",
            style = MaterialTheme.typography.labelMedium,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Denominations.COINS.forEach { d ->
            DenominationRow(
                denomination = d,
                qty = denominations[d.key] ?: 0,
                enabled = enabled,
                onChange = { qty -> onChange(d.key, qty) },
                onIncrement = { delta -> onIncrement(d.key, delta) },
            )
        }

        HorizontalDivider()

        Surface(
            modifier = Modifier.fillMaxWidth(),
            color = MaterialTheme.colorScheme.surfaceVariant,
            shape = RoundedCornerShape(8.dp)
        ) {
            Column(Modifier.padding(10.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                AmountRow("Total contado", total.toPlainString(), emphasized = true)
                AmountRow("Esperado", expected)
                val (label, color) = when {
                    diff.signum() == 0 -> "Cuadrado" to MaterialTheme.colorScheme.primary
                    diff.signum() > 0 -> "Sobrante" to MaterialTheme.colorScheme.tertiary
                    else -> "Faltante" to MaterialTheme.colorScheme.error
                }
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text(label, fontWeight = FontWeight.Bold, color = color)
                    Text(
                        "RD$ ${diff.abs().toPlainString()}",
                        fontFamily = FontFamily.Monospace,
                        fontWeight = FontWeight.Bold,
                        color = color,
                    )
                }
            }
        }

        TextButton(onClick = onClear, enabled = enabled) {
            Text("Limpiar conteo")
        }
    }
}

@Composable
private fun DenominationRow(
    denomination: Denomination,
    qty: Int,
    enabled: Boolean,
    onChange: (Int) -> Unit,
    onIncrement: (Int) -> Unit,
) {
    val subtotal = denomination.value.multiply(BigDecimal(qty))
        .setScale(2, RoundingMode.HALF_UP).toPlainString()
    val kindColor = if (denomination.kind == DenominationKind.BILL)
        MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.tertiary

    Row(
        Modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp)
    ) {
        Surface(
            shape = RoundedCornerShape(6.dp),
            color = kindColor.copy(alpha = 0.12f),
            modifier = Modifier.width(86.dp)
        ) {
            Text(
                denomination.label,
                modifier = Modifier.padding(horizontal = 6.dp, vertical = 6.dp),
                fontSize = 12.sp,
                fontWeight = FontWeight.SemiBold,
                color = kindColor,
            )
        }

        OutlinedIconButton(
            onClick = { onIncrement(-1) },
            enabled = enabled && qty > 0,
            modifier = Modifier.size(36.dp)
        ) { Icon(Icons.Default.Remove, null, modifier = Modifier.size(18.dp)) }

        OutlinedTextField(
            value = if (qty == 0) "" else qty.toString(),
            onValueChange = { input ->
                val parsed = input.filter { it.isDigit() }.take(6).toIntOrNull() ?: 0
                onChange(parsed)
            },
            placeholder = { Text("0", fontSize = 14.sp) },
            singleLine = true,
            modifier = Modifier.weight(1f),
            enabled = enabled,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
            textStyle = androidx.compose.ui.text.TextStyle(
                fontFamily = FontFamily.Monospace,
                fontWeight = FontWeight.Bold,
                fontSize = 14.sp,
                textAlign = androidx.compose.ui.text.style.TextAlign.Center,
            )
        )

        OutlinedIconButton(
            onClick = { onIncrement(1) },
            enabled = enabled,
            modifier = Modifier.size(36.dp)
        ) { Icon(Icons.Default.Add, null, modifier = Modifier.size(18.dp)) }

        Text(
            "RD$ $subtotal",
            fontFamily = FontFamily.Monospace,
            fontSize = 12.sp,
            fontWeight = FontWeight.SemiBold,
            modifier = Modifier.width(86.dp),
            textAlign = androidx.compose.ui.text.style.TextAlign.End,
            color = if (qty > 0) MaterialTheme.colorScheme.onSurface
            else MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.5f),
        )
    }
}

@Composable
private fun ClosedSessionCard(session: CashSessionDto) {
    Card {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
            Text(
                "Última caja: ${session.status}",
                fontWeight = FontWeight.Bold,
                style = MaterialTheme.typography.titleMedium
            )
            AmountRow("Fondo inicial", session.openingAmount)
            AmountRow("Esperado", session.expectedCash)
            AmountRow("Contado", session.countedCash ?: "—")
            if (session.shortageAmount != "0.00") AmountRow("Faltante", session.shortageAmount, emphasized = true)
            if (session.surplusAmount != "0.00") AmountRow("Sobrante", session.surplusAmount, emphasized = true)
            Spacer(Modifier.height(4.dp))
            Text(
                "Para abrir una nueva caja, refresca esta pantalla.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
private fun AmountRow(label: String, value: String, emphasized: Boolean = false) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(
            label,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = if (emphasized) FontWeight.Bold else FontWeight.Normal
        )
        Text(
            "RD$ $value",
            fontFamily = FontFamily.Monospace,
            fontWeight = if (emphasized) FontWeight.Bold else FontWeight.SemiBold,
            fontSize = if (emphasized) 16.sp else 14.sp
        )
    }
}

private fun addAmounts(a: String, b: String): String {
    val x = a.toBigDecimalOrNull() ?: java.math.BigDecimal.ZERO
    val y = b.toBigDecimalOrNull() ?: java.math.BigDecimal.ZERO
    return (x + y).setScale(2, java.math.RoundingMode.HALF_UP).toPlainString()
}

@Composable
private fun SyncStatusBanner(
    isOfflineCache: Boolean,
    cachedAtMillis: Long?,
    lastSyncedMillis: Long?,
) {
    val timeFormat = remember { SimpleDateFormat("HH:mm", Locale.getDefault()) }
    when {
        isOfflineCache -> {
            val ts = cachedAtMillis?.let { timeFormat.format(Date(it)) } ?: "—"
            Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.errorContainer,
                shape = RoundedCornerShape(8.dp)
            ) {
                Row(
                    Modifier.padding(horizontal = 12.dp, vertical = 8.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Icon(
                        Icons.Default.CloudOff, null,
                        tint = MaterialTheme.colorScheme.onErrorContainer,
                        modifier = Modifier.size(18.dp)
                    )
                    Column(Modifier.weight(1f)) {
                        Text(
                            "Sin conexión — mostrando datos guardados",
                            style = MaterialTheme.typography.bodySmall,
                            fontWeight = FontWeight.SemiBold,
                            color = MaterialTheme.colorScheme.onErrorContainer
                        )
                        Text(
                            "Última actualización: $ts. Las acciones de abrir/cerrar caja requieren conexión.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onErrorContainer.copy(alpha = 0.75f),
                            fontSize = 11.sp
                        )
                    }
                }
            }
        }
        lastSyncedMillis != null -> {
            Text(
                "Sincronizado a las ${timeFormat.format(Date(lastSyncedMillis))}",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f),
                fontSize = 11.sp
            )
        }
    }
}

@Composable
private fun MovementsCard(movements: List<CashMovementDto>) {
    Card {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Default.History, null, tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(20.dp))
                Spacer(Modifier.width(6.dp))
                Text(
                    "Movimientos recientes",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
                Spacer(Modifier.weight(1f))
                if (movements.isNotEmpty()) {
                    Surface(
                        shape = RoundedCornerShape(6.dp),
                        color = MaterialTheme.colorScheme.primary.copy(alpha = 0.1f)
                    ) {
                        Text(
                            "${movements.size}",
                            Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                            fontSize = 11.sp, fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.primary
                        )
                    }
                }
            }

            if (movements.isEmpty()) {
                Text(
                    "Sin movimientos registrados aún.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            } else {
                movements.forEach { m -> MovementRow(m) }
            }
        }
    }
}

@Composable
private fun MovementRow(m: CashMovementDto) {
    val isIn = m.direction.uppercase() == "IN"
    val color = if (isIn) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.error
    val sign = if (isIn) "+" else "-"
    val label = when (m.type.uppercase()) {
        "SALE" -> "Venta"
        "CANCELLATION" -> "Cancelación"
        "PRIZE_PAYMENT" -> "Pago de premio"
        "CASH_IN" -> "Entrada"
        "CASH_OUT" -> "Salida"
        "EXPENSE" -> "Gasto"
        "PAYROLL_PAYMENT" -> "Pago nómina"
        else -> m.type
    }
    val timeStr = m.createdAt?.let { formatIsoTime(it) } ?: "—"

    Row(
        Modifier.fillMaxWidth().padding(vertical = 6.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Surface(
            shape = RoundedCornerShape(6.dp),
            color = color.copy(alpha = 0.12f),
            modifier = Modifier.width(54.dp)
        ) {
            Text(
                if (isIn) "IN" else "OUT",
                Modifier.padding(horizontal = 4.dp, vertical = 4.dp),
                fontSize = 10.sp, fontWeight = FontWeight.Bold,
                color = color,
                textAlign = androidx.compose.ui.text.style.TextAlign.Center
            )
        }
        Spacer(Modifier.width(8.dp))
        Column(Modifier.weight(1f)) {
            Text(label, fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
            Text(
                listOfNotNull(m.description?.takeIf { it.isNotBlank() }, timeStr).joinToString(" · "),
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                fontSize = 11.sp,
                maxLines = 2
            )
        }
        Text(
            "$sign RD$ ${m.amount}",
            fontFamily = FontFamily.Monospace,
            fontWeight = FontWeight.Bold,
            color = color,
            fontSize = 13.sp
        )
    }
    HorizontalDivider(thickness = 0.5.dp, color = MaterialTheme.colorScheme.outlineVariant.copy(0.4f))
}

private fun formatIsoTime(iso: String): String = try {
    val cleaned = iso.replace("Z", "+0000")
    val parser = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX", Locale.getDefault())
    val date = parser.parse(cleaned) ?: return iso.take(16)
    SimpleDateFormat("HH:mm", Locale.getDefault()).format(date)
} catch (_: Exception) {
    iso.substringAfter('T').take(5)
}
