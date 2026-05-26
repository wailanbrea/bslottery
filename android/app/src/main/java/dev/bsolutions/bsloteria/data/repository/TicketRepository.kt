package dev.bsolutions.bsloteria.data.repository

import com.squareup.moshi.Moshi
import com.squareup.moshi.Types
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import dev.bsolutions.bsloteria.data.local.dao.DrawDao
import dev.bsolutions.bsloteria.data.local.dao.TicketDao
import dev.bsolutions.bsloteria.data.local.entity.DrawEntity
import dev.bsolutions.bsloteria.data.local.entity.OfflineTicketEntity
import dev.bsolutions.bsloteria.data.local.entity.TicketDetailEntity
import dev.bsolutions.bsloteria.data.local.entity.TicketEntity
import dev.bsolutions.bsloteria.data.remote.ApiService
import dev.bsolutions.bsloteria.data.remote.dto.ApiError
import dev.bsolutions.bsloteria.data.remote.dto.CheckLimitResponse
import dev.bsolutions.bsloteria.data.remote.dto.OfflineDetailRequest
import dev.bsolutions.bsloteria.data.remote.dto.OfflineTicketRequest
import dev.bsolutions.bsloteria.data.remote.dto.TicketResponse
import dev.bsolutions.bsloteria.domain.model.SaleDetail
import dev.bsolutions.bsloteria.util.Result
import dev.bsolutions.bsloteria.util.SessionStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.firstOrNull
import timber.log.Timber
import java.io.IOException
import java.math.BigDecimal
import java.math.RoundingMode
import java.time.Instant
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.util.UUID
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class TicketRepository @Inject constructor(
    private val api: ApiService,
    private val ticketDao: TicketDao,
    private val drawDao: DrawDao,
    private val session: SessionStore,
    private val offlineRepository: OfflineRepository
) {
    private val moshi = Moshi.Builder().addLast(KotlinJsonAdapterFactory()).build()
    private val apiErrorAdapter = moshi.adapter(ApiError::class.java)

    fun observeRecentTickets() = ticketDao.observeRecent()
    fun observePendingCount(): Flow<Int> = ticketDao.observePendingCount()
    fun observeOpenDraws(today: String, nowTime: String): Flow<List<DrawEntity>> =
        drawDao.observeOpenDraws(today, nowTime)

    /**
     * Consulta disponibilidad de limite para un numero antes de agregar la jugada.
     * Devuelve null si no hay red o el endpoint falla — el caller debe permitir
     * la jugada y dejar que el backend valide al vender (mismo fallback que web).
     */
    suspend fun checkLimit(drawId: Long, betTypeId: Long, numberValue: String): CheckLimitResponse? {
        return try {
            val response = api.checkLimit(drawId, betTypeId, numberValue)
            if (response.isSuccessful) response.body() else null
        } catch (e: IOException) {
            null
        } catch (e: Exception) {
            Timber.w(e, "checkLimit fallo")
            null
        }
    }

    private fun extractTokenUuid(input: String): String {
        val trimmed = input.trim()
        if (trimmed.startsWith("http://", ignoreCase = true) || trimmed.startsWith("https://", ignoreCase = true)) {
            val parts = trimmed.split("/")
            val cleanParts = parts.filter { it.isNotBlank() }
            if (cleanParts.isNotEmpty()) {
                var lastPart = cleanParts.last()
                val qIdx = lastPart.indexOf('?')
                if (qIdx != -1) {
                    lastPart = lastPart.substring(0, qIdx)
                }
                val hIdx = lastPart.indexOf('#')
                if (hIdx != -1) {
                    lastPart = lastPart.substring(0, hIdx)
                }
                return lastPart
            }
        }
        if (trimmed.startsWith("ticket:", ignoreCase = true)) {
            return trimmed.substring(7)
        }
        return trimmed
    }

    suspend fun lookupTicket(token: String): Result<String> {
        val cleanToken = extractTokenUuid(token)
        if (cleanToken.isBlank()) {
            return Result.Error("Ingrese numero de ticket o QR")
        }

        // 1. Intentar buscar localmente primero para rapidez y funcionamiento offline
        val localByUuid = ticketDao.findByUuid(cleanToken)
        if (localByUuid != null) {
            return Result.Success(localByUuid.uuid)
        }

        val localOffline = ticketDao.findOfflineByUuid(cleanToken)
        if (localOffline != null) {
            return Result.Success(localOffline.uuid)
        }

        val localByNumber = ticketDao.findByTicketNumber(cleanToken)
        if (localByNumber != null) {
            return Result.Success(localByNumber.uuid)
        }

        // 2. Si no se encuentra localmente, buscar en el servidor
        return try {
            val response = api.lookupTicket(cleanToken)
            if (!response.isSuccessful) {
                return Result.Error("Ticket no encontrado (${response.code()})")
            }

            val ticket = response.body() ?: return Result.Error("Respuesta vacia")
            val sessionData = session.sessionFlow.firstOrNull()
            val draw = ticket.drawId?.let { drawDao.findById(it) }

            cacheOnlineTicket(
                ticket = ticket,
                draw = draw,
                branchId = sessionData?.branchId ?: 0L,
                userId = sessionData?.userId ?: 0L
            )

            Result.Success(ticket.uuid)
        } catch (e: IOException) {
            Result.Error("Sin conexion para buscar ticket")
        } catch (e: Exception) {
            Result.Error(e.localizedMessage ?: "No se pudo cargar el ticket")
        }
    }

    suspend fun createSale(drawId: Long, details: List<SaleDetail>): Result<String> {
        val sessionData = session.sessionFlow.firstOrNull()
            ?: return Result.Error("Sesión no iniciada")

        val uuid = UUID.randomUUID().toString()
        val nowIso = Instant.now().atOffset(ZoneOffset.UTC)
            .format(DateTimeFormatter.ISO_OFFSET_DATE_TIME)

        val totalAmount = details.fold(BigDecimal.ZERO) { carry, detail ->
            carry + (detail.amount.toBigDecimalOrNull() ?: BigDecimal.ZERO)
        }.setScale(2, RoundingMode.HALF_UP).toPlainString()

        val detailRequests = details.map { d ->
            OfflineDetailRequest(d.numberValue, d.betTypeId, d.amount)
        }

        val detailsJson = moshi.adapter<List<OfflineDetailRequest>>(
            Types.newParameterizedType(List::class.java, OfflineDetailRequest::class.java)
        ).toJson(detailRequests)

        val draw = drawDao.findById(drawId)
        val offlineTicket = OfflineTicketEntity(
            uuid = uuid,
            drawId = drawId,
            drawName = draw?.name ?: "Sorteo $drawId",
            lotteryName = draw?.lotteryName ?: "",
            detailsJson = detailsJson,
            totalAmount = totalAmount,
            branchId = sessionData.branchId,
            userId = sessionData.userId
        )
        // Try online first. Offline queuing is only allowed after a network/server outage
        // and must consume a locally authorized offline session quota.
        return try {
            val response = api.createTicket(
                OfflineTicketRequest(uuid, drawId, detailRequests, nowIso, offline = false)
            )
            if (response.isSuccessful) {
                val created = response.body()
                if (created != null) {
                    cacheOnlineTicket(created, draw, sessionData.branchId, sessionData.userId)
                }

                Timber.d("Ticket ${created?.uuid ?: uuid} created online")
                Result.Success(created?.uuid ?: uuid)
            } else if (response.code() >= 500 || response.code() == 408) {
                queueOffline(offlineTicket, details, "Servidor no disponible (${response.code()})")
            } else {
                Result.Error(extractApiErrorMessage(response) ?: "Venta rechazada por servidor: ${response.code()}")
            }
        } catch (e: IOException) {
            queueOffline(offlineTicket, details, e.localizedMessage ?: "Sin conexión")
        } catch (e: Exception) {
            Result.Error(e.localizedMessage ?: "Error creando ticket")
        }
    }

    private suspend fun cacheOnlineTicket(
        ticket: TicketResponse,
        draw: DrawEntity?,
        branchId: Long,
        userId: Long
    ) {
        ticketDao.upsertAll(
            listOf(
                TicketEntity(
                    id = ticket.id,
                    uuid = ticket.uuid,
                    ticketNumber = ticket.ticketNumber,
                    drawId = draw?.id ?: 0L,
                    drawName = ticket.drawName ?: draw?.name ?: "",
                    lotteryName = ticket.lotteryName ?: draw?.lotteryName ?: "",
                    totalAmount = ticket.totalAmount,
                    status = ticket.status,
                    soldAt = ticket.soldAt,
                    soldAtEpoch = parseIsoToEpoch(ticket.soldAt),
                    branchId = branchId,
                    userId = userId,
                    isSyncPending = false
                )
            )
        )

        if (ticket.details.isNotEmpty()) {
            ticketDao.upsertDetails(
                ticket.details.map { detail ->
                    TicketDetailEntity(
                        id = detail.id,
                        ticketId = ticket.id,
                        ticketUuid = ticket.uuid,
                        numberValue = detail.numberValue,
                        betTypeId = detail.betTypeId ?: 0L,
                        betTypeName = detail.betTypeName,
                        amount = detail.amount,
                        potentialPrize = detail.potentialPrize
                    )
                }
            )
        }
    }

    private suspend fun queueOffline(
        ticket: OfflineTicketEntity,
        details: List<SaleDetail>,
        reason: String
    ): Result<String> {
        Timber.w("Queueing ticket ${ticket.uuid} offline: $reason")
        return offlineRepository.queueOfflineTicket(ticket, details)
    }

    private fun extractApiErrorMessage(response: retrofit2.Response<*>): String? {
        return try {
            val raw = response.errorBody()?.string()?.trim()
            if (raw.isNullOrBlank()) return null
            apiErrorAdapter.fromJson(raw)?.message?.takeIf { it.isNotBlank() }
        } catch (_: Exception) {
            null
        }
    }

    /**
     * Parsea timestamps que el backend retorna como "yyyy-MM-dd HH:mm:ss" o ISO-8601 a
     * epoch millis. Si falla, retorna 0 para que el filtro de fecha lo trate como muy antiguo.
     */
    private fun parseIsoToEpoch(value: String?): Long {
        if (value.isNullOrBlank()) return 0L
        return try {
            // Try ISO-8601 with offset first (e.g. "2026-05-21T14:30:00-04:00")
            Instant.parse(value).toEpochMilli()
        } catch (_: Exception) {
            try {
                // Fall back to "yyyy-MM-dd HH:mm:ss" assumed in device local TZ.
                val fmt = java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.getDefault())
                fmt.parse(value)?.time ?: 0L
            } catch (_: Exception) {
                0L
            }
        }
    }
}
