package dev.bsolutions.bsloteria.ui.screen.sync

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.work.WorkManager
import dagger.hilt.android.lifecycle.HiltViewModel
import dev.bsolutions.bsloteria.data.local.entity.OfflineSessionEntity
import dev.bsolutions.bsloteria.data.repository.OfflineRepository
import dev.bsolutions.bsloteria.data.repository.SyncRepository
import dev.bsolutions.bsloteria.data.repository.TicketRepository
import dev.bsolutions.bsloteria.util.Result
import dev.bsolutions.bsloteria.util.SessionStore
import dev.bsolutions.bsloteria.worker.SyncWorker
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class SyncUiState(
    val isLoading: Boolean = false,
    val message: String? = null,
    val error: String? = null
)

@HiltViewModel
class SyncViewModel @Inject constructor(
    private val syncRepository: SyncRepository,
    private val offlineRepository: OfflineRepository,
    private val ticketRepository: TicketRepository,
    private val workManager: WorkManager,
    val sessionStore: SessionStore
) : ViewModel() {

    private val _state = MutableStateFlow(SyncUiState())
    val state: StateFlow<SyncUiState> = _state

    val pendingCount = ticketRepository.observePendingCount()
        .stateIn(viewModelScope, SharingStarted.Eagerly, 0)

    val lastSync = sessionStore.lastSyncFlow
        .stateIn(viewModelScope, SharingStarted.Eagerly, 0L)

    val activeOfflineSession: StateFlow<OfflineSessionEntity?> =
        offlineRepository.observeActiveSession()
            .stateIn(viewModelScope, SharingStarted.Eagerly, null)

    fun syncNow() {
        viewModelScope.launch {
            _state.update { it.copy(isLoading = true, message = null, error = null) }

            val catalogResult = syncRepository.syncCatalog()
            if (catalogResult is Result.Error) {
                _state.update { it.copy(isLoading = false, error = "Catálogo: ${catalogResult.message}") }
                return@launch
            }

            when (val result = syncRepository.uploadPendingTickets()) {
                is Result.Success -> _state.update {
                    it.copy(
                        isLoading = false,
                        message = "Sincronizado: ${result.data.synced} tickets. Fallidos: ${result.data.failed}"
                    )
                }
                is Result.Error -> _state.update { it.copy(isLoading = false, error = result.message) }
                else -> {}
            }
        }
    }

    fun openOfflineSession() {
        viewModelScope.launch {
            _state.update { it.copy(isLoading = true, message = null, error = null) }

            when (val result = offlineRepository.openSession()) {
                is Result.Success -> _state.update {
                    it.copy(
                        isLoading = false,
                        message = "Sesión offline activa hasta ${result.data.expiresAt ?: "sin vencimiento"}"
                    )
                }
                is Result.Error -> _state.update { it.copy(isLoading = false, error = result.message) }
                else -> {}
            }
        }
    }

    fun enqueueBackgroundSync() {
        workManager.enqueue(SyncWorker.immediateRequest())
        _state.update { it.copy(message = "Sincronización en segundo plano iniciada") }
    }
}
