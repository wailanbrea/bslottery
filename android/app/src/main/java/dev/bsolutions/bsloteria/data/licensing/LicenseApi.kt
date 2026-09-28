package dev.bsolutions.bsloteria.data.licensing

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.POST

interface LicenseApi {
    @POST("activation/activate")
    suspend fun activate(@Body request: ActivateLicenseRequest): Response<LicenseResponse>

    @POST("activation/validate")
    suspend fun validate(@Body request: ValidateLicenseRequest): Response<LicenseResponse>
}

@JsonClass(generateAdapter = true)
data class ActivateLicenseRequest(
    @Json(name = "project_code") val projectCode: String,
    @Json(name = "activation_code") val activationCode: String,
    @Json(name = "device_fingerprint") val deviceFingerprint: String,
    @Json(name = "device_name") val deviceName: String,
    @Json(name = "device_type") val deviceType: String = "android",
    val domain: String? = null,
    @Json(name = "app_version") val appVersion: String,
)

@JsonClass(generateAdapter = true)
data class ValidateLicenseRequest(
    @Json(name = "project_code") val projectCode: String,
    @Json(name = "license_key") val licenseKey: String,
    @Json(name = "device_fingerprint") val deviceFingerprint: String,
    val domain: String? = null,
    @Json(name = "app_version") val appVersion: String,
)

@JsonClass(generateAdapter = true)
data class LicenseResponse(
    val success: Boolean = false,
    val valid: Boolean = false,
    @Json(name = "reason_code") val reasonCode: String? = null,
    val message: String? = null,
    @Json(name = "license_key") val licenseKey: String? = null,
    val status: String? = null,
    @Json(name = "expires_at") val expiresAt: String? = null,
    @Json(name = "server_time") val serverTime: String? = null,
    val features: Map<String, Boolean> = emptyMap(),
    val limits: Map<String, Double> = emptyMap(),
)
