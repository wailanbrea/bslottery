package dev.bsolutions.bsloteria.data.remote

import dev.bsolutions.bsloteria.util.SessionStore
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.runBlocking
import okhttp3.HttpUrl.Companion.toHttpUrlOrNull
import okhttp3.Interceptor
import okhttp3.Response
import javax.inject.Inject

class DynamicBaseUrlInterceptor @Inject constructor(
    private val sessionStore: SessionStore
) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val serverUrl = runBlocking { sessionStore.serverUrlFlow.firstOrNull() }
        val request = chain.request()

        if (serverUrl.isNullOrBlank()) return chain.proceed(request)

        val base = serverUrl.trimEnd('/').plus("/api/").toHttpUrlOrNull()
            ?: return chain.proceed(request)

        val newUrl = request.url.newBuilder()
            .scheme(base.scheme)
            .host(base.host)
            .port(base.port)
            .build()

        return chain.proceed(request.newBuilder().url(newUrl).build())
    }
}
