package dev.bsolutions.bsloteria.data.repository

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.longPreferencesKey
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import dagger.hilt.android.qualifiers.ApplicationContext
import dev.bsolutions.bsloteria.data.remote.ApiService
import dev.bsolutions.bsloteria.data.remote.dto.ApiError
import dev.bsolutions.bsloteria.data.remote.dto.CashCloseRequest
import dev.bsolutions.bsloteria.data.remote.dto.CashOpenRequest
import dev.bsolutions.bsloteria.data.remote.dto.CashSessionDto
import dev.bsolutions.bsloteria.data.remote.dto.CashStatusResponse
import dev.bsolutions.bsloteria.util.Result
import kotlinx.coroutines.flow.firstOrNull
import okhttp3.ResponseBody
import retrofit2.Response
import timber.log.Timber
import java.io.IOException
import javax.inject.Inject
import javax.inject.Singleton

private val Context.cashCacheDataStore: DataStore<Preferences> by preferencesDataStore(name = "cash_cache")

data class CashStatusResult(
    val data: CashStatusResponse,
    val fromCache: Boolean,
    val cachedAtMillis: Long? = null,
)

@Singleton
class CashRepository @Inject constructor(
    private val api: ApiService,
    @ApplicationContext private val context: Context,
) {
    private val moshi = Moshi.Builder().addLast(KotlinJsonAdapterFactory()).build()
    private val errorAdapter = moshi.adapter(ApiError::class.java)
    private val statusAdapter = moshi.adapter(CashStatusResponse::class.java)

    private val STATUS_JSON = stringPreferencesKey("status_json")
    private val STATUS_TS = longPreferencesKey("status_ts")

    suspend fun getStatus(): Result<CashStatusResult> {
        return try {
            val response = api.getCashStatus()
            if (response.isSuccessful) {
                val body = response.body()
                if (body != null) {
                    cacheStatus(body)
                    Result.Success(CashStatusResult(body, fromCache = false))
                } else {
                    fallbackFromCache("Respuesta vacia")
                }
            } else {
                val parsed = parseApiError(response.errorBody())
                Result.Error(
                    message = parsed?.message ?: "Error ${response.code()}",
                    code = parsed?.code,
                )
            }
        } catch (e: IOException) {
            Timber.w(e, "Cash status network error, falling back to cache")
            fallbackFromCache("Sin conexion")
        } catch (e: Exception) {
            Timber.e(e, "Cash status failed")
            Result.Error(e.localizedMessage ?: "Error inesperado")
        }
    }

    private suspend fun fallbackFromCache(reason: String): Result<CashStatusResult> {
        val prefs = context.cashCacheDataStore.data.firstOrNull()
        val json = prefs?.get(STATUS_JSON)
        val ts = prefs?.get(STATUS_TS)
        if (json.isNullOrBlank()) return Result.Error(reason)
        return try {
            val cached = statusAdapter.fromJson(json) ?: return Result.Error(reason)
            Result.Success(CashStatusResult(cached, fromCache = true, cachedAtMillis = ts))
        } catch (e: Exception) {
            Result.Error(reason)
        }
    }

    private suspend fun cacheStatus(body: CashStatusResponse) {
        try {
            val json = statusAdapter.toJson(body)
            context.cashCacheDataStore.edit {
                it[STATUS_JSON] = json
                it[STATUS_TS] = System.currentTimeMillis()
            }
        } catch (e: Exception) {
            Timber.w(e, "Failed to cache cash status")
        }
    }

    suspend fun clearCache() {
        context.cashCacheDataStore.edit { it.clear() }
    }

    suspend fun open(openingAmount: String, notes: String?): Result<CashSessionDto> = safeCall {
        api.openCash(CashOpenRequest(openingAmount = openingAmount, notes = notes?.ifBlank { null }))
    }.mapData { it.session }

    suspend fun close(
        countedCash: String?,
        denominations: Map<String, Int>?,
        notes: String?,
    ): Result<CashSessionDto> = safeCall {
        api.closeCash(
            CashCloseRequest(
                countedCash = countedCash?.ifBlank { null },
                denominations = denominations?.filterValues { it > 0 }?.takeIf { it.isNotEmpty() },
                notes = notes?.ifBlank { null },
            )
        )
    }.mapData { it.session }

    private suspend fun <T> safeCall(call: suspend () -> Response<T>): Result<T> {
        return try {
            val response = call()
            if (response.isSuccessful) {
                val body = response.body()
                if (body != null) Result.Success(body) else Result.Error("Respuesta vacia")
            } else {
                val parsed = parseApiError(response.errorBody())
                Result.Error(
                    message = parsed?.message ?: "Error ${response.code()}",
                    code = parsed?.code,
                )
            }
        } catch (e: IOException) {
            Timber.w(e, "Cash request network error")
            Result.Error("Sin conexion")
        } catch (e: Exception) {
            Timber.e(e, "Cash request failed")
            Result.Error(e.localizedMessage ?: "Error inesperado")
        }
    }

    private fun parseApiError(body: ResponseBody?): ApiError? = try {
        body?.string()?.let { errorAdapter.fromJson(it) }
    } catch (_: Exception) {
        null
    }

    private inline fun <T, R> Result<T>.mapData(transform: (T) -> R): Result<R> = when (this) {
        is Result.Success -> Result.Success(transform(data))
        is Result.Error -> this
        Result.Loading -> Result.Loading
    }
}
