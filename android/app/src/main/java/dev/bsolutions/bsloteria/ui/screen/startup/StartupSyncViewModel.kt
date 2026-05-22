package dev.bsolutions.bsloteria.ui.screen.startup

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import dev.bsolutions.bsloteria.data.repository.AuthRepository
import dev.bsolutions.bsloteria.data.repository.CashRepository
import dev.bsolutions.bsloteria.data.repository.SyncRepository
import dev.bsolutions.bsloteria.printer.BluetoothPrinterManager
import dev.bsolutions.bsloteria.util.Result
import dev.bsolutions.bsloteria.util.SessionStore
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.async
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import javax.inject.Inject

enum class SyncStepStatus { PENDING, RUNNING, OK, OFFLINE, ERROR }

data class SyncStep(
    val key: String,
    val label: String,
    val status: SyncStepStatus = SyncStepStatus.PENDING,
    val detail: String? = null,
)

data class StartupSyncUiState(
    val steps: List<SyncStep> = listOf(
        SyncStep("catalog", "Catálogo (sorteos y tipos de jugada)"),
        SyncStep("cash", "Estado de caja del usuario"),
        SyncStep("tickets", "Tickets pendientes de sincronizar"),
        SyncStep("printer", "Impresora Bluetooth"),
    ),
    val isFinished: Boolean = false,
    val hasErrors: Boolean = false,
    val forceLogout: Boolean = false,
    val forceLogoutReason: String? = null,
)

private val DEVICE_ERROR_CODES = setOf(
    "DEVICE_UUID_REQUIRED",
    "DEVICE_NOT_FOUND",
    "DEVICE_NOT_AUTHORIZED",
    "DEVICE_BLOCKED",
)

@HiltViewModel
class StartupSyncViewModel @Inject constructor(
    private val syncRepository: SyncRepository,
    private val cashRepository: CashRepository,
    private val authRepository: AuthRepository,
    private val sessionStore: SessionStore,
    private val printerManager: BluetoothPrinterManager,
) : ViewModel() {

    private val _state = MutableStateFlow(StartupSyncUiState())
    val state: StateFlow<StartupSyncUiState> = _state

    init {
        startSync()
    }

    /** Re-ejecuta toda la sincronizacion. Resetea pasos a PENDING y vuelve a lanzar. */
    fun retrySync() {
        _state.update {
            it.copy(
                isFinished = false,
                hasErrors = false,
                steps = it.steps.map { step -> step.copy(status = SyncStepStatus.PENDING, detail = null) },
            )
        }
        startSync()
    }

    private fun startSync() {
        viewModelScope.launch {
            updateStep("catalog", SyncStepStatus.RUNNING)
            updateStep("cash", SyncStepStatus.RUNNING)
            updateStep("tickets", SyncStepStatus.RUNNING)
            updateStep("printer", SyncStepStatus.RUNNING)

            val catalogJob = async {
                when (val r = syncRepository.syncCatalog()) {
                    is Result.Success -> updateStep("catalog", SyncStepStatus.OK)
                    is Result.Error -> updateStep(
                        "catalog",
                        if (r.message.contains("conexion", ignoreCase = true) ||
                            r.message.contains("conexi", ignoreCase = true) ||
                            r.message.contains("network", ignoreCase = true))
                            SyncStepStatus.OFFLINE else SyncStepStatus.ERROR,
                        r.message
                    )
                    Result.Loading -> Unit
                }
            }

            val cashJob = async {
                when (val r = cashRepository.getStatus()) {
                    is Result.Success -> {
                        val payload = r.data
                        val detail = when {
                            !payload.data.cashControlEnabled -> "Sucursal sin control de caja"
                            payload.data.session != null -> "Caja abierta — fondo RD$ ${payload.data.session.openingAmount}, esperado RD$ ${payload.data.session.expectedCash}"
                            else -> "Sin caja abierta — puedes abrirla desde la app"
                        }
                        updateStep(
                            "cash",
                            if (payload.fromCache) SyncStepStatus.OFFLINE else SyncStepStatus.OK,
                            detail
                        )
                    }
                    is Result.Error -> {
                        if (r.code in DEVICE_ERROR_CODES) {
                            triggerForceLogout(r.message)
                        }
                        updateStep("cash", SyncStepStatus.ERROR, r.message)
                    }
                    Result.Loading -> Unit
                }
            }

            val ticketsJob = async {
                when (val r = syncRepository.uploadPendingTickets()) {
                    is Result.Success -> {
                        val detail = when {
                            r.data.synced == 0 && r.data.failed == 0 -> "Nada pendiente"
                            else -> "${r.data.synced} sincronizados, ${r.data.failed} fallidos"
                        }
                        updateStep("tickets", SyncStepStatus.OK, detail)
                    }
                    is Result.Error -> updateStep(
                        "tickets",
                        if (r.message.contains("conexion", ignoreCase = true))
                            SyncStepStatus.OFFLINE else SyncStepStatus.ERROR,
                        r.message
                    )
                    Result.Loading -> Unit
                }
            }

            val printerJob = async { reconnectPrinter() }

            catalogJob.await()
            cashJob.await()
            ticketsJob.await()
            printerJob.await()

            // La impresora desconectada no bloquea: se reporta OFFLINE para que
            // el cajero vea el aviso pero pueda continuar a vender.
            val hasErrors = _state.value.steps.any { it.status == SyncStepStatus.ERROR }
            _state.update { it.copy(isFinished = true, hasErrors = hasErrors) }
        }
    }

    private suspend fun reconnectPrinter() {
        val address = sessionStore.printerAddressFlow.firstOrNull()
        val savedName = sessionStore.printerNameFlow.firstOrNull()

        if (address.isNullOrBlank()) {
            updateStep("printer", SyncStepStatus.OK, "Sin impresora configurada")
            return
        }

        if (!printerManager.isAvailable()) {
            updateStep(
                "printer",
                SyncStepStatus.OFFLINE,
                "Guardada: ${savedName ?: address} (Bluetooth apagado o sin permisos)",
            )
            return
        }

        if (printerManager.isConnected()) {
            updateStep("printer", SyncStepStatus.OK, "Conectada: ${savedName ?: address}")
            return
        }

        val ok = withContext(Dispatchers.IO) { printerManager.connectByAddress(address) }
        if (ok) {
            updateStep("printer", SyncStepStatus.OK, "Conectada: ${savedName ?: address}")
        } else {
            updateStep(
                "printer",
                SyncStepStatus.OFFLINE,
                "Guardada: ${savedName ?: address} — toca Settings para reconectar",
            )
        }
    }

    private fun updateStep(key: String, status: SyncStepStatus, detail: String? = null) {
        _state.update { s ->
            s.copy(steps = s.steps.map { step ->
                if (step.key == key) step.copy(status = status, detail = detail ?: step.detail)
                else step
            })
        }
    }

    private fun triggerForceLogout(reason: String) {
        if (_state.value.forceLogout) return
        _state.update { it.copy(forceLogout = true, forceLogoutReason = reason) }
        viewModelScope.launch {
            try {
                authRepository.logout()
            } catch (_: Exception) { /* offline: limpieza local ya hecha en logout() */ }
            cashRepository.clearCache()
        }
    }
}
