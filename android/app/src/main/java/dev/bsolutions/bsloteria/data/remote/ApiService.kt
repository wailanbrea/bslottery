package dev.bsolutions.bsloteria.data.remote

import dev.bsolutions.bsloteria.data.remote.dto.*
import retrofit2.Response
import retrofit2.http.*

interface ApiService {

    @POST("mobile/login")
    suspend fun login(@Body request: LoginRequest): Response<LoginResponse>

    @POST("mobile/logout")
    suspend fun logout(): Response<Unit>

    @GET("mobile/sync/data")
    suspend fun getSyncData(
        @Query("since") since: String? = null
    ): Response<SyncDataResponse>

    @POST("mobile/tickets/batch")
    suspend fun syncTicketsBatch(
        @Body request: SyncBatchRequest
    ): Response<SyncBatchResponse>

    @POST("mobile/tickets")
    suspend fun createTicket(
        @Body request: OfflineTicketRequest
    ): Response<TicketResponse>

    @POST("offline/session/open")
    suspend fun openOfflineSession(
        @Body request: OpenOfflineSessionRequest
    ): Response<OpenOfflineSessionResponse>

    @GET("offline/bootstrap")
    suspend fun getOfflineBootstrap(
        @Query("session_uuid") sessionUuid: String
    ): Response<OfflineBootstrapResponse>

    @POST("offline/sync")
    suspend fun syncOfflineTickets(
        @Body request: OfflineSyncRequest
    ): Response<OfflineSyncResponse>

    @GET("mobile/tickets")
    suspend fun getTickets(
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 50
    ): Response<List<TicketResponse>>

    @GET("mobile/tickets/lookup")
    suspend fun lookupTicket(@Query("token") token: String): Response<TicketResponse>

    @GET("mobile/tickets/check-limit")
    suspend fun checkLimit(
        @Query("draw_id") drawId: Long,
        @Query("bet_type_id") betTypeId: Long,
        @Query("number_value") numberValue: String,
    ): Response<CheckLimitResponse>

    @GET("mobile/tickets/{uuid}")
    suspend fun getTicket(@Path("uuid") uuid: String): Response<TicketResponse>

    @GET("mobile/cash/status")
    suspend fun getCashStatus(): Response<CashStatusResponse>

    @POST("mobile/cash/open")
    suspend fun openCash(@Body request: CashOpenRequest): Response<CashSessionResponse>

    @POST("mobile/cash/close")
    suspend fun closeCash(@Body request: CashCloseRequest): Response<CashSessionResponse>

    @GET("mobile/tickets/{uuid}/winners")
    suspend fun getTicketWinners(@Path("uuid") uuid: String): Response<TicketWinnersResponse>

    @POST("mobile/tickets/{uuid}/pay-prize")
    suspend fun payTicketPrize(@Path("uuid") uuid: String): Response<PayPrizeResponse>
}
