package dev.bsolutions.bsloteria.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class LoginRequest(
    val login: String,
    val password: String,
    @Json(name = "device_name") val deviceName: String,
    @Json(name = "device_uuid") val deviceUuid: String,
    val platform: String = "ANDROID",
    @Json(name = "app_version") val appVersion: String,
    @Json(name = "device_fingerprint") val deviceFingerprint: String = deviceUuid
)

@JsonClass(generateAdapter = true)
data class LoginResponse(
    val token: String,
    val user: UserDto,
    val branch: BranchDto?,
    val company: CompanyDto?,
    @Json(name = "device_status") val deviceStatus: String? = null
)

@JsonClass(generateAdapter = true)
data class UserDto(
    val id: Long,
    val name: String,
    val email: String,
    val roles: List<String> = emptyList(),
    val permissions: List<String> = emptyList()
)

@JsonClass(generateAdapter = true)
data class BranchDto(
    val id: Long,
    val name: String,
    // Algunos endpoints (cash/status) omiten company_id porque ya está
    // implícito en el token. Default 0 para que Moshi no falle.
    @Json(name = "company_id") val companyId: Long = 0L
)

@JsonClass(generateAdapter = true)
data class CompanyDto(
    val id: Long,
    val name: String
)

@JsonClass(generateAdapter = true)
data class DrawDto(
    val id: Long,
    @Json(name = "lottery_id") val lotteryId: Long,
    @Json(name = "lottery_name") val lotteryName: String,
    val name: String,
    @Json(name = "draw_date") val drawDate: String,
    @Json(name = "draw_time") val drawTime: String,
    val status: String,
    @Json(name = "cutoff_time") val cutoffTime: String?
)

@JsonClass(generateAdapter = true)
data class BetTypeDto(
    val id: Long,
    val name: String,
    val code: String,
    val multiplier: String,
    @Json(name = "lottery_id") val lotteryId: Long?
)

@JsonClass(generateAdapter = true)
data class SyncDataResponse(
    val draws: List<DrawDto> = emptyList(),
    @Json(name = "bet_types") val betTypes: List<BetTypeDto> = emptyList(),
    @Json(name = "server_time") val serverTime: String
)

@JsonClass(generateAdapter = true)
data class OfflineTicketRequest(
    val uuid: String,
    @Json(name = "draw_id") val drawId: Long,
    val details: List<OfflineDetailRequest>,
    @Json(name = "sold_at") val soldAt: String,
    @Json(name = "offline") val offline: Boolean = true
)

@JsonClass(generateAdapter = true)
data class OfflineDetailRequest(
    @Json(name = "number_value") val numberValue: String,
    @Json(name = "bet_type_id") val betTypeId: Long,
    val amount: String
)

@JsonClass(generateAdapter = true)
data class TicketResponse(
    val id: Long,
    val uuid: String,
    @Json(name = "ticket_number") val ticketNumber: String? = null,
    @Json(name = "total_amount") val totalAmount: String,
    val status: String,
    @Json(name = "sold_at") val soldAt: String,
    @Json(name = "draw_id") val drawId: Long? = null,
    @Json(name = "draw_name") val drawName: String? = null,
    @Json(name = "lottery_name") val lotteryName: String? = null,
    val details: List<TicketDetailResponse> = emptyList()
)

@JsonClass(generateAdapter = true)
data class TicketDetailResponse(
    val id: Long,
    @Json(name = "number_value") val numberValue: String,
    @Json(name = "bet_type_id") val betTypeId: Long? = null,
    @Json(name = "bet_type_name") val betTypeName: String,
    val amount: String,
    @Json(name = "potential_prize") val potentialPrize: String?
)

@JsonClass(generateAdapter = true)
data class SyncBatchRequest(
    val tickets: List<OfflineTicketRequest>
)

@JsonClass(generateAdapter = true)
data class SyncBatchResponse(
    val synced: List<String> = emptyList(),
    val failed: List<SyncFailure> = emptyList()
)

