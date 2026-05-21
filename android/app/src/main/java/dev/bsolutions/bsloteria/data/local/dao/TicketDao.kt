package dev.bsolutions.bsloteria.data.local.dao

import androidx.room.*
import dev.bsolutions.bsloteria.data.local.entity.OfflineTicketEntity
import dev.bsolutions.bsloteria.data.local.entity.TicketDetailEntity
import dev.bsolutions.bsloteria.data.local.entity.TicketEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface TicketDao {

    @Query("SELECT * FROM tickets ORDER BY soldAt DESC LIMIT 100")
    fun observeRecent(): Flow<List<TicketEntity>>

    @Query("SELECT * FROM tickets WHERE id = :id")
    suspend fun findById(id: Long): TicketEntity?

    @Query("SELECT * FROM tickets WHERE uuid = :uuid")
    suspend fun findByUuid(uuid: String): TicketEntity?

    @Query("SELECT * FROM ticket_details WHERE ticketId = :ticketId")
    suspend fun findDetailsByTicketId(ticketId: Long): List<TicketDetailEntity>

    /** Observa los detalles de todos los tickets cacheados (para mostrar preview de jugadas en la lista). */
    @Query("SELECT * FROM ticket_details")
    fun observeAllDetails(): Flow<List<TicketDetailEntity>>

    @Query("SELECT * FROM offline_tickets ORDER BY createdAt DESC LIMIT 100")
    fun observeAllOffline(): Flow<List<OfflineTicketEntity>>

    @Query("SELECT * FROM offline_tickets WHERE uuid = :uuid")
    suspend fun findOfflineByUuid(uuid: String): OfflineTicketEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(tickets: List<TicketEntity>)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertDetails(details: List<TicketDetailEntity>)

    // Offline tickets
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertOffline(ticket: OfflineTicketEntity)

    @Query("SELECT * FROM offline_tickets WHERE syncStatus = 'PENDING' OR syncStatus = 'FAILED' ORDER BY createdAt ASC LIMIT 50")
    suspend fun getPendingOffline(): List<OfflineTicketEntity>

    @Query("UPDATE offline_tickets SET syncStatus = :status, syncAttempts = syncAttempts + 1, lastSyncError = :error WHERE uuid = :uuid")
    suspend fun updateSyncStatus(uuid: String, status: String, error: String? = null)

    @Query("SELECT COUNT(*) FROM offline_tickets WHERE syncStatus = 'PENDING'")
    fun observePendingCount(): Flow<Int>

    @Query("DELETE FROM offline_tickets WHERE syncStatus = 'SYNCED' AND createdAt < :before")
    suspend fun deleteSyncedBefore(before: Long)
}
