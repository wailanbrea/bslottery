package dev.bsolutions.bsloteria.util

import timber.log.Timber
import java.io.IOException
import java.net.ConnectException
import java.net.SocketTimeoutException
import java.net.UnknownHostException
import javax.net.ssl.SSLException
import javax.net.ssl.SSLHandshakeException

/**
 * Mensajes consistentes para errores de red en toda la app.
 *
 * Filosofia:
 * - Categorizar la excepcion (timeout vs host inalcanzable vs DNS vs TLS) y dar
 *   un mensaje accionable al cajero.
 * - NUNCA exponer detalles tecnicos como IPs o stack en la UI.
 * - Loggear los detalles con Timber para que sysadmin pueda diagnosticar.
 */
object NetworkErrors {

    fun describe(throwable: Throwable, context: String = ""): String {
        val tag = if (context.isNotBlank()) "[$context] " else ""
        return when (throwable) {
            is UnknownHostException -> {
                Timber.w(throwable, "%sDNS no resuelve el servidor", tag)
                "No se encuentra el servidor. Verifica internet o la URL del servidor en Configuración."
            }
            is SocketTimeoutException -> {
                Timber.w(throwable, "%sTimeout conectando al servidor", tag)
                "El servidor tardó demasiado en responder. Verifica tu conexión e intenta de nuevo."
            }
            is ConnectException -> {
                Timber.w(throwable, "%sNo se pudo conectar al servidor", tag)
                "No se puede conectar al servidor. Verifica que estés en internet y que la URL sea correcta."
            }
            is SSLHandshakeException -> {
                Timber.e(throwable, "%sError TLS/SSL", tag)
                "Error de certificado del servidor. Contacta al administrador."
            }
            is SSLException -> {
                Timber.e(throwable, "%sError SSL generico", tag)
                "Error de seguridad de la conexión. Intenta de nuevo."
            }
            is IOException -> {
                Timber.w(throwable, "%sIO error", tag)
                "Problema de red. Verifica tu conexión e intenta de nuevo."
            }
            else -> {
                Timber.e(throwable, "%sError no clasificado", tag)
                throwable.localizedMessage ?: "Ocurrió un error inesperado."
            }
        }
    }

    /** True cuando el error es probablemente "no hay internet" (no fallo lógico del backend). */
    fun isLikelyOffline(throwable: Throwable): Boolean = when (throwable) {
        is UnknownHostException, is ConnectException, is SocketTimeoutException, is IOException -> true
        else -> false
    }
}
