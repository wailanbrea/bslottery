package dev.bsolutions.bsloteria.ui.screen.dashboard

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.RoundedCornerShape
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
    onLogout: () -> Unit,
    scannedToken: String? = null,
    onScannedTokenConsumed: () -> Unit = {}
) {
    val session by viewModel.session.collectAsState()
    val pendingCount by viewModel.pendingCount.collectAsState()
    val lookupLoading by viewModel.lookupLoading.collectAsState()
    val lookupError by viewModel.lookupError.collectAsState()
    var showLogoutDialog by remember { mutableStateOf(false) }
    var showRevisarDialog by remember { mutableStateOf(false) }

    LaunchedEffect(scannedToken) {
        val token = scannedToken
        if (!token.isNullOrBlank()) {
            onScannedTokenConsumed()
            showRevisarDialog = true
            viewModel.lookupTicket(token) { uuid ->
                showRevisarDialog = false
                onNavigate(Screen.TicketDetail.createRoute(uuid))
            }
        }
    }

    val items = listOf(
        DashboardItem("Caja", Icons.Default.AccountBalanceWallet, Screen.Cash.route,
            color = { MaterialTheme.colorScheme.tertiary }),
        DashboardItem("Venta rápida", Icons.Default.ConfirmationNumber, Screen.Sale.route,
            color = { MaterialTheme.colorScheme.primary }),
        DashboardItem("Historial tickets", Icons.Default.History, Screen.Tickets.route),
        DashboardItem("Sincronización", Icons.Default.Sync, Screen.Sync.route, badge = pendingCount,
            color = { if (pendingCount > 0) MaterialTheme.colorScheme.tertiary else MaterialTheme.colorScheme.secondary }),
        DashboardItem("Revisar o Pagar", Icons.Default.QrCodeScanner, "revisar_pagar",
            color = { MaterialTheme.colorScheme.primary }),
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
                DashboardCard(
                    item = item,
                    onClick = {
                        if (item.route == "revisar_pagar") {
                            showRevisarDialog = true
                        } else {
                            onNavigate(item.route)
                        }
                    }
                )
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

    if (showRevisarDialog) {
        var ticketQuery by remember { mutableStateOf("") }
        AlertDialog(
            onDismissRequest = {
                if (!lookupLoading) {
                    showRevisarDialog = false
                    viewModel.clearLookupError()
                }
            },
            title = {
                Text(
                    text = "Revisar o Pagar Ticket",
                    fontWeight = FontWeight.Bold,
                    style = MaterialTheme.typography.titleLarge
                )
            },
            text = {
                Column(
                    verticalArrangement = Arrangement.spacedBy(12.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(
                        text = "Ingrese el número de ticket o use el escáner QR.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        OutlinedTextField(
                            value = ticketQuery,
                            onValueChange = {
                                ticketQuery = it
                                viewModel.clearLookupError()
                            },
                            label = { Text("Número de ticket") },
                            placeholder = { Text("Ej: MOB01-260520-0001") },
                            singleLine = true,
                            enabled = !lookupLoading,
                            modifier = Modifier.weight(1f),
                            leadingIcon = {
                                Icon(Icons.Default.ConfirmationNumber, contentDescription = null)
                            }
                        )
                        
                        IconButton(
                            onClick = {
                                viewModel.clearLookupError()
                                onNavigate(Screen.ScanQr.route)
                            },
                            enabled = !lookupLoading,
                            modifier = Modifier
                                .size(52.dp)
                                .background(
                                    color = MaterialTheme.colorScheme.primaryContainer,
                                    shape = RoundedCornerShape(12.dp)
                                )
                        ) {
                            Icon(
                                imageVector = Icons.Default.QrCodeScanner,
                                contentDescription = "Escanear QR",
                                tint = MaterialTheme.colorScheme.onPrimaryContainer,
                                modifier = Modifier.size(24.dp)
                            )
                        }
                    }
                    
                    if (lookupLoading) {
                        Box(
                            modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(24.dp),
                                strokeWidth = 2.5.dp
                            )
                        }
                    }
                    
                    lookupError?.let { error ->
                        Text(
                            text = error,
                            color = MaterialTheme.colorScheme.error,
                            style = MaterialTheme.typography.bodySmall,
                            fontWeight = FontWeight.Medium,
                            modifier = Modifier.padding(horizontal = 4.dp)
                        )
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.lookupTicket(ticketQuery) { uuid ->
                            showRevisarDialog = false
                            viewModel.clearLookupError()
                            onNavigate(Screen.TicketDetail.createRoute(uuid))
                        }
                    },
                    enabled = !lookupLoading && ticketQuery.isNotBlank()
                ) {
                    Text("Buscar")
                }
            },
            dismissButton = {
                TextButton(
                    onClick = {
                        showRevisarDialog = false
                        viewModel.clearLookupError()
                    },
                    enabled = !lookupLoading
                ) {
                    Text("Cancelar")
                }
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
