package dev.bsolutions.bsloteria.data.local.dao

import androidx.room.*
import dev.bsolutions.bsloteria.data.local.entity.BetTypeEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface BetTypeDao {
    @Query("SELECT * FROM bet_types ORDER BY name ASC")
    fun observeAll(): Flow<List<BetTypeEntity>>

    @Query("SELECT * FROM bet_types")
    suspend fun findAll(): List<BetTypeEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(betTypes: List<BetTypeEntity>)
}
