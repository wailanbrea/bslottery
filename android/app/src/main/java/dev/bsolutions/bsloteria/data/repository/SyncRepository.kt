package dev.bsolutions.bsloteria.data.repository

import com.squareup.moshi.Moshi
import com.squareup.moshi.Types
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import dev.bsolutions.bsloteria.data.local.dao.BetTypeDao
import dev.bsolutions.bsloteria.data.local.dao.DrawDao
import dev.bsolutions.bsloteria.data.local.dao.TicketDao
import dev.bsolutions.bsloteria.data.local.entity.BetTypeEntity
import dev.bsolutions.bsloteria.data.local.entity.DrawEntity
import dev.bsolutions.bsloteria.data.remote.ApiService
import dev.bsolutions.bsloteria.data.remote.dto.OfflineDetailRequest
import dev.bsolutions.bsloteria.data.remote.dto.OfflineSyncRequest
import dev.bsolutions.bsloteria.data.remote.dto.OfflineSyncTicketRequest
import dev.bsolutions.bsloteria.data.remote.dto.OfflineTicketRequest
import dev.bsolutions.bsloteria.data.remote.dto.SyncBatchRequest
import dev.bsolutions.bsloteria.util.Result
import dev.bsolutions.bsloteria.util.SessionStore
import timber.log.Timber
import java.time.Instant
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SyncRepository @Inject constructor(
    private val api: ApiService,
    private val drawDao: DrawDao,
    private val ticketDao: TicketDao,
    private val betTypeDao: BetTypeDao,
    private val session: SessionStore
) {
    private val moshi = Moshi.Builder().addLast(KotlinJsonAdapterFactory()).build()

    suspend fun syncCatalog(): Result<Unit> {
        return try {
            val response = api.getSyncData(null)
            if (!response.isSuccessful) return Result.Error("Sync falló: ${response.code()}")

            val body = response.body()!!

            drawDao.upsertAll(body.draws.map { d ->
                DrawEntity(d.id, d.lotteryId, d.lotteryName, d.name, d.drawDate, d.drawTime, d.status, d.cutoffTime)
            })
            betTypeDao.upsertAll(body.betTypes.map { b ->
                BetTypeEntity(b.id, b.name, b.code, b.multiplier, b.lotteryId)
            })

            session.updateLastSync(System.currentTimeMillis())
            Timber.d("Catalog sync OK: ${body.draws.size} draws, ${body.betTypes.size} bet types")
            Result.Success(Unit)
        } catch (e: Exception) {
            Timber.e(e, "Catalog sync error")
            Result.Error(e.localizedMessage ?: "Error desconocido")
        }
    }

    suspend fun uploadPendingTickets(): Result<SyncSummary> {
        return try {
            val pending = ticketDao.getPendingOffline()
            if (pending.isEmpty()) return Result.Success(SyncSummary(0, 0))

            val controlled = pending.filter { it.offlineSessionUuid != null }
            val legacy = pending.filter { it.offlineSessionUuid == null }
            var synced = 0
            var failed = 0

            controlled.groupBy { it.offlineSessionUuid!! }.forEach { (sessionUuid, tickets) ->
                val response = api.syncOfflineTickets(
                    OfflineSyncRequest(
                        sessionUuid = sessionUuid,
                        tickets = tickets.map { ticket ->
                            OfflineSyncTicketRequest(
                                uuid = ticket.uuid,
                                drawId = ticket.drawId,
                                plays = parseDetails(ticket.detailsJson),
                                soldAt = ticket.createdAt.toIsoDateTime()
                            )
                        }
                    )
                )
                if (!response.isSuccessful) return Result.Error("Sync offline falló: ${response.code()}")

                val body = response.body()!!
                val acceptedUuids = body.acceptedUuids.ifEmpty {
                    if (body.rejectedTickets == 0) tickets.map { it.uuid } else emptyList()
                }

                acceptedUuids.forEach { uuid ->
                    ticketDao.updateSyncStatus(uuid, "SYNCED")
                }
                body.failed.forEach { failure ->
                    ticketDao.updateSyncStatus(failure.uuid, "FAILED", failure.reason)
                }

                synced += acceptedUuids.size
                failed += body.failed.size
            }

            if (legacy.isNotEmpty()) {
                val requests = legacy.map { ticket ->
                    OfflineTicketRequest(
                        uuid = ticket.uuid,
                        drawId = ticket.drawId,
                        details = parseDetails(ticket.detailsJson),
                        soldAt = ticket.createdAt.toIsoDateTime(),
                        offline = true
                    )
                }

                val response = api.syncTicketsBatch(SyncBatchRequest(requests))
                if (!response.isSuccessful) return Result.Error("Batch legacy falló: ${response.code()}")

                val body = response.body()!!
                body.synced.forEach { uuid ->
                    ticketDao.updateSyncStatus(uuid, "SYNCED")
                }
                body.failed.forEach { failure ->
                    ticketDao.updateSyncStatus(failure.uuid, "FAILED", failure.reason)
                }

                synced += body.synced.size
                failed += body.failed.size
            }

            Timber.d("Upload: $synced synced, $failed failed")
            Result.Success(SyncSummary(synced, failed))
        } catch (e: Exception) {
            Timber.e(e, "Ticket upload error")
            Result.Error(e.localizedMessage ?: "Error desconocido")
        }
    }

    private fun parseDetails(json: String): List<OfflineDetailRequest> {
        return try {
            moshi.adapter<List<OfflineDetailRequest>>(
                Types.newParameterizedType(List::class.java, OfflineDetailRequest::class.java)
            ).fromJson(json) ?: emptyList()
        } catch (_: Exception) {
            emptyList()
        }
    }

    private fun Long.toIsoDateTime(): String =
        Instant.ofEpochMilli(this).atOffset(ZoneOffset.UTC)
            .format(DateTimeFormatter.ISO_OFFSET_DATE_TIME)
}

data class SyncSummary(val synced: Int, val failed: Int)
