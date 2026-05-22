package dev.bsolutions.bsloteria.ui.screen.settings

import android.Manifest
import android.bluetooth.BluetoothDevice
import android.os.Build
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.google.accompanist.permissions.ExperimentalPermissionsApi
import com.google.accompanist.permissions.rememberMultiplePermissionsState
import dev.bsolutions.bsloteria.BuildConfig

@OptIn(ExperimentalMaterial3Api::class, ExperimentalPermissionsApi::class)
@Composable
fun SettingsScreen(
    viewModel: SettingsViewModel,
    onBack: () -> Unit,
    onLogout: () -> Unit
) {
    val state by viewModel.state.collectAsState()
    val session by viewModel.session.collectAsState()
    var showLogoutDialog by remember { mutableStateOf(false) }

    val scanPermissions = rememberMultiplePermissionsState(
        permissions = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            listOf(Manifest.permission.BLUETOOTH_SCAN, Manifest.permission.BLUETOOTH_CONNECT)
        } else {
            listOf(Manifest.permission.ACCESS_FINE_LOCATION)
        }
    )
    var pendingScanAfterGrant by remember { mutableStateOf(false) }

    fun startScanWithPermission() {
        if (scanPermissions.allPermissionsGranted) {
            viewModel.refreshPairedDevices()
            viewModel.scanForDevices()
        } else {
            pendingScanAfterGrant = true
            scanPermissions.launchMultiplePermissionRequest()
        }
    }

    // Cuando el usuario otorga los permisos despues de tocar el boton,
    // arrancamos el scan automaticamente.
    LaunchedEffect(scanPermissions.allPermissionsGranted) {
        if (scanPermissions.allPermissionsGranted && pendingScanAfterGrant) {
            pendingScanAfterGrant = false
            viewModel.refreshPairedDevices()
            viewModel.scanForDevices()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Configuración", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, null)
                    }
                }
            )
        }
    ) { padding ->
        LazyColumn(
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, top = 8.dp, bottom = 32.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
            modifier = Modifier.padding(padding)
        ) {
            // ── Session info ──────────────────────────────────────────────
            session?.let { s ->
                item {
                    SectionLabel("Cuenta")
                    Card(modifier = Modifier.fillMaxWidth()) {
                        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                            InfoRow(Icons.Default.Person, "Usuario", s.userName)
                            HorizontalDivider(thickness = 0.5.dp)
                            InfoRow(Icons.Default.Business, "Sucursal", s.branchName)
                            if (s.permissions.isNotEmpty()) {
                                HorizontalDivider(thickness = 0.5.dp)
                                InfoRow(Icons.Default.Shield, "Permisos", "${s.permissions.size} activos")
                            }
                        }
                    }
                }
            }

            // ── Server URL ────────────────────────────────────────────────
            item {
                SectionLabel("Servidor")
                Card(modifier = Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        Surface(
                            shape = RoundedCornerShape(6.dp),
                            color = if (state.hasUrlOverride) {
                                MaterialTheme.colorScheme.tertiary.copy(alpha = 0.10f)
                            } else {
                                MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                            },
                            modifier = Modifier.fillMaxWidth(),
                        ) {
                            Column(Modifier.padding(8.dp), verticalArrangement = Arrangement.spacedBy(2.dp)) {
                                Text(
                                    if (state.hasUrlOverride) "Override de admin activo" else "Usando default del APK",
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.SemiBold,
                                    color = if (state.hasUrlOverride) {
                                        MaterialTheme.colorScheme.tertiary
                                    } else {
                                        MaterialTheme.colorScheme.onSurfaceVariant
                                    },
                                )
                                Text(
                                    state.effectiveServerUrl,
                                    fontSize = 10.sp,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                )
                                if (state.hasUrlOverride && state.defaultServerUrl != state.effectiveServerUrl) {
                                    Text(
                                        "Default APK: ${state.defaultServerUrl}",
                                        fontSize = 9.sp,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f),
                                    )
                                }
                            }
                        }

                        OutlinedTextField(
                            value = state.serverUrl,
                            onValueChange = viewModel::onServerUrlChange,
                            label = { Text("URL del servidor") },
                            placeholder = { Text(state.defaultServerUrl) },
                            leadingIcon = { Icon(Icons.Default.CloudQueue, null) },
                            modifier = Modifier.fillMaxWidth(),
                            singleLine = true,
                            shape = RoundedCornerShape(10.dp)
                        )
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            Button(
                                onClick = viewModel::saveServerUrl,
                                modifier = Modifier.weight(1f),
                                shape = RoundedCornerShape(10.dp)
                            ) {
                                Icon(if (state.serverUrlSaved) Icons.Default.CheckCircle else Icons.Default.Save,
                                    null, modifier = Modifier.size(18.dp))
                                Spacer(Modifier.width(8.dp))
                                Text(if (state.serverUrlSaved) "Guardada" else "Guardar")
                            }
                            OutlinedButton(
                                onClick = viewModel::resetServerUrlToDefault,
                                modifier = Modifier.weight(1f),
                                shape = RoundedCornerShape(10.dp),
                                enabled = state.hasUrlOverride,
                            ) {
                                Icon(Icons.Default.Refresh, null, modifier = Modifier.size(18.dp))
                                Spacer(Modifier.width(8.dp))
                                Text("Restablecer")
                            }
                        }
                    }
                }
            }

            // ── Bluetooth printer ─────────────────────────────────────────
            item {
                SectionLabel("Impresora Bluetooth")
                Card(modifier = Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        Row(
                            horizontalArrangement = Arrangement.spacedBy(10.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            val isConnected = state.connectedDeviceName != null
                            val hasRemembered = state.rememberedDeviceName != null
                            Icon(
                                when {
                                    isConnected -> Icons.Default.BluetoothConnected
                                    hasRemembered -> Icons.Default.BluetoothSearching
                                    else -> Icons.Default.Bluetooth
                                },
                                null,
                                tint = when {
                                    isConnected -> MaterialTheme.colorScheme.primary
                                    hasRemembered -> MaterialTheme.colorScheme.tertiary
                                    else -> MaterialTheme.colorScheme.onSurfaceVariant
                                },
                                modifier = Modifier.size(24.dp)
                            )
                            Column(Modifier.weight(1f)) {
                                Text(
                                    state.connectedDeviceName ?: state.rememberedDeviceName ?: "Sin impresora",
                                    fontWeight = FontWeight.SemiBold, fontSize = 14.sp
                                )
                                Text(state.printerStatus, fontSize = 12.sp,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            if (state.isConnecting) {
                                CircularProgressIndicator(modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                            }
                        }

                        state.testPrintMessage?.let { msg ->
                            LaunchedEffect(msg) {
                                kotlinx.coroutines.delay(2500)
                                viewModel.clearTestPrintMessage()
                            }
                            Text(
                                msg,
                                fontSize = 12.sp,
                                color = if (msg.startsWith("Sin")) MaterialTheme.colorScheme.error
                                else MaterialTheme.colorScheme.primary,
                            )
                        }

                        if (state.connectedDeviceName != null) {
                            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                OutlinedButton(onClick = viewModel::testPrint,
                                    modifier = Modifier.weight(1f), shape = RoundedCornerShape(10.dp)) {
                                    Icon(Icons.Default.Print, null, modifier = Modifier.size(16.dp))
                                    Spacer(Modifier.width(4.dp))
                                    Text("Prueba")
                                }
                                OutlinedButton(onClick = viewModel::disconnectPrinter,
                                    modifier = Modifier.weight(1f), shape = RoundedCornerShape(10.dp)) {
                                    Icon(Icons.Default.BluetoothDisabled, null, modifier = Modifier.size(16.dp))
                                    Spacer(Modifier.width(4.dp))
                                    Text("Olvidar")
                                }
                            }
                        } else if (state.rememberedDeviceName != null) {
                            Button(
                                onClick = viewModel::reconnectSaved,
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(10.dp),
                                enabled = !state.isConnecting,
                            ) {
                                Icon(Icons.Default.BluetoothSearching, null, modifier = Modifier.size(16.dp))
                                Spacer(Modifier.width(6.dp))
                                Text("Reconectar")
                            }
                        }

                        // Toggle de impresion automatica post-venta
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(12.dp),
                        ) {
                            Column(Modifier.weight(1f)) {
                                Text("Imprimir tras vender", fontWeight = FontWeight.Medium, fontSize = 14.sp)
                                Text(
                                    "Envia el ticket a la impresora automaticamente al guardarse la venta.",
                                    fontSize = 11.sp,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                )
                            }
                            Switch(
                                checked = state.autoPrint,
                                onCheckedChange = viewModel::setAutoPrint,
                            )
                        }

                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedButton(
                                onClick = viewModel::refreshPairedDevices,
                                modifier = Modifier.weight(1f),
                                shape = RoundedCornerShape(10.dp),
                                enabled = !state.isScanning,
                            ) {
                                Icon(Icons.Default.Refresh, null, modifier = Modifier.size(16.dp))
                                Spacer(Modifier.width(6.dp))
                                Text("Pareados (${state.pairedDevices.size})")
                            }
                            if (state.isScanning) {
                                Button(
                                    onClick = viewModel::cancelScan,
                                    modifier = Modifier.weight(1f),
                                    shape = RoundedCornerShape(10.dp),
                                ) {
                                    CircularProgressIndicator(
                                        modifier = Modifier.size(14.dp),
                                        strokeWidth = 2.dp,
                                        color = MaterialTheme.colorScheme.onPrimary,
                                    )
                                    Spacer(Modifier.width(6.dp))
                                    Text("Cancelar")
                                }
                            } else {
                                Button(
                                    onClick = ::startScanWithPermission,
                                    modifier = Modifier.weight(1f),
                                    shape = RoundedCornerShape(10.dp),
                                ) {
                                    Icon(Icons.Default.Search, null, modifier = Modifier.size(16.dp))
                                    Spacer(Modifier.width(6.dp))
                                    Text("Buscar nuevos")
                                }
                            }
                        }

                        state.scanMessage?.let { msg ->
                            Text(
                                msg,
                                fontSize = 12.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                        if (!scanPermissions.allPermissionsGranted && pendingScanAfterGrant) {
                            Text(
                                "Esperando permisos de Bluetooth…",
                                fontSize = 12.sp,
                                color = MaterialTheme.colorScheme.error,
                            )
                        }
                    }
                }
            }

            // ── Paired devices list ───────────────────────────────────────
            if (state.pairedDevices.isNotEmpty()) {
                item {
                    Text(
                        "PAREADOS EN EL SISTEMA",
                        modifier = Modifier.padding(top = 4.dp, start = 4.dp),
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold,
                        letterSpacing = 1.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                items(state.pairedDevices) { device ->
                    BluetoothDeviceCard(device, onConnect = { viewModel.connectPrinter(device) })
                }
            }

            // ── Discovered devices list (no pareados aun) ────────────────
            if (state.discoveredDevices.isNotEmpty()) {
                item {
                    Text(
                        "ENCONTRADOS CERCA (NO PAREADOS)",
                        modifier = Modifier.padding(top = 8.dp, start = 4.dp),
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold,
                        letterSpacing = 1.sp,
                        color = MaterialTheme.colorScheme.tertiary,
                    )
                }
                items(state.discoveredDevices) { device ->
                    BluetoothDeviceCard(
                        device = device,
                        onConnect = { viewModel.connectPrinter(device) },
                        accentColor = MaterialTheme.colorScheme.tertiary,
                    )
                }
            }

            // ── App info ──────────────────────────────────────────────────
            item {
                SectionLabel("Aplicación")
                Card(modifier = Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        InfoRow(Icons.Default.Info, "Versión", BuildConfig.VERSION_NAME)
                        HorizontalDivider(thickness = 0.5.dp)
                        InfoRow(Icons.Default.Build, "Build", BuildConfig.VERSION_CODE.toString())
                    }
                }
            }

            // ── Logout ────────────────────────────────────────────────────
            item {
                Spacer(Modifier.height(4.dp))
                OutlinedButton(
                    onClick = { showLogoutDialog = true },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    colors = ButtonDefaults.outlinedButtonColors(
                        contentColor = MaterialTheme.colorScheme.error
                    )
                ) {
                    Icon(Icons.AutoMirrored.Filled.Logout, null, modifier = Modifier.size(18.dp))
                    Spacer(Modifier.width(8.dp))
                    Text("Cerrar sesión", fontWeight = FontWeight.SemiBold)
                }
            }
        }
    }

    if (showLogoutDialog) {
        AlertDialog(
            onDismissRequest = { showLogoutDialog = false },
            icon = { Icon(Icons.AutoMirrored.Filled.Logout, null, tint = MaterialTheme.colorScheme.error) },
            title = { Text("Cerrar sesión") },
            text = { Text("¿Desea cerrar la sesión actual?") },
            confirmButton = {
                Button(
                    onClick = { viewModel.logout(onLogout) },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) { Text("Cerrar sesión") }
            },
            dismissButton = {
                TextButton(onClick = { showLogoutDialog = false }) { Text("Cancelar") }
            }
        )
    }
}

