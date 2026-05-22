package dev.bsolutions.bsloteria.data.repository

import dev.bsolutions.bsloteria.BuildConfig
import dev.bsolutions.bsloteria.data.remote.ApiService
import dev.bsolutions.bsloteria.data.remote.dto.LoginRequest
import dev.bsolutions.bsloteria.util.NetworkErrors
import dev.bsolutions.bsloteria.util.Result
import dev.bsolutions.bsloteria.util.SessionStore
import timber.log.Timber
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    private val api: ApiService,
    private val session: SessionStore,
) {
    /**
     * Hace login contra la URL efectiva (override de Settings o BuildConfig.SERVER_URL).
     * NO sobreescribe SessionStore.serverUrl — la URL la maneja el admin desde Settings.
     */
    suspend fun login(email: String, password: String): Result<Unit> {
        return try {
            val deviceUuid = session.getOrCreateDeviceUuid()
            val response = api.login(
                LoginRequest(
                    login = email,
                    password = password,
                    deviceName = android.os.Build.MODEL,
                    deviceUuid = deviceUuid,
                    appVersion = BuildConfig.VERSION_NAME,
                ),
            )
            if (response.isSuccessful) {
                val body = response.body()!!
                if (!body.deviceStatus.equals("AUTHORIZED", ignoreCase = true)) {
                    session.clear()
                    return Result.Error(
                        "Dispositivo ${body.deviceStatus ?: "PENDING"}. Autorízalo en el panel web antes de vender.",
                    )
                }
                session.save(
                    token = body.token,
                    userId = body.user.id,
                    userName = body.user.name,
                    userEmail = body.user.email,
                    branchId = body.branch?.id ?: 0L,
                    branchName = body.branch?.name ?: "",
                    companyId = body.company?.id ?: 0L,
                    permissions = body.user.permissions.toSet(),
                )
                Timber.i("Login OK como %s", body.user.name)
                Result.Success(Unit)
            } else {
                val msg = when (response.code()) {
                    401 -> "Credenciales incorrectas."
                    403 -> "Usuario inactivo o dispositivo bloqueado."
                    422 -> "Datos incompletos. Reintenta."
                    in 500..599 -> "El servidor reportó un error (${response.code()}). Intenta más tarde."
                    else -> "Login rechazado por el servidor (${response.code()})."
                }
                Timber.w("Login fallo HTTP %d: %s", response.code(), msg)
                Result.Error(msg)
            }
        } catch (e: Exception) {
            Result.Error(NetworkErrors.describe(e, "login"))
        }
    }

    suspend fun logout() {
        try { api.logout() } catch (_: Exception) { /* offline: limpieza local de todos modos */ }
        session.clear()
    }
}
