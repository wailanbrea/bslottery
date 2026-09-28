package dev.bsolutions.bsloteria.di

import com.squareup.moshi.Moshi
import dev.bsolutions.bsloteria.BuildConfig
import dev.bsolutions.bsloteria.data.licensing.LicenseApi
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import okhttp3.OkHttpClient
import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory
import java.util.concurrent.TimeUnit
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object LicenseModule {
    @Provides
    @Singleton
    fun provideLicenseApi(moshi: Moshi): LicenseApi = Retrofit.Builder()
        .baseUrl(BuildConfig.LICENSE_API_URL)
        .client(
            OkHttpClient.Builder()
                .connectTimeout(10, TimeUnit.SECONDS)
                .readTimeout(15, TimeUnit.SECONDS)
                .writeTimeout(15, TimeUnit.SECONDS)
                .build(),
        )
        .addConverterFactory(MoshiConverterFactory.create(moshi))
        .build()
        .create(LicenseApi::class.java)
}
