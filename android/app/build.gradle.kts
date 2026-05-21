import java.util.Properties
import org.jetbrains.kotlin.gradle.dsl.JvmTarget

val keystorePropertiesFile = rootProject.file("keystore.properties")
val keystoreProperties = Properties().apply {
    if (keystorePropertiesFile.exists()) {
        keystorePropertiesFile.inputStream().use(::load)
    }
}

fun String.asBuildConfigString(): String = "\"${replace("\\", "\\\\").replace("\"", "\\\"")}\""

plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.compose)
    alias(libs.plugins.hilt)
    alias(libs.plugins.ksp)
}

android {
    namespace = "dev.bsolutions.bsloteria"
    compileSdk = 35

    defaultConfig {
        applicationId = "dev.bsolutions.bsloteria"
        minSdk = 26
        targetSdk = 35
        versionCode = 1
        versionName = "1.0.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"

        buildConfigField("String", "SERVER_URL", "http://10.0.2.2:8000".asBuildConfigString())
        buildConfigField("String", "API_BASE_URL", "\"http://10.0.2.2:8000/api/\"")

        // Presets que el usuario puede alternar en LoginScreen / Settings sin reinstalar.
        // EMULATOR_URL es fijo (alias del host visto desde AVD). LAN_URL viene de
        // gradle.properties (BSLOTTERY_LAN_SERVER_URL) para que cada PC use su IP local.
        buildConfigField("String", "EMULATOR_URL", "http://10.0.2.2:8000".asBuildConfigString())
        val lanPresetUrl = providers.gradleProperty("BSLOTTERY_LAN_SERVER_URL")
            .orElse("http://192.168.1.100:8000")
            .get()
            .trimEnd('/')
        buildConfigField("String", "LAN_URL", lanPresetUrl.asBuildConfigString())
    }

    flavorDimensions += "environment"

    productFlavors {
        create("emulator") {
            dimension = "environment"
            applicationIdSuffix = ".emulator"
            versionNameSuffix = "-emulator"

            val serverUrl = "http://10.0.2.2:8000"
            buildConfigField("String", "SERVER_URL", serverUrl.asBuildConfigString())
            buildConfigField("String", "API_BASE_URL", "${serverUrl.trimEnd('/')}/api/".asBuildConfigString())
        }

        create("lan") {
            dimension = "environment"
            applicationIdSuffix = ".lan"
            versionNameSuffix = "-lan"

            val serverUrl = providers.gradleProperty("BSLOTTERY_LAN_SERVER_URL")
                .orElse("http://192.168.1.100:8000")
                .get()
                .trimEnd('/')

            buildConfigField("String", "SERVER_URL", serverUrl.asBuildConfigString())
            buildConfigField("String", "API_BASE_URL", "$serverUrl/api/".asBuildConfigString())
        }

        create("production") {
            dimension = "environment"

            val serverUrl = providers.gradleProperty("BSLOTTERY_PRODUCTION_SERVER_URL")
                .orElse("https://api.tu-dominio.com")
                .get()
                .trimEnd('/')

            require(serverUrl.startsWith("https://")) {
                "BSLOTTERY_PRODUCTION_SERVER_URL debe usar HTTPS para production."
            }

            buildConfigField("String", "SERVER_URL", serverUrl.asBuildConfigString())
            buildConfigField("String", "API_BASE_URL", "$serverUrl/api/".asBuildConfigString())
        }
    }

    signingConfigs {
        create("release") {
            val storeFilePath = keystoreProperties.getProperty("storeFile")
            if (!storeFilePath.isNullOrBlank()) {
                storeFile = rootProject.file(storeFilePath)
            }
            storePassword = keystoreProperties.getProperty("storePassword")
            keyAlias = keystoreProperties.getProperty("keyAlias")
            keyPassword = keystoreProperties.getProperty("keyPassword")
        }
    }

    buildTypes {
        debug {
            isDebuggable = true
            applicationIdSuffix = ".debug"
        }
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            if (
                keystoreProperties.getProperty("storeFile").isNullOrBlank().not()
                && keystoreProperties.getProperty("storePassword").isNullOrBlank().not()
                && keystoreProperties.getProperty("keyAlias").isNullOrBlank().not()
                && keystoreProperties.getProperty("keyPassword").isNullOrBlank().not()
            ) {
                signingConfig = signingConfigs.getByName("release")
            }
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    buildFeatures {
        compose = true
        buildConfig = true
    }

    packaging {
        resources {
            excludes += "/META-INF/{AL2.0,LGPL2.1}"
        }
    }
}

dependencies {
    implementation(libs.core.ktx)
    implementation(libs.activity.compose)
    implementation(libs.lifecycle.runtime.ktx)
    implementation(libs.lifecycle.viewmodel.compose)
    implementation(libs.coroutines.android)

    // Compose
    val composeBom = platform(libs.compose.bom)
    implementation(composeBom)
    implementation(libs.compose.ui)
    implementation(libs.compose.ui.graphics)
    implementation(libs.compose.ui.tooling.preview)
    implementation(libs.compose.material3)
    implementation(libs.compose.material.icons.extended)
    implementation(libs.compose.runtime)
    debugImplementation(libs.compose.ui.tooling)

    // Navigation
    implementation(libs.navigation.compose)
    implementation(libs.hilt.navigation.compose)

    // Hilt
    implementation(libs.hilt.android)
    ksp(libs.hilt.compiler)

    // Room
    implementation(libs.room.runtime)
    implementation(libs.room.ktx)
    ksp(libs.room.compiler)

    // Retrofit + Moshi
    implementation(libs.retrofit)
    implementation(libs.retrofit.moshi)
    implementation(libs.okhttp)
    implementation(libs.okhttp.logging)
    implementation(libs.moshi.kotlin)
    ksp(libs.moshi.codegen)

    // WorkManager
    implementation(libs.workmanager.ktx)
    implementation(libs.hilt.workmanager)
    ksp(libs.hilt.workmanager.compiler)

    // DataStore
    implementation(libs.datastore.preferences)

    // Coil
    implementation(libs.coil.compose)

    // AppCompat (necesario para AppCompatDelegate.setDefaultNightMode)
    implementation("androidx.appcompat:appcompat:1.7.0")

    // Timber
    implementation(libs.timber)

    // CameraX (escaneo QR)
    implementation(libs.camerax.core)
    implementation(libs.camerax.camera2)
    implementation(libs.camerax.lifecycle)
    implementation(libs.camerax.view)

    // ML Kit barcode scanning (lectura QR)
    implementation(libs.mlkit.barcode.scanning)

    // Accompanist permissions
    implementation(libs.accompanist.permissions)
}

kotlin {
    compilerOptions {
        jvmTarget.set(JvmTarget.JVM_17)
    }
}
