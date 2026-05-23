package dev.bsolutions.bsloteria.ui.screen.settings

import android.Manifest
import android.bluetooth.BluetoothDevice
import android.os.Build
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.expandVertically
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.shrinkVertically
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.google.accompanist.permissions.ExperimentalPermissionsApi
import com.google.accompanist.permissions.rememberMultiplePermissionsState
import dev.bsolutions.bsloteria.BuildConfig

private enum class SettingsTab(val label: String, val icon: ImageVector) {
    Cuenta("Cuenta", Icons.Default.AccountCircle),
    Servidor("Servidor", Icons.Default.CloudQueue),
    Impresora("Impresora", Icons.Default.Print),
    Aplicacion("App", Icons.Default.Info),
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SettingsScreen(
    viewModel: SettingsViewModel,
    onBack: () -> Unit,
    onLogout: () -> Unit,
) {
    val state by viewModel.state.collectAsStateWithLifecycle()
    val session by viewModel.session.collectAsStateWithLifecycle()
    var selectedTab by rememberSaveable { mutableStateOf(SettingsTab.Cuenta) }
    var showLogoutDialog by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Configuración", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, null)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface,
                ),
            )
        },
        bottomBar = {
            NavigationBar(
                containerColor = MaterialTheme.colorScheme.surface,
                tonalElevation = 4.dp,
            ) {
                SettingsTab.values().forEach { tab ->
                    NavigationBarItem(
                        selected = tab == selectedTab,
                        onClick = { selectedTab = tab },
                        icon = { Icon(tab.icon, null, modifier = Modifier.size(22.dp)) },
                        label = { Text(tab.label, fontSize = 11.sp, fontWeight = FontWeight.Medium) },
                        alwaysShowLabel = true,
                    )
                }
            }
        },
    ) { padding ->
        Box(
            Modifier.fillMaxSize().padding(padding),
        ) {
            when (selectedTab) {
                SettingsTab.Cuenta -> CuentaTab(
                    session = session,
                    onLogoutRequest = { showLogoutDialog = true },
                )
                SettingsTab.Servidor -> ServidorTab(state = state, viewModel = viewModel)
                SettingsTab.Impresora -> ImpresoraTab(state = state, viewModel = viewModel)
                SettingsTab.Aplicacion -> AplicacionTab()
            }
        }
    }

    if (showLogoutDialog) {
        AlertDialog(
            onDismissRequest = { showLogoutDialog = false },
            icon = { Icon(Icons.AutoMirrored.Filled.Logout, null, tint = MaterialTheme.colorScheme.error) },
            title = { Text("Cerrar sesión") },
            text = { Text("¿Deseas cerrar la sesión actual? Tendrás que volver a iniciar sesión.") },
            confirmButton = {
                Button(
                    onClick = { viewModel.logout(onLogout) },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error),
                ) { Text("Cerrar sesión") }
            },
            dismissButton = {
                TextButton(onClick = { showLogoutDialog = false }) { Text("Cancelar") }
            },
        )
    }
}

// ─── TAB: Cuenta ──────────────────────────────────────────────────────────────

