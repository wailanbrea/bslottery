package dev.bsolutions.bsloteria.data.repository

import dev.bsolutions.bsloteria.BuildConfig
import dev.bsolutions.bsloteria.data.remote.ApiService
import dev.bsolutions.bsloteria.data.remote.dto.LoginRequest
import dev.bsolutions.bsloteria.util.Result
import dev.bsolutions.bsloteria.util.SessionStore
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    private val api: ApiService,
    private val session: SessionStore
) {
    suspend fun login(email: String, password: String, serverUrl: String): Result<Unit> {
        return try {
            session.updateServerUrl(serverUrl)
            val deviceUuid = session.getOrCreateDeviceUuid()
            val response = api.login(
                LoginRequest(
                    login = email,
                    password = password,
                    deviceName = android.os.Build.MODEL,
                    deviceUuid = deviceUuid,
                    appVersion = BuildConfig.VERSION_NAME
                )
            )
            if (response.isSuccessful) {
                val body = response.body()!!
                if (!body.deviceStatus.equals("AUTHORIZED", ignoreCase = true)) {
                    session.clear()

                    return Result.Error(
                        "Dispositivo ${body.deviceStatus ?: "PENDING"}. Autoricelo en el panel web antes de vender."
                    )
                }

                session.save(
                    token = body.token,
                    userId = body.user.id,
                    userName = body.user.name,
                    branchId = body.branch?.id ?: 0L,
                    branchName = body.branch?.name ?: "",
                    companyId = body.company?.id ?: 0L,
                    serverUrl = serverUrl,
                    permissions = body.user.permissions.toSet()
                )
                Result.Success(Unit)
            } else {
                Result.Error("Login rechazado por servidor (${response.code()})")
            }
        } catch (e: Exception) {
            Result.Error("No se pudo conectar al servidor: ${e.localizedMessage}")
        }
    }

    suspend fun logout() {
        try { api.logout() } catch (_: Exception) {}
        session.clear()
    }
}
