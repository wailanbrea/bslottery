package dev.bsolutions.bsloteria.ui.screen.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import dev.bsolutions.bsloteria.data.repository.AuthRepository
import dev.bsolutions.bsloteria.data.repository.SyncRepository
import dev.bsolutions.bsloteria.data.repository.TicketRepository
import dev.bsolutions.bsloteria.util.SessionStore
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class DashboardViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val syncRepository: SyncRepository,
    private val ticketRepository: TicketRepository,
    val sessionStore: SessionStore
) : ViewModel() {

    val pendingCount = ticketRepository.observePendingCount()
        .stateIn(viewModelScope, SharingStarted.Eagerly, 0)

    val session = sessionStore.sessionFlow
        .stateIn(viewModelScope, SharingStarted.Eagerly, null)

    init {
        viewModelScope.launch {
            syncRepository.syncCatalog()
        }
    }

    private val _lookupLoading = kotlinx.coroutines.flow.MutableStateFlow(false)
    val lookupLoading: kotlinx.coroutines.flow.StateFlow<Boolean> = _lookupLoading

    private val _lookupError = kotlinx.coroutines.flow.MutableStateFlow<String?>(null)
    val lookupError: kotlinx.coroutines.flow.StateFlow<String?> = _lookupError

    fun lookupTicket(token: String, onFound: (String) -> Unit) {
        val cleanToken = token.trim()
        if (cleanToken.isBlank()) {
            _lookupError.value = "Ingrese número de ticket o QR"
            return
        }
        viewModelScope.launch {
            _lookupLoading.value = true
            _lookupError.value = null
            when (val result = ticketRepository.lookupTicket(cleanToken)) {
                is dev.bsolutions.bsloteria.util.Result.Success -> {
                    _lookupLoading.value = false
                    onFound(result.data)
                }
                is dev.bsolutions.bsloteria.util.Result.Error -> {
                    _lookupLoading.value = false
                    _lookupError.value = result.message
                }
                else -> {
                    _lookupLoading.value = false
                }
            }
        }
    }

    fun clearLookupError() {
        _lookupError.value = null
    }

    fun logout(onDone: () -> Unit) {
        viewModelScope.launch {
            authRepository.logout()
            onDone()
        }
    }
}
