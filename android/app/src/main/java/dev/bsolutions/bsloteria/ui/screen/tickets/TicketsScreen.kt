package dev.bsolutions.bsloteria.ui.screen.tickets

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.CloudDone
import androidx.compose.material.icons.filled.CloudOff
import androidx.compose.material.icons.filled.ConfirmationNumber
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DatePicker
import androidx.compose.material3.DatePickerDialog
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.rememberDatePickerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TicketsScreen(
    viewModel: TicketsViewModel,
    onBack: () -> Unit,
    onTicketClick: (String) -> Unit,
    onScanQr: () -> Unit = {},
    scannedToken: String? = null,
    onScannedTokenConsumed: () -> Unit = {},
) {
    val tickets by viewModel.tickets.collectAsState()
    val searchState by viewModel.searchState.collectAsState()
    val filterState by viewModel.filterState.collectAsState()
    val primary = MaterialTheme.colorScheme.primary

    var showCustomPicker by remember { mutableStateOf(false) }

    LaunchedEffect(scannedToken) {
        val token = scannedToken
        if (!token.isNullOrBlank()) {
            viewModel.onSearchChange(token)
            onScannedTokenConsumed()
            viewModel.lookup(onTicketClick)
        }
    }

    if (showCustomPicker) {
        CustomDateRangeDialog(
            onDismiss = { showCustomPicker = false },
            onConfirm = { start, end ->
                viewModel.setCustomRange(start, end)
                showCustomPicker = false
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Historial de tickets", fontWeight = FontWeight.Bold)
                        Text(
                            "${tickets.size} ${if (tickets.size == 1) "registro" else "registros"}",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, null) }
                }
            )
        }
    ) { padding ->
        LazyColumn(
            contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp),
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
        ) {
            item {
                TicketLookupCard(
                    state = searchState,
                    onQueryChange = viewModel::onSearchChange,
                    onLookup = { viewModel.lookup(onTicketClick) },
                    onScanQr = onScanQr,
                )
            }

            item {
                DateFilterRow(
                    state = filterState,
                    onSelectQuick = viewModel::setDateFilter,
                    onOpenCustom = { showCustomPicker = true },
                )
            }

            if (tickets.isEmpty()) {
                item {
                    EmptyState(filter = filterState.dateFilter)
                }
            } else {
                val pendingCount = tickets.count { it.isSyncPending }
                if (pendingCount > 0) {
                    item {
                        PendingBanner(pendingCount)
                    }
                }

                items(tickets) { ticket ->
                    TicketCard(ticket, primary = primary, onClick = { onTicketClick(ticket.uuid) })
                }
            }
        }
    }
}

