package dev.bsolutions.bsloteria.util

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.*
import androidx.datastore.preferences.preferencesDataStore
import dagger.hilt.android.qualifiers.ApplicationContext
import dev.bsolutions.bsloteria.BuildConfig
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.map
import java.util.UUID
import javax.inject.Inject
import javax.inject.Singleton

val Context.dataStore: DataStore<Preferences> by preferencesDataStore(name = "session")

@Singleton
class SessionStore @Inject constructor(@ApplicationContext private val context: Context) {

    private val TOKEN = stringPreferencesKey("auth_token")
    private val USER_ID = longPreferencesKey("user_id")
    private val USER_NAME = stringPreferencesKey("user_name")
    private val BRANCH_ID = longPreferencesKey("branch_id")
    private val BRANCH_NAME = stringPreferencesKey("branch_name")
    private val COMPANY_ID = longPreferencesKey("company_id")
    private val SERVER_URL = stringPreferencesKey("server_url")
    private val PERMISSIONS = stringPreferencesKey("permissions")
    private val LAST_SYNC = longPreferencesKey("last_sync_ts")
    private val DEVICE_UUID = stringPreferencesKey("device_uuid")
    private val PRINTER_ADDRESS = stringPreferencesKey("printer_address")
    private val PRINTER_NAME = stringPreferencesKey("printer_name")
    private val AUTO_PRINT = booleanPreferencesKey("auto_print")

    val tokenFlow: Flow<String?> = context.dataStore.data.map { it[TOKEN] }

    /**
     * Override de URL persistido por el admin desde SettingsScreen. Si es null/blank,
     * la app usa [BuildConfig.SERVER_URL]. NUNCA se setea desde login — solo desde Settings.
     */
    val serverUrlFlow: Flow<String?> = context.dataStore.data.map { prefs ->
        if (BuildConfig.ALLOW_SERVER_OVERRIDE) prefs[SERVER_URL] else null
    }

    /**
     * URL efectiva a usar para todas las llamadas. Override de Settings tiene
     * prioridad; si no existe, el default compilado en el APK.
     */
    val effectiveServerUrlFlow: Flow<String> = context.dataStore.data.map { prefs ->
        if (BuildConfig.ALLOW_SERVER_OVERRIDE) {
            prefs[SERVER_URL]?.takeIf { it.isNotBlank() } ?: BuildConfig.SERVER_URL
        } else {
            BuildConfig.SERVER_URL
        }
    }

    val lastSyncFlow: Flow<Long> = context.dataStore.data.map { it[LAST_SYNC] ?: 0L }
    val deviceUuidFlow: Flow<String?> = context.dataStore.data.map { it[DEVICE_UUID] }
    val printerAddressFlow: Flow<String?> = context.dataStore.data.map { it[PRINTER_ADDRESS] }
    val printerNameFlow: Flow<String?> = context.dataStore.data.map { it[PRINTER_NAME] }
    val autoPrintFlow: Flow<Boolean> = context.dataStore.data.map { it[AUTO_PRINT] ?: true }

    val sessionFlow: Flow<SessionData?> = context.dataStore.data.map { prefs ->
        val token = prefs[TOKEN] ?: return@map null
        SessionData(
            token = token,
            userId = prefs[USER_ID] ?: 0L,
            userName = prefs[USER_NAME] ?: "",
            branchId = prefs[BRANCH_ID] ?: 0L,
            branchName = prefs[BRANCH_NAME] ?: "",
            companyId = prefs[COMPANY_ID] ?: 0L,
            serverUrl = if (BuildConfig.ALLOW_SERVER_OVERRIDE) {
                prefs[SERVER_URL]?.takeIf { it.isNotBlank() } ?: BuildConfig.SERVER_URL
            } else {
                BuildConfig.SERVER_URL
            },
            permissions = prefs[PERMISSIONS]?.split(",")?.toSet() ?: emptySet(),
        )
    }

    /**
     * Persiste la sesion despues de un login exitoso.
     * NOTA: el serverUrl ya NO se persiste aqui — es un override de admin
     * gestionado solo desde Settings (updateServerUrl/clearServerUrlOverride).
     */
    suspend fun save(
        token: String,
        userId: Long,
        userName: String,
        branchId: Long,
        branchName: String,
        companyId: Long,
        permissions: Set<String>,
    ) {
        context.dataStore.edit { prefs ->
            prefs[TOKEN] = token
            prefs[USER_ID] = userId
            prefs[USER_NAME] = userName
            prefs[BRANCH_ID] = branchId
            prefs[BRANCH_NAME] = branchName
            prefs[COMPANY_ID] = companyId
            prefs[PERMISSIONS] = permissions.joinToString(",")
        }
    }

    suspend fun updateLastSync(timestamp: Long) {
        context.dataStore.edit { it[LAST_SYNC] = timestamp }
    }

    /** Setea un override de URL (desde Settings). Vacio = limpia override. */
    suspend fun updateServerUrl(url: String) {
        context.dataStore.edit {
            if (!BuildConfig.ALLOW_SERVER_OVERRIDE || url.isBlank()) {
                it.remove(SERVER_URL)
            } else {
                it[SERVER_URL] = url
            }
        }
    }

    /** Limpia el override y vuelve al default del APK ([BuildConfig.SERVER_URL]). */
    suspend fun clearServerUrlOverride() {
        context.dataStore.edit { it.remove(SERVER_URL) }
    }

    suspend fun savePrinter(address: String, name: String) {
        context.dataStore.edit {
            it[PRINTER_ADDRESS] = address
            it[PRINTER_NAME] = name
        }
    }

    suspend fun clearPrinter() {
        context.dataStore.edit {
            it.remove(PRINTER_ADDRESS)
            it.remove(PRINTER_NAME)
        }
    }

    suspend fun setAutoPrint(enabled: Boolean) {
        context.dataStore.edit { it[AUTO_PRINT] = enabled }
    }

    suspend fun getOrCreateDeviceUuid(): String {
        val existing = deviceUuidFlow.firstOrNull()
        if (!existing.isNullOrBlank()) return existing

        val generated = UUID.randomUUID().toString()
        context.dataStore.edit { it[DEVICE_UUID] = generated }

        return generated
    }

    /**
     * Limpia la sesion (logout). Preserva:
     * - deviceUuid (identidad del dispositivo)
     * - override de URL si admin lo configuro (no debe perderse al logout)
     * - configuracion de impresora bluetooth
     */
    suspend fun clear() {
        val deviceUuid = deviceUuidFlow.firstOrNull()
        val urlOverride = serverUrlFlow.firstOrNull()
        val printerAddress = printerAddressFlow.firstOrNull()
        val printerName = printerNameFlow.firstOrNull()
        val autoPrint = autoPrintFlow.firstOrNull() ?: true
        context.dataStore.edit {
            it.clear()
            if (!deviceUuid.isNullOrBlank()) it[DEVICE_UUID] = deviceUuid
            if (BuildConfig.ALLOW_SERVER_OVERRIDE && !urlOverride.isNullOrBlank()) it[SERVER_URL] = urlOverride
            if (!printerAddress.isNullOrBlank()) it[PRINTER_ADDRESS] = printerAddress
            if (!printerName.isNullOrBlank()) it[PRINTER_NAME] = printerName
            it[AUTO_PRINT] = autoPrint
        }
    }
}

data class SessionData(
    val token: String,
    val userId: Long,
    val userName: String,
    val branchId: Long,
    val branchName: String,
    val companyId: Long,
    val serverUrl: String,
    val permissions: Set<String>
) {
    fun hasPermission(permission: String) = permission in permissions
}
