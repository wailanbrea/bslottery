package dev.bsolutions.bsloteria.ui.screen.sale

import androidx.compose.animation.animateColorAsState
import androidx.compose.animation.core.*
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.automirrored.filled.Backspace
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import dev.bsolutions.bsloteria.data.local.entity.DrawEntity
import kotlinx.coroutines.flow.StateFlow

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SaleScreen(
    viewModel: SaleViewModel,
    onBack: () -> Unit
) {
    val state by viewModel.state.collectAsStateWithLifecycle()
    val session by viewModel.session.collectAsStateWithLifecycle()
    val primary = MaterialTheme.colorScheme.primary
    var showSuccessDialog by remember { mutableStateOf(false) }

    LaunchedEffect(state.successUuid) {
        if (state.successUuid != null) showSuccessDialog = true
    }

    // derivedStateOf evita recalcular en cada recomposicion si jugadas no cambio.
    val totalAmount by remember(state.jugadas) {
        derivedStateOf { viewModel.totalAmount() }
    }

    Scaffold(
        topBar = {
            SaleTopBar(
                branchName = session?.branchName ?: "Sucursal",
                userName = session?.userName ?: "",
                onBack = onBack,
                primaryColor = primary
            )
        }
    ) { innerPadding ->
        Column(
            Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            // ── Scrollable content ─────────────────────────────────────────
            LazyColumn(Modifier.weight(1f)) {
                item(key = "carousel") {
                    // Solo el carrusel observa el tick — el resto de la UI no
                    // recompose cada segundo.
                    DrawCarousel(
                        draws = state.draws,
                        selectedIds = state.selectedDrawIds,
                        tickFlow = viewModel.tick,
                        onToggle = viewModel::toggleDraw,
                        onSelectAll = viewModel::selectAllDraws,
                        onDeselectAll = viewModel::deselectAllDraws,
                        onClear = viewModel::clearAll,
                        hasJugadas = state.jugadas.isNotEmpty(),
                        primaryColor = primary,
                        countdownFor = viewModel::drawCountdown,
                    )
                }

                state.error?.let { errorMsg ->
                    item(key = "error") {
                        Text(
                            errorMsg,
                            Modifier.padding(horizontal = 16.dp, vertical = 4.dp),
                            color = MaterialTheme.colorScheme.error,
                            style = MaterialTheme.typography.bodySmall
                        )
                    }
                }

                if (state.jugadas.isNotEmpty()) {
                    item(key = "toolbar") {
                        JugadasToolbar(
                            count = state.jugadas.size,
                            voltearActivo = state.voltearActivo,
                            onVoltear = viewModel::toggleVoltear,
                            onCombinar = viewModel::openCombinarDialog,
                            primaryColor = primary,
                        )
                    }
                    item(key = "header") { JugadasHeader() }
                    itemsIndexed(
                        items = state.jugadas,
                        key = { _, j -> "${j.drawId}-${j.betTypeId}-${j.numberValue}-${j.flipped}-${j.combined}" },
                    ) { index, jugada ->
                        JugadaRow(jugada, onDelete = { viewModel.removeJugada(index) })
                    }
                }
            }

            // ── Footer: total + VENDER ─────────────────────────────────────
            FooterSection(
                total = totalAmount,
                jugadasCount = state.jugadas.size,
                isLoading = state.isLoading,
                onSell = viewModel::sell,
                primaryColor = primary
            )

            // ── Custom keypad ──────────────────────────────────────────────
            if (state.isKeypadVisible) {
                KeypadPanel(
                    state = state,
                    onDigit = viewModel::onKeypadDigit,
                    onBackspace = viewModel::onKeypadBackspace,
                    onEnter = viewModel::onKeypadEnter,
                    onActivateNumero = viewModel::activateNumero,
                    onActivateMonto = viewModel::activateMonto,
                    onToggleSuper = viewModel::toggleSuperMode,
                    onHide = viewModel::toggleKeypad,
                    primaryColor = primary
                )
            } else {
                Surface(
                    modifier = Modifier
                        .fillMaxWidth()
                        .navigationBarsPadding()
                        .clickable(onClick = viewModel::toggleKeypad),
                    color = MaterialTheme.colorScheme.surfaceVariant
                ) {
                    Row(
                        Modifier.padding(vertical = 10.dp),
                        horizontalArrangement = Arrangement.Center,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(Icons.Default.KeyboardArrowUp, null,
                            tint = primary, modifier = Modifier.size(20.dp))
                        Spacer(Modifier.width(4.dp))
                        Text("Mostrar teclado", fontSize = 12.sp, color = primary,
                            fontWeight = FontWeight.Medium)
                    }
                }
            }
        }
    }

    // Dialog de pre-check de limite
    if (state.limitDialog.visible) {
        LimitConflictDialog(
            dialog = state.limitDialog,
            onDecisionChange = viewModel::updateLimitDecision,
            onAdjustAll = viewModel::applyAllAdjust,
            onApply = viewModel::applyLimitDecisions,
            onCancel = viewModel::cancelLimitDialog,
            primaryColor = primary,
        )
    }

    // Dialog de combinar — memoizar cálculos para no recalcular en cada checkbox.
    if (state.combinarDialog.visible) {
        val preview by remember(state.jugadas, state.combinarDialog) {
            derivedStateOf { viewModel.combinarPreview() }
        }
        val sourceSummary by remember(state.jugadas) {
            derivedStateOf { viewModel.combinarSourceSummary() }
        }
        val canSuper by remember(state.jugadas) {
            derivedStateOf { viewModel.canCombinarSuper() }
        }
        CombinarDialog(
            dialog = state.combinarDialog,
            preview = preview,
            sourceSummary = sourceSummary,
            canSuper = canSuper,
            onChange = viewModel::updateCombinarDialog,
            onConfirm = viewModel::submitCombinar,
            onCancel = viewModel::cancelCombinarDialog,
            primaryColor = primary,
        )
    }

    if (showSuccessDialog) {
        AlertDialog(
            onDismissRequest = {
                showSuccessDialog = false
                viewModel.clearSuccess()
                viewModel.clearPrintMessage()
            },
            icon = { Icon(Icons.Default.CheckCircle, null, tint = MaterialTheme.colorScheme.primary) },
            title = { Text("¡Ticket vendido!") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Text("El ticket fue registrado correctamente.")
                    state.printMessage?.let { msg ->
                        Text(
                            msg,
                            fontSize = 12.sp,
                            color = if (msg.startsWith("Sin")) MaterialTheme.colorScheme.error
                                    else MaterialTheme.colorScheme.primary
                        )
                    }
                }
            },
            confirmButton = {
                Button(onClick = {
                    showSuccessDialog = false
                    viewModel.clearSuccess()
                    viewModel.clearPrintMessage()
                }) {
                    Text("Nueva venta")
                }
            },
            dismissButton = {
                OutlinedButton(onClick = viewModel::printLastTicket) {
                    Icon(Icons.Default.Print, null, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(4.dp))
                    Text("Imprimir")
                }
            }
        )
    }
}

