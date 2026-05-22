package dev.bsolutions.bsloteria.ui.screen.sale

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import dev.bsolutions.bsloteria.data.local.dao.BetTypeDao
import dev.bsolutions.bsloteria.data.local.entity.BetTypeEntity
import dev.bsolutions.bsloteria.data.local.entity.DrawEntity
import dev.bsolutions.bsloteria.data.repository.TicketRepository
import dev.bsolutions.bsloteria.domain.model.SaleDetail
import dev.bsolutions.bsloteria.printer.BluetoothPrinterManager
import dev.bsolutions.bsloteria.util.Result
import dev.bsolutions.bsloteria.util.SessionStore
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.async
import kotlinx.coroutines.delay
import kotlinx.coroutines.withContext
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import java.math.BigDecimal
import java.math.RoundingMode
import javax.inject.Inject

data class JugadaItem(
    val drawId: Long,
    val drawName: String,
    val lotteryName: String,
    val betTypeId: Long,
    val betTypeName: String,
    val betTypeCode: String,
    val numberValue: String,
    val amount: String,
    val flipped: Boolean = false,
    val combined: Boolean = false,
    val limitAdjusted: Boolean = false,
)

/** Estado por draw para el modal de conflictos de limite. */
data class LimitConflictItem(
    val drawId: Long,
    val drawLabel: String,
    /** Monto disponible reportado por el backend (sin restar cart). null = sin reglas. */
    val available: Double?,
    /** Lo que el cajero ya tiene pendiente en el cart para este draw+betType+number. */
    val pendingInCart: Double,
    /** disponible_backend - pendingInCart, clamped >= 0. null si available es null. */
    val effective: Double?,
    /** Monto solicitado original. */
    val requested: Double,
    /** "ok" | "partial" | "agotado". */
    val status: String,
)

data class LimitConflictDecision(
    val drawId: Long,
    /** "ok" | "adjust" | "omit" */
    val action: String,
)

data class LimitDialogState(
    val visible: Boolean = false,
    val numberValue: String = "",
    val betTypeId: Long = 0L,
    val betTypeCode: String = "",
    val betTypeName: String = "",
    val requestedAmount: Double = 0.0,
    val conflicts: List<LimitConflictItem> = emptyList(),
    val decisions: List<LimitConflictDecision> = emptyList(),
)

data class CombinarDialogState(
    val visible: Boolean = false,
    val pale: Boolean = false,
    val tripleta: Boolean = false,
    val superPale: Boolean = false,
    val quiniela: Boolean = false,
    val montoPale: String = "",
    val montoTripleta: String = "",
    val montoSuper: String = "",
    val montoQuiniela: String = "",
    val montoGlobal: String = "",
)

data class SaleUiState(
    val draws: List<DrawEntity> = emptyList(),
    val betTypes: List<BetTypeEntity> = emptyList(),
    val selectedDrawIds: Set<Long> = emptySet(),
    val jugadas: List<JugadaItem> = emptyList(),
    val numberInput: String = "",
    val amountInput: String = "",
    val selectedBetTypeId: Long? = null,
    val nowMillis: Long = System.currentTimeMillis(),
    val isLoading: Boolean = false,
    val successUuid: String? = null,
    val error: String? = null,
    val isMontoActive: Boolean = false,
    val isSuperMode: Boolean = false,
    val isKeypadVisible: Boolean = true,
    val printMessage: String? = null,
    val voltearActivo: Boolean = false,
    val preCheckLoading: Boolean = false,
    val limitDialog: LimitDialogState = LimitDialogState(),
    val combinarDialog: CombinarDialogState = CombinarDialogState(),
)

