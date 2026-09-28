package dev.bsolutions.bsloteria.data.licensing

import android.os.Build
import dev.bsolutions.bsloteria.BuildConfig
import dev.bsolutions.bsloteria.util.SessionStore
import java.io.IOException
import retrofit2.HttpException
import javax.inject.Inject
import javax.inject.Singleton

sealed interface LicenseCheckResult {
    data object Valid : LicenseCheckResult
    data object OfflineGrace : LicenseCheckResult
    data object NeedsActivation : LicenseCheckResult
    data class Blocked(val reason: String, val message: String) : LicenseCheckResult
}

@Singleton
class LicenseRepository @Inject constructor(
    private val api: LicenseApi,
    private val store: LicenseStore,
    private val sessionStore: SessionStore,
) {
    suspend fun activate(activationCode: String): LicenseCheckResult {
        if (activationCode.isBlank()) {
            return LicenseCheckResult.Blocked("ACTIVATION_CODE_REQUIRED", "Escribe el código de activación.")
        }

        return runCatching {
            api.activate(
                ActivateLicenseRequest(
                    projectCode = BuildConfig.LICENSE_PROJECT_CODE,
                    activationCode = activationCode.trim(),
                    deviceFingerprint = sessionStore.getOrCreateDeviceUuid(),
                    deviceName = "Android ${Build.MODEL}",
                    deviceType = "android",
                    appVersion = BuildConfig.VERSION_NAME,
                ),
            )
        }.fold(
            onSuccess = { response ->
                val body = response.body()
                if (response.isSuccessful && body?.success == true && body.valid && !body.licenseKey.isNullOrBlank()) {
                    store.saveActivation(body)
                    LicenseCheckResult.Valid
                } else {
                    LicenseCheckResult.Blocked(
                        body?.reasonCode ?: "SERVER_ERROR",
                        body?.message ?: "No fue posible activar la licencia.",
                    )
                }
            },
            onFailure = { error ->
                LicenseCheckResult.Blocked("SERVER_ERROR", error.userMessage())
            },
        )
    }

    suspend fun validate(): LicenseCheckResult {
        val licenseKey = store.licenseKey()
            ?: return LicenseCheckResult.NeedsActivation

        return runCatching {
            api.validate(
                ValidateLicenseRequest(
                    projectCode = BuildConfig.LICENSE_PROJECT_CODE,
                    licenseKey = licenseKey,
                    deviceFingerprint = sessionStore.getOrCreateDeviceUuid(),
                    appVersion = BuildConfig.VERSION_NAME,
                ),
            )
        }.fold(
            onSuccess = { response ->
                val body = response.body()
                if (response.isSuccessful && body?.valid == true) {
                    store.saveValidation(body)
                    LicenseCheckResult.Valid
                } else {
                    LicenseCheckResult.Blocked(
                        body?.reasonCode ?: "SERVER_ERROR",
                        body?.message ?: "La licencia no es válida.",
                    )
                }
            },
            onFailure = { error ->
                if (error.isTransient() && store.canUseOfflineGrace()) {
                    LicenseCheckResult.OfflineGrace
                } else {
                    LicenseCheckResult.Blocked("SERVER_ERROR", error.userMessage())
                }
            },
        )
    }

    private fun Throwable.isTransient(): Boolean = this is IOException || (this is HttpException && code() >= 500)

    private fun Throwable.userMessage(): String = when {
        this is IOException -> "No se pudo conectar con el servidor de licencias."
        else -> message ?: "No se pudo validar la licencia."
    }
}
