package dev.bsolutions.bsloteria.printer

import android.Manifest
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothDevice
import android.bluetooth.BluetoothManager
import android.bluetooth.BluetoothSocket
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.content.ContextCompat
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.channels.awaitClose
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.callbackFlow
import timber.log.Timber
import java.io.OutputStream
import java.util.UUID
import javax.inject.Inject
import javax.inject.Singleton

private val SPP_UUID: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")

/** Evento que emite [BluetoothPrinterManager.scanForDevices] mientras descubre. */
sealed class ScanEvent {
    data class DeviceFound(val device: BluetoothDevice) : ScanEvent()
    object Finished : ScanEvent()
    data class Error(val message: String) : ScanEvent()
}

@Singleton
class BluetoothPrinterManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val adapter: BluetoothAdapter? by lazy {
        (context.getSystemService(Context.BLUETOOTH_SERVICE) as? BluetoothManager)?.adapter
    }

    private var socket: BluetoothSocket? = null
    private var outputStream: OutputStream? = null

    fun isAvailable(): Boolean = adapter?.isEnabled == true && hasConnectPermission()

    fun pairedPrinters(): List<BluetoothDevice> {
        if (!hasConnectPermission()) return emptyList()
        return try {
            @Suppress("MissingPermission")
            adapter?.bondedDevices?.toList() ?: emptyList()
        } catch (e: SecurityException) {
            Timber.w(e, "No BT_CONNECT permission")
            emptyList()
        }
    }

    fun connect(device: BluetoothDevice): Boolean {
        if (!hasConnectPermission()) return false
        return try {
            disconnect()
            @Suppress("MissingPermission")
            val s = device.createRfcommSocketToServiceRecord(SPP_UUID)
            @Suppress("MissingPermission")
            adapter?.cancelDiscovery()
            s.connect()
            socket = s
            outputStream = s.outputStream
            @Suppress("MissingPermission")
            Timber.d("BT connected to ${device.name}")
            true
        } catch (e: Exception) {
            Timber.e(e, "BT connect failed")
            false
        }
    }

    /**
     * Reconecta a una impresora previamente conocida usando su MAC address.
     * Util para restaurar la conexion al arrancar la app sin pedir al usuario
     * que vuelva a seleccionarla.
     */
    fun connectByAddress(address: String): Boolean {
        if (address.isBlank()) return false
        val a = adapter ?: return false
        if (!hasConnectPermission()) return false
        return try {
            @Suppress("MissingPermission")
            val device = a.getRemoteDevice(address)
            connect(device)
        } catch (e: Exception) {
            Timber.e(e, "BT reconnect by address failed: $address")
            false
        }
    }

    /**
     * Descubre dispositivos Bluetooth encendidos en el rango. Emite [ScanEvent.DeviceFound]
     * por cada dispositivo nuevo y [ScanEvent.Finished] cuando termina (~12 segundos).
     *
     * El Flow cancela el discovery y desregistra el receiver cuando el collector se cierra,
     * asi que es seguro llamarlo desde un ViewModelScope que se cancele al rotar pantalla.
     */
    fun scanForDevices(): Flow<ScanEvent> = callbackFlow {
        val a = adapter
        if (a == null) {
            trySend(ScanEvent.Error("Bluetooth no disponible en este dispositivo"))
            close()
            return@callbackFlow
        }
        if (!a.isEnabled) {
            trySend(ScanEvent.Error("Bluetooth apagado"))
            close()
            return@callbackFlow
        }
        if (!hasScanPermission()) {
            trySend(ScanEvent.Error("Falta permiso de busqueda Bluetooth"))
            close()
            return@callbackFlow
        }

        val seenAddresses = mutableSetOf<String>()
        val receiver = object : BroadcastReceiver() {
            override fun onReceive(ctx: Context?, intent: Intent?) {
                when (intent?.action) {
                    BluetoothDevice.ACTION_FOUND -> {
                        @Suppress("DEPRECATION")
                        val device: BluetoothDevice? = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                            intent.getParcelableExtra(BluetoothDevice.EXTRA_DEVICE, BluetoothDevice::class.java)
                        } else {
                            intent.getParcelableExtra(BluetoothDevice.EXTRA_DEVICE)
                        }
                        device?.let {
                            if (seenAddresses.add(it.address)) {
                                trySend(ScanEvent.DeviceFound(it))
                            }
                        }
                    }
                    BluetoothAdapter.ACTION_DISCOVERY_FINISHED -> {
                        trySend(ScanEvent.Finished)
                        close()
                    }
                }
            }
        }

        val filter = IntentFilter().apply {
            addAction(BluetoothDevice.ACTION_FOUND)
            addAction(BluetoothAdapter.ACTION_DISCOVERY_FINISHED)
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            context.registerReceiver(receiver, filter, Context.RECEIVER_NOT_EXPORTED)
        } else {
            @Suppress("UnspecifiedRegisterReceiverFlag")
            context.registerReceiver(receiver, filter)
        }

        try {
            @Suppress("MissingPermission")
            if (a.isDiscovering) a.cancelDiscovery()
            @Suppress("MissingPermission")
            val started = a.startDiscovery()
            if (!started) {
                trySend(ScanEvent.Error("No se pudo iniciar la busqueda"))
                close()
            }
        } catch (e: SecurityException) {
            trySend(ScanEvent.Error("Permiso denegado al iniciar busqueda"))
            close()
        }

        awaitClose {
            try {
                context.unregisterReceiver(receiver)
            } catch (_: Exception) { /* ya desregistrado */ }
            try {
                @Suppress("MissingPermission")
                if (hasScanPermission()) adapter?.cancelDiscovery()
            } catch (_: Exception) {}
        }
    }

    /** Cancela cualquier discovery en curso. */
    fun cancelScan() {
        try {
            @Suppress("MissingPermission")
            if (hasScanPermission()) adapter?.cancelDiscovery()
        } catch (_: Exception) {}
    }

    fun print(content: String): Boolean {
        val out = outputStream ?: return false
        return try {
            val bytes = buildEscPos(content)
            out.write(bytes)
            out.flush()
            true
        } catch (e: Exception) {
            Timber.e(e, "BT print failed")
            false
        }
    }

    fun disconnect() {
        try { outputStream?.close() } catch (_: Exception) {}
        try { socket?.close() } catch (_: Exception) {}
        outputStream = null
        socket = null
    }

    fun isConnected(): Boolean = socket?.isConnected == true

    private fun hasConnectPermission(): Boolean =
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            ContextCompat.checkSelfPermission(context, Manifest.permission.BLUETOOTH_CONNECT) ==
                PackageManager.PERMISSION_GRANTED
        } else {
            true
        }

    private fun hasScanPermission(): Boolean =
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            ContextCompat.checkSelfPermission(context, Manifest.permission.BLUETOOTH_SCAN) ==
                PackageManager.PERMISSION_GRANTED
        } else {
            ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) ==
                PackageManager.PERMISSION_GRANTED
        }

    private fun buildEscPos(content: String): ByteArray {
        val buf = mutableListOf<Byte>()
        buf.addAll(listOf(0x1B, 0x40).map { it.toByte() })  // INIT
        content.lines().forEach { line ->
            val qrMatch = QR_MARKER.find(line.trim())
            if (qrMatch != null) {
                val data = qrMatch.groupValues[1]
                buf.addAll(listOf(0x1B, 0x61, 0x01).map { it.toByte() })  // align center
                appendQrCode(buf, data)
                buf.addAll(listOf(0x1B, 0x61, 0x00).map { it.toByte() })  // align left
            } else {
                buf.addAll(line.toByteArray(Charsets.ISO_8859_1).toList())
                buf.add(0x0A)
            }
        }
        repeat(4) { buf.add(0x0A) }
        buf.addAll(listOf(0x1D, 0x56, 0x41, 0x00).map { it.toByte() })  // cut
        return buf.toByteArray()
    }

    /**
     * Codifica una secuencia ESC/POS de QR modelo 2 dentro del buffer dado.
     * Tamaño de módulo 6 (~21mm @203dpi), correccion de errores nivel M.
     */
    private fun appendQrCode(buf: MutableList<Byte>, data: String) {
        if (data.isEmpty()) return

        // 1) Seleccionar modelo 2: GS ( k 04 00 31 41 32 00
        buf.addAll(listOf(0x1D, 0x28, 0x6B, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00).map { it.toByte() })
        // 2) Tamano de modulo: GS ( k 03 00 31 43 06
        buf.addAll(listOf(0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x43, 0x06).map { it.toByte() })
        // 3) Correccion de errores M: GS ( k 03 00 31 45 31
        buf.addAll(listOf(0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x45, 0x31).map { it.toByte() })

        // 4) Guardar data en buffer del simbolo: GS ( k pL pH 31 50 30 <data>
        val payload = data.toByteArray(Charsets.UTF_8)
        val len = payload.size + 3
        val pL = (len and 0xFF).toByte()
        val pH = ((len shr 8) and 0xFF).toByte()
        buf.addAll(listOf(0x1D, 0x28, 0x6B).map { it.toByte() })
        buf.add(pL)
        buf.add(pH)
        buf.addAll(listOf(0x31, 0x50, 0x30).map { it.toByte() })
        buf.addAll(payload.toList())

        // 5) Imprimir simbolo: GS ( k 03 00 31 51 30
        buf.addAll(listOf(0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x51, 0x30).map { it.toByte() })

        // Salto de linea tras el QR
        buf.add(0x0A)
    }

    companion object {
        private val QR_MARKER = Regex("""\[\[QR:(.+?)]]""")
    }
}
