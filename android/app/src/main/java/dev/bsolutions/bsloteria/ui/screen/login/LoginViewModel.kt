package dev.bsolutions.bsloteria.ui.screen.login

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import dev.bsolutions.bsloteria.BuildConfig
import dev.bsolutions.bsloteria.data.repository.AuthRepository
import dev.bsolutions.bsloteria.util.Result
import dev.bsolutions.bsloteria.util.SessionStore
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class LoginUiState(
    val email: String = "",
    val password: String = "",
    val serverUrl: String = BuildConfig.SERVER_URL,
    val isLoading: Boolean = false,
    val error: String? = null
)

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val sessionStore: SessionStore
) : ViewModel() {

    val isLoggedIn: StateFlow<Boolean> = sessionStore.tokenFlow
        .map { !it.isNullOrBlank() }
        .stateIn(viewModelScope, SharingStarted.Eagerly, false)

    private val _state = MutableStateFlow(LoginUiState())
    val state: StateFlow<LoginUiState> = _state

    fun onEmailChange(v: String) = _state.update { it.copy(email = v, error = null) }
    fun onPasswordChange(v: String) = _state.update { it.copy(password = v, error = null) }
    fun onServerUrlChange(v: String) = _state.update { it.copy(serverUrl = v, error = null) }

    fun login(onSuccess: () -> Unit) {
        val s = _state.value
        if (s.email.isBlank() || s.password.isBlank() || s.serverUrl.isBlank()) {
            _state.update { it.copy(error = "Todos los campos son obligatorios") }
            return
        }
        viewModelScope.launch {
            _state.update { it.copy(isLoading = true, error = null) }
            when (val result = authRepository.login(s.email, s.password, s.serverUrl.trimEnd('/'))) {
                is Result.Success -> onSuccess()
                is Result.Error -> _state.update { it.copy(isLoading = false, error = result.message) }
                else -> {}
            }
        }
    }
}
