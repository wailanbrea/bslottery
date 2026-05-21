package dev.bsolutions.bsloteria.ui.screen.tickets

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import dev.bsolutions.bsloteria.data.remote.dto.WinnerDto
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TicketDetailScreen(
    viewModel: TicketDetailViewModel,
    onBack: () -> Unit
) {
    val state by viewModel.state.collectAsState()
    val primary = MaterialTheme.colorScheme.primary
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(state.printMessage) {
        state.printMessage?.let { msg ->
            snackbarHostState.showSnackbar(msg)
            viewModel.clearPrintMessage()
        }
    }

    LaunchedEffect(state.prizeSuccessMessage) {
        state.prizeSuccessMessage?.let { msg ->
            snackbarHostState.showSnackbar(msg)
            viewModel.clearPrizeSuccess()
        }
    }

    var showConfirmPayDialog by remember { mutableStateOf(false) }

    if (showConfirmPayDialog) {
        AlertDialog(
            onDismissRequest = { showConfirmPayDialog = false },
            icon = { Icon(Icons.Default.AttachMoney, null, tint = MaterialTheme.colorScheme.primary) },
            title = { Text("Pagar premio") },
            text = {
                Text(
                    "Se pagarán RD\$ ${state.totalReleased} al portador de este ticket. " +
                        "Se registrará un movimiento de caja PRIZE_PAYMENT y un asiento contable. " +
                        "¿Confirmar?"
                )
            },
            confirmButton = {
                Button(onClick = {
                    showConfirmPayDialog = false
                    viewModel.payPrize()
                }) { Text("Pagar") }
            },
            dismissButton = {
                TextButton(onClick = { showConfirmPayDialog = false }) { Text("Cancelar") }
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Detalle de ticket", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, null) }
                },
                actions = {
                    IconButton(onClick = viewModel::printTicket) {
                        Icon(Icons.Default.Print, "Imprimir ticket")
                    }
                }
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { padding ->
        if (state.isLoading) {
            Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
            return@Scaffold
        }

        LazyColumn(
            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 16.dp),
            verticalArrangement = Arrangement.spacedBy(14.dp),
            modifier = Modifier.padding(padding)
        ) {
            // ── Header con numero + meta ───────────────────────────────────
            item { TicketHeaderCard(state, primary) }

            // ── Total destacado ────────────────────────────────────────────
            item { TotalCard(state.totalAmount, primary) }

            // ── Premios (si no es offline pendiente) ──────────────────────
            if (!state.isSyncPending) {
                item {
                    PrizesSection(
                        winners = state.winners,
                        totalReleased = state.totalReleased,
                        hasReleasable = state.hasReleasablePrizes,
                        isLoading = state.winnersLoading,
                        isPaying = state.isPayingPrize,
                        error = state.winnersError,
                        onRefresh = viewModel::loadWinners,
                        onPayClick = { showConfirmPayDialog = true },
                        onDismissError = viewModel::clearWinnersError,
                    )
                }
            }

            // ── Jugadas ─────────────────────────────────────────────────────
            item {
                SectionLabel("JUGADAS (${state.details.size})", primary)
            }
            if (state.details.isEmpty()) {
                item {
                    Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(12.dp)) {
                        Text(
                            "Sin jugadas disponibles",
                            modifier = Modifier.fillMaxWidth().padding(20.dp),
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            textAlign = TextAlign.Center,
                            style = MaterialTheme.typography.bodyMedium,
                        )
                    }
                }
            } else {
                items(state.details.size) { idx ->
                    JugadaCard(line = state.details[idx], primary = primary)
                }
            }

            // ── UUID al pie (debug/soporte) ────────────────────────────────
            item {
                Text(
                    "ID: ${state.uuid}",
                    fontSize = 9.sp,
                    fontFamily = FontFamily.Monospace,
                    color = MaterialTheme.colorScheme.onSurfaceVariant.copy(0.45f),
                    modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                    textAlign = TextAlign.Center,
                )
            }
        }
    }
}

// ─── Header card ───────────────────────────────────────────────────────────

