package dev.bsolutions.bsloteria.domain.model

import java.math.BigDecimal
import java.math.RoundingMode

enum class DenominationKind { BILL, COIN }

data class Denomination(
    val key: String,
    val label: String,
    val value: BigDecimal,
    val kind: DenominationKind,
)

// El orden y los keys deben coincidir con CashService::denominationMap() del backend.
object Denominations {
    val ALL: List<Denomination> = listOf(
        Denomination("bill_2000", "RD$ 2,000", BigDecimal("2000.00"), DenominationKind.BILL),
        Denomination("bill_1000", "RD$ 1,000", BigDecimal("1000.00"), DenominationKind.BILL),
        Denomination("bill_500", "RD$ 500", BigDecimal("500.00"), DenominationKind.BILL),
        Denomination("bill_200", "RD$ 200", BigDecimal("200.00"), DenominationKind.BILL),
        Denomination("bill_100", "RD$ 100", BigDecimal("100.00"), DenominationKind.BILL),
        Denomination("bill_50", "RD$ 50", BigDecimal("50.00"), DenominationKind.BILL),
        Denomination("coin_25", "RD$ 25", BigDecimal("25.00"), DenominationKind.COIN),
        Denomination("coin_10", "RD$ 10", BigDecimal("10.00"), DenominationKind.COIN),
        Denomination("coin_5", "RD$ 5", BigDecimal("5.00"), DenominationKind.COIN),
        Denomination("coin_1", "RD$ 1", BigDecimal("1.00"), DenominationKind.COIN),
    )

    val BILLS: List<Denomination> = ALL.filter { it.kind == DenominationKind.BILL }
    val COINS: List<Denomination> = ALL.filter { it.kind == DenominationKind.COIN }

    fun total(quantities: Map<String, Int>): BigDecimal {
        var total = BigDecimal.ZERO
        ALL.forEach { d ->
            val qty = quantities[d.key] ?: 0
            if (qty > 0) total = total + d.value.multiply(BigDecimal(qty))
        }
        return total.setScale(2, RoundingMode.HALF_UP)
    }

    fun emptyMap(): Map<String, Int> = ALL.associate { it.key to 0 }
}
