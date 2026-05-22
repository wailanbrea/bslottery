package dev.bsolutions.bsloteria.data.remote

import dev.bsolutions.bsloteria.util.SessionStore
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.runBlocking
import okhttp3.HttpUrl.Companion.toHttpUrlOrNull
import okhttp3.Interceptor
import okhttp3.Response
import timber.log.Timber
import javax.inject.Inject

/**
 * Reescribe scheme/host/port de cada request a la URL efectiva del backend.
 * Prioridad: override de admin (SessionStore) > default del APK (BuildConfig.SERVER_URL).
 * El baseUrl de Retrofit es solo un placeholder; este interceptor es la fuente real.
 */
class DynamicBaseUrlInterceptor @Inject constructor(
    private val sessionStore: SessionStore,
) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val serverUrl = runBlocking { sessionStore.effectiveServerUrlFlow.firstOrNull() }
        val request = chain.request()

        if (serverUrl.isNullOrBlank()) {
            Timber.w("DynamicBaseUrlInterceptor: sin URL efectiva, pasando request sin reescribir")
            return chain.proceed(request)
        }

        val base = serverUrl.trimEnd('/').plus("/api/").toHttpUrlOrNull()
        if (base == null) {
            Timber.e("DynamicBaseUrlInterceptor: URL invalida '%s', pasando sin reescribir", serverUrl)
            return chain.proceed(request)
        }

        val newUrl = request.url.newBuilder()
            .scheme(base.scheme)
            .host(base.host)
            .port(base.port)
            .build()

        Timber.v("Request -> %s", newUrl)
        return chain.proceed(request.newBuilder().url(newUrl).build())
    }
}
