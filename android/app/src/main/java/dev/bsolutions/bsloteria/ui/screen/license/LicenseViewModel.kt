package dev.bsolutions.bsloteria.ui.screen.license

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import dev.bsolutions.bsloteria.data.licensing.LicenseCheckResult
import dev.bsolutions.bsloteria.data.licensing.LicenseRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

enum class LicenseUiStatus { CHECKING, ACTIVATION_REQUIRED, VALID, OFFLINE_GRACE, BLOCKED }

data class LicenseUiState(
    val status: LicenseUiStatus = LicenseUiStatus.CHECKING,
    val reason: String? = null,
    val message: String? = null,
    val isLoading: Boolean = false,
)

@HiltViewModel
class LicenseViewModel @Inject constructor(
    private val repository: LicenseRepository,
) : ViewModel() {
    private val _state = MutableStateFlow(LicenseUiState())
    val state: StateFlow<LicenseUiState> = _state

    init {
        validate()
    }

    fun activate(code: String) {
        viewModelScope.launch {
            _state.update { it.copy(isLoading = true, message = null) }
            applyResult(repository.activate(code))
        }
    }

    fun validate() {
        viewModelScope.launch {
            _state.update { it.copy(status = LicenseUiStatus.CHECKING, isLoading = true, message = null) }
            applyResult(repository.validate())
        }
    }

    fun showActivation() {
        _state.update { it.copy(status = LicenseUiStatus.ACTIVATION_REQUIRED, reason = null, message = null, isLoading = false) }
    }

    private fun applyResult(result: LicenseCheckResult) {
        _state.value = when (result) {
            LicenseCheckResult.Valid -> LicenseUiState(LicenseUiStatus.VALID)
            LicenseCheckResult.OfflineGrace -> LicenseUiState(
                status = LicenseUiStatus.OFFLINE_GRACE,
                message = "Sin conexión. Se está usando la gracia offline autorizada.",
            )
            LicenseCheckResult.NeedsActivation -> LicenseUiState(
                status = LicenseUiStatus.ACTIVATION_REQUIRED,
                message = "Activa esta instalación para continuar.",
            )
            is LicenseCheckResult.Blocked -> LicenseUiState(
                status = LicenseUiStatus.BLOCKED,
                reason = result.reason,
                message = result.message,
            )
        }
    }
}
