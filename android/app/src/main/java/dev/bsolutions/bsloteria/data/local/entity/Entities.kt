package dev.bsolutions.bsloteria.data.local.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "draws")
data class DrawEntity(
    @PrimaryKey val id: Long,
    val lotteryId: Long,
    val lotteryName: String,
    val name: String,
    val drawDate: String,
    val drawTime: String,
    val status: String,
    val cutoffTime: String?,
    val syncedAt: Long = System.currentTimeMillis()
)

@Entity(tableName = "tickets")
data class TicketEntity(
    @PrimaryKey val id: Long,
    val uuid: String,
    val ticketNumber: String? = null,
    val drawId: Long,
    val drawName: String,
    val lotteryName: String,
    val totalAmount: String,
    val status: String,
    val soldAt: String,
    val soldAtEpoch: Long = 0L,
    val branchId: Long,
    val userId: Long,
    val isSyncPending: Boolean = false,
    val syncAttempts: Int = 0,
    val createdLocallyAt: Long = System.currentTimeMillis()
)

@Entity(tableName = "ticket_details")
data class TicketDetailEntity(
    @PrimaryKey val id: Long,
    val ticketId: Long,
    val ticketUuid: String,
    val numberValue: String,
    val betTypeId: Long,
    val betTypeName: String,
    val amount: String,
    val potentialPrize: String?
)

@Entity(tableName = "offline_tickets")
data class OfflineTicketEntity(
    @PrimaryKey val uuid: String,
    val offlineSessionUuid: String? = null,
    val drawId: Long,
    val drawName: String,
    val lotteryName: String,
    val detailsJson: String,
    val totalAmount: String,
    val branchId: Long,
    val userId: Long,
    val createdAt: Long = System.currentTimeMillis(),
    val syncStatus: String = "PENDING",
    val syncAttempts: Int = 0,
    val lastSyncError: String? = null
)

@Entity(tableName = "bet_types")
data class BetTypeEntity(
    @PrimaryKey val id: Long,
    val name: String,
    val code: String,
    val multiplier: String,
    val lotteryId: Long?
)

@Entity(tableName = "offline_sessions")
data class OfflineSessionEntity(
    @PrimaryKey val uuid: String,
    val status: String,
    val branchId: Long,
    val expiresAt: String?,
    val allocatedTicketsLimit: Int,
    val usedTicketsCount: Int,
    val allocatedAmount: String?,
    val updatedAt: Long = System.currentTimeMillis()
)

@Entity(tableName = "offline_allocations")
data class OfflineAllocationEntity(
    @PrimaryKey val localKey: String,
    val offlineSessionUuid: String,
    val remoteId: Long?,
    val lotteryId: Long?,
    val drawId: Long?,
    val betTypeId: Long?,
    val numberValue: String?,
    val allocatedAmount: String,
    val usedAmount: String
)