@Composable
private fun TicketHeaderCard(state: TicketDetailUiState, primary: Color) {
    Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp)) {
        Column(
            Modifier.fillMaxWidth().padding(18.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            // Status chip arriba a la derecha
            Row(
                Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top,
            ) {
                Column(Modifier.weight(1f)) {
                    Text(
                        "Número de ticket",
                        fontSize = 11.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        fontWeight = FontWeight.Medium,
                    )
                    Text(
                        state.ticketNumber.ifBlank { "—" },
                        fontFamily = FontFamily.Monospace,
                        fontWeight = FontWeight.Bold,
                        fontSize = 22.sp,
                        color = MaterialTheme.colorScheme.onSurface,
                    )
                }
                StatusChip(state.isSyncPending)
            }

            HorizontalDivider(thickness = 0.5.dp)

            // Lotería y sorteo
            MetaRow(label = "Lotería", value = state.lotteryName.ifBlank { "—" })
            MetaRow(label = "Sorteo", value = state.drawName.ifBlank { "—" })
            MetaRow(label = "Fecha y hora", value = state.soldAt.ifBlank { "—" })

            if (state.syncError != null) {
                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = MaterialTheme.colorScheme.errorContainer,
                ) {
                    Row(
                        Modifier.fillMaxWidth().padding(10.dp),
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Icon(
                            Icons.Default.Warning, null,
                            tint = MaterialTheme.colorScheme.error,
                            modifier = Modifier.size(16.dp),
                        )
                        Text(
                            state.syncError,
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onErrorContainer,
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun MetaRow(label: String, value: String) {
    Row(
        Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Text(
            label,
            fontSize = 12.sp,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            fontWeight = FontWeight.Medium,
        )
        Text(
            value,
            fontSize = 13.sp,
            fontWeight = FontWeight.SemiBold,
            color = MaterialTheme.colorScheme.onSurface,
            textAlign = TextAlign.End,
            modifier = Modifier.padding(start = 12.dp),
        )
    }
}

@Composable
private fun StatusChip(isPending: Boolean) {
    val (bg, fg, icon, label) = if (isPending) {
        Quad(
            MaterialTheme.colorScheme.tertiary.copy(0.15f),
            MaterialTheme.colorScheme.tertiary,
            Icons.Default.CloudOff,
            "Pendiente",
        )
    } else {
        Quad(
            MaterialTheme.colorScheme.primary.copy(0.12f),
            MaterialTheme.colorScheme.primary,
            Icons.Default.CheckCircle,
            "Sincronizado",
        )
    }
    Surface(
        shape = RoundedCornerShape(10.dp),
        color = bg,
    ) {
        Row(
            Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
            horizontalArrangement = Arrangement.spacedBy(4.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Icon(icon, null, Modifier.size(14.dp), tint = fg)
            Text(label, fontSize = 11.sp, color = fg, fontWeight = FontWeight.SemiBold)
        }
    }
}

private data class Quad<A, B, C, D>(val a: A, val b: B, val c: C, val d: D)

// ─── Total card ────────────────────────────────────────────────────────────

@Composable
private fun TotalCard(totalAmount: String, primary: Color) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = primary.copy(0.08f)),
    ) {
        Row(
            Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 16.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column {
                Text(
                    "Total apostado",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    fontWeight = FontWeight.Medium,
                )
                Text(
                    "RD\$ $totalAmount",
                    fontFamily = FontFamily.Monospace,
                    fontWeight = FontWeight.Bold,
                    fontSize = 26.sp,
                    color = primary,
                )
            }
            Icon(
                Icons.Default.Payments,
                null,
                tint = primary,
                modifier = Modifier.size(40.dp),
            )
        }
    }
}

// ─── Section label ─────────────────────────────────────────────────────────

@Composable
private fun SectionLabel(text: String, color: Color) {
    Text(
        text,
        fontSize = 11.sp,
        fontWeight = FontWeight.Bold,
        letterSpacing = 1.sp,
        color = color,
        modifier = Modifier.padding(start = 4.dp, top = 4.dp, bottom = 0.dp),
    )
}

// ─── Jugada card ───────────────────────────────────────────────────────────

@Composable
private fun JugadaCard(line: TicketLineItem, primary: Color) {
    val tipoLabel = when (line.betTypeName.uppercase()) {
        "QUINIELA" -> "Quiniela"
        "PALE" -> "Palé"
        "TRIPLETA" -> "Tripleta"
        "SUPER_PALE", "SUPER PALE" -> "Súper Palé"
        else -> line.betTypeName
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Row(
            Modifier.fillMaxWidth().padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            // Numero grande
            Box(
                modifier = Modifier
                    .background(primary.copy(0.1f), RoundedCornerShape(10.dp))
                    .padding(horizontal = 14.dp, vertical = 10.dp),
                contentAlignment = Alignment.Center,
            ) {
                Text(
                    line.numberValue,
                    fontFamily = FontFamily.Monospace,
                    fontWeight = FontWeight.Bold,
                    fontSize = 22.sp,
                    color = primary,
                )
            }

            // Tipo + posible premio
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(2.dp)) {
                Surface(
                    shape = RoundedCornerShape(6.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(0.6f),
                ) {
                    Text(
                        tipoLabel,
                        Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                        fontSize = 11.sp,
                        fontWeight = FontWeight.SemiBold,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                line.potentialPrize?.let {
                    Text(
                        "Premio posible: RD\$ $it",
                        fontSize = 11.sp,
                        color = MaterialTheme.colorScheme.secondary,
                        fontWeight = FontWeight.Medium,
                    )
                }
            }

            // Monto
            Column(horizontalAlignment = Alignment.End) {
                Text(
                    "Monto",
                    fontSize = 10.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Text(
                    "RD\$ ${line.amount}",
                    fontFamily = FontFamily.Monospace,
                    fontWeight = FontWeight.Bold,
                    fontSize = 15.sp,
                    color = primary,
                )
            }
        }
    }
}

// ─── Premios section (mantengo similar) ───────────────────────────────────

@Composable
private fun PrizesSection(
    winners: List<WinnerDto>,
    totalReleased: String,
    hasReleasable: Boolean,
    isLoading: Boolean,
    isPaying: Boolean,
    error: String?,
    onRefresh: () -> Unit,
    onPayClick: () -> Unit,
    onDismissError: () -> Unit,
) {
    val primary = MaterialTheme.colorScheme.primary

    Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp)) {
        Column(
            Modifier.fillMaxWidth().padding(18.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Row(
                Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.EmojiEvents, null, tint = primary,
                        modifier = Modifier.size(22.dp))
                    Spacer(Modifier.width(8.dp))
                    Text("Premios", fontWeight = FontWeight.Bold,
                        style = MaterialTheme.typography.titleMedium)
                }
                IconButton(onClick = onRefresh, enabled = !isLoading) {
                    Icon(Icons.Default.Refresh, "Recargar")
                }
            }

            when {
                isLoading -> Box(Modifier.fillMaxWidth().padding(12.dp), Alignment.Center) {
                    CircularProgressIndicator(Modifier.size(22.dp), strokeWidth = 2.dp)
                }
                error != null -> {
                    Surface(
                        color = MaterialTheme.colorScheme.errorContainer,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Row(Modifier.padding(10.dp), verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.Warning, null,
                                tint = MaterialTheme.colorScheme.error,
                                modifier = Modifier.size(16.dp))
                            Spacer(Modifier.width(6.dp))
                            Text(error, Modifier.weight(1f),
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onErrorContainer)
                            TextButton(onClick = onDismissError) { Text("OK") }
                        }
                    }
                }
                winners.isEmpty() -> Text(
                    "Este ticket no tiene premios registrados.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                else -> {
                    winners.forEach { w -> WinnerRow(w) }
                    HorizontalDivider(thickness = 0.5.dp)
                    Row(Modifier.fillMaxWidth(), Arrangement.SpaceBetween) {
                        Text("Total liberado", fontWeight = FontWeight.SemiBold)
                        Text(
                            "RD\$ $totalReleased",
                            fontFamily = FontFamily.Monospace,
                            fontWeight = FontWeight.Bold,
                            color = primary,
                            fontSize = 18.sp
                        )
                    }
                    if (hasReleasable) {
                        Button(
                            onClick = onPayClick,
                            enabled = !isPaying,
                            modifier = Modifier.fillMaxWidth().height(50.dp)
                        ) {
                            if (isPaying) {
                                CircularProgressIndicator(
                                    Modifier.size(20.dp), strokeWidth = 2.dp,
                                    color = MaterialTheme.colorScheme.onPrimary
                                )
                            } else {
                                Icon(Icons.Default.AttachMoney, null, modifier = Modifier.size(18.dp))
                                Spacer(Modifier.width(6.dp))
                                Text("PAGAR PREMIO", fontWeight = FontWeight.Bold, letterSpacing = 1.sp)
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun WinnerRow(w: WinnerDto) {
    val statusColor = when (w.status.uppercase()) {
        "PAID" -> MaterialTheme.colorScheme.primary
        "RELEASED" -> MaterialTheme.colorScheme.tertiary
        "HELD" -> MaterialTheme.colorScheme.secondary
        else -> MaterialTheme.colorScheme.onSurfaceVariant
    }
    val statusLabel = when (w.status.uppercase()) {
        "PAID" -> "Pagado"
        "RELEASED" -> "Liberado"
        "HELD" -> "Retenido"
        else -> w.status
    }
    val typeShort = when (w.betTypeName.uppercase()) {
        "QUINIELA" -> "Q"
        "PALE" -> "Pl"
        "TRIPLETA" -> "Tri"
        "SUPER PALE", "SUPER_PALE" -> "SP"
        else -> w.betTypeName.take(3)
    }

    Row(
        Modifier.fillMaxWidth().padding(vertical = 6.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Surface(
            shape = RoundedCornerShape(6.dp),
            color = MaterialTheme.colorScheme.primary.copy(0.1f),
            modifier = Modifier.width(42.dp)
        ) {
            Text(typeShort, Modifier.padding(horizontal = 6.dp, vertical = 4.dp),
                fontSize = 12.sp, fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary,
                textAlign = TextAlign.Center)
        }
        Spacer(Modifier.width(10.dp))
        Column(Modifier.weight(1f)) {
            Text(
                w.numberValue,
                fontFamily = FontFamily.Monospace,
                fontWeight = FontWeight.Bold, fontSize = 17.sp
            )
            Text(
                listOfNotNull(w.lotteryName.takeIf { it.isNotBlank() }, w.matchedPosition).joinToString(" · "),
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        Column(horizontalAlignment = Alignment.End) {
            Text(
                "RD\$ ${w.prizeAmount}",
                fontFamily = FontFamily.Monospace,
                fontWeight = FontWeight.Bold,
                color = statusColor,
                fontSize = 15.sp
            )
            Surface(
                shape = RoundedCornerShape(4.dp),
                color = statusColor.copy(alpha = 0.12f)
            ) {
                Text(
                    statusLabel,
                    Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                    fontSize = 9.sp, fontWeight = FontWeight.SemiBold,
                    color = statusColor
                )
            }
        }
    }
}
