package dev.bsolutions.bsloteria.ui.screen.tickets

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.squareup.moshi.Moshi
import com.squareup.moshi.Types
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import dagger.hilt.android.lifecycle.HiltViewModel
import dev.bsolutions.bsloteria.data.local.dao.BetTypeDao
import dev.bsolutions.bsloteria.data.local.dao.TicketDao
import dev.bsolutions.bsloteria.data.local.entity.BetTypeEntity
import dev.bsolutions.bsloteria.data.remote.dto.OfflineDetailRequest
import dev.bsolutions.bsloteria.data.remote.dto.WinnerDto
import dev.bsolutions.bsloteria.data.repository.PrizeRepository
import dev.bsolutions.bsloteria.printer.BluetoothPrinterManager
import dev.bsolutions.bsloteria.util.Result
import dev.bsolutions.bsloteria.util.SessionStore
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import javax.inject.Inject

data class TicketDetailUiState(
    val uuid: String = "",
    val ticketNumber: String = "",
    val drawName: String = "",
    val lotteryName: String = "",
    val totalAmount: String = "0.00",
    val soldAt: String = "",
    val status: String = "",
    val isSyncPending: Boolean = false,
    val syncError: String? = null,
    val details: List<TicketLineItem> = emptyList(),
    val isLoading: Boolean = true,
    val printMessage: String? = null,
    val winners: List<WinnerDto> = emptyList(),
    val totalReleased: String = "0.00",
    val hasReleasablePrizes: Boolean = false,
    val winnersLoading: Boolean = false,
    val winnersError: String? = null,
    val isPayingPrize: Boolean = false,
    val prizeSuccessMessage: String? = null,
)

data class TicketLineItem(
    val numberValue: String,
    val betTypeName: String,
    val betTypeCode: String,
    val amount: String,
    val potentialPrize: String?
)