@JsonClass(generateAdapter = true)
data class SyncFailure(
    val uuid: String,
    val reason: String
)

@JsonClass(generateAdapter = true)
data class OpenOfflineSessionRequest(
    @Json(name = "branch_id") val branchId: Long,
    @Json(name = "ticket_limit") val ticketLimit: Int = 100,
    @Json(name = "amount_limit") val amountLimit: String? = null,
    @Json(name = "expires_hours") val expiresHours: Int = 8
)

@JsonClass(generateAdapter = true)
data class OpenOfflineSessionResponse(
    val session: OfflineSessionDto,
    val bootstrap: OfflineBootstrapResponse
)

@JsonClass(generateAdapter = true)
data class OfflineBootstrapResponse(
    val session: OfflineSessionDto,
    val allocations: List<OfflineAllocationDto> = emptyList(),
    @Json(name = "generated_at") val generatedAt: String? = null
)

@JsonClass(generateAdapter = true)
data class OfflineSessionDto(
    val uuid: String,
    val status: String,
    @Json(name = "expires_at") val expiresAt: String?,
    @Json(name = "allocated_tickets_limit") val allocatedTicketsLimit: Int = 0,
    @Json(name = "used_tickets_count") val usedTicketsCount: Int = 0,
    @Json(name = "allocated_amount") val allocatedAmount: String? = null
)

@JsonClass(generateAdapter = true)
data class OfflineAllocationDto(
    val id: Long,
    @Json(name = "lottery_id") val lotteryId: Long? = null,
    @Json(name = "draw_id") val drawId: Long? = null,
    @Json(name = "bet_type_id") val betTypeId: Long? = null,
    @Json(name = "number_value") val numberValue: String? = null,
    @Json(name = "allocated_amount") val allocatedAmount: String,
    @Json(name = "used_amount") val usedAmount: String
)

@JsonClass(generateAdapter = true)
data class OfflineSyncRequest(
    @Json(name = "session_uuid") val sessionUuid: String,
    val tickets: List<OfflineSyncTicketRequest>
)

@JsonClass(generateAdapter = true)
data class OfflineSyncTicketRequest(
    val uuid: String,
    @Json(name = "draw_id") val drawId: Long,
    val plays: List<OfflineDetailRequest>,
    @Json(name = "sold_at") val soldAt: String
)

@JsonClass(generateAdapter = true)
data class OfflineSyncResponse(
    @Json(name = "batch_id") val batchId: Long,
    val status: String,
    @Json(name = "accepted_tickets") val acceptedTickets: Int = 0,
    @Json(name = "rejected_tickets") val rejectedTickets: Int = 0,
    @Json(name = "accepted_uuids") val acceptedUuids: List<String> = emptyList(),
    val failed: List<SyncFailure> = emptyList()
)

@JsonClass(generateAdapter = true)
data class ApiError(
    val message: String,
    val code: String? = null,
    val errors: Map<String, List<String>> = emptyMap()
)

// ── Caja ─────────────────────────────────────────────────────────────────────

@JsonClass(generateAdapter = true)
data class CashStatusResponse(
    @Json(name = "cash_control_enabled") val cashControlEnabled: Boolean,
    val branch: BranchDto? = null,
    val session: CashSessionDto? = null,
    val movements: List<CashMovementDto> = emptyList(),
    @Json(name = "server_time") val serverTime: String? = null,
    val message: String? = null
)

@JsonClass(generateAdapter = true)
data class CashMovementDto(
    val id: Long,
    val type: String,
    val direction: String,
    val amount: String,
    @Json(name = "payment_method") val paymentMethod: String = "CASH",
    val description: String? = null,
    @Json(name = "reference_type") val referenceType: String? = null,
    @Json(name = "reference_id") val referenceId: Long? = null,
    @Json(name = "created_at") val createdAt: String? = null,
)

