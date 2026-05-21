package dev.bsolutions.bsloteria.util

import dev.bsolutions.bsloteria.BuildConfig

/**
 * Preset de conexion al backend. Lo expone LoginScreen y SettingsScreen como
 * chips clickables para que el cajero alterne entre Emulador (AVD) y LAN
 * (PC con XAMPP/artisan serve) sin tener que escribir la URL completa cada vez.
 *
 * Los URLs vienen de BuildConfig (generados desde build.gradle.kts +
 * gradle.properties), no estan hardcoded en el binario de produccion.
 */
data class ServerPreset(val label: String, val url: String)

object ServerPresets {
    val ALL: List<ServerPreset> = listOfNotNull(
        ServerPreset("Emulador", BuildConfig.EMULATOR_URL).takeIf { it.url.isNotBlank() },
        ServerPreset("LAN", BuildConfig.LAN_URL).takeIf { it.url.isNotBlank() },
    )

    /** Devuelve el preset cuya URL coincida con el valor dado, o null si es custom. */
    fun match(url: String): ServerPreset? {
        val normalized = url.trimEnd('/')
        return ALL.firstOrNull { it.url.trimEnd('/') == normalized }
    }
}
