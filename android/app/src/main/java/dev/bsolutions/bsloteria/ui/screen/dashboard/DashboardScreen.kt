package dev.bsolutions.bsloteria.ui.screen.dashboard

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import dev.bsolutions.bsloteria.ui.navigation.Screen

private data class DashboardItem(
    val title: String,
    val icon: ImageVector,
    val route: String,
    val badge: Int = 0,
    val color: @Composable () -> androidx.compose.ui.graphics.Color = { MaterialTheme.colorScheme.primary }
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(
    viewModel: DashboardViewModel,
    onNavigate: (String) -> Unit,
    onLogout: () -> Unit
) {
    val session by viewModel.session.collectAsState()
    val pendingCount by viewModel.pendingCount.collectAsState()
    var showLogoutDialog by remember { mutableStateOf(false) }

    val items = listOf(
        DashboardItem("Caja", Icons.Default.AccountBalanceWallet, Screen.Cash.route,
            color = { MaterialTheme.colorScheme.tertiary }),
        DashboardItem("Venta rápida", Icons.Default.ConfirmationNumber, Screen.Sale.route,
            color = { MaterialTheme.colorScheme.primary }),
        DashboardItem("Historial tickets", Icons.Default.History, Screen.Tickets.route),
        DashboardItem("Sincronización", Icons.Default.Sync, Screen.Sync.route, badge = pendingCount,
            color = { if (pendingCount > 0) MaterialTheme.colorScheme.tertiary else MaterialTheme.colorScheme.secondary }),
        DashboardItem("Configuración", Icons.Default.Settings, Screen.Settings.route),
    )

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("BSLottery", fontWeight = FontWeight.Bold)
                        session?.let {
                            Text(
                                "${it.branchName} · ${it.userName}",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onPrimary.copy(alpha = 0.85f)
                            )
                        }
                    }
                },
                actions = {
                    IconButton(onClick = { showLogoutDialog = true }) {
                        Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = "Cerrar sesión")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    actionIconContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { padding ->
        LazyVerticalGrid(
            columns = GridCells.Fixed(2),
            contentPadding = PaddingValues(16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
            modifier = Modifier.fillMaxSize().padding(padding)
        ) {
            items(items) { item ->
                DashboardCard(item = item, onClick = { onNavigate(item.route) })
            }
        }
    }

    if (showLogoutDialog) {
        AlertDialog(
            onDismissRequest = { showLogoutDialog = false },
            title = { Text("Cerrar sesión") },
            text = { Text("¿Seguro que desea cerrar sesión?") },
            confirmButton = {
                TextButton(onClick = { viewModel.logout(onLogout) }) { Text("Sí") }
            },
            dismissButton = {
                TextButton(onClick = { showLogoutDialog = false }) { Text("No") }
            }
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun DashboardCard(item: DashboardItem, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth().aspectRatio(1f),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Box(Modifier.fillMaxSize()) {
            Column(
                modifier = Modifier.fillMaxSize().padding(16.dp),
                verticalArrangement = Arrangement.Center,
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Icon(
                    item.icon,
                    contentDescription = item.title,
                    modifier = Modifier.size(40.dp),
                    tint = item.color()
                )
                Spacer(Modifier.height(8.dp))
                Text(item.title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Medium)
            }
            if (item.badge > 0) {
                Badge(
                    modifier = Modifier.align(Alignment.TopEnd).padding(8.dp),
                    containerColor = MaterialTheme.colorScheme.error
                ) {
                    Text(item.badge.toString())
                }
            }
        }
    }
}