// ─── Top bar ───────────────────────────────────────────────────────────────────

@Composable
private fun SaleTopBar(branchName: String, userName: String, onBack: () -> Unit, primaryColor: Color) {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        color = MaterialTheme.colorScheme.surface,
        shadowElevation = 2.dp
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .statusBarsPadding()
                .padding(horizontal = 12.dp, vertical = 10.dp),
            horizontalArrangement = Arrangement.spacedBy(10.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            IconButton(onClick = onBack, modifier = Modifier.size(32.dp)) {
                Icon(Icons.AutoMirrored.Filled.ArrowBack, null, tint = primaryColor)
            }
            Box(
                modifier = Modifier
                    .size(36.dp)
                    .clip(CircleShape)
                    .background(primaryColor.copy(alpha = 0.12f)),
                contentAlignment = Alignment.Center
            ) {
                Text(branchName.take(2).uppercase(), fontWeight = FontWeight.Bold,
                    color = primaryColor, fontSize = 13.sp)
            }
            Column(Modifier.weight(1f)) {
                Text(branchName, fontWeight = FontWeight.Bold, fontSize = 14.sp,
                    maxLines = 1, color = MaterialTheme.colorScheme.onSurface)
                Text(userName, fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant, maxLines = 1)
            }
            Surface(shape = RoundedCornerShape(6.dp), color = primaryColor.copy(alpha = 0.1f)) {
                Text("VENTA", modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                    fontWeight = FontWeight.Bold, fontSize = 11.sp,
                    color = primaryColor, letterSpacing = 1.sp)
            }
        }
    }
}

// ─── Draw carousel ────────────────────────────────────────────────────────────