@HiltViewModel
class SaleViewModel @Inject constructor(
    private val ticketRepository: TicketRepository,
    private val betTypeDao: BetTypeDao,
    private val sessionStore: SessionStore,
    private val printerManager: BluetoothPrinterManager
) : ViewModel() {

    private val _state = MutableStateFlow(SaleUiState())
    val state: StateFlow<SaleUiState> = _state

    val session = sessionStore.sessionFlow.stateIn(viewModelScope, SharingStarted.Eagerly, null)

    private var lastSoldJugadas: List<JugadaItem> = emptyList()
    private var lastSoldTotal: String = "0.00"
    private var lastSoldUuid: String = ""

    init {
        viewModelScope.launch {
            ticketRepository.observeOpenDraws().collect { draws ->
                _state.update { s ->
                    val validSelected = s.selectedDrawIds.filter { id -> draws.any { it.id == id } }.toSet()
                    s.copy(draws = draws, selectedDrawIds = validSelected)
                }
            }
        }
        viewModelScope.launch {
            betTypeDao.observeAll().collect { types ->
                _state.update { it.copy(betTypes = types) }
            }
        }
        viewModelScope.launch {
            while (true) {
                delay(1000L)
                _state.update { it.copy(nowMillis = System.currentTimeMillis()) }
            }
        }
    }

    // ── Draw selection ──────────────────────────────────────────────────────────

    fun toggleDraw(drawId: Long) {
        _state.update { s ->
            val updated = if (drawId in s.selectedDrawIds)
                s.selectedDrawIds - drawId else s.selectedDrawIds + drawId
            s.copy(selectedDrawIds = updated)
        }
    }

    fun selectAllDraws() = _state.update { s -> s.copy(selectedDrawIds = s.draws.map { it.id }.toSet()) }
    fun deselectAllDraws() = _state.update { it.copy(selectedDrawIds = emptySet()) }

    // ── Keypad actions ──────────────────────────────────────────────────────────

    fun toggleKeypad() = _state.update { it.copy(isKeypadVisible = !it.isKeypadVisible) }
    fun activateNumero() = _state.update { it.copy(isMontoActive = false) }
    fun activateMonto() = _state.update { it.copy(isMontoActive = true) }

    fun toggleSuperMode() {
        _state.update { s ->
            val superType = s.betTypes.firstOrNull { it.code.equals("SUPER_PALE", ignoreCase = true) }
            if (s.isSuperMode) {
                s.copy(isSuperMode = false, numberInput = "", amountInput = "",
                    isMontoActive = false, selectedBetTypeId = null)
            } else {
                s.copy(isSuperMode = true, numberInput = "", amountInput = "",
                    isMontoActive = false, selectedBetTypeId = superType?.id ?: s.selectedBetTypeId)
            }
        }
    }

    fun onKeypadDigit(digit: String) {
        _state.update { s ->
            val maxLen = maxNumberLength(s)
            when {
                s.isMontoActive -> {
                    val newMonto = s.amountInput + digit
                    if (newMonto.length <= 7) s.copy(amountInput = newMonto, error = null) else s
                }
                s.numberInput.length < maxLen -> {
                    val newNum = s.numberInput + digit
                    val autoType = if (!s.isSuperMode) autoDetectBetType(newNum, s.betTypes) else null
                    s.copy(
                        numberInput = newNum,
                        selectedBetTypeId = autoType ?: s.selectedBetTypeId,
                        isMontoActive = false,
                        error = null
                    )
                }
                else -> s
            }
        }
    }

    fun onKeypadBackspace() {
        _state.update { s ->
            when {
                s.isMontoActive && s.amountInput.isNotEmpty() ->
                    s.copy(amountInput = s.amountInput.dropLast(1))
                s.isMontoActive ->
                    s.copy(isMontoActive = false)
                s.numberInput.isNotEmpty() -> {
                    val newNum = s.numberInput.dropLast(1)
                    val autoType = autoDetectBetType(newNum, s.betTypes)
                    s.copy(numberInput = newNum, selectedBetTypeId = autoType ?: s.selectedBetTypeId)
                }
                else -> s
            }
        }
    }

    fun onKeypadEnter() {
        val s = _state.value
        if (isCompleteNumberLength(s) && !s.isMontoActive) {
            activateMonto()
        } else {
            addJugada()
        }
    }

    // ── Jugadas ─────────────────────────────────────────────────────────────────

    fun addJugada() {
        val s = _state.value
        if (s.selectedDrawIds.isEmpty()) { _state.update { it.copy(error = "Seleccione al menos un sorteo") }; return }
        if (s.numberInput.isBlank()) { _state.update { it.copy(error = "Ingrese un número") }; return }
        val amount = s.amountInput.toBigDecimalOrNull()?.setScale(2, RoundingMode.HALF_UP)
        if (amount == null || amount <= BigDecimal.ZERO) { _state.update { it.copy(error = "Monto inválido") }; return }
        val betType = resolveBetTypeForNumber(s)
            ?: run { _state.update { it.copy(error = "La jugada debe tener 2, 4 o 6 dígitos completos") }; return }

        val activeDrawIds = s.selectedDrawIds.toList()
        val requestedAmount = amount.toDouble()
        val numberValue = s.numberInput

        viewModelScope.launch {
            _state.update { it.copy(preCheckLoading = true, error = null) }

            val conflicts = resolveLimitConflicts(
                drawIds = activeDrawIds,
                betTypeId = betType.id,
                numberValue = numberValue,
                requestedAmount = requestedAmount,
            )

            _state.update { it.copy(preCheckLoading = false) }

            // Sin conflictos: anadir directo
            if (conflicts.all { it.status == "ok" }) {
                addJugadasDirect(activeDrawIds, betType, numberValue, amount.toPlainString(), adjusted = false)
                return@launch
            }

            // Con conflictos: abrir dialog
            val decisions = conflicts.map { c ->
                LimitConflictDecision(
                    drawId = c.drawId,
                    action = when (c.status) {
                        "ok" -> "ok"
                        "partial" -> "adjust"
                        else -> "omit"
                    }
                )
            }
            _state.update {
                it.copy(
                    limitDialog = LimitDialogState(
                        visible = true,
                        numberValue = numberValue,
                        betTypeId = betType.id,
                        betTypeCode = betType.code,
                        betTypeName = betType.name,
                        requestedAmount = requestedAmount,
                        conflicts = conflicts,
                        decisions = decisions,
                    )
                )
            }
        }
    }

    private suspend fun resolveLimitConflicts(
        drawIds: List<Long>,
        betTypeId: Long,
        numberValue: String,
        requestedAmount: Double,
    ): List<LimitConflictItem> = withContext(Dispatchers.IO) {
        val numberDigits = numberValue.replace("-", "")
        val current = _state.value

        drawIds.map { drawId ->
            async {
                val response = ticketRepository.checkLimit(drawId, betTypeId, numberDigits)
                val available = response?.available
                val pendingInCart = getCartPendingFor(drawId, betTypeId, numberValue)
                val effective = available?.let { maxOf(0.0, it - pendingInCart) }
                val status = when {
                    effective == null -> "ok"
                    effective >= requestedAmount -> "ok"
                    effective > 0.0 -> "partial"
                    else -> "agotado"
                }
                val draw = current.draws.firstOrNull { it.id == drawId }
                val label = draw?.let { "${it.lotteryName} — ${it.name}" } ?: "Loteria $drawId"

                LimitConflictItem(
                    drawId = drawId,
                    drawLabel = label,
                    available = available,
                    pendingInCart = pendingInCart,
                    effective = effective,
                    requested = requestedAmount,
                    status = status,
                )
            }
        }.map { it.await() }
    }

    private fun getCartPendingFor(drawId: Long, betTypeId: Long, numberValue: String): Double {
        return _state.value.jugadas
            .filter { it.drawId == drawId && it.betTypeId == betTypeId && it.numberValue == numberValue }
            .sumOf { it.amount.toBigDecimalOrNull()?.toDouble() ?: 0.0 }
    }

    private fun addJugadasDirect(
        drawIds: List<Long>,
        betType: BetTypeEntity,
        numberValue: String,
        amount: String,
        adjusted: Boolean,
    ) {
        val s = _state.value
        val newJugadas = drawIds.mapNotNull { drawId ->
            val draw = s.draws.firstOrNull { it.id == drawId } ?: return@mapNotNull null
            JugadaItem(
                drawId = drawId,
                drawName = draw.name,
                lotteryName = draw.lotteryName,
                betTypeId = betType.id,
                betTypeName = betType.name,
                betTypeCode = betType.code,
                numberValue = numberValue,
                amount = amount,
                limitAdjusted = adjusted,
            )
        }
        _state.update {
            it.copy(
                jugadas = it.jugadas + newJugadas,
                numberInput = "",
                amountInput = "",
                isMontoActive = false,
                error = null
            )
        }
    }

    fun applyLimitDecisions() {
        val dialog = _state.value.limitDialog
        if (!dialog.visible) return

        val betType = _state.value.betTypes.firstOrNull { it.id == dialog.betTypeId } ?: run {
            cancelLimitDialog(); return
        }

        dialog.decisions.forEach { d ->
            val conflict = dialog.conflicts.firstOrNull { it.drawId == d.drawId } ?: return@forEach
            val amount: Double = when (d.action) {
                "ok" -> dialog.requestedAmount
                "adjust" -> conflict.effective ?: return@forEach
                else -> return@forEach
            }
            if (amount <= 0.0) return@forEach

            val amountStr = BigDecimal(amount).setScale(2, RoundingMode.HALF_UP).toPlainString()
            addJugadasDirect(
                drawIds = listOf(d.drawId),
                betType = betType,
                numberValue = dialog.numberValue,
                amount = amountStr,
                adjusted = d.action == "adjust",
            )
        }

        _state.update { it.copy(limitDialog = LimitDialogState()) }
    }

    fun updateLimitDecision(drawId: Long, action: String) {
        _state.update { s ->
            val updated = s.limitDialog.decisions.map { d ->
                if (d.drawId == drawId) d.copy(action = action) else d
            }
            s.copy(limitDialog = s.limitDialog.copy(decisions = updated))
        }
    }

    fun applyAllAdjust() {
        _state.update { s ->
            val updated = s.limitDialog.decisions.map { d ->
                val c = s.limitDialog.conflicts.firstOrNull { it.drawId == d.drawId }
                if (c != null && c.status == "agotado") d.copy(action = "omit") else d.copy(action = "adjust")
            }
            s.copy(limitDialog = s.limitDialog.copy(decisions = updated))
        }
    }

    fun cancelLimitDialog() {
        _state.update { it.copy(limitDialog = LimitDialogState()) }
    }

    fun removeJugada(index: Int) {
        _state.update { it.copy(jugadas = it.jugadas.toMutableList().also { l -> l.removeAt(index) }) }
    }

    fun clearAll() {
        _state.update {
            it.copy(
                jugadas = emptyList(),
                selectedDrawIds = emptySet(),
                numberInput = "",
                amountInput = "",
                isMontoActive = false,
                voltearActivo = false,
                error = null
            )
        }
    }

    // ── Voltear ─────────────────────────────────────────────────────────────────

    /** Invierte cada chunk de 2 digitos: "12" -> "21", "12-34" -> "21-43". */
    private fun flipNumberValue(value: String): String =
        value.split("-").joinToString("-") { chunk ->
            if (chunk.length == 2) chunk.reversed() else chunk
        }

    fun toggleVoltear() {
        val s = _state.value
        if (s.voltearActivo) {
            // Quitar las auto-volteadas
            _state.update { it.copy(jugadas = it.jugadas.filterNot { j -> j.flipped }, voltearActivo = false) }
            return
        }
        val source = s.jugadas.filterNot { it.flipped }
        val flipped = mutableListOf<JugadaItem>()
        source.forEach { j ->
            val flippedValue = flipNumberValue(j.numberValue)
            if (flippedValue == j.numberValue) return@forEach // capicua
            val existsAlready = s.jugadas.any {
                it.drawId == j.drawId && it.betTypeId == j.betTypeId && it.numberValue == flippedValue
            }
            if (existsAlready) return@forEach
            flipped += j.copy(numberValue = flippedValue, flipped = true, combined = false, limitAdjusted = false)
        }
        if (flipped.isEmpty()) {
            _state.update { it.copy(error = "No se generaron jugadas volteadas (capicuas o ya existian)") }
            return
        }
        _state.update { it.copy(jugadas = it.jugadas + flipped, voltearActivo = true, error = null) }
    }

    // ── Combinar ────────────────────────────────────────────────────────────────

    fun openCombinarDialog() {
        if (_state.value.jugadas.isEmpty()) return
        val avgAmount = averagePlayAmount()
        _state.update {
            it.copy(
                combinarDialog = CombinarDialogState(
                    visible = true,
                    montoGlobal = BigDecimal(avgAmount).setScale(2, RoundingMode.HALF_UP).toPlainString(),
                )
            )
        }
    }

    fun updateCombinarDialog(transform: (CombinarDialogState) -> CombinarDialogState) {
        _state.update { it.copy(combinarDialog = transform(it.combinarDialog)) }
    }

    fun cancelCombinarDialog() {
        _state.update { it.copy(combinarDialog = CombinarDialogState()) }
    }

    private fun averagePlayAmount(): Double {
        val all = _state.value.jugadas.mapNotNull { it.amount.toBigDecimalOrNull()?.toDouble() }
        if (all.isEmpty()) return 0.0
        return all.average().let { BigDecimal(it).setScale(2, RoundingMode.HALF_UP).toDouble() }
    }

    /** Numeros unicos de 2 digitos por draw, extraidos de TODAS las jugadas (ignora SUPER_PALE). */
    private fun expandNumbersByDraw(): Map<Long, List<String>> {
        val map = mutableMapOf<Long, LinkedHashSet<String>>()
        _state.value.jugadas.forEach { j ->
            if (j.betTypeCode.equals("SUPER_PALE", ignoreCase = true)) return@forEach
            val chunks = j.numberValue.split("-").filter { it.length == 2 }
            if (chunks.isEmpty()) return@forEach
            val set = map.getOrPut(j.drawId) { linkedSetOf() }
            set.addAll(chunks)
        }
        return map.mapValues { it.value.sorted() }
    }

    data class CombinarPreview(val pale: Int, val tripleta: Int, val superPale: Int, val quiniela: Int) {
        val total: Int get() = pale + tripleta + superPale + quiniela
    }

    fun combinarPreview(): CombinarPreview {
        val s = _state.value
        val dialog = s.combinarDialog
        val byDraw = expandNumbersByDraw()
        var pale = 0; var tri = 0; var sup = 0; var q = 0

        byDraw.forEach { (_, numeros) ->
            val n = numeros.size
            if (dialog.pale && n >= 2) pale += n * (n - 1) / 2
            if (dialog.tripleta && n >= 3) tri += n * (n - 1) * (n - 2) / 6
        }
        if (dialog.superPale && canCombinarSuper()) {
            val withNumbers = byDraw.filter { it.value.isNotEmpty() }.entries.toList().take(2)
            if (withNumbers.size == 2) sup = withNumbers[0].value.size * withNumbers[1].value.size
        }
        if (dialog.quiniela) q = byDraw.values.sumOf { it.size }

        return CombinarPreview(pale, tri, sup, q)
    }

    fun canCombinarSuper(): Boolean = expandNumbersByDraw().count { it.value.isNotEmpty() } >= 2

    fun combinarSourceSummary(): String {
        val byDraw = expandNumbersByDraw()
        val totalNumbers = byDraw.values.sumOf { it.size }
        return "$totalNumbers numero(s) en ${byDraw.size} loteria(s)"
    }

    fun submitCombinar() {
        val s = _state.value
        val dialog = s.combinarDialog
        val byDraw = expandNumbersByDraw()
        val preview = combinarPreview()
        if (preview.total == 0) { cancelCombinarDialog(); return }

        val typeFor: (String) -> BetTypeEntity? = { code ->
            s.betTypes.firstOrNull { it.code.equals(code, ignoreCase = true) }
        }
        val global = dialog.montoGlobal.toDoubleOrNull() ?: averagePlayAmount()
        val amountFor: (String, String) -> Double = { own, _ ->
            val v = own.toDoubleOrNull()
            if (v != null && v > 0) v else global
        }

        val newJugadas = mutableListOf<JugadaItem>()

        // PALE: C(n,2)
        if (dialog.pale) {
            val bt = typeFor("PALE") ?: run { _state.update { it.copy(error = "Falta tipo PALE") }; return }
            val amount = amountFor(dialog.montoPale, "pale")
            val amountStr = BigDecimal(amount).setScale(2, RoundingMode.HALF_UP).toPlainString()
            byDraw.forEach { (drawId, numeros) ->
                if (numeros.size < 2) return@forEach
                val draw = s.draws.firstOrNull { it.id == drawId } ?: return@forEach
                for (i in 0 until numeros.size - 1) {
                    for (j in i + 1 until numeros.size) {
                        newJugadas += JugadaItem(
                            drawId = drawId, drawName = draw.name, lotteryName = draw.lotteryName,
                            betTypeId = bt.id, betTypeName = bt.name, betTypeCode = bt.code,
                            numberValue = "${numeros[i]}-${numeros[j]}",
                            amount = amountStr, combined = true,
                        )
                    }
                }
            }
        }

        // TRIPLETA: C(n,3)
        if (dialog.tripleta) {
            val bt = typeFor("TRIPLETA") ?: run { _state.update { it.copy(error = "Falta tipo TRIPLETA") }; return }
            val amount = amountFor(dialog.montoTripleta, "tripleta")
            val amountStr = BigDecimal(amount).setScale(2, RoundingMode.HALF_UP).toPlainString()
            byDraw.forEach { (drawId, numeros) ->
                if (numeros.size < 3) return@forEach
                val draw = s.draws.firstOrNull { it.id == drawId } ?: return@forEach
                for (i in 0 until numeros.size - 2) {
                    for (j in i + 1 until numeros.size - 1) {
                        for (k in j + 1 until numeros.size) {
                            newJugadas += JugadaItem(
                                drawId = drawId, drawName = draw.name, lotteryName = draw.lotteryName,
                                betTypeId = bt.id, betTypeName = bt.name, betTypeCode = bt.code,
                                numberValue = "${numeros[i]}-${numeros[j]}-${numeros[k]}",
                                amount = amountStr, combined = true,
                            )
                        }
                    }
                }
            }
        }

        // SUPER_PALE: cross-product 2 loterias
        if (dialog.superPale && canCombinarSuper()) {
            val bt = typeFor("SUPER_PALE") ?: run { _state.update { it.copy(error = "Falta tipo SUPER_PALE") }; return }
            val amount = amountFor(dialog.montoSuper, "super")
            val amountStr = BigDecimal(amount).setScale(2, RoundingMode.HALF_UP).toPlainString()
            val withNumbers = byDraw.filter { it.value.isNotEmpty() }.entries.toList().take(2)
            if (withNumbers.size == 2) {
                val (a, b) = withNumbers
                val drawA = s.draws.firstOrNull { it.id == a.key }
                a.value.forEach { n1 ->
                    b.value.forEach { n2 ->
                        if (drawA != null) {
                            newJugadas += JugadaItem(
                                drawId = drawA.id, drawName = drawA.name, lotteryName = drawA.lotteryName,
                                betTypeId = bt.id, betTypeName = bt.name, betTypeCode = bt.code,
                                numberValue = "$n1-$n2",
                                amount = amountStr, combined = true,
                            )
                        }
                    }
                }
            }
        }

        // QUINIELA: 1 por numero unico por loteria
        if (dialog.quiniela) {
            val bt = typeFor("QUINIELA") ?: run { _state.update { it.copy(error = "Falta tipo QUINIELA") }; return }
            val amount = amountFor(dialog.montoQuiniela, "quiniela")
            val amountStr = BigDecimal(amount).setScale(2, RoundingMode.HALF_UP).toPlainString()
            byDraw.forEach { (drawId, numeros) ->
                val draw = s.draws.firstOrNull { it.id == drawId } ?: return@forEach
                numeros.forEach { n ->
                    newJugadas += JugadaItem(
                        drawId = drawId, drawName = draw.name, lotteryName = draw.lotteryName,
                        betTypeId = bt.id, betTypeName = bt.name, betTypeCode = bt.code,
                        numberValue = n, amount = amountStr, combined = true,
                    )
                }
            }
        }

        // Dedup: si ya existe, sumar monto en la jugada existente; sino agregar nueva
        val merged = s.jugadas.toMutableList()
        newJugadas.forEach { nj ->
            val existingIdx = merged.indexOfFirst {
                it.drawId == nj.drawId && it.betTypeId == nj.betTypeId && it.numberValue == nj.numberValue
            }
            if (existingIdx >= 0) {
                val existing = merged[existingIdx]
                val sum = (existing.amount.toBigDecimalOrNull() ?: BigDecimal.ZERO)
                    .add(nj.amount.toBigDecimalOrNull() ?: BigDecimal.ZERO)
                    .setScale(2, RoundingMode.HALF_UP)
                merged[existingIdx] = existing.copy(amount = sum.toPlainString())
            } else {
                merged += nj
            }
        }

        _state.update { it.copy(jugadas = merged, combinarDialog = CombinarDialogState(), error = null) }
    }

    fun sell() {
        val s = _state.value
        if (s.jugadas.isEmpty()) { _state.update { it.copy(error = "Agregue al menos una jugada") }; return }

        val jugadasSnapshot = s.jugadas
        val totalSnapshot = totalAmount()

        viewModelScope.launch {
            _state.update { it.copy(isLoading = true, error = null) }
            val byDraw = jugadasSnapshot.groupBy { it.drawId }
            var lastUuid: String? = null
            var hasError = false

            for ((drawId, jugadas) in byDraw) {
                val details = jugadas.map { j ->
                    SaleDetail(j.numberValue, j.betTypeId, j.betTypeName, j.amount)
                }
                when (val result = ticketRepository.createSale(drawId, details)) {
                    is Result.Success -> lastUuid = result.data
                    is Result.Error -> { hasError = true; _state.update { it.copy(error = result.message) } }
                    else -> {}
                }
            }

            if (!hasError) {
                lastSoldJugadas = jugadasSnapshot
                lastSoldTotal = totalSnapshot
                lastSoldUuid = lastUuid ?: ""
            }

            _state.update {
                it.copy(
                    isLoading = false,
                    successUuid = if (!hasError) lastUuid else null,
                    jugadas = if (!hasError) emptyList() else it.jugadas
                )
            }

            // Impresion automatica si el toggle esta activo y la venta fue exitosa.
            if (!hasError && sessionStore.autoPrintFlow.firstOrNull() == true) {
                printLastTicket()
            }
        }
    }

    fun clearSuccess() = _state.update { it.copy(successUuid = null) }

    fun printLastTicket() {
        viewModelScope.launch {
            _state.update { it.copy(printMessage = "Imprimiendo...") }
            val serverUrl = sessionStore.effectiveServerUrlFlow.firstOrNull().orEmpty()
            val text = buildTicketText(
                jugadas = lastSoldJugadas,
                total = lastSoldTotal,
                uuid = lastSoldUuid,
                branchName = session.value?.branchName ?: "",
                userName = session.value?.userName ?: "",
                serverUrl = serverUrl,
            )
            val ok = withContext(Dispatchers.IO) { printerManager.print(text) }
            _state.update { it.copy(printMessage = if (ok) null else "Sin impresora conectada") }
        }
    }

    fun clearPrintMessage() = _state.update { it.copy(printMessage = null) }

    fun totalAmount(): String {
        val sum = _state.value.jugadas.fold(BigDecimal.ZERO) { carry, jugada ->
            carry + (jugada.amount.toBigDecimalOrNull() ?: BigDecimal.ZERO)
        }

        return sum.setScale(2, RoundingMode.HALF_UP).toPlainString()
    }

    fun drawCountdown(draw: DrawEntity, nowMillis: Long): String? {
        val cutoff = draw.cutoffTime ?: draw.drawTime
        return try {
            val parts = cutoff.split(":").map { it.toInt() }
            val cal = java.util.Calendar.getInstance()
            cal.set(java.util.Calendar.HOUR_OF_DAY, parts[0])
            cal.set(java.util.Calendar.MINUTE, parts[1])
            cal.set(java.util.Calendar.SECOND, 0)
            cal.set(java.util.Calendar.MILLISECOND, 0)
            val diff = cal.timeInMillis - nowMillis
            if (diff <= 0) null
            else {
                val h = (diff / 3_600_000).toInt()
                val m = ((diff % 3_600_000) / 60_000).toInt()
                val sec = ((diff % 60_000) / 1_000).toInt()
                "%02d:%02d:%02d".format(h, m, sec)
            }
        } catch (_: Exception) { null }
    }

    // ── Private helpers ─────────────────────────────────────────────────────────

    private fun autoDetectBetType(digits: String, types: List<BetTypeEntity>): Long? {
        val code = when (digits.length) {
            2 -> "QUINIELA"
            4 -> "PALE"
            6 -> "TRIPLETA"
            else -> null
        } ?: return null
        return types.firstOrNull { it.code.equals(code, ignoreCase = true) }?.id
    }

    private fun resolveBetTypeForNumber(s: SaleUiState): BetTypeEntity? {
        if (!s.numberInput.all { it.isDigit() }) return null

        val expectedCode = when {
            s.isSuperMode && s.numberInput.length == 4 -> "SUPER_PALE"
            s.numberInput.length == 2 -> "QUINIELA"
            s.numberInput.length == 4 -> "PALE"
            s.numberInput.length == 6 -> "TRIPLETA"
            else -> return null
        }

        return s.betTypes.firstOrNull { it.code.equals(expectedCode, ignoreCase = true) }
    }

    private fun maxNumberLength(s: SaleUiState): Int {
        if (s.isSuperMode) return 4
        return 6
    }

    private fun isCompleteNumberLength(s: SaleUiState): Boolean =
        if (s.isSuperMode) s.numberInput.length == 4 else s.numberInput.length in setOf(2, 4, 6)

    private fun buildTicketText(
        jugadas: List<JugadaItem>,
        total: String,
        uuid: String,
        branchName: String,
        userName: String,
        serverUrl: String,
    ): String {
        val sep = "-".repeat(32)
        val dblSep = "=".repeat(32)
        val now = java.text.SimpleDateFormat("dd/MM/yy HH:mm", java.util.Locale.getDefault())
            .format(java.util.Date())
        return buildString {
            appendLine(dblSep)
            appendLine(branchName.centerPad(32))
            appendLine(dblSep)
            appendLine("Cajero: $userName")
            appendLine("Fecha:  $now")
            if (uuid.isNotBlank()) appendLine("ID: ${uuid.take(12)}")
            jugadas.groupBy { it.drawId }.forEach { (_, group) ->
                val first = group.first()
                appendLine(dblSep)
                appendLine("${first.lotteryName} - ${first.drawName}".take(32))
                appendLine(sep)
                group.forEach { j ->
                    val t = when (j.betTypeCode.uppercase()) {
                        "QUINIELA" -> "Q "
                        "PALE" -> "Pl"
                        "TRIPLETA" -> "Tr"
                        "SUPER_PALE" -> "SP"
                        else -> j.betTypeCode.take(2)
                    }
                    appendLine("${j.numberValue.padEnd(8)} $t  RD\$ ${j.amount}")
                }
            }
            appendLine(dblSep)
            appendLine("TOTAL:       RD\$ $total")
            appendLine(dblSep)
            // Marcador QR -> el BluetoothPrinterManager lo reemplaza por bytes ESC/POS
            // de QR real. Codifica la URL publica de consulta del ticket.
            if (serverUrl.isNotBlank() && uuid.isNotBlank()) {
                val qrUrl = serverUrl.trimEnd('/') + "/t/" + uuid
                appendLine("[[QR:$qrUrl]]")
                appendLine("Escanea para ver tu ticket")
            }
        }
    }

    private fun String.centerPad(width: Int): String {
        if (length >= width) return take(width)
        val pad = (width - length) / 2
        return " ".repeat(pad) + this
    }
}
