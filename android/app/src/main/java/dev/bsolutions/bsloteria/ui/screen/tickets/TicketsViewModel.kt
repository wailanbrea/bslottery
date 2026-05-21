package dev.bsolutions.bsloteria.ui.screen.tickets

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import dev.bsolutions.bsloteria.data.local.dao.TicketDao
import dev.bsolutions.bsloteria.data.repository.TicketRepository
import dev.bsolutions.bsloteria.util.Result
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale
import javax.inject.Inject

enum class DateFilter { TODAY, YESTERDAY, CUSTOM }

data class TicketDisplayItem(
    val uuid: String,
    val ticketNumber: String,
    val drawName: String,
    val lotteryName: String,
    val totalAmount: String,
    val soldAtEpoch: Long,
    val timeLabel: String,   // "h:mm a"  ej. "8:35 PM"
    val dateLabel: String,   // "dd/MM/yyyy"
    val status: String,
    val isSyncPending: Boolean,
    val syncError: String? = null,
    val jugadasPreview: String,  // "25Q, 47P, 1234Pl" (truncado)
    val jugadasCount: Int,
)

data class TicketSearchState(
    val query: String = "",
    val isLoading: Boolean = false,
    val error: String? = null
)

data class TicketFilterState(
    val dateFilter: DateFilter = DateFilter.TODAY,
    val customStartMs: Long? = null,
    val customEndMs: Long? = null,
)