@HiltViewModel
class TicketDetailViewModel @Inject constructor(
    savedStateHandle: SavedStateHandle,
    private val ticketDao: TicketDao,
    private val betTypeDao: BetTypeDao,
    private val printerManager: BluetoothPrinterManager,
    private val prizeRepository: PrizeRepository,
    private val sessionStore: SessionStore,
) : ViewModel() {

    private val uuid: String = savedStateHandle["uuid"] ?: ""
    private val moshi = Moshi.Builder().addLast(KotlinJsonAdapterFactory()).build()

    private val _state = MutableStateFlow(TicketDetailUiState(uuid = uuid))
    val state: StateFlow<TicketDetailUiState> = _state

    init {
        viewModelScope.launch {
            load()
            // Solo intentar consultar premios si el ticket no es offline pendiente.
            if (!_state.value.isSyncPending && uuid.isNotBlank()) {
                loadWinners()
            }
        }
    }

    fun loadWinners() {
        if (uuid.isBlank()) return
        viewModelScope.launch {
            _state.update { it.copy(winnersLoading = true, winnersError = null) }
            when (val result = prizeRepository.getWinners(uuid)) {
                is Result.Success -> _state.update {
                    it.copy(
                        winnersLoading = false,
                        winners = result.data.winners,
                        totalReleased = result.data.totalReleased,
                        hasReleasablePrizes = result.data.hasReleasablePrizes,
                        status = result.data.ticket.status.ifBlank { it.status },
                    )
                }
                is Result.Error -> _state.update {
                    it.copy(winnersLoading = false, winnersError = result.message)
                }
                Result.Loading -> Unit
            }
        }
    }

    fun payPrize() {
        if (_state.value.isPayingPrize) return
        viewModelScope.launch {
            _state.update { it.copy(isPayingPrize = true, winnersError = null) }
            when (val result = prizeRepository.payPrize(uuid)) {
                is Result.Success -> {
                    _state.update {
                        it.copy(
                            isPayingPrize = false,
                            prizeSuccessMessage = "Premio pagado: ${result.data.paymentsCount} jugada(s) — RD$ ${result.data.totalPaid}",
                        )
                    }
                    // Refrescar winners para reflejar PAID
                    loadWinners()
                }
                is Result.Error -> _state.update {
                    it.copy(isPayingPrize = false, winnersError = result.message)
                }
                Result.Loading -> Unit
            }
        }
    }

    fun clearPrizeSuccess() = _state.update { it.copy(prizeSuccessMessage = null) }
    fun clearWinnersError() = _state.update { it.copy(winnersError = null) }

    private suspend fun load() {
        val betTypes = betTypeDao.findAll().associateBy { it.id }

        // Try synced ticket first
        val synced = ticketDao.findByUuid(uuid)
        if (synced != null) {
            val details = ticketDao.findDetailsByTicketId(synced.id).map { d ->
                TicketLineItem(d.numberValue, d.betTypeName, d.betTypeName, d.amount, d.potentialPrize)
            }
            _state.value = TicketDetailUiState(
                uuid = synced.uuid,
                ticketNumber = synced.ticketNumber ?: synced.uuid.take(8).uppercase(),
                drawName = synced.drawName,
                lotteryName = synced.lotteryName,
                totalAmount = synced.totalAmount,
                soldAt = synced.soldAt,
                status = synced.status,
                isSyncPending = synced.isSyncPending,
                details = details,
                isLoading = false
            )
            return
        }

        // Fall back to offline ticket
        val offline = ticketDao.findOfflineByUuid(uuid)
        if (offline != null) {
            val details = parseOfflineDetails(offline.detailsJson, betTypes)
            _state.value = TicketDetailUiState(
                uuid = offline.uuid,
                ticketNumber = "OFFLINE-" + offline.uuid.take(8).uppercase(),
                drawName = offline.drawName,
                lotteryName = offline.lotteryName,
                totalAmount = offline.totalAmount,
                soldAt = formatEpoch(offline.createdAt),
                status = offline.syncStatus,
                isSyncPending = offline.syncStatus == "PENDING" || offline.syncStatus == "FAILED",
                syncError = offline.lastSyncError,
                details = details,
                isLoading = false
            )
            return
        }

        _state.value = _state.value.copy(isLoading = false)
    }

    private fun parseOfflineDetails(
        json: String,
        betTypes: Map<Long, BetTypeEntity>
    ): List<TicketLineItem> = try {
        val type = Types.newParameterizedType(List::class.java, OfflineDetailRequest::class.java)
        val adapter = moshi.adapter<List<OfflineDetailRequest>>(type)
        adapter.fromJson(json)?.map { d ->
            val bt = betTypes[d.betTypeId]
            TicketLineItem(
                numberValue = d.numberValue,
                betTypeName = bt?.name ?: "Tipo ${d.betTypeId}",
                betTypeCode = bt?.code ?: "",
                amount = d.amount,
                potentialPrize = null
            )
        } ?: emptyList()
    } catch (_: Exception) { emptyList() }

    fun printTicket() {
        val s = _state.value
        viewModelScope.launch {
            _state.update { it.copy(printMessage = "Imprimiendo...") }
            val serverUrl = sessionStore.effectiveServerUrlFlow.firstOrNull().orEmpty()
            val text = buildDetailTicketText(s, serverUrl)
            val ok = withContext(Dispatchers.IO) { printerManager.print(text) }
            _state.update { it.copy(printMessage = if (ok) null else "Sin impresora conectada") }
        }
    }

    fun clearPrintMessage() = _state.update { it.copy(printMessage = null) }

    private fun buildDetailTicketText(s: TicketDetailUiState, serverUrl: String): String {
        val sep = "-".repeat(32)
        val dblSep = "=".repeat(32)
        return buildString {
            appendLine(dblSep)
            appendLine(s.lotteryName.centerPad(32))
            appendLine(dblSep)
            appendLine(s.drawName.take(32))
            appendLine("Fecha: ${s.soldAt}")
            if (s.uuid.isNotBlank()) appendLine("ID: ${s.uuid.take(12)}")
            appendLine(sep)
            s.details.forEach { d ->
                val t = when (d.betTypeCode.uppercase()) {
                    "QUINIELA" -> "Q "
                    "PALE" -> "Pl"
                    "TRIPLETA" -> "Tr"
                    "SUPER_PALE" -> "SP"
                    else -> d.betTypeCode.take(2)
                }
                appendLine("${d.numberValue.padEnd(8)} $t  RD\$ ${d.amount}")
            }
            appendLine(dblSep)
            appendLine("TOTAL:       RD\$ ${s.totalAmount}")
            appendLine(dblSep)
            if (serverUrl.isNotBlank() && s.uuid.isNotBlank()) {
                val qrUrl = serverUrl.trimEnd('/') + "/t/" + s.uuid
                appendLine("[[QR:$qrUrl]]")
                appendLine("Escanea para ver tu ticket")
            }
        }
    }

    private fun String.centerPad(width: Int): String {
        if (length >= width) return take(width)
        val pad = (width - length) / 2
        return " ".repeat(pad) + this
    }

    private fun formatEpoch(ms: Long): String = try {
        SimpleDateFormat("yyyy-MM-dd HH:mm", Locale.getDefault()).format(Date(ms))
    } catch (_: Exception) { ms.toString() }
}
