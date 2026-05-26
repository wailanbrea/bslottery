package dev.bsolutions.bsloteria.ui.screen.tickets

import androidx.compose.foundation.BorderStroke
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
            // ── Banner de Estado ───────────────────────────────────────────
            item { TicketStatusBanner(state) }

            // ── Ficha del Ticket Unificada ─────────────────────────────────
            item { UnifiedTicketCard(state, primary) }

            // ── Premios (si no es offline pendiente) ──────────────────────
            if (!state.isSyncPending) {
                item {
                    val showPayButton = state.hasReleasablePrizes || 
                            state.status.uppercase() in listOf("WINNER", "PARTIALLY_PAID")
                    PrizesSection(
                        winners = state.winners,
                        totalReleased = state.totalReleased,
                        showPayButton = showPayButton,
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

private fun formatTicketStatus(status: String): String {
    return when (status.uppercase()) {
        "ACTIVE" -> "Activo"
        "WINNER" -> "Ganador"
        "LOSER" -> "No Ganador"
        "PARTIALLY_PAID" -> "Pago Parcial"
        "PAID" -> "Pagado"
        "CANCELLED" -> "Anulado"
        else -> status
    }
}

@Composable
private fun TicketStatusBanner(state: TicketDetailUiState) {
    if (state.status.isBlank()) return

    val status = state.status.uppercase()
    val isWinner = status in listOf("WINNER", "PARTIALLY_PAID")
    
    val containerColor: Color
    val contentColor: Color
    val icon: androidx.compose.ui.graphics.vector.ImageVector
    val title: String
    val subtitle: String?

    when {
        isWinner -> {
            containerColor = Color(0xFFE8F5E9) // Verde suave
            contentColor = Color(0xFF2E7D32)   // Verde oscuro
            icon = Icons.Default.EmojiEvents
            title = "¡TICKET GANADOR!"
            subtitle = "Monto a Pagar: RD$ ${state.totalReleased}"
        }
        status == "PAID" -> {
            containerColor = Color(0xFFE3F2FD) // Azul suave
            contentColor = Color(0xFF1565C0)   // Azul oscuro
            icon = Icons.Default.CheckCircle
            title = "TICKET PAGADO"
            subtitle = "Monto entregado: RD$ ${state.totalReleased}"
        }
        status == "CANCELLED" -> {
            containerColor = Color(0xFFFFEBEE) // Rojo suave
            contentColor = Color(0xFFC62828)   // Rojo oscuro
            icon = Icons.Default.Block
            title = "TICKET ANULADO"
            subtitle = if (!state.syncError.isNullOrBlank()) state.syncError else null
        }
        status == "LOSER" -> {
            containerColor = Color(0xFFF5F5F5) // Gris suave
            contentColor = Color(0xFF616161)   // Gris oscuro
            icon = Icons.Default.Cancel
            title = "TICKET NO GANADOR"
            subtitle = "No acertó ninguna combinación."
        }
        else -> { // ACTIVE
            containerColor = Color(0xFFFFF8E1) // Amarillo/Naranja suave
            contentColor = Color(0xFFE65100)   // Naranja oscuro
            icon = Icons.Default.ConfirmationNumber
            title = "TICKET ACTIVO"
            subtitle = "Pendiente de sorteo y resultados."
        }
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = containerColor),
        border = BorderStroke(1.dp, contentColor.copy(alpha = 0.3f))
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            Box(
                modifier = Modifier
                    .size(52.dp)
                    .background(contentColor.copy(alpha = 0.12f), RoundedCornerShape(12.dp)),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = icon,
                    contentDescription = null,
                    tint = contentColor,
                    modifier = Modifier.size(28.dp)
                )
            }
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = title,
                    fontWeight = FontWeight.Bold,
                    color = contentColor,
                    style = MaterialTheme.typography.titleMedium,
                    letterSpacing = 0.5.sp
                )
                subtitle?.let {
                    Spacer(Modifier.height(4.dp))
                    Text(
                        text = it,
                        fontWeight = if (isWinner || status == "PAID") FontWeight.Bold else FontWeight.Medium,
                        color = contentColor.copy(alpha = 0.9f),
                        style = if (isWinner || status == "PAID") MaterialTheme.typography.titleLarge else MaterialTheme.typography.bodyMedium,
                        fontFamily = if (isWinner || status == "PAID") FontFamily.Monospace else FontFamily.Default
                    )
                }
            }
        }
    }
}

@Composable
private fun UnifiedTicketCard(state: TicketDetailUiState, primary: Color) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f)),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
    ) {
        Column(
            modifier = Modifier.padding(18.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Cabecera: Ticket ID + Sync status
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text(
                        text = "Número de ticket",
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Bold,
                        color = primary,
                        letterSpacing = 0.5.sp
                    )
                    Spacer(Modifier.height(2.dp))
                    Text(
                        text = state.ticketNumber.ifBlank { "—" },
                        fontFamily = FontFamily.Monospace,
                        fontWeight = FontWeight.Bold,
                        fontSize = 18.sp,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                }
                StatusChip(state.isSyncPending)
            }

            HorizontalDivider(thickness = 0.5.dp, color = MaterialTheme.colorScheme.outlineVariant)

            // Detalles
            MetaRow(label = "Lotería", value = state.lotteryName.ifBlank { "—" })
            MetaRow(label = "Sorteo", value = state.drawName.ifBlank { "—" })
            MetaRow(label = "Fecha y hora", value = state.soldAt.ifBlank { "—" })

            HorizontalDivider(thickness = 0.5.dp, color = MaterialTheme.colorScheme.outlineVariant)

            // Total Apostado Destacado
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Total Jugado",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Text(
                    text = "RD$ ${state.totalAmount}",
                    fontFamily = FontFamily.Monospace,
                    fontWeight = FontWeight.Bold,
                    fontSize = 20.sp,
                    color = primary
                )
            }

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
    showPayButton: Boolean,
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

            if (isLoading) {
                Box(Modifier.fillMaxWidth().padding(12.dp), Alignment.Center) {
                    CircularProgressIndicator(Modifier.size(22.dp), strokeWidth = 2.dp)
                }
            } else {
                error?.let { err ->
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
                            Text(err, Modifier.weight(1f),
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onErrorContainer)
                            TextButton(onClick = onDismissError) { Text("OK") }
                        }
                    }
                }

                if (winners.isEmpty()) {
                    Text(
                        "Este ticket no tiene premios registrados.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                } else {
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
                }

                if (showPayButton) {
                    Spacer(Modifier.height(4.dp))
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
