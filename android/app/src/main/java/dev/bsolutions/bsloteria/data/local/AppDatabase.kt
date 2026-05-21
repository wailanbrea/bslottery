package dev.bsolutions.bsloteria.data.local

import androidx.room.Database
import androidx.room.RoomDatabase
import dev.bsolutions.bsloteria.data.local.dao.BetTypeDao
import dev.bsolutions.bsloteria.data.local.dao.DrawDao
import dev.bsolutions.bsloteria.data.local.dao.OfflineSessionDao
import dev.bsolutions.bsloteria.data.local.dao.TicketDao
import dev.bsolutions.bsloteria.data.local.entity.*

@Database(
    entities = [
        DrawEntity::class,
        TicketEntity::class,
        TicketDetailEntity::class,
        OfflineTicketEntity::class,
        BetTypeEntity::class,
        OfflineSessionEntity::class,
        OfflineAllocationEntity::class,
    ],
    version = 3,
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    abstract fun drawDao(): DrawDao
    abstract fun ticketDao(): TicketDao
    abstract fun betTypeDao(): BetTypeDao
    abstract fun offlineSessionDao(): OfflineSessionDao

    companion object {
        const val NAME = "bsloteria.db"
    }
}
