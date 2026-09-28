package dev.bsolutions.bsloteria.ui.screen.license

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.widthIn
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp

@Composable
fun LicenseGateScreen(
    state: LicenseUiState,
    onActivate: (String) -> Unit,
    onRetry: () -> Unit,
    onChangeActivation: () -> Unit,
) {
    when (state.status) {
        LicenseUiStatus.CHECKING -> CenteredLicenseMessage {
            CircularProgressIndicator()
            Text("Validando licencia…")
        }
        LicenseUiStatus.ACTIVATION_REQUIRED -> ActivationScreen(state, onActivate)
        LicenseUiStatus.BLOCKED -> BlockedLicenseScreen(state, onRetry, onChangeActivation)
        LicenseUiStatus.VALID, LicenseUiStatus.OFFLINE_GRACE -> Unit
    }
}

@Composable
private fun ActivationScreen(state: LicenseUiState, onActivate: (String) -> Unit) {
    var code by remember { mutableStateOf("") }

    CenteredLicenseMessage {
        Text("Activar BSLottery", style = MaterialTheme.typography.headlineMedium)
        Text(
            "Esta instalación debe activarse antes de iniciar sesión.",
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        OutlinedTextField(
            value = code,
            onValueChange = { code = it },
            modifier = Modifier.fillMaxWidth(),
            label = { Text("Código de activación") },
            singleLine = true,
            enabled = !state.isLoading,
        )
        state.message?.let {
            LicenseErrorMessage(it)
        }
        Button(
            onClick = { onActivate(code) },
            modifier = Modifier.fillMaxWidth(),
            enabled = !state.isLoading,
        ) {
            if (state.isLoading) CircularProgressIndicator(strokeWidth = 2.dp)
            else Text("Activar licencia")
        }
    }
}

@Composable
private fun BlockedLicenseScreen(
    state: LicenseUiState,
    onRetry: () -> Unit,
    onChangeActivation: () -> Unit,
) {
    CenteredLicenseMessage {
        Text("Licencia no disponible", style = MaterialTheme.typography.headlineMedium)
        Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer)) {
            Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                Text(state.reason ?: "LICENSE_INVALID", style = MaterialTheme.typography.labelLarge)
                Text(state.message ?: "Contacta al administrador.", color = MaterialTheme.colorScheme.onErrorContainer)
            }
        }
        Button(onClick = onRetry, modifier = Modifier.fillMaxWidth(), enabled = !state.isLoading) {
            Text("Reintentar validación")
        }
        Button(onClick = onChangeActivation, modifier = Modifier.fillMaxWidth()) {
            Text("Usar otro código")
        }
    }
}

@Composable
private fun CenteredLicenseMessage(content: @Composable () -> Unit) {
    Box(Modifier.fillMaxSize().padding(24.dp), contentAlignment = Alignment.Center) {
        Column(
            Modifier.fillMaxWidth().widthIn(max = 420.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            content()
        }
    }
}

@Composable
private fun LicenseErrorMessage(message: String) {
    Text(message, color = MaterialTheme.colorScheme.error)
}