@Composable
private fun CuentaTab(
    session: dev.bsolutions.bsloteria.util.SessionData?,
    onLogoutRequest: () -> Unit,
) {
    val primary = MaterialTheme.colorScheme.primary

    LazyColumn(
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        if (session == null) {
            item {
                EmptyState(
                    icon = Icons.Default.AccountCircle,
                    title = "Sin sesión",
                    subtitle = "No hay un usuario activo.",
                )
            }
            return@LazyColumn
        }

        // Hero card con avatar + nombre + email
        item {
            ElevatedCard(modifier = Modifier.fillMaxWidth()) {
                Column(
                    modifier = Modifier.fillMaxWidth().padding(20.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(10.dp),
                ) {
                    Box(
                        modifier = Modifier
                            .size(72.dp)
                            .clip(CircleShape)
                            .background(primary.copy(alpha = 0.15f)),
                        contentAlignment = Alignment.Center,
                    ) {
                        Text(
                            session.userName.take(2).uppercase(),
                            fontWeight = FontWeight.Bold,
                            color = primary,
                            fontSize = 26.sp,
                        )
                    }
                    Text(
                        session.userName.ifBlank { "Usuario" },
                        fontWeight = FontWeight.Bold,
                        fontSize = 18.sp,
                        color = MaterialTheme.colorScheme.onSurface,
                    )
                    if (session.userEmail.isNotBlank()) {
                        Text(
                            session.userEmail,
                            fontSize = 13.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                    AssistChip(
                        onClick = {},
                        enabled = false,
                        label = {
                            Text(session.branchName.ifBlank { "Sin sucursal" }, fontSize = 11.sp)
                        },
                        leadingIcon = { Icon(Icons.Default.Business, null, modifier = Modifier.size(14.dp)) },
                    )
                }
            }
        }

        // Detalle de la cuenta
        item {
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    InfoRow(Icons.Default.Person, "Usuario", session.userName.ifBlank { "—" })
                    HorizontalDivider(thickness = 0.5.dp, color = MaterialTheme.colorScheme.outlineVariant)
                    InfoRow(
                        Icons.Default.Email,
                        "Correo",
                        session.userEmail.ifBlank { "(sin correo)" },
                    )
                    HorizontalDivider(thickness = 0.5.dp, color = MaterialTheme.colorScheme.outlineVariant)
                    InfoRow(Icons.Default.Business, "Sucursal", session.branchName.ifBlank { "—" })
                    HorizontalDivider(thickness = 0.5.dp, color = MaterialTheme.colorScheme.outlineVariant)
                    InfoRow(
                        Icons.Default.Shield,
                        "Permisos activos",
                        session.permissions.size.toString(),
                    )
                }
            }
        }

        item {
            OutlinedButton(
                onClick = onLogoutRequest,
                modifier = Modifier.fillMaxWidth().height(50.dp),
                shape = RoundedCornerShape(12.dp),
                colors = ButtonDefaults.outlinedButtonColors(
                    contentColor = MaterialTheme.colorScheme.error,
                ),
                border = androidx.compose.foundation.BorderStroke(1.dp, MaterialTheme.colorScheme.error),
            ) {
                Icon(Icons.AutoMirrored.Filled.Logout, null, modifier = Modifier.size(18.dp))
                Spacer(Modifier.width(8.dp))
                Text("Cerrar sesión", fontWeight = FontWeight.SemiBold)
            }
        }
    }
}

// ─── TAB: Servidor ────────────────────────────────────────────────────────────

@Composable
private fun ServidorTab(
    state: SettingsUiState,
    viewModel: SettingsViewModel,
) {
    LazyColumn(
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            ElevatedCard(modifier = Modifier.fillMaxWidth()) {
                Column(Modifier.padding(20.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(12.dp),
                    ) {
                        Box(
                            modifier = Modifier
                                .size(44.dp)
                                .clip(CircleShape)
                                .background(MaterialTheme.colorScheme.primary.copy(alpha = 0.12f)),
                            contentAlignment = Alignment.Center,
                        ) {
                            Icon(
                                Icons.Default.CloudQueue, null,
                                tint = MaterialTheme.colorScheme.primary,
                                modifier = Modifier.size(22.dp),
                            )
                        }
                        Column(Modifier.weight(1f)) {
                            Text(
                                "Conexión actual",
                                fontWeight = FontWeight.SemiBold,
                                fontSize = 14.sp,
                            )
                            Text(
                                if (state.hasUrlOverride) "Override personalizado" else "Default del APK",
                                fontSize = 11.sp,
                                color = if (state.hasUrlOverride) {
                                    MaterialTheme.colorScheme.tertiary
                                } else {
                                    MaterialTheme.colorScheme.onSurfaceVariant
                                },
                                fontWeight = FontWeight.Medium,
                            )
                        }
                        StatusDot(active = true)
                    }
                    Text(
                        state.effectiveServerUrl,
                        fontFamily = FontFamily.Monospace,
                        fontSize = 12.sp,
                        color = MaterialTheme.colorScheme.onSurface,
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(8.dp))
                            .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f))
                            .padding(12.dp),
                    )
                    if (state.hasUrlOverride && state.defaultServerUrl != state.effectiveServerUrl) {
                        Text(
                            "Default del APK: ${state.defaultServerUrl}",
                            fontSize = 10.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f),
                        )
                    }
                }
            }
        }

        item {
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Text(
                        "EDITAR URL DEL SERVIDOR",
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold,
                        letterSpacing = 1.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                    OutlinedTextField(
                        value = state.serverUrl,
                        onValueChange = viewModel::onServerUrlChange,
                        label = { Text("URL del servidor") },
                        placeholder = { Text(state.defaultServerUrl) },
                        leadingIcon = { Icon(Icons.Default.CloudQueue, null) },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        shape = RoundedCornerShape(10.dp),
                    )
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        Button(
                            onClick = viewModel::saveServerUrl,
                            modifier = Modifier.weight(1f).height(46.dp),
                            shape = RoundedCornerShape(10.dp),
                        ) {
                            Icon(
                                if (state.serverUrlSaved) Icons.Default.CheckCircle else Icons.Default.Save,
                                null, modifier = Modifier.size(18.dp),
                            )
                            Spacer(Modifier.width(8.dp))
                            Text(if (state.serverUrlSaved) "Guardada" else "Guardar")
                        }
                        OutlinedButton(
                            onClick = viewModel::resetServerUrlToDefault,
                            modifier = Modifier.weight(1f).height(46.dp),
                            shape = RoundedCornerShape(10.dp),
                            enabled = state.hasUrlOverride,
                        ) {
                            Icon(Icons.Default.Refresh, null, modifier = Modifier.size(18.dp))
                            Spacer(Modifier.width(8.dp))
                            Text("Restablecer")
                        }
                    }
                    Text(
                        "Cambia esta URL solo si soporte te lo indica para apuntar a un ambiente de prueba. La app vuelve al default tras Restablecer.",
                        fontSize = 11.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            }
        }
    }
}