@Composable
private fun SectionLabel(text: String) {
    Text(
        text.uppercase(),
        modifier = Modifier.padding(bottom = 6.dp, start = 4.dp),
        fontSize = 10.sp,
        fontWeight = FontWeight.Bold,
        letterSpacing = 1.sp,
        color = MaterialTheme.colorScheme.primary
    )
}

@Composable
private fun InfoRow(icon: ImageVector, label: String, value: String) {
    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
        Icon(icon, null, modifier = Modifier.size(18.dp),
            tint = MaterialTheme.colorScheme.onSurfaceVariant)
        Column {
            Text(label, fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Text(value, fontSize = 14.sp, fontWeight = FontWeight.Medium)
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun BluetoothDeviceCard(
    device: BluetoothDevice,
    onConnect: () -> Unit,
    accentColor: androidx.compose.ui.graphics.Color = MaterialTheme.colorScheme.primary,
) {
    @Suppress("MissingPermission")
    OutlinedCard(modifier = Modifier.fillMaxWidth(), onClick = onConnect) {
        Row(
            Modifier.fillMaxWidth().padding(12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Icon(Icons.Default.Bluetooth, null, tint = accentColor, modifier = Modifier.size(20.dp))
            Column(Modifier.weight(1f)) {
                @Suppress("MissingPermission")
                Text(device.name ?: "Desconocido", fontWeight = FontWeight.Medium, fontSize = 14.sp)
                Text(device.address, style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            Icon(Icons.Default.ChevronRight, null,
                tint = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.size(18.dp))
        }
    }
}
