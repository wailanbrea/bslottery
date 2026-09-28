package dev.bsolutions.bsloteria.data.licensing

import android.content.Context
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import dagger.hilt.android.qualifiers.ApplicationContext
import java.time.OffsetDateTime
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class LicenseStore @Inject constructor(@ApplicationContext context: Context) {
    private val preferences = EncryptedSharedPreferences.create(
        context,
        "bslottery_license",
        MasterKey.Builder(context).setKeyScheme(MasterKey.KeyScheme.AES256_GCM).build(),
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM,
    )

    fun licenseKey(): String? = preferences.getString(KEY_LICENSE, null)

    fun saveValidation(response: LicenseResponse) {
        preferences.edit()
            .putString(KEY_LICENSE, response.licenseKey ?: licenseKey())
            .putLong(KEY_LAST_VALIDATION, System.currentTimeMillis())
            .putString(KEY_EXPIRES_AT, response.expiresAt)
            .putBoolean(KEY_OFFLINE_MODE, response.features["offline_mode"] == true)
            .putLong(KEY_GRACE_HOURS, response.limits["offline_grace_hours"]?.toLong() ?: 72L)
            .putString(KEY_REASON, response.reasonCode)
            .apply()
    }

    fun saveActivation(response: LicenseResponse) {
        saveValidation(response)
        preferences.edit().putString(KEY_LICENSE, response.licenseKey).apply()
    }

    fun canUseOfflineGrace(now: Long = System.currentTimeMillis()): Boolean {
        val lastValidation = preferences.getLong(KEY_LAST_VALIDATION, 0L)
        if (lastValidation <= 0L || now < lastValidation) return false
        if (!preferences.getBoolean(KEY_OFFLINE_MODE, false)) return false

        val graceHours = preferences.getLong(KEY_GRACE_HOURS, 72L).coerceAtLeast(0L)
        if (graceHours == 0L || now - lastValidation > graceHours * 60L * 60L * 1000L) return false

        val expiresAt = preferences.getString(KEY_EXPIRES_AT, null)
        if (!expiresAt.isNullOrBlank()) {
            val expiresMillis = runCatching { OffsetDateTime.parse(expiresAt).toInstant().toEpochMilli() }.getOrNull()
                ?: return false
            if (now >= expiresMillis) return false
        }

        return true
    }

    companion object {
        private const val KEY_LICENSE = "license_key"
        private const val KEY_LAST_VALIDATION = "last_validation_at"
        private const val KEY_EXPIRES_AT = "expires_at"
        private const val KEY_OFFLINE_MODE = "offline_mode"
        private const val KEY_GRACE_HOURS = "offline_grace_hours"
        private const val KEY_REASON = "reason_code"
    }
}