// ─── TAB: Impresora ───────────────────────────────────────────────────────────

@OptIn(ExperimentalPermissionsApi::class)
@Composable
private fun ImpresoraTab(
    state: SettingsUiState,
    viewModel: SettingsViewModel,
) {
    val scanPermissions = rememberMultiplePermissionsState(
        permissions = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            listOf(Manifest.permission.BLUETOOTH_SCAN, Manifest.permission.BLUETOOTH_CONNECT)
        } else {
            listOf(Manifest.permission.ACCESS_FINE_LOCATION)
        },
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

    LaunchedEffect(scanPermissions.allPermissionsGranted) {
        if (scanPermissions.allPermissionsGranted && pendingScanAfterGrant) {
            pendingScanAfterGrant = false
            viewModel.refreshPairedDevices()
            viewModel.scanForDevices()
        }
    }

    LazyColumn(
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            ElevatedCard(modifier = Modifier.fillMaxWidth()) {
                Column(Modifier.padding(20.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(12.dp),
                    ) {
                        val isConnected = state.connectedDeviceName != null
                        val hasRemembered = state.rememberedDeviceName != null
                        val accent = when {
                            isConnected -> MaterialTheme.colorScheme.primary
                            hasRemembered -> MaterialTheme.colorScheme.tertiary
                            else -> MaterialTheme.colorScheme.onSurfaceVariant
                        }
                        Box(
                            modifier = Modifier
                                .size(44.dp)
                                .clip(CircleShape)
                                .background(accent.copy(alpha = 0.12f)),
                            contentAlignment = Alignment.Center,
                        ) {
                            Icon(
                                when {
                                    isConnected -> Icons.Default.BluetoothConnected
                                    hasRemembered -> Icons.Default.BluetoothSearching
                                    else -> Icons.Default.Bluetooth
                                },
                                null,
                                tint = accent,
                                modifier = Modifier.size(22.dp),
                            )
                        }
                        Column(Modifier.weight(1f)) {
                            Text(
                                state.connectedDeviceName ?: state.rememberedDeviceName ?: "Sin impresora",
                                fontWeight = FontWeight.SemiBold,
                                fontSize = 14.sp,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                            )
                            Text(
                                state.printerStatus,
                                fontSize = 12.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                        if (state.isConnecting) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(22.dp),
                                strokeWidth = 2.dp,
                            )
                        } else {
                            StatusDot(active = state.connectedDeviceName != null)
                        }
                    }

                    state.testPrintMessage?.let { msg ->
                        LaunchedEffect(msg) {
                            kotlinx.coroutines.delay(2500)
                            viewModel.clearTestPrintMessage()
                        }
                        Surface(
                            shape = RoundedCornerShape(6.dp),
                            color = if (msg.startsWith("Sin")) {
                                MaterialTheme.colorScheme.errorContainer
                            } else {
                                MaterialTheme.colorScheme.primary.copy(alpha = 0.1f)
                            },
                        ) {
                            Text(
                                msg,
                                modifier = Modifier.padding(8.dp),
                                fontSize = 12.sp,
                                color = if (msg.startsWith("Sin")) {
                                    MaterialTheme.colorScheme.onErrorContainer
                                } else {
                                    MaterialTheme.colorScheme.primary
                                },
                            )
                        }
                    }

                    if (state.connectedDeviceName != null) {
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedButton(
                                onClick = viewModel::testPrint,
                                modifier = Modifier.weight(1f).height(44.dp),
                                shape = RoundedCornerShape(10.dp),
                            ) {
                                Icon(Icons.Default.Print, null, modifier = Modifier.size(16.dp))
                                Spacer(Modifier.width(6.dp))
                                Text("Prueba")
                            }
                            OutlinedButton(
                                onClick = viewModel::disconnectPrinter,
                                modifier = Modifier.weight(1f).height(44.dp),
                                shape = RoundedCornerShape(10.dp),
                            ) {
                                Icon(Icons.Default.BluetoothDisabled, null, modifier = Modifier.size(16.dp))
                                Spacer(Modifier.width(6.dp))
                                Text("Olvidar")
                            }
                        }
                    } else if (state.rememberedDeviceName != null) {
                        Button(
                            onClick = viewModel::reconnectSaved,
                            modifier = Modifier.fillMaxWidth().height(44.dp),
                            shape = RoundedCornerShape(10.dp),
                            enabled = !state.isConnecting,
                        ) {
                            Icon(Icons.Default.BluetoothSearching, null, modifier = Modifier.size(16.dp))
                            Spacer(Modifier.width(6.dp))
                            Text("Reconectar")
                        }
                    }
                }
            }
        }

        // Imprimir tras vender
        item {
            Card(modifier = Modifier.fillMaxWidth()) {
                Row(
                    Modifier.fillMaxWidth().padding(16.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                ) {
                    Icon(
                        Icons.Default.Print, null,
                        modifier = Modifier.size(22.dp),
                        tint = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                    Column(Modifier.weight(1f)) {
                        Text("Imprimir tras vender", fontWeight = FontWeight.Medium, fontSize = 14.sp)
                        Text(
                            "Envía el ticket a la impresora automáticamente al guardarse la venta.",
                            fontSize = 11.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                    Switch(
                        checked = state.autoPrint,
                        onCheckedChange = viewModel::setAutoPrint,
                    )
                }
            }
        }

        // Buscar / refrescar
        item {
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text(
                        "BUSCAR IMPRESORAS",
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold,
                        letterSpacing = 1.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        OutlinedButton(
                            onClick = viewModel::refreshPairedDevices,
                            modifier = Modifier.weight(1f).height(44.dp),
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
                                modifier = Modifier.weight(1f).height(44.dp),
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
                                modifier = Modifier.weight(1f).height(44.dp),
                                shape = RoundedCornerShape(10.dp),
                            ) {
                                Icon(Icons.Default.Search, null, modifier = Modifier.size(16.dp))
                                Spacer(Modifier.width(6.dp))
                                Text("Buscar")
                            }
                        }
                    }
                    state.scanMessage?.let { msg ->
                        Text(msg, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                    AnimatedVisibility(
                        visible = !scanPermissions.allPermissionsGranted && pendingScanAfterGrant,
                        enter = fadeIn() + expandVertically(),
                        exit = fadeOut() + shrinkVertically(),
                    ) {
                        Text(
                            "Esperando permisos de Bluetooth…",
                            fontSize = 11.sp,
                            color = MaterialTheme.colorScheme.error,
                        )
                    }
                }
            }
        }

        // Pareados
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
            items(state.pairedDevices, key = { it.address }) { device ->
                BluetoothDeviceCard(device, onConnect = { viewModel.connectPrinter(device) })
            }
        }

        // Descubiertos
        if (state.discoveredDevices.isNotEmpty()) {
            item {
                Text(
                    "ENCONTRADOS CERCA",
                    modifier = Modifier.padding(top = 8.dp, start = 4.dp),
                    fontSize = 10.sp,
                    fontWeight = FontWeight.Bold,
                    letterSpacing = 1.sp,
                    color = MaterialTheme.colorScheme.tertiary,
                )
            }
            items(state.discoveredDevices, key = { it.address }) { device ->
                BluetoothDeviceCard(
                    device = device,
                    onConnect = { viewModel.connectPrinter(device) },
                    accentColor = MaterialTheme.colorScheme.tertiary,
                )
            }
        }
    }
}

