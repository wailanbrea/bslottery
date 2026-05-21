package dev.bsolutions.bsloteria.data.local.dao

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import dev.bsolutions.bsloteria.data.local.entity.OfflineAllocationEntity
import dev.bsolutions.bsloteria.data.local.entity.OfflineSessionEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface OfflineSessionDao {
    @Query("SELECT * FROM offline_sessions WHERE status = 'ACTIVE' ORDER BY updatedAt DESC LIMIT 1")
    fun observeActiveSession(): Flow<OfflineSessionEntity?>

    @Query("SELECT * FROM offline_sessions WHERE status = 'ACTIVE' ORDER BY updatedAt DESC LIMIT 1")
    suspend fun findActiveSession(): OfflineSessionEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertSession(session: OfflineSessionEntity)

    @Query("UPDATE offline_sessions SET usedTicketsCount = usedTicketsCount + 1, updatedAt = :updatedAt WHERE uuid = :uuid")
    suspend fun incrementTicketUsage(uuid: String, updatedAt: Long = System.currentTimeMillis())

    @Query("UPDATE offline_sessions SET status = :status, updatedAt = :updatedAt WHERE uuid = :uuid")
    suspend fun updateSessionStatus(uuid: String, status: String, updatedAt: Long = System.currentTimeMillis())

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAllocations(allocations: List<OfflineAllocationEntity>)

    @Query("DELETE FROM offline_allocations WHERE offlineSessionUuid = :sessionUuid")
    suspend fun deleteAllocationsForSession(sessionUuid: String)

    @Query(
        """
        SELECT * FROM offline_allocations
        WHERE offlineSessionUuid = :sessionUuid
          AND lotteryId IS NULL
          AND drawId IS NULL
          AND betTypeId IS NULL
          AND numberValue IS NULL
        LIMIT 1
        """
    )
    suspend fun findGenericAllocation(sessionUuid: String): OfflineAllocationEntity?

    @Query("UPDATE offline_allocations SET usedAmount = :usedAmount WHERE localKey = :localKey")
    suspend fun updateAllocationUsedAmount(localKey: String, usedAmount: String)
}
