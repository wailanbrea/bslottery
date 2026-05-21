package dev.bsolutions.bsloteria.data.remote

import dev.bsolutions.bsloteria.util.SessionStore
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.runBlocking
import okhttp3.Interceptor
import okhttp3.Response
import timber.log.Timber
import javax.inject.Inject

class AuthInterceptor @Inject constructor(
    private val sessionStore: SessionStore
) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        // El device_uuid debe acompañar TODA petición autenticada (lo exige
        // EnsureDeviceIsAuthorized en el backend). Si por algún motivo
        // (upgrade del APK, datos parciales) no está en DataStore, lo
        // generamos y persistimos antes de mandar el request.
        val token = runBlocking { sessionStore.tokenFlow.firstOrNull() }
        val deviceUuid = runBlocking {
            val existing = sessionStore.deviceUuidFlow.firstOrNull()
            if (!existing.isNullOrBlank()) existing
            else {
                Timber.w("device_uuid faltante en DataStore — generando uno nuevo")
                sessionStore.getOrCreateDeviceUuid()
            }
        }

        val request = chain.request().newBuilder()
            .addHeader("Accept", "application/json")
            .addHeader("X-Requested-With", "BSLoteria-Android")
            .addHeader("X-Device-UUID", deviceUuid)
            .apply { if (token != null) addHeader("Authorization", "Bearer $token") }
            .build()
        return chain.proceed(request)
    }
}