@JsonClass(generateAdapter = true)
data class CashSessionDto(
    val id: Long,
    val uuid: String,
    val status: String,
    @Json(name = "opening_amount") val openingAmount: String,
    @Json(name = "expected_cash") val expectedCash: String,
    @Json(name = "counted_cash") val countedCash: String? = null,
    @Json(name = "sales_total") val salesTotal: String = "0.00",
    @Json(name = "cancellations_total") val cancellationsTotal: String = "0.00",
    @Json(name = "prizes_paid_total") val prizesPaidTotal: String = "0.00",
    @Json(name = "cash_in_total") val cashInTotal: String = "0.00",
    @Json(name = "cash_out_total") val cashOutTotal: String = "0.00",
    @Json(name = "expenses_total") val expensesTotal: String = "0.00",
    @Json(name = "shortage_amount") val shortageAmount: String = "0.00",
    @Json(name = "surplus_amount") val surplusAmount: String = "0.00",
    @Json(name = "opened_at") val openedAt: String? = null,
    @Json(name = "closed_at") val closedAt: String? = null,
    val notes: String? = null,
)

@JsonClass(generateAdapter = true)
data class CashOpenRequest(
    @Json(name = "opening_amount") val openingAmount: String,
    val notes: String? = null,
)

@JsonClass(generateAdapter = true)
data class CashCloseRequest(
    @Json(name = "counted_cash") val countedCash: String? = null,
    val denominations: Map<String, Int>? = null,
    val notes: String? = null,
)

@JsonClass(generateAdapter = true)
data class CashSessionResponse(
    val session: CashSessionDto
)

// ── Premios ──────────────────────────────────────────────────────────────────

@JsonClass(generateAdapter = true)
data class WinnerDto(
    val id: Long,
    @Json(name = "number_value") val numberValue: String,
    @Json(name = "matched_position") val matchedPosition: String? = null,
    @Json(name = "amount_played") val amountPlayed: String,
    @Json(name = "payout_multiplier") val payoutMultiplier: String,
    @Json(name = "prize_amount") val prizeAmount: String,
    val status: String,
    @Json(name = "paid_at") val paidAt: String? = null,
    @Json(name = "bet_type_name") val betTypeName: String = "",
    @Json(name = "draw_name") val drawName: String = "",
    @Json(name = "lottery_name") val lotteryName: String = "",
)

@JsonClass(generateAdapter = true)
data class TicketWinnersResponse(
    val ticket: TicketSummaryDto,
    val winners: List<WinnerDto> = emptyList(),
    @Json(name = "total_released") val totalReleased: String = "0.00",
    @Json(name = "has_releasable_prizes") val hasReleasablePrizes: Boolean = false,
)

@JsonClass(generateAdapter = true)
data class TicketSummaryDto(
    val uuid: String,
    @Json(name = "ticket_number") val ticketNumber: String? = null,
    val status: String,
    @Json(name = "paid_at") val paidAt: String? = null,
)

@JsonClass(generateAdapter = true)
data class PrizePaymentEntry(
    val id: Long,
    @Json(name = "winner_ticket_id") val winnerTicketId: Long,
    val amount: String,
    @Json(name = "paid_at") val paidAt: String? = null,
)

@JsonClass(generateAdapter = true)
data class PayPrizeResponse(
    @Json(name = "ticket_uuid") val ticketUuid: String,
    @Json(name = "payments_count") val paymentsCount: Int,
    @Json(name = "total_paid") val totalPaid: String,
    val payments: List<PrizePaymentEntry> = emptyList(),
)

@JsonClass(generateAdapter = true)
data class CheckLimitResponse(
    /** Monto disponible para ese numero. null = sin reglas (sin limite). */
    val available: Double?,
    /** Tope absoluto por jugada de ese bet type (max_amount). null = sin tope. */
    @Json(name = "max_bet") val maxBet: Double?,
    /** true si available == 0 (numero agotado). */
    val blocked: Boolean,
)
