package dev.bsolutions.bsloteria.data.local.dao

import androidx.room.*
import dev.bsolutions.bsloteria.data.local.entity.DrawEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface DrawDao {
    @Query("SELECT * FROM draws WHERE status = 'OPEN' ORDER BY drawTime ASC")
    fun observeOpenDraws(): Flow<List<DrawEntity>>

    @Query("SELECT * FROM draws WHERE id = :id")
    suspend fun findById(id: Long): DrawEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(draws: List<DrawEntity>)

    @Query("DELETE FROM draws WHERE syncedAt < :before")
    suspend fun deleteStaleDraws(before: Long)
}
