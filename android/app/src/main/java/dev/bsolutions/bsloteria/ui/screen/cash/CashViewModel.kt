package dev.bsolutions.bsloteria.ui.screen.cash

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import dev.bsolutions.bsloteria.data.remote.dto.CashMovementDto
import dev.bsolutions.bsloteria.data.remote.dto.CashSessionDto
import dev.bsolutions.bsloteria.data.repository.CashRepository
import dev.bsolutions.bsloteria.domain.model.Denominations
import dev.bsolutions.bsloteria.util.Result
import dev.bsolutions.bsloteria.util.SessionStore
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.math.BigDecimal
import java.math.RoundingMode
import javax.inject.Inject

enum class CashCloseMode { AMOUNT, DENOMINATIONS }

data class CashUiState(
    val isLoading: Boolean = false,
    val isSubmitting: Boolean = false,
    val cashControlEnabled: Boolean = true,
    val branchName: String? = null,
    val session: CashSessionDto? = null,
    val movements: List<CashMovementDto> = emptyList(),
    val isOfflineCache: Boolean = false,
    val cachedAtMillis: Long? = null,
    val lastSyncedMillis: Long? = null,
    val openingAmountInput: String = "",
    val notesInput: String = "",
    val countedCashInput: String = "",
    val closeMode: CashCloseMode = CashCloseMode.AMOUNT,
    val denominations: Map<String, Int> = Denominations.emptyMap(),
    val error: String? = null,
    val successMessage: String? = null,
    val initialLoadDone: Boolean = false,
)

@HiltViewModel
class CashViewModel @Inject constructor(
    private val cashRepository: CashRepository,
    sessionStore: SessionStore,
) : ViewModel() {

    private val _state = MutableStateFlow(CashUiState())
    val state: StateFlow<CashUiState> = _state

    val session = sessionStore.sessionFlow.stateIn(viewModelScope, SharingStarted.Eagerly, null)

    init {
        refresh()
    }

    fun refresh() {
        viewModelScope.launch {
            _state.update { it.copy(isLoading = true, error = null) }
            when (val result = cashRepository.getStatus()) {
                is Result.Success -> {
                    val payload = result.data.data
                    _state.update {
                        it.copy(
                            isLoading = false,
                            cashControlEnabled = payload.cashControlEnabled,
                            branchName = payload.branch?.name ?: it.branchName,
                            session = payload.session,
                            movements = payload.movements,
                            isOfflineCache = result.data.fromCache,
                            cachedAtMillis = result.data.cachedAtMillis,
                            lastSyncedMillis = if (!result.data.fromCache) System.currentTimeMillis() else it.lastSyncedMillis,
                            initialLoadDone = true,
                            error = null,
                        )
                    }
                }
                is Result.Error -> _state.update {
                    it.copy(
                        isLoading = false,
                        initialLoadDone = true,
                        error = result.message,
                    )
                }
                Result.Loading -> Unit
            }
        }
    }

    fun onOpeningAmountChange(value: String) {
        _state.update { it.copy(openingAmountInput = sanitizeAmount(value), error = null) }
    }

    fun onCountedCashChange(value: String) {
        _state.update { it.copy(countedCashInput = sanitizeAmount(value), error = null) }
    }

    fun setCloseMode(mode: CashCloseMode) {
        _state.update { it.copy(closeMode = mode, error = null) }
    }

    fun setDenominationQty(key: String, qty: Int) {
        val safe = qty.coerceIn(0, 100_000)
        _state.update {
            val updated = it.denominations.toMutableMap().apply { this[key] = safe }
            it.copy(denominations = updated, error = null)
        }
    }

    fun incrementDenomination(key: String, delta: Int) {
        val current = _state.value.denominations[key] ?: 0
        setDenominationQty(key, current + delta)
    }

    fun clearDenominations() {
        _state.update { it.copy(denominations = Denominations.emptyMap(), error = null) }
    }

    fun denominationsTotal(): String =
        Denominations.total(_state.value.denominations).toPlainString()

    fun onNotesChange(value: String) {
        _state.update { it.copy(notesInput = value.take(500), error = null) }
    }

    fun openCash() {
        val s = _state.value
        val amount = s.openingAmountInput.toBigDecimalOrNull()
        if (amount == null || amount < BigDecimal.ZERO) {
            _state.update { it.copy(error = "Monto inicial inválido") }
            return
        }
        val normalized = amount.setScale(2, RoundingMode.HALF_UP).toPlainString()

        viewModelScope.launch {
            _state.update { it.copy(isSubmitting = true, error = null, successMessage = null) }
            when (val result = cashRepository.open(normalized, s.notesInput.ifBlank { null })) {
                is Result.Success -> _state.update {
                    it.copy(
                        isSubmitting = false,
                        session = result.data,
                        openingAmountInput = "",
                        notesInput = "",
                        successMessage = "Caja abierta correctamente.",
                    )
                }
                is Result.Error -> _state.update {
                    it.copy(isSubmitting = false, error = result.message)
                }
                Result.Loading -> Unit
            }
        }
    }

    fun closeCash() {
        val s = _state.value
        if (s.session == null || s.session.status != "OPEN") {
            _state.update { it.copy(error = "No hay caja abierta") }
            return
        }

        val countedCashToSend: String?
        val denominationsToSend: Map<String, Int>?

        if (s.closeMode == CashCloseMode.DENOMINATIONS) {
            val positives = s.denominations.filterValues { it > 0 }
            if (positives.isEmpty()) {
                _state.update { it.copy(error = "Cuente al menos un billete o moneda") }
                return
            }
            denominationsToSend = positives
            countedCashToSend = null
        } else {
            val countedRaw = s.countedCashInput.ifBlank { s.session.expectedCash }
            val counted = countedRaw.toBigDecimalOrNull()
            if (counted == null || counted < BigDecimal.ZERO) {
                _state.update { it.copy(error = "Efectivo contado inválido") }
                return
            }
            countedCashToSend = counted.setScale(2, RoundingMode.HALF_UP).toPlainString()
            denominationsToSend = null
        }

        viewModelScope.launch {
            _state.update { it.copy(isSubmitting = true, error = null, successMessage = null) }
            when (val result = cashRepository.close(
                countedCash = countedCashToSend,
                denominations = denominationsToSend,
                notes = s.notesInput.ifBlank { null },
            )) {
                is Result.Success -> _state.update {
                    it.copy(
                        isSubmitting = false,
                        session = result.data,
                        countedCashInput = "",
                        denominations = Denominations.emptyMap(),
                        notesInput = "",
                        successMessage = "Caja cerrada correctamente.",
                    )
                }
                is Result.Error -> _state.update {
                    it.copy(isSubmitting = false, error = result.message)
                }
                Result.Loading -> Unit
            }
        }
    }

    fun clearSuccessMessage() = _state.update { it.copy(successMessage = null) }
    fun clearError() = _state.update { it.copy(error = null) }

    private fun sanitizeAmount(value: String): String {
        val cleaned = value.filter { it.isDigit() || it == '.' }
        val firstDot = cleaned.indexOf('.')
        return if (firstDot >= 0) {
            val head = cleaned.substring(0, firstDot + 1)
            val tail = cleaned.substring(firstDot + 1).filter { it.isDigit() }.take(2)
            head + tail
        } else cleaned.take(10)
    }
}