// ─── TAB: Aplicación ──────────────────────────────────────────────────────────

@Composable
private fun AplicacionTab() {
    val primary = MaterialTheme.colorScheme.primary

    LazyColumn(
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            ElevatedCard(modifier = Modifier.fillMaxWidth()) {
                Column(
                    modifier = Modifier.fillMaxWidth().padding(24.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(10.dp),
                ) {
                    Box(
                        modifier = Modifier
                            .size(64.dp)
                            .clip(RoundedCornerShape(16.dp))
                            .background(primary.copy(alpha = 0.12f)),
                        contentAlignment = Alignment.Center,
                    ) {
                        Icon(
                            Icons.Default.Storefront, null,
                            tint = primary, modifier = Modifier.size(34.dp),
                        )
                    }
                    Text("BSLottery", fontWeight = FontWeight.Bold, fontSize = 20.sp)
                    Text(
                        "App de cajero",
                        fontSize = 12.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            }
        }
        item {
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    InfoRow(Icons.Default.Info, "Versión", BuildConfig.VERSION_NAME)
                    HorizontalDivider(thickness = 0.5.dp, color = MaterialTheme.colorScheme.outlineVariant)
                    InfoRow(Icons.Default.Build, "Build", BuildConfig.VERSION_CODE.toString())
                    HorizontalDivider(thickness = 0.5.dp, color = MaterialTheme.colorScheme.outlineVariant)
                    InfoRow(
                        Icons.Default.Code,
                        "Package",
                        BuildConfig.APPLICATION_ID,
                    )
                    HorizontalDivider(thickness = 0.5.dp, color = MaterialTheme.colorScheme.outlineVariant)
                    InfoRow(
                        if (BuildConfig.DEBUG) Icons.Default.BugReport else Icons.Default.Verified,
                        "Tipo",
                        if (BuildConfig.DEBUG) "Debug" else "Release",
                    )
                }
            }
        }
        item {
            Text(
                "© BSolutions — bsolutions.dev",
                modifier = Modifier.fillMaxWidth().padding(8.dp),
                fontSize = 10.sp,
                color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.6f),
                textAlign = androidx.compose.ui.text.style.TextAlign.Center,
            )
        }
    }
}