// ─── Date filter row ───────────────────────────────────────────────────────

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun DateFilterRow(
    state: TicketFilterState,
    onSelectQuick: (DateFilter) -> Unit,
    onOpenCustom: () -> Unit,
) {
    val customLabel = remember(state.customStartMs, state.customEndMs) {
        if (state.customStartMs != null && state.customEndMs != null) {
            val fmt = SimpleDateFormat("dd/MM", Locale.getDefault())
            "${fmt.format(Date(state.customStartMs))} — ${fmt.format(Date(state.customEndMs))}"
        } else "Personalizada"
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(10.dp),
    ) {
        Column(
            Modifier.padding(horizontal = 12.dp, vertical = 10.dp),
            verticalArrangement = Arrangement.spacedBy(6.dp),
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(6.dp),
            ) {
                Icon(
                    Icons.Default.CalendarMonth, null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(16.dp),
                )
                Text(
                    "Filtrar por fecha",
                    fontSize = 11.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                FilterChip(
                    selected = state.dateFilter == DateFilter.TODAY,
                    onClick = { onSelectQuick(DateFilter.TODAY) },
                    label = { Text("Hoy") },
                )
                FilterChip(
                    selected = state.dateFilter == DateFilter.YESTERDAY,
                    onClick = { onSelectQuick(DateFilter.YESTERDAY) },
                    label = { Text("Ayer") },
                )
                FilterChip(
                    selected = state.dateFilter == DateFilter.CUSTOM,
                    onClick = onOpenCustom,
                    label = { Text(customLabel, maxLines = 1) },
                    leadingIcon = {
                        Icon(
                            Icons.Default.CalendarMonth, null,
                            modifier = Modifier.size(FilterChipDefaults.IconSize),
                        )
                    },
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun CustomDateRangeDialog(
    onDismiss: () -> Unit,
    onConfirm: (Long, Long) -> Unit,
) {
    val startState = rememberDatePickerState(initialSelectedDateMillis = System.currentTimeMillis())
    val endState = rememberDatePickerState(initialSelectedDateMillis = System.currentTimeMillis())
    var pickingEnd by remember { mutableStateOf(false) }

    DatePickerDialog(
        onDismissRequest = onDismiss,
        confirmButton = {
            if (!pickingEnd) {
                TextButton(
                    onClick = { pickingEnd = true },
                    enabled = startState.selectedDateMillis != null,
                ) { Text("Siguiente: fecha final") }
            } else {
                TextButton(onClick = {
                    val s = startState.selectedDateMillis
                    val e = endState.selectedDateMillis
                    if (s != null && e != null) {
                        val (start, end) = if (s <= e) (s to dayEnd(e)) else (e to dayEnd(s))
                        onConfirm(start, end)
                    } else {
                        onDismiss()
                    }
                }) { Text("Aplicar") }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) { Text("Cancelar") }
        },
    ) {
        Column {
            Text(
                if (pickingEnd) "Fecha final" else "Fecha inicial",
                modifier = Modifier.padding(start = 24.dp, top = 16.dp),
                fontWeight = FontWeight.SemiBold,
            )
            DatePicker(
                state = if (pickingEnd) endState else startState,
                showModeToggle = false,
            )
        }
    }
}

private fun dayEnd(timestamp: Long): Long {
    val cal = Calendar.getInstance().apply {
        timeInMillis = timestamp
        set(Calendar.HOUR_OF_DAY, 23); set(Calendar.MINUTE, 59)
        set(Calendar.SECOND, 59); set(Calendar.MILLISECOND, 999)
    }
    return cal.timeInMillis
}

// ─── Lookup card (unchanged styling, compacted) ────────────────────────────

@Composable
private fun TicketLookupCard(
    state: TicketSearchState,
    onQueryChange: (String) -> Unit,
    onLookup: () -> Unit,
    onScanQr: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(10.dp)
    ) {
        Column(
            Modifier.padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            Text("Cargar ticket", fontWeight = FontWeight.Bold, fontSize = 14.sp)
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                OutlinedTextField(
                    value = state.query,
                    onValueChange = onQueryChange,
                    modifier = Modifier.weight(1f),
                    label = { Text("Numero o QR") },
                    placeholder = { Text("MOB01-260520-0001") },
                    singleLine = true,
                    leadingIcon = { Icon(Icons.Default.QrCodeScanner, null) }
                )
                IconButton(
                    onClick = onScanQr,
                    enabled = !state.isLoading,
                    modifier = Modifier.size(56.dp)
                ) {
                    Icon(
                        Icons.Default.QrCodeScanner,
                        contentDescription = "Escanear QR",
                        tint = MaterialTheme.colorScheme.primary,
                        modifier = Modifier.size(28.dp)
                    )
                }
                Button(
                    onClick = onLookup,
                    enabled = !state.isLoading,
                    modifier = Modifier.height(56.dp)
                ) {
                    if (state.isLoading) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(18.dp),
                            strokeWidth = 2.dp,
                            color = MaterialTheme.colorScheme.onPrimary
                        )
                    } else {
                        Icon(Icons.Default.Search, null)
                    }
                }
            }
            state.error?.let {
                Text(it, color = MaterialTheme.colorScheme.error, fontSize = 12.sp)
            }
        }
    }
}

// ─── Empty state ───────────────────────────────────────────────────────────

@Composable
private fun EmptyState(filter: DateFilter) {
    Box(
        Modifier.fillMaxWidth().padding(vertical = 48.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Icon(
                Icons.Default.ConfirmationNumber,
                null,
                modifier = Modifier.size(64.dp),
                tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.3f)
            )
            val msg = when (filter) {
                DateFilter.TODAY -> "Sin tickets hoy"
                DateFilter.YESTERDAY -> "Sin tickets ayer"
                DateFilter.CUSTOM -> "Sin tickets en el rango seleccionado"
            }
            Text(
                msg,
                style = MaterialTheme.typography.titleMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Text(
                "Cambia el filtro de fecha o registra una venta nueva",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant.copy(0.7f)
            )
        }
    }
}

// ─── Pending banner ────────────────────────────────────────────────────────

@Composable
private fun PendingBanner(count: Int) {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(10.dp),
        color = MaterialTheme.colorScheme.tertiaryContainer
    ) {
        Row(
            Modifier.padding(horizontal = 14.dp, vertical = 10.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                Icons.Default.CloudOff,
                null,
                tint = MaterialTheme.colorScheme.onTertiaryContainer,
                modifier = Modifier.size(18.dp)
            )
            Text(
                "$count ticket${if (count != 1) "s" else ""} pendiente${if (count != 1) "s" else ""} de sincronizar",
                color = MaterialTheme.colorScheme.onTertiaryContainer,
                fontSize = 13.sp,
                fontWeight = FontWeight.Medium
            )
        }
    }
}

