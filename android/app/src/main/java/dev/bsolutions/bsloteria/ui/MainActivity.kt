package dev.bsolutions.bsloteria.ui

import android.os.Bundle
import androidx.appcompat.app.AppCompatDelegate
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.runtime.*
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.WorkManager
import dagger.hilt.android.AndroidEntryPoint
import dev.bsolutions.bsloteria.ui.navigation.Screen
import dev.bsolutions.bsloteria.ui.screen.cash.CashScreen
import dev.bsolutions.bsloteria.ui.screen.dashboard.DashboardScreen
import dev.bsolutions.bsloteria.ui.screen.login.LoginScreen
import dev.bsolutions.bsloteria.ui.screen.scanner.QrScannerScreen
import dev.bsolutions.bsloteria.ui.screen.startup.StartupSyncScreen
import dev.bsolutions.bsloteria.ui.navigation.Routes
import dev.bsolutions.bsloteria.ui.screen.login.LoginViewModel
import dev.bsolutions.bsloteria.ui.screen.sale.SaleScreen
import dev.bsolutions.bsloteria.ui.screen.settings.SettingsScreen
import dev.bsolutions.bsloteria.ui.screen.sync.SyncScreen
import dev.bsolutions.bsloteria.ui.screen.tickets.TicketDetailScreen
import dev.bsolutions.bsloteria.ui.screen.tickets.TicketsScreen
import dev.bsolutions.bsloteria.ui.theme.BSLoteriaTheme
import dev.bsolutions.bsloteria.worker.SyncWorker

@AndroidEntryPoint
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        // Forzar siempre modo claro sin importar la preferencia del sistema
        AppCompatDelegate.setDefaultNightMode(AppCompatDelegate.MODE_NIGHT_NO)
        enableEdgeToEdge()

        WorkManager.getInstance(this).enqueueUniquePeriodicWork(
            SyncWorker.WORK_NAME,
            ExistingPeriodicWorkPolicy.KEEP,
            SyncWorker.periodicRequest()
        )

        setContent {
            BSLoteriaTheme {
                BSLoteriaNavHost()
            }
        }
    }
}

@Composable
fun BSLoteriaNavHost() {
    val navController = rememberNavController()
    val loginVm: LoginViewModel = hiltViewModel()
    val isLoggedIn by loginVm.isLoggedIn.collectAsState()

    // Si ya está logueado al abrir la app, también pasamos por StartupSync para
    // alinear el estado con el sistema antes de mostrar el dashboard.
    val startDestination = if (isLoggedIn) Screen.StartupSync.route else Screen.Login.route

    NavHost(navController = navController, startDestination = startDestination) {
        composable(Screen.Login.route) {
            LoginScreen(
                viewModel = hiltViewModel(),
                onLoginSuccess = {
                    navController.navigate(Screen.StartupSync.route) {
                        popUpTo(Screen.Login.route) { inclusive = true }
                    }
                }
            )
        }
        composable(Screen.StartupSync.route) {
            StartupSyncScreen(
                viewModel = hiltViewModel(),
                onContinue = {
                    navController.navigate(Screen.Dashboard.route) {
                        popUpTo(Screen.StartupSync.route) { inclusive = true }
                    }
                },
                onForceLogout = {
                    navController.navigate(Screen.Login.route) {
                        popUpTo(0) { inclusive = true }
                    }
                }
            )
        }
        composable(Screen.Dashboard.route) {
            DashboardScreen(
                viewModel = hiltViewModel(),
                onNavigate = { route -> navController.navigate(route) },
                onLogout = {
                    navController.navigate(Screen.Login.route) {
                        popUpTo(0) { inclusive = true }
                    }
                }
            )
        }
        composable(Screen.Sale.route) {
            SaleScreen(
                viewModel = hiltViewModel(),
                onBack = { navController.popBackStack() }
            )
        }
        composable(Screen.Tickets.route) { backStackEntry ->
            TicketsScreen(
                viewModel = hiltViewModel(),
                onBack = { navController.popBackStack() },
                onTicketClick = { uuid ->
                    navController.navigate(Screen.TicketDetail.createRoute(uuid))
                },
                onScanQr = { navController.navigate(Screen.ScanQr.route) },
                scannedToken = backStackEntry.savedStateHandle
                    .getStateFlow<String?>(Routes.SCAN_RESULT_KEY, null)
                    .collectAsState().value,
                onScannedTokenConsumed = {
                    backStackEntry.savedStateHandle[Routes.SCAN_RESULT_KEY] = null
                }
            )
        }
        composable(Screen.TicketDetail.route) {
            TicketDetailScreen(
                viewModel = hiltViewModel(),
                onBack = { navController.popBackStack() }
            )
        }
        composable(Screen.Sync.route) {
            SyncScreen(
                viewModel = hiltViewModel(),
                onBack = { navController.popBackStack() }
            )
        }
        composable(Screen.Settings.route) {
            SettingsScreen(
                viewModel = hiltViewModel(),
                onBack = { navController.popBackStack() },
                onLogout = {
                    navController.navigate(Screen.Login.route) {
                        popUpTo(0) { inclusive = true }
                    }
                }
            )
        }
        composable(Screen.Cash.route) {
            CashScreen(
                viewModel = hiltViewModel(),
                onBack = { navController.popBackStack() }
            )
        }
        composable(Screen.ScanQr.route) {
            QrScannerScreen(
                onBack = { navController.popBackStack() },
                onScanned = { token ->
                    navController.previousBackStackEntry
                        ?.savedStateHandle
                        ?.set(Routes.SCAN_RESULT_KEY, token)
                    navController.popBackStack()
                }
            )
        }
    }
}
