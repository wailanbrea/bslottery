package dev.bsolutions.bsloteria.ui.navigation

object Routes {
    const val LOGIN = "login"
    const val DASHBOARD = "dashboard"
    const val SALE = "sale"
    const val TICKETS = "tickets"
    const val TICKET_DETAIL = "tickets/{uuid}"
    const val SYNC = "sync"
    const val SETTINGS = "settings"
    const val CASH = "cash"
    const val SCAN_QR = "scan_qr"
    const val STARTUP_SYNC = "startup_sync"

    const val SCAN_RESULT_KEY = "scanned_qr_token"
}

sealed class Screen(val route: String) {
    object Login : Screen(Routes.LOGIN)
    object Dashboard : Screen(Routes.DASHBOARD)
    object Sale : Screen(Routes.SALE)
    object Tickets : Screen(Routes.TICKETS)
    object Sync : Screen(Routes.SYNC)
    object Settings : Screen(Routes.SETTINGS)
    object Cash : Screen(Routes.CASH)
    object ScanQr : Screen(Routes.SCAN_QR)
    object StartupSync : Screen(Routes.STARTUP_SYNC)
    object TicketDetail : Screen(Routes.TICKET_DETAIL) {
        fun createRoute(uuid: String) = "tickets/$uuid"
    }
}
