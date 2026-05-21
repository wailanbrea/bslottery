package dev.bsolutions.bsloteria.data.repository

import androidx.room.withTransaction
import dev.bsolutions.bsloteria.data.local.AppDatabase
import dev.bsolutions.bsloteria.data.local.dao.OfflineSessionDao
import dev.bsolutions.bsloteria.data.local.dao.TicketDao
import dev.bsolutions.bsloteria.data.local.entity.OfflineAllocationEntity
import dev.bsolutions.bsloteria.data.local.entity.OfflineSessionEntity
import dev.bsolutions.bsloteria.data.local.entity.OfflineTicketEntity
import dev.bsolutions.bsloteria.data.remote.ApiService
import dev.bsolutions.bsloteria.data.remote.dto.OfflineAllocationDto
import dev.bsolutions.bsloteria.data.remote.dto.OfflineSessionDto
import dev.bsolutions.bsloteria.data.remote.dto.OpenOfflineSessionRequest
import dev.bsolutions.bsloteria.domain.model.SaleDetail
import dev.bsolutions.bsloteria.util.Result
import dev.bsolutions.bsloteria.util.SessionStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.firstOrNull
import java.math.BigDecimal
import java.math.RoundingMode
import java.time.Instant
import java.time.OffsetDateTime
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class OfflineRepository @Inject constructor(
    private val api: ApiService,
    private val db: AppDatabase,
    private val offlineSessionDao: OfflineSessionDao,
    private val ticketDao: TicketDao,
    private val sessionStore: SessionStore
) {
    fun observeActiveSession(): Flow<OfflineSessionEntity?> = offlineSessionDao.observeActiveSession()

    suspend fun openSession(
        ticketLimit: Int = 100,
        amountLimit: String = "5000.00",
        expiresHours: Int = 8
    ): Result<OfflineSessionEntity> {
        val session = sessionStore.sessionFlow.firstOrNull()
            ?: return Result.Error("Sesión no iniciada")

        return try {
            val response = api.openOfflineSession(
                OpenOfflineSessionRequest(
                    branchId = session.branchId,
                    ticketLimit = ticketLimit,
                    amountLimit = amountLimit,
                    expiresHours = expiresHours
                )
            )

            if (!response.isSuccessful) {
                return Result.Error("No se pudo abrir sesión offline: ${response.code()}")
            }

            val body = response.body() ?: return Result.Error("Respuesta offline vacía")
            persistBootstrap(
                sessionDto = body.bootstrap.session,
                branchId = session.branchId,
                allocations = body.bootstrap.allocations
            )

            Result.Success(toEntity(body.bootstrap.session, session.branchId))
        } catch (e: Exception) {
            Result.Error(e.localizedMessage ?: "Error abriendo sesión offline")
        }
    }

    suspend fun refreshBootstrap(sessionUuid: String): Result<Unit> {
        val session = sessionStore.sessionFlow.firstOrNull()
            ?: return Result.Error("Sesión no iniciada")

        return try {
            val response = api.getOfflineBootstrap(sessionUuid)
            if (!response.isSuccessful) {
                if (response.code() == 410) {
                    offlineSessionDao.updateSessionStatus(sessionUuid, "EXPIRED")
                }
                return Result.Error("Bootstrap offline falló: ${response.code()}")
            }

            val body = response.body() ?: return Result.Error("Respuesta offline vacía")
            persistBootstrap(body.session, session.branchId, body.allocations)
            Result.Success(Unit)
        } catch (e: Exception) {
            Result.Error(e.localizedMessage ?: "Error refrescando sesión offline")
        }
    }

    suspend fun queueOfflineTicket(
        ticket: OfflineTicketEntity,
        details: List<SaleDetail>
    ): Result<String> {
        return try {
            db.withTransaction {
                val offlineSession = offlineSessionDao.findActiveSession()
                    ?: return@withTransaction Result.Error("No hay sesión offline activa")

                if (isExpired(offlineSession.expiresAt)) {
                    offlineSessionDao.updateSessionStatus(offlineSession.uuid, "EXPIRED")
                    return@withTransaction Result.Error("La sesión offline expiró")
                }

                if (offlineSession.allocatedTicketsLimit > 0 &&
                    offlineSession.usedTicketsCount >= offlineSession.allocatedTicketsLimit
                ) {
                    return@withTransaction Result.Error("Cupo de tickets offline agotado")
                }

                val total = details.fold(BigDecimal.ZERO) { carry, detail ->
                    carry + money(detail.amount)
                }

                val genericAllocation = offlineSessionDao.findGenericAllocation(offlineSession.uuid)
                if (genericAllocation != null) {
                    val allocated = money(genericAllocation.allocatedAmount)
                    val used = money(genericAllocation.usedAmount)
                    if (used + total > allocated) {
                        return@withTransaction Result.Error("Cupo de monto offline agotado")
                    }

                    offlineSessionDao.updateAllocationUsedAmount(
                        localKey = genericAllocation.localKey,
                        usedAmount = formatMoney(used + total)
                    )
                }

                ticketDao.insertOffline(ticket.copy(offlineSessionUuid = offlineSession.uuid))
                offlineSessionDao.incrementTicketUsage(offlineSession.uuid)
                Result.Success(ticket.uuid)
            }
        } catch (e: Exception) {
            Result.Error(e.localizedMessage ?: "No se pudo guardar venta offline")
        }
    }

    private suspend fun persistBootstrap(
        sessionDto: OfflineSessionDto,
        branchId: Long,
        allocations: List<OfflineAllocationDto>
    ) {
        db.withTransaction {
            val session = toEntity(sessionDto, branchId)
            offlineSessionDao.upsertSession(session)
            offlineSessionDao.deleteAllocationsForSession(session.uuid)
            offlineSessionDao.upsertAllocations(allocations.map { it.toEntity(session.uuid) })
        }
    }

    private fun toEntity(dto: OfflineSessionDto, branchId: Long): OfflineSessionEntity =
        OfflineSessionEntity(
            uuid = dto.uuid,
            status = dto.status,
            branchId = branchId,
            expiresAt = dto.expiresAt,
            allocatedTicketsLimit = dto.allocatedTicketsLimit,
            usedTicketsCount = dto.usedTicketsCount,
            allocatedAmount = dto.allocatedAmount
        )

    private fun OfflineAllocationDto.toEntity(sessionUuid: String): OfflineAllocationEntity =
        OfflineAllocationEntity(
            localKey = listOf(
                sessionUuid,
                lotteryId?.toString() ?: "all",
                drawId?.toString() ?: "all",
                betTypeId?.toString() ?: "all",
                numberValue ?: "all"
            ).joinToString(":"),
            offlineSessionUuid = sessionUuid,
            remoteId = id,
            lotteryId = lotteryId,
            drawId = drawId,
            betTypeId = betTypeId,
            numberValue = numberValue,
            allocatedAmount = allocatedAmount,
            usedAmount = usedAmount
        )

    private fun isExpired(expiresAt: String?): Boolean {
        if (expiresAt.isNullOrBlank()) return false
        return runCatching {
            OffsetDateTime.parse(expiresAt).toInstant().isBefore(Instant.now())
        }.getOrDefault(false)
    }

    private fun money(value: String): BigDecimal =
        value.toBigDecimalOrNull()?.setScale(2, RoundingMode.HALF_UP) ?: BigDecimal.ZERO

    private fun formatMoney(value: BigDecimal): String =
        value.setScale(2, RoundingMode.HALF_UP).toPlainString()
}