// ─── Helpers compartidos ──────────────────────────────────────────────────────

@Composable
private fun InfoRow(icon: ImageVector, label: String, value: String) {
    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
        Box(
            modifier = Modifier
                .size(36.dp)
                .clip(CircleShape)
                .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.6f)),
            contentAlignment = Alignment.Center,
        ) {
            Icon(
                icon, null,
                modifier = Modifier.size(18.dp),
                tint = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
        Column(Modifier.weight(1f)) {
            Text(label, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Text(value, fontSize = 14.sp, fontWeight = FontWeight.Medium, maxLines = 1, overflow = TextOverflow.Ellipsis)
        }
    }
}

@Composable
private fun StatusDot(active: Boolean) {
    Box(
        modifier = Modifier
            .size(10.dp)
            .clip(CircleShape)
            .background(
                if (active) MaterialTheme.colorScheme.primary
                else MaterialTheme.colorScheme.outlineVariant,
            ),
    )
}

@Composable
private fun EmptyState(icon: ImageVector, title: String, subtitle: String) {
    Column(
        Modifier.fillMaxWidth().padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        Icon(
            icon, null,
            modifier = Modifier.size(48.dp),
            tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.5f),
        )
        Text(title, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
        Text(
            subtitle, fontSize = 12.sp,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun BluetoothDeviceCard(
    device: BluetoothDevice,
    onConnect: () -> Unit,
    accentColor: Color = MaterialTheme.colorScheme.primary,
) {
    @Suppress("MissingPermission")
    OutlinedCard(modifier = Modifier.fillMaxWidth(), onClick = onConnect) {
        Row(
            Modifier.fillMaxWidth().padding(12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Icon(Icons.Default.Bluetooth, null, tint = accentColor, modifier = Modifier.size(20.dp))
            Column(Modifier.weight(1f)) {
                @Suppress("MissingPermission")
                Text(device.name ?: "Desconocido", fontWeight = FontWeight.Medium, fontSize = 14.sp)
                Text(
                    device.address,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    fontFamily = FontFamily.Monospace,
                )
            }
            Icon(
                Icons.Default.ChevronRight, null,
                tint = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.size(18.dp),
            )
        }
    }
}
