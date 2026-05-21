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

    fun logout(onDone: () -> Unit) {
        viewModelScope.launch {
            authRepository.logout()
            onDone()
        }
    }
}