@HiltViewModel
class TicketsViewModel @Inject constructor(
    private val ticketDao: TicketDao,
    private val ticketRepository: TicketRepository
) : ViewModel() {

    private val _searchState = MutableStateFlow(TicketSearchState())
    val searchState: StateFlow<TicketSearchState> = _searchState

    private val _filterState = MutableStateFlow(TicketFilterState())
    val filterState: StateFlow<TicketFilterState> = _filterState

    private val timeFormat = SimpleDateFormat("h:mm a", Locale.getDefault())
    private val dateFormat = SimpleDateFormat("dd/MM/yyyy", Locale.getDefault())
    private val iso1 = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
    private val iso2 = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX", Locale.getDefault())

    /**
     * Combina los tickets sincronizados, los offline pendientes y los detalles
     * para mostrar preview de jugadas. Aplica filtro de fecha (Hoy/Ayer/Personalizada).
     */
    val tickets: StateFlow<List<TicketDisplayItem>> = combine(
        ticketDao.observeRecent(),
        ticketDao.observeAllOffline(),
        ticketDao.observeAllDetails(),
        _filterState,
    ) { synced, offline, allDetails, filter ->
        val detailsByTicketId = allDetails.groupBy { it.ticketId }
        val detailsByUuid = allDetails.groupBy { it.ticketUuid }

        val syncedItems = synced.map { t ->
            val details = detailsByTicketId[t.id].orEmpty()
            val epoch = if (t.soldAtEpoch > 0) t.soldAtEpoch else tryParseTimestamp(t.soldAt)
            TicketDisplayItem(
                uuid = t.uuid,
                ticketNumber = t.ticketNumber ?: shortUuid(t.uuid),
                drawName = t.drawName,
                lotteryName = t.lotteryName,
                totalAmount = t.totalAmount,
                soldAtEpoch = epoch,
                timeLabel = if (epoch > 0) timeFormat.format(Date(epoch)) else "—",
                dateLabel = if (epoch > 0) dateFormat.format(Date(epoch)) else "—",
                status = t.status,
                isSyncPending = t.isSyncPending,
                jugadasPreview = buildJugadasPreview(details.map { it.numberValue to it.betTypeName }),
                jugadasCount = details.size,
            )
        }

        val offlineItems = offline
            .filter { o -> synced.none { s -> s.uuid == o.uuid } }
            .map { o ->
                val details = detailsByUuid[o.uuid].orEmpty()
                TicketDisplayItem(
                    uuid = o.uuid,
                    ticketNumber = "OFFLINE-" + shortUuid(o.uuid),
                    drawName = o.drawName,
                    lotteryName = o.lotteryName,
                    totalAmount = o.totalAmount,
                    soldAtEpoch = o.createdAt,
                    timeLabel = timeFormat.format(Date(o.createdAt)),
                    dateLabel = dateFormat.format(Date(o.createdAt)),
                    status = o.syncStatus,
                    isSyncPending = o.syncStatus == "PENDING" || o.syncStatus == "FAILED",
                    syncError = o.lastSyncError,
                    jugadasPreview = buildJugadasPreview(details.map { it.numberValue to it.betTypeName }),
                    jugadasCount = details.size,
                )
            }

        val all = syncedItems + offlineItems
        val filtered = applyDateFilter(all, filter)
        filtered.sortedByDescending { it.soldAtEpoch }.take(100)
    }.stateIn(viewModelScope, SharingStarted.Eagerly, emptyList())

    // ── Filter actions ─────────────────────────────────────────────────────

    fun setDateFilter(filter: DateFilter) {
        _filterState.update { it.copy(dateFilter = filter) }
    }

    fun setCustomRange(startMs: Long, endMs: Long) {
        _filterState.update {
            it.copy(
                dateFilter = DateFilter.CUSTOM,
                customStartMs = startMs,
                customEndMs = endMs,
            )
        }
    }

    // ── Search actions (unchanged) ─────────────────────────────────────────

    fun onSearchChange(value: String) {
        _searchState.update { it.copy(query = value, error = null) }
    }

    fun lookup(onFound: (String) -> Unit) {
        val query = _searchState.value.query.trim()
        if (query.isBlank()) {
            _searchState.update { it.copy(error = "Ingrese numero de ticket o QR") }
            return
        }

        viewModelScope.launch {
            _searchState.update { it.copy(isLoading = true, error = null) }
            when (val result = ticketRepository.lookupTicket(query)) {
                is Result.Success -> {
                    _searchState.update { it.copy(isLoading = false, query = "") }
                    onFound(result.data)
                }
                is Result.Error -> _searchState.update { it.copy(isLoading = false, error = result.message) }
                else -> {}
            }
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private fun applyDateFilter(items: List<TicketDisplayItem>, filter: TicketFilterState): List<TicketDisplayItem> {
        val (start, end) = computeRange(filter) ?: return items
        return items.filter { it.soldAtEpoch in start..end }
    }

    private fun computeRange(filter: TicketFilterState): Pair<Long, Long>? {
        val cal = Calendar.getInstance()
        return when (filter.dateFilter) {
            DateFilter.TODAY -> {
                cal.set(Calendar.HOUR_OF_DAY, 0); cal.set(Calendar.MINUTE, 0)
                cal.set(Calendar.SECOND, 0); cal.set(Calendar.MILLISECOND, 0)
                val start = cal.timeInMillis
                cal.add(Calendar.DAY_OF_YEAR, 1); cal.add(Calendar.MILLISECOND, -1)
                start to cal.timeInMillis
            }
            DateFilter.YESTERDAY -> {
                cal.add(Calendar.DAY_OF_YEAR, -1)
                cal.set(Calendar.HOUR_OF_DAY, 0); cal.set(Calendar.MINUTE, 0)
                cal.set(Calendar.SECOND, 0); cal.set(Calendar.MILLISECOND, 0)
                val start = cal.timeInMillis
                cal.add(Calendar.DAY_OF_YEAR, 1); cal.add(Calendar.MILLISECOND, -1)
                start to cal.timeInMillis
            }
            DateFilter.CUSTOM -> {
                val start = filter.customStartMs ?: return null
                val end = filter.customEndMs ?: return null
                start to end
            }
        }
    }

    private fun tryParseTimestamp(value: String?): Long {
        if (value.isNullOrBlank()) return 0L
        return try { iso2.parse(value)?.time ?: 0L } catch (_: Exception) {
            try { iso1.parse(value)?.time ?: 0L } catch (_: Exception) { 0L }
        }
    }

    private fun buildJugadasPreview(pairs: List<Pair<String, String>>): String {
        if (pairs.isEmpty()) return ""
        return pairs.take(3).joinToString(", ") { (num, type) ->
            val short = when (type.uppercase()) {
                "QUINIELA" -> "Q"
                "PALE" -> "P"
                "TRIPLETA" -> "T"
                "SUPER_PALE", "SUPER PALE" -> "SP"
                else -> type.take(1).uppercase()
            }
            "$num$short"
        } + if (pairs.size > 3) "…" else ""
    }

    private fun shortUuid(uuid: String): String = uuid.take(8).uppercase()
}
