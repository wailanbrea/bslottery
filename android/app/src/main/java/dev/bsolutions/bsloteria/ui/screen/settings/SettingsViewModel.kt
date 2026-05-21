package dev.bsolutions.bsloteria.ui.screen.settings

import android.bluetooth.BluetoothDevice
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import dev.bsolutions.bsloteria.data.repository.AuthRepository
import dev.bsolutions.bsloteria.printer.BluetoothPrinterManager
import dev.bsolutions.bsloteria.printer.ScanEvent
import dev.bsolutions.bsloteria.util.SessionStore
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import javax.inject.Inject

data class SettingsUiState(
    val serverUrl: String = "",
    val pairedDevices: List<BluetoothDevice> = emptyList(),
    val discoveredDevices: List<BluetoothDevice> = emptyList(),
    val connectedDeviceName: String? = null,
    val rememberedDeviceName: String? = null,
    val printerStatus: String = "Desconectado",
    val isConnecting: Boolean = false,
    val isScanning: Boolean = false,
    val scanMessage: String? = null,
    val serverUrlSaved: Boolean = false,
    val autoPrint: Boolean = true,
    val testPrintMessage: String? = null,
)

@HiltViewModel
class SettingsViewModel @Inject constructor(
    private val sessionStore: SessionStore,
    private val authRepository: AuthRepository,
    private val printerManager: BluetoothPrinterManager
) : ViewModel() {

    private val _state = MutableStateFlow(SettingsUiState())
    val state: StateFlow<SettingsUiState> = _state

    val session = sessionStore.sessionFlow.stateIn(viewModelScope, SharingStarted.Eagerly, null)

    private var scanJob: Job? = null

    init {
        viewModelScope.launch {
            sessionStore.serverUrlFlow.collect { url ->
                _state.update { it.copy(serverUrl = url ?: "") }
            }
        }
        viewModelScope.launch {
            sessionStore.printerNameFlow.collect { name ->
                _state.update { s ->
                    val isLive = printerManager.isConnected()
                    s.copy(
                        rememberedDeviceName = name,
                        connectedDeviceName = if (isLive) name else null,
                        printerStatus = when {
                            isLive && name != null -> "Conectado: $name"
                            name != null -> "Guardada: $name (desconectada)"
                            else -> "Desconectado"
                        },
                    )
                }
            }
        }
        viewModelScope.launch {
            sessionStore.autoPrintFlow.collect { enabled ->
                _state.update { it.copy(autoPrint = enabled) }
            }
        }
        refreshPairedDevices()
    }

    fun onServerUrlChange(url: String) = _state.update { it.copy(serverUrl = url, serverUrlSaved = false) }

    fun saveServerUrl() {
        viewModelScope.launch {
            sessionStore.updateServerUrl(_state.value.serverUrl.trimEnd('/'))
            _state.update { it.copy(serverUrlSaved = true) }
        }
    }

    fun setAutoPrint(enabled: Boolean) {
        viewModelScope.launch { sessionStore.setAutoPrint(enabled) }
    }

    @Suppress("MissingPermission")
    fun refreshPairedDevices() {
        if (printerManager.isAvailable()) {
            _state.update { it.copy(pairedDevices = printerManager.pairedPrinters()) }
        }
    }

    /**
     * Inicia discovery activo. Llamar despues de garantizar que el usuario otorgo
     * BLUETOOTH_SCAN (Android 12+) o ACCESS_FINE_LOCATION (Android <=11) -- los
     * permisos se piden desde la UI con accompanist-permissions.
     */
    fun scanForDevices() {
        if (_state.value.isScanning) return
        scanJob?.cancel()
        _state.update {
            it.copy(
                isScanning = true,
                scanMessage = "Buscando dispositivos cercanos…",
                discoveredDevices = emptyList(),
            )
        }
        scanJob = viewModelScope.launch {
            printerManager.scanForDevices().collect { event ->
                when (event) {
                    is ScanEvent.DeviceFound -> {
                        val alreadyPaired = _state.value.pairedDevices.any { it.address == event.device.address }
                        if (!alreadyPaired) {
                            _state.update { s ->
                                if (s.discoveredDevices.any { it.address == event.device.address }) s
                                else s.copy(discoveredDevices = s.discoveredDevices + event.device)
                            }
                        }
                    }
                    is ScanEvent.Finished -> {
                        _state.update {
                            val count = it.discoveredDevices.size
                            it.copy(
                                isScanning = false,
                                scanMessage = if (count == 0) "No se encontraron dispositivos nuevos"
                                else "Encontrados $count dispositivo(s)",
                            )
                        }
                    }
                    is ScanEvent.Error -> {
                        _state.update {
                            it.copy(isScanning = false, scanMessage = event.message)
                        }
                    }
                }
            }
        }
    }

    fun cancelScan() {
        scanJob?.cancel()
        scanJob = null
        printerManager.cancelScan()
        _state.update { it.copy(isScanning = false, scanMessage = null) }
    }

    fun clearScanMessage() = _state.update { it.copy(scanMessage = null) }

    @Suppress("MissingPermission")
    fun connectPrinter(device: BluetoothDevice) {
        viewModelScope.launch {
            _state.update { it.copy(isConnecting = true, printerStatus = "Conectando…") }
            val ok = withContext(Dispatchers.IO) { printerManager.connect(device) }
            if (ok) {
                val name = device.name ?: device.address
                sessionStore.savePrinter(device.address, name)
                _state.update {
                    it.copy(
                        isConnecting = false,
                        connectedDeviceName = name,
                        rememberedDeviceName = name,
                        printerStatus = "Conectado: $name",
                    )
                }
            } else {
                _state.update {
                    it.copy(
                        isConnecting = false,
                        connectedDeviceName = null,
                        printerStatus = "Error al conectar",
                    )
                }
            }
        }
    }

    /**
     * Reintentar conexion con la impresora guardada. Llamar tras conceder
     * permisos BT o cuando el usuario toca "Reconectar" en UI.
     */
    fun reconnectSaved() {
        viewModelScope.launch {
            val address = sessionStore.printerAddressFlow.firstOrNull() ?: return@launch
            val name = sessionStore.printerNameFlow.firstOrNull() ?: address
            _state.update { it.copy(isConnecting = true, printerStatus = "Reconectando…") }
            val ok = withContext(Dispatchers.IO) { printerManager.connectByAddress(address) }
            _state.update {
                it.copy(
                    isConnecting = false,
                    connectedDeviceName = if (ok) name else null,
                    rememberedDeviceName = name,
                    printerStatus = if (ok) "Conectado: $name" else "Guardada: $name (desconectada)",
                )
            }
        }
    }

    fun testPrint() {
        val content = buildString {
            appendLine("================================")
            appendLine("       BSLottery - TEST")
            appendLine("================================")
            appendLine("Impresora configurada correctamente")
            appendLine("================================")
        }
        viewModelScope.launch {
            _state.update { it.copy(testPrintMessage = "Imprimiendo…") }
            val ok = withContext(Dispatchers.IO) { printerManager.print(content) }
            _state.update {
                it.copy(testPrintMessage = if (ok) "Prueba enviada" else "Sin impresora conectada")
            }
        }
    }

    fun clearTestPrintMessage() = _state.update { it.copy(testPrintMessage = null) }

    fun disconnectPrinter() {
        viewModelScope.launch {
            printerManager.disconnect()
            sessionStore.clearPrinter()
            _state.update {
                it.copy(
                    connectedDeviceName = null,
                    rememberedDeviceName = null,
                    printerStatus = "Desconectado",
                )
            }
        }
    }

    fun logout(onDone: () -> Unit) {
        viewModelScope.launch {
            authRepository.logout()
            onDone()
        }
    }
}
