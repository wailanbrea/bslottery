package dev.bsolutions.bsloteria.data.repository

import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import dev.bsolutions.bsloteria.data.remote.ApiService
import dev.bsolutions.bsloteria.data.remote.dto.ApiError
import dev.bsolutions.bsloteria.data.remote.dto.PayPrizeResponse
import dev.bsolutions.bsloteria.data.remote.dto.TicketWinnersResponse
import dev.bsolutions.bsloteria.util.Result
import okhttp3.ResponseBody
import retrofit2.Response
import timber.log.Timber
import java.io.IOException
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class PrizeRepository @Inject constructor(
    private val api: ApiService,
) {
    private val moshi = Moshi.Builder().addLast(KotlinJsonAdapterFactory()).build()
    private val errorAdapter = moshi.adapter(ApiError::class.java)

    suspend fun getWinners(ticketUuid: String): Result<TicketWinnersResponse> = safeCall {
        api.getTicketWinners(ticketUuid)
    }

    suspend fun payPrize(ticketUuid: String): Result<PayPrizeResponse> = safeCall {
        api.payTicketPrize(ticketUuid)
    }

    private suspend fun <T> safeCall(call: suspend () -> Response<T>): Result<T> {
        return try {
            val response = call()
            if (response.isSuccessful) {
                response.body()?.let { Result.Success(it) } ?: Result.Error("Respuesta vacia")
            } else {
                Result.Error(parseError(response.errorBody()) ?: "Error ${response.code()}")
            }
        } catch (e: IOException) {
            Timber.w(e, "Prize request network error")
            Result.Error("Sin conexion")
        } catch (e: Exception) {
            Timber.e(e, "Prize request failed")
            Result.Error(e.localizedMessage ?: "Error inesperado")
        }
    }

    private fun parseError(body: ResponseBody?): String? = try {
        body?.string()?.let { errorAdapter.fromJson(it)?.message }
    } catch (_: Exception) {
        null
    }
}