// ─── Ticket card — nuevas columnas ────────────────────────────────────────

@Composable
private fun TicketCard(ticket: TicketDisplayItem, primary: Color, onClick: () -> Unit) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        shape = RoundedCornerShape(10.dp)
    ) {
        Row(
            Modifier.fillMaxWidth().padding(12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            // ── "Ver" leading icon ────────────────────────────────────
            Box(
                modifier = Modifier
                    .size(40.dp)
                    .background(primary.copy(0.1f), RoundedCornerShape(8.dp)),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    Icons.Default.ChevronRight,
                    contentDescription = "Ver detalle",
                    tint = primary,
                    modifier = Modifier.size(22.dp),
                )
            }

            // ── Numero + jugadas (peso flex) ──────────────────────────
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(2.dp)) {
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                    Text(
                        ticket.ticketNumber,
                        fontWeight = FontWeight.Bold,
                        fontFamily = FontFamily.Monospace,
                        fontSize = 13.sp,
                        color = MaterialTheme.colorScheme.onSurface,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                    if (ticket.isSyncPending) {
                        Icon(
                            Icons.Default.CloudOff,
                            null,
                            modifier = Modifier.size(11.dp),
                            tint = MaterialTheme.colorScheme.tertiary,
                        )
                    } else {
                        Icon(
                            Icons.Default.CloudDone,
                            null,
                            modifier = Modifier.size(11.dp),
                            tint = MaterialTheme.colorScheme.primary.copy(0.6f),
                        )
                    }
                }
                val preview = ticket.jugadasPreview.ifBlank {
                    "${ticket.lotteryName} — ${ticket.drawName}"
                }
                Text(
                    preview,
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                )
                if (ticket.jugadasCount > 0) {
                    Text(
                        "${ticket.jugadasCount} jugada${if (ticket.jugadasCount != 1) "s" else ""}",
                        fontSize = 10.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant.copy(0.6f),
                    )
                }
            }

            Spacer(Modifier.width(2.dp))

            // ── Monto + hora + fecha (col derecha) ────────────────────
            Column(horizontalAlignment = Alignment.End, verticalArrangement = Arrangement.spacedBy(2.dp)) {
                Text(
                    "RD$ ${ticket.totalAmount}",
                    fontWeight = FontWeight.Bold,
                    fontFamily = FontFamily.Monospace,
                    color = primary,
                    fontSize = 14.sp,
                )
                Text(
                    ticket.timeLabel,
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurface,
                    fontWeight = FontWeight.Medium,
                )
                Text(
                    ticket.dateLabel,
                    fontSize = 10.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant.copy(0.7f),
                )
            }
        }
    }
}