@Composable
private fun DrawCarousel(
    draws: List<DrawEntity>,
    selectedIds: Set<Long>,
    tickFlow: StateFlow<Long>,
    onToggle: (Long) -> Unit,
    onSelectAll: () -> Unit,
    onDeselectAll: () -> Unit,
    onClear: () -> Unit,
    hasJugadas: Boolean,
    primaryColor: Color,
    countdownFor: (DrawEntity, Long) -> String?,
) {
    // El tick solo entra aqui; el resto de SaleScreen no se entera del segundo.
    val nowMillis by tickFlow.collectAsStateWithLifecycle()
    val allSelected = draws.isNotEmpty() && selectedIds.size == draws.size

    Column(Modifier.fillMaxWidth().padding(top = 8.dp, bottom = 4.dp)) {
        Row(
            Modifier.fillMaxWidth().padding(horizontal = 16.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                if (selectedIds.isNotEmpty()) "Sorteos (${selectedIds.size})" else "Sorteos",
                style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurfaceVariant, fontSize = 10.sp, letterSpacing = 0.5.sp
            )
            Row(verticalAlignment = Alignment.CenterVertically) {
                TextButton(
                    onClick = if (allSelected) onDeselectAll else onSelectAll,
                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 0.dp),
                    enabled = draws.isNotEmpty()
                ) {
                    Text(if (allSelected) "Ninguna" else "Todas",
                        color = primaryColor, fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                }
                Text("|", color = MaterialTheme.colorScheme.outlineVariant, fontSize = 12.sp)
                TextButton(
                    onClick = onClear,
                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 0.dp),
                    enabled = hasJugadas || selectedIds.isNotEmpty()
                ) {
                    Text("Limpiar", color = primaryColor, fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                }
            }
        }

        if (draws.isEmpty()) {
            Box(Modifier.fillMaxWidth().height(68.dp), contentAlignment = Alignment.Center) {
                Text("Sin sorteos — sincroniza para cargar", fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant, textAlign = TextAlign.Center)
            }
        } else {
            Row(
                Modifier
                    .horizontalScroll(rememberScrollState())
                    .padding(horizontal = 16.dp, vertical = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                draws.forEach { draw ->
                    DrawCard(
                        draw = draw, isSelected = draw.id in selectedIds,
                        countdown = countdownFor(draw, nowMillis),
                        onClick = { onToggle(draw.id) }, primaryColor = primaryColor
                    )
                }
            }
        }
    }
}

@Composable
private fun DrawCard(
    draw: DrawEntity, isSelected: Boolean, countdown: String?,
    onClick: () -> Unit, primaryColor: Color
) {
    val cardColor by animateColorAsState(
        targetValue = if (isSelected) primaryColor else MaterialTheme.colorScheme.surface,
        animationSpec = tween(300), label = "DrawCardColor"
    )
    val textColor = if (isSelected) Color.White else MaterialTheme.colorScheme.onSurface
    val subColor = if (isSelected) Color.White.copy(0.85f) else MaterialTheme.colorScheme.onSurfaceVariant

    Surface(
        modifier = Modifier.defaultMinSize(minWidth = 90.dp).height(72.dp).clickable(onClick = onClick),
        shape = RoundedCornerShape(10.dp), color = cardColor,
        border = BorderStroke(if (isSelected) 2.dp else 1.dp,
            if (isSelected) primaryColor else MaterialTheme.colorScheme.outlineVariant),
        shadowElevation = if (isSelected) 4.dp else 0.dp
    ) {
        Column(
            Modifier.width(IntrinsicSize.Max).fillMaxHeight().padding(horizontal = 10.dp, vertical = 8.dp),
            verticalArrangement = Arrangement.SpaceBetween
        ) {
            Row(Modifier.fillMaxWidth(), Arrangement.SpaceBetween, Alignment.CenterVertically) {
                Text(draw.lotteryName.uppercase(), fontWeight = FontWeight.Bold, color = textColor,
                    fontSize = 11.sp, maxLines = 1, overflow = TextOverflow.Ellipsis,
                    modifier = Modifier.weight(1f))
                if (isSelected) Icon(Icons.Default.CheckCircle, null,
                    Modifier.size(14.dp), tint = Color.White)
            }
            Text(draw.name, color = subColor, fontSize = 9.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
            if (countdown != null) {
                Text(countdown,
                    color = if (isSelected) Color.White.copy(0.9f) else primaryColor,
                    fontWeight = FontWeight.SemiBold, fontSize = 10.sp, fontFamily = FontFamily.Monospace)
            } else {
                Text("Cerró",
                    color = if (isSelected) Color.White.copy(0.7f) else MaterialTheme.colorScheme.error,
                    fontSize = 10.sp, fontWeight = FontWeight.Medium)
            }
        }
    }
}

// ─── Jugadas table ────────────────────────────────────────────────────────────

@Composable
private fun JugadasHeader() {
    Row(
        Modifier
            .fillMaxWidth()
            .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
            .padding(horizontal = 12.dp, vertical = 5.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text("Lotería", Modifier.weight(1.5f), fontSize = 10.sp, fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text("Tipo", Modifier.width(34.dp), fontSize = 10.sp, fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text("Núm", Modifier.width(52.dp), fontSize = 10.sp, fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text("Monto", Modifier.width(68.dp), fontSize = 10.sp, fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurfaceVariant, textAlign = TextAlign.End)
        Spacer(Modifier.width(28.dp))
    }
}

@Composable
private fun JugadaRow(jugada: JugadaItem, onDelete: () -> Unit) {
    val primary = MaterialTheme.colorScheme.primary
    Row(
        Modifier.fillMaxWidth().padding(horizontal = 12.dp, vertical = 5.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(Modifier.weight(1.5f)) {
            Text(jugada.lotteryName.uppercase(),
                fontWeight = FontWeight.Bold, color = primary, fontSize = 12.sp,
                maxLines = 1, overflow = TextOverflow.Ellipsis)
            Row(horizontalArrangement = Arrangement.spacedBy(3.dp)) {
                if (jugada.flipped) {
                    JugadaBadge(label = "VOL", color = MaterialTheme.colorScheme.tertiary)
                }
                if (jugada.combined) {
                    JugadaBadge(label = "COMB", color = MaterialTheme.colorScheme.primary)
                }
                if (jugada.limitAdjusted) {
                    JugadaBadge(label = "AJUST", color = Color(0xFFE6A700))
                }
            }
        }
        Text(
            when (jugada.betTypeCode.uppercase()) {
                "QUINIELA" -> "Q"
                "PALE" -> "Pl"
                "TRIPLETA" -> "Tri"
                "SUPER_PALE" -> "SP"
                else -> jugada.betTypeCode.take(2)
            },
            Modifier.width(34.dp), fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurfaceVariant, fontSize = 12.sp
        )
        Text(jugada.numberValue, Modifier.width(52.dp),
            fontFamily = FontFamily.Monospace, fontWeight = FontWeight.Bold, fontSize = 15.sp,
            color = MaterialTheme.colorScheme.onSurface)
        Text(jugada.amount, Modifier.width(68.dp),
            fontFamily = FontFamily.Monospace, fontWeight = FontWeight.Bold, fontSize = 13.sp,
            color = primary, textAlign = TextAlign.End, maxLines = 1)
        IconButton(onClick = onDelete, modifier = Modifier.size(28.dp)) {
            Icon(Icons.Default.Close, null, Modifier.size(16.dp),
                tint = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
    HorizontalDivider(thickness = 0.5.dp, color = MaterialTheme.colorScheme.outlineVariant.copy(0.4f))
}

@Composable
private fun JugadaBadge(label: String, color: Color) {
    Surface(
        shape = RoundedCornerShape(3.dp),
        color = color.copy(alpha = 0.15f),
    ) {
        Text(
            label,
            modifier = Modifier.padding(horizontal = 3.dp, vertical = 1.dp),
            fontSize = 8.sp, fontWeight = FontWeight.Bold, color = color, letterSpacing = 0.3.sp,
        )
    }
}

// ─── Cart toolbar (Voltear / Combinar) ────────────────────────────────────────

@Composable
private fun JugadasToolbar(
    count: Int,
    voltearActivo: Boolean,
    onVoltear: () -> Unit,
    onCombinar: () -> Unit,
    primaryColor: Color,
) {
    Row(
        Modifier.fillMaxWidth().padding(horizontal = 12.dp, vertical = 6.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Text(
            "Ticket actual ($count)",
            fontWeight = FontWeight.Bold, fontSize = 12.sp,
            color = MaterialTheme.colorScheme.onSurface,
        )
        Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
            AssistChip(
                onClick = onVoltear,
                label = { Text(if (voltearActivo) "Volteado" else "Voltear", fontSize = 11.sp) },
                leadingIcon = {
                    Icon(Icons.Default.SwapHoriz, null, Modifier.size(14.dp))
                },
                colors = if (voltearActivo) AssistChipDefaults.assistChipColors(
                    containerColor = MaterialTheme.colorScheme.tertiary.copy(0.15f),
                    labelColor = MaterialTheme.colorScheme.tertiary,
                    leadingIconContentColor = MaterialTheme.colorScheme.tertiary,
                ) else AssistChipDefaults.assistChipColors(),
            )
            AssistChip(
                onClick = onCombinar,
                label = { Text("Combinar", fontSize = 11.sp) },
                leadingIcon = {
                    Icon(Icons.Default.Shuffle, null, Modifier.size(14.dp))
                },
                colors = AssistChipDefaults.assistChipColors(
                    containerColor = primaryColor.copy(0.1f),
                    labelColor = primaryColor,
                    leadingIconContentColor = primaryColor,
                ),
            )
        }
    }
}

// ─── Limit Conflict Dialog ────────────────────────────────────────────────────

@Composable
private fun LimitConflictDialog(
    dialog: LimitDialogState,
    onDecisionChange: (drawId: Long, action: String) -> Unit,
    onAdjustAll: () -> Unit,
    onApply: () -> Unit,
    onCancel: () -> Unit,
    primaryColor: Color,
) {
    AlertDialog(
        onDismissRequest = onCancel,
        icon = { Icon(Icons.Default.Warning, null, tint = Color(0xFFE6A700)) },
        title = { Text("Limite alcanzado", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text(
                    "Numero ${dialog.numberValue} por RD\$ ${"%.2f".format(dialog.requestedAmount)} no cabe en algunas loterias.",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Row(
                    Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End,
                ) {
                    TextButton(onClick = onAdjustAll) {
                        Icon(Icons.Default.AutoFixHigh, null, Modifier.size(14.dp))
                        Spacer(Modifier.width(4.dp))
                        Text("Ajustar todo", fontSize = 11.sp)
                    }
                }
                dialog.conflicts.forEachIndexed { idx, c ->
                    val decision = dialog.decisions.getOrNull(idx)?.action ?: "omit"
                    Surface(
                        shape = RoundedCornerShape(8.dp),
                        color = when (c.status) {
                            "ok" -> Color(0xFFE8F5E9)
                            "partial" -> Color(0xFFFFF8E1)
                            else -> Color(0xFFFFEBEE)
                        },
                        border = BorderStroke(1.dp, when (c.status) {
                            "ok" -> Color(0xFF66BB6A)
                            "partial" -> Color(0xFFFFB300)
                            else -> Color(0xFFEF5350)
                        }),
                        modifier = Modifier.fillMaxWidth(),
                    ) {
                        Column(Modifier.padding(8.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                            Text(c.drawLabel, fontWeight = FontWeight.SemiBold, fontSize = 12.sp,
                                maxLines = 1, overflow = TextOverflow.Ellipsis)
                            when (c.status) {
                                "ok" -> Text(
                                    "Disponible: RD\$ ${"%.2f".format(c.effective ?: 0.0)} - se anadira con RD\$ ${"%.2f".format(c.requested)}",
                                    fontSize = 11.sp, color = Color(0xFF2E7D32),
                                )
                                "partial" -> {
                                    Text(
                                        "Disponible: RD\$ ${"%.2f".format(c.effective ?: 0.0)} de RD\$ ${"%.2f".format(c.requested)} solicitado" +
                                            if (c.pendingInCart > 0) " (ya en cart: RD\$ ${"%.2f".format(c.pendingInCart)})" else "",
                                        fontSize = 11.sp,
                                    )
                                    Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                                        FilterChip(
                                            selected = decision == "adjust",
                                            onClick = { onDecisionChange(c.drawId, "adjust") },
                                            label = { Text("Jugar RD\$ ${"%.2f".format(c.effective ?: 0.0)}", fontSize = 10.sp) },
                                        )
                                        FilterChip(
                                            selected = decision == "omit",
                                            onClick = { onDecisionChange(c.drawId, "omit") },
                                            label = { Text("Omitir", fontSize = 10.sp) },
                                        )
                                    }
                                }
                                else -> {
                                    Text("Numero agotado en esta loteria.", fontSize = 11.sp, color = Color(0xFFC62828))
                                    FilterChip(
                                        selected = true, onClick = { onDecisionChange(c.drawId, "omit") },
                                        label = { Text("Omitir", fontSize = 10.sp) },
                                    )
                                }
                            }
                        }
                    }
                }
            }
        },
        confirmButton = {
            Button(onClick = onApply, colors = ButtonDefaults.buttonColors(containerColor = primaryColor)) {
                Icon(Icons.Default.Check, null, Modifier.size(16.dp))
                Spacer(Modifier.width(4.dp))
                Text("Aplicar")
            }
        },
        dismissButton = {
            OutlinedButton(onClick = onCancel) { Text("Cancelar todo") }
        },
    )
}

// ─── Combinar Dialog ──────────────────────────────────────────────────────────

@Composable
private fun CombinarDialog(
    dialog: CombinarDialogState,
    preview: SaleViewModel.CombinarPreview,
    sourceSummary: String,
    canSuper: Boolean,
    onChange: ((CombinarDialogState) -> CombinarDialogState) -> Unit,
    onConfirm: () -> Unit,
    onCancel: () -> Unit,
    primaryColor: Color,
) {
    AlertDialog(
        onDismissRequest = onCancel,
        icon = { Icon(Icons.Default.Shuffle, null, tint = primaryColor) },
        title = { Text("Combinar jugadas", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text("Fuente: $sourceSummary", fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant)

                if (preview.total == 0) {
                    Surface(
                        shape = RoundedCornerShape(6.dp),
                        color = Color(0xFFE3F2FD),
                        modifier = Modifier.fillMaxWidth(),
                    ) {
                        Text(
                            "Selecciona al menos una opcion. Pale requiere 2+ numeros; Tripleta 3+; Super requiere 2 loterias.",
                            modifier = Modifier.padding(8.dp), fontSize = 11.sp, color = Color(0xFF0D47A1),
                        )
                    }
                }

                CombinarOption(
                    label = "Pale", description = "2 numeros, misma loteria",
                    checked = dialog.pale, count = preview.pale,
                    monto = dialog.montoPale,
                    onChecked = { onChange { it.copy(pale = !it.pale) } },
                    onMontoChange = { v -> onChange { it.copy(montoPale = v) } },
                    primaryColor = primaryColor,
                )
                CombinarOption(
                    label = "Tripleta", description = "3 numeros, misma loteria",
                    checked = dialog.tripleta, count = preview.tripleta,
                    monto = dialog.montoTripleta,
                    onChecked = { onChange { it.copy(tripleta = !it.tripleta) } },
                    onMontoChange = { v -> onChange { it.copy(montoTripleta = v) } },
                    primaryColor = primaryColor,
                )
                CombinarOption(
                    label = "Super Pale",
                    description = if (canSuper) "2 numeros, 2 loterias" else "Requiere 2+ loterias con numeros",
                    checked = dialog.superPale, count = preview.superPale,
                    monto = dialog.montoSuper,
                    enabled = canSuper,
                    onChecked = { onChange { it.copy(superPale = !it.superPale) } },
                    onMontoChange = { v -> onChange { it.copy(montoSuper = v) } },
                    primaryColor = primaryColor,
                )
                CombinarOption(
                    label = "Quiniela", description = "1 por numero unico por loteria",
                    checked = dialog.quiniela, count = preview.quiniela,
                    monto = dialog.montoQuiniela,
                    onChecked = { onChange { it.copy(quiniela = !it.quiniela) } },
                    onMontoChange = { v -> onChange { it.copy(montoQuiniela = v) } },
                    primaryColor = primaryColor,
                )

                OutlinedTextField(
                    value = dialog.montoGlobal,
                    onValueChange = { v -> onChange { it.copy(montoGlobal = v) } },
                    label = { Text("Monto global (fallback)", fontSize = 11.sp) },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                    textStyle = androidx.compose.ui.text.TextStyle(fontFamily = FontFamily.Monospace, fontSize = 13.sp),
                )
            }
        },
        confirmButton = {
            Button(
                onClick = onConfirm,
                enabled = preview.total > 0,
                colors = ButtonDefaults.buttonColors(containerColor = primaryColor),
            ) {
                Text("Agregar ${preview.total}")
            }
        },
        dismissButton = {
            OutlinedButton(onClick = onCancel) { Text("Cancelar") }
        },
    )
}

@Composable
private fun CombinarOption(
    label: String,
    description: String,
    checked: Boolean,
    count: Int,
    monto: String,
    enabled: Boolean = true,
    onChecked: () -> Unit,
    onMontoChange: (String) -> Unit,
    primaryColor: Color,
) {
    Surface(
        shape = RoundedCornerShape(6.dp),
        color = if (checked) primaryColor.copy(0.07f) else MaterialTheme.colorScheme.surfaceVariant.copy(0.4f),
        border = BorderStroke(1.dp, if (checked) primaryColor else MaterialTheme.colorScheme.outlineVariant),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(8.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Checkbox(checked = checked, onCheckedChange = { onChecked() }, enabled = enabled)
                Column(Modifier.weight(1f)) {
                    Text(label, fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                    Text(description, fontSize = 10.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                if (checked && count > 0) {
                    Surface(shape = RoundedCornerShape(4.dp), color = primaryColor) {
                        Text("$count", modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                            color = Color.White, fontWeight = FontWeight.Bold, fontSize = 11.sp)
                    }
                }
            }
            if (checked) {
                OutlinedTextField(
                    value = monto, onValueChange = onMontoChange,
                    label = { Text("Monto (opcional)", fontSize = 10.sp) },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                    textStyle = androidx.compose.ui.text.TextStyle(fontFamily = FontFamily.Monospace, fontSize = 12.sp),
                )
            }
        }
    }
}

// ─── Footer ────────────────────────────────────────────────────────────────────

@Composable
private fun FooterSection(
    total: String, jugadasCount: Int, isLoading: Boolean,
    onSell: () -> Unit, primaryColor: Color
) {
    Surface(shadowElevation = 4.dp, color = MaterialTheme.colorScheme.surface) {
        Row(
            Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 10.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column {
                Text("Total", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Text("RD\$ $total", fontFamily = FontFamily.Monospace,
                    fontWeight = FontWeight.Bold, fontSize = 22.sp,
                    color = MaterialTheme.colorScheme.onSurface)
            }
            Button(
                onClick = onSell,
                modifier = Modifier.height(52.dp).widthIn(min = 140.dp),
                shape = RoundedCornerShape(12.dp),
                enabled = jugadasCount > 0 && !isLoading
            ) {
                if (isLoading) {
                    CircularProgressIndicator(Modifier.size(20.dp), strokeWidth = 2.dp,
                        color = MaterialTheme.colorScheme.onPrimary)
                } else {
                    Text("VENDER", fontWeight = FontWeight.Bold, fontSize = 15.sp, letterSpacing = 1.sp)
                    Spacer(Modifier.width(8.dp))
                    Icon(Icons.AutoMirrored.Filled.ArrowForward, null, Modifier.size(18.dp))
                }
            }
        }
    }
}

// ─── Custom keypad panel ──────────────────────────────────────────────────────

@Composable
private fun KeypadPanel(
    state: SaleUiState,
    onDigit: (String) -> Unit,
    onBackspace: () -> Unit,
    onEnter: () -> Unit,
    onActivateNumero: () -> Unit,
    onActivateMonto: () -> Unit,
    onToggleSuper: () -> Unit,
    onHide: () -> Unit,
    primaryColor: Color
) {
    val showArrow = isCompleteNumberLength(state) && state.amountInput.isBlank() && !state.isMontoActive

    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(topStart = 16.dp, topEnd = 16.dp),
        color = MaterialTheme.colorScheme.surfaceVariant,
        shadowElevation = 8.dp
    ) {
        Column(
            Modifier
                .fillMaxWidth()
                .navigationBarsPadding()
                .padding(horizontal = 12.dp)
        ) {
            // ─ Header: Super chip + hide button ───────────────────────────
            Row(
                Modifier.fillMaxWidth().padding(top = 8.dp, bottom = 4.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                val superBt = state.betTypes.firstOrNull { it.code.equals("SUPER_PALE", ignoreCase = true) }
                if (superBt != null) {
                    FilterChip(
                        selected = state.isSuperMode,
                        onClick = onToggleSuper,
                        label = { Text("Super", fontSize = 12.sp, fontWeight = FontWeight.SemiBold) },
                        leadingIcon = if (state.isSuperMode) {
                            { Icon(Icons.Default.Star, null, Modifier.size(14.dp)) }
                        } else null,
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = primaryColor,
                            selectedLabelColor = Color.White,
                            selectedLeadingIconColor = Color.White
                        )
                    )
                } else {
                    Spacer(Modifier.width(1.dp))
                }
                IconButton(onClick = onHide, modifier = Modifier.size(32.dp)) {
                    Icon(Icons.Default.KeyboardArrowDown, "Ocultar teclado",
                        tint = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            }

            // ─ Input display fields ───────────────────────────────────────
            Row(
                Modifier.fillMaxWidth().padding(bottom = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                InputDisplay(
                    label = "Número",
                    value = state.numberInput,
                    isActive = !state.isMontoActive,
                    modifier = Modifier.weight(1f).clickable(onClick = onActivateNumero),
                    primaryColor = primaryColor
                )
                InputDisplay(
                    label = "Monto",
                    value = state.amountInput,
                    isActive = state.isMontoActive,
                    prefix = if (state.amountInput.isNotBlank()) "$ " else "",
                    modifier = Modifier.weight(1f).clickable(onClick = onActivateMonto),
                    primaryColor = primaryColor
                )
            }

            // ─ Digit grid 1–9 ─────────────────────────────────────────────
            listOf(listOf("1","2","3"), listOf("4","5","6"), listOf("7","8","9")).forEach { row ->
                Row(
                    Modifier.fillMaxWidth().padding(bottom = 5.dp),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    row.forEach { d ->
                        KeypadButton(text = d, onClick = { onDigit(d) }, modifier = Modifier.weight(1f))
                    }
                }
            }

            // ─ Bottom row: ⌫ | 0 | → / + ──────────────────────────────────
            Row(
                Modifier.fillMaxWidth().padding(bottom = 10.dp),
                horizontalArrangement = Arrangement.spacedBy(6.dp)
            ) {
                KeypadButton(icon = Icons.AutoMirrored.Filled.Backspace, iconTint = MaterialTheme.colorScheme.error,
                    onClick = onBackspace, modifier = Modifier.weight(1f))
                KeypadButton(text = "0", onClick = { onDigit("0") }, modifier = Modifier.weight(1f))
                KeypadButton(
                    icon = if (showArrow) Icons.AutoMirrored.Filled.ArrowForward else Icons.Default.Add,
                    iconTint = primaryColor,
                    onClick = onEnter,
                    modifier = Modifier.weight(1f)
                )
            }
        }
    }
}

@Composable
private fun InputDisplay(
    label: String,
    value: String,
    isActive: Boolean,
    modifier: Modifier = Modifier,
    primaryColor: Color,
    prefix: String = ""
) {
    val infiniteTransition = rememberInfiniteTransition(label = "cursor")
    val cursorVisible by infiniteTransition.animateFloat(
        initialValue = 1f, targetValue = 0f,
        animationSpec = infiniteRepeatable(tween(500, easing = LinearEasing), RepeatMode.Reverse),
        label = "cursorBlink"
    )

    Column(modifier) {
        Text(
            label, fontSize = 10.sp,
            color = if (isActive) primaryColor else MaterialTheme.colorScheme.onSurfaceVariant,
            fontWeight = if (isActive) FontWeight.Bold else FontWeight.Normal,
            modifier = Modifier.padding(bottom = 2.dp)
        )
        Surface(
            Modifier.fillMaxWidth().height(52.dp),
            shape = RoundedCornerShape(8.dp),
            color = MaterialTheme.colorScheme.surface,
            border = BorderStroke(
                if (isActive) 2.dp else 1.dp,
                if (isActive) primaryColor else MaterialTheme.colorScheme.outline.copy(0.4f)
            )
        ) {
            Box(Modifier.fillMaxSize().padding(horizontal = 10.dp), Alignment.CenterStart) {
                val displayText = buildString {
                    append(prefix)
                    append(value)
                    if (isActive) append(if (cursorVisible > 0.5f) "|" else " ")
                }
                Text(
                    text = if (displayText.isBlank() && !isActive) "—" else displayText,
                    fontFamily = FontFamily.Monospace,
                    fontWeight = FontWeight.Bold,
                    fontSize = 20.sp,
                    color = if (value.isBlank() && !isActive)
                        MaterialTheme.colorScheme.onSurface.copy(0.25f)
                    else MaterialTheme.colorScheme.onSurface,
                    maxLines = 1
                )
            }
        }
    }
}

@Composable
private fun KeypadButton(
    modifier: Modifier = Modifier,
    text: String? = null,
    icon: ImageVector? = null,
    iconTint: Color = Color.Unspecified,
    onClick: () -> Unit
) {
    Surface(
        modifier = modifier.height(46.dp).clickable(onClick = onClick),
        shape = RoundedCornerShape(8.dp),
        color = MaterialTheme.colorScheme.surface,
        border = BorderStroke(0.5.dp, MaterialTheme.colorScheme.outline.copy(0.2f))
    ) {
        Box(contentAlignment = Alignment.Center) {
            when {
                icon != null -> Icon(icon, null, tint = iconTint, modifier = Modifier.size(22.dp))
                text != null -> Text(text, fontFamily = FontFamily.Monospace,
                    fontWeight = FontWeight.Bold, fontSize = 18.sp,
                    color = MaterialTheme.colorScheme.onSurface)
            }
        }
    }
}

private fun maxNumberLength(state: SaleUiState): Int {
    if (state.isSuperMode) return 4
    return 6
}

private fun isCompleteNumberLength(state: SaleUiState): Boolean =
    if (state.isSuperMode) state.numberInput.length == 4 else state.numberInput.length in setOf(2, 4, 6)
