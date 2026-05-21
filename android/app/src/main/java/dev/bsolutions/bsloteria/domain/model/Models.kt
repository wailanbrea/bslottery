package dev.bsolutions.bsloteria.domain.model

data class SaleDetail(
    val numberValue: String,
    val betTypeId: Long,
    val betTypeName: String,
    val amount: String
)

data class Draw(
    val id: Long,
    val lotteryName: String,
    val name: String,
    val drawDate: String,
    val drawTime: String,
    val status: String,
    val cutoffTime: String?
)

data class BetType(
    val id: Long,
    val name: String,
    val code: String,
    val multiplier: String
)
