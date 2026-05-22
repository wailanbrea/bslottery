package dev.bsolutions.bsloteria.ui.screen.startup

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.CloudOff
import androidx.compose.material.icons.filled.Error
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Sync
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

@Composable
fun StartupSyncScreen(
    viewModel: StartupSyncViewModel,
    onContinue: () -> Unit,
    onForceLogout: (String) -> Unit,
) {
    val state by viewModel.state.collectAsState()

    // Si el servidor reporta un problema con el dispositivo (UUID faltante,
    // no registrado, bloqueado, etc.), la sesión local ya quedó limpia.
    // Mandamos al usuario de vuelta al login para que se registre de nuevo.
    LaunchedEffect(state.forceLogout) {
        if (state.forceLogout) {
            kotlinx.coroutines.delay(800)
            onForceLogout(state.forceLogoutReason ?: "Sesión inválida. Inicia sesión de nuevo.")
        }
    }

    // Cuando termina sin errores y sin forzar logout, auto-continúa.
    LaunchedEffect(state.isFinished) {
        if (state.isFinished && !state.hasErrors && !state.forceLogout) {
            kotlinx.coroutines.delay(500)
            onContinue()
        }
    }

    Surface(Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) {
        Column(
            Modifier.fillMaxSize().padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(20.dp)
        ) {
            Spacer(Modifier.height(40.dp))

            Box(
                modifier = Modifier
                    .size(72.dp)
                    .clip(CircleShape)
                    .background(MaterialTheme.colorScheme.primary.copy(alpha = 0.12f)),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    Icons.Default.Sync, null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(36.dp)
                )
            }

            Text(
                "Sincronizando con el sistema",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold
            )
            Text(
                "Cargamos tu estado para que veas en el teléfono lo mismo que en el sistema.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = androidx.compose.ui.text.style.TextAlign.Center
            )

            Spacer(Modifier.height(8.dp))

            Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(12.dp)) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
                    state.steps.forEach { step ->
                        StepRow(step)
                    }
                }
            }

            Spacer(Modifier.weight(1f))

            when {
                state.forceLogout -> {
                    Surface(
                        modifier = Modifier.fillMaxWidth(),
                        color = MaterialTheme.colorScheme.errorContainer,
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Column(Modifier.padding(12.dp)) {
                            Text(
                                "Sesión inválida — regresando al login",
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onErrorContainer
                            )
                            state.forceLogoutReason?.let {
                                Text(
                                    it,
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onErrorContainer.copy(0.8f)
                                )
                            }
                        }
                    }
                }
                state.isFinished -> {
                    if (state.hasErrors) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                        ) {
                            OutlinedButton(
                                onClick = viewModel::retrySync,
                                modifier = Modifier.weight(1f).height(52.dp),
                            ) {
                                Icon(Icons.Default.Refresh, null, Modifier.size(18.dp))
                                Spacer(Modifier.width(6.dp))
                                Text("Reintentar", fontWeight = FontWeight.Bold)
                            }
                            Button(
                                onClick = onContinue,
                                modifier = Modifier.weight(1f).height(52.dp),
                            ) {
                                Text("Continuar", fontWeight = FontWeight.Bold)
                            }
                        }
                        Text(
                            "Hubo errores. Algunos datos pueden no estar actualizados.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.error,
                            textAlign = androidx.compose.ui.text.style.TextAlign.Center,
                        )
                    } else {
                        Button(
                            onClick = onContinue,
                            modifier = Modifier.fillMaxWidth().height(52.dp),
                        ) {
                            Text(
                                "Entrar al sistema",
                                fontWeight = FontWeight.Bold,
                                letterSpacing = 1.sp,
                            )
                        }
                    }
                }
                else -> {
                    LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
                }
            }
        }
    }
}

@Composable
private fun StepRow(step: SyncStep) {
    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
        Box(
            modifier = Modifier
                .size(28.dp)
                .clip(CircleShape)
                .background(stepBg(step.status)),
            contentAlignment = Alignment.Center
        ) {
            when (step.status) {
                SyncStepStatus.OK -> Icon(
                    Icons.Default.CheckCircle, null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(18.dp)
                )
                SyncStepStatus.RUNNING -> CircularProgressIndicator(
                    Modifier.size(16.dp), strokeWidth = 2.dp
                )
                SyncStepStatus.OFFLINE -> Icon(
                    Icons.Default.CloudOff, null,
                    tint = MaterialTheme.colorScheme.tertiary,
                    modifier = Modifier.size(16.dp)
                )
                SyncStepStatus.ERROR -> Icon(
                    Icons.Default.Error, null,
                    tint = MaterialTheme.colorScheme.error,
                    modifier = Modifier.size(16.dp)
                )
                SyncStepStatus.PENDING -> {}
            }
        }
        Column(Modifier.weight(1f)) {
            Text(step.label, fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
            val detail = step.detail
            if (!detail.isNullOrBlank()) {
                Text(
                    detail,
                    style = MaterialTheme.typography.bodySmall,
                    fontSize = 11.sp,
                    color = when (step.status) {
                        SyncStepStatus.ERROR -> MaterialTheme.colorScheme.error
                        SyncStepStatus.OFFLINE -> MaterialTheme.colorScheme.tertiary
                        else -> MaterialTheme.colorScheme.onSurfaceVariant
                    }
                )
            }
        }
    }
}

@Composable
private fun stepBg(status: SyncStepStatus): Color = when (status) {
    SyncStepStatus.OK -> MaterialTheme.colorScheme.primary.copy(alpha = 0.12f)
    SyncStepStatus.RUNNING -> MaterialTheme.colorScheme.surfaceVariant
    SyncStepStatus.OFFLINE -> MaterialTheme.colorScheme.tertiary.copy(alpha = 0.12f)
    SyncStepStatus.ERROR -> MaterialTheme.colorScheme.error.copy(alpha = 0.12f)
    SyncStepStatus.PENDING -> MaterialTheme.colorScheme.surfaceVariant
}
