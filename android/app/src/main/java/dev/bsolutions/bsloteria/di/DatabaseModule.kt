package dev.bsolutions.bsloteria.di

import android.content.Context
import androidx.room.Room
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import dev.bsolutions.bsloteria.data.local.AppDatabase
import dev.bsolutions.bsloteria.data.local.dao.BetTypeDao
import dev.bsolutions.bsloteria.data.local.dao.DrawDao
import dev.bsolutions.bsloteria.data.local.dao.OfflineSessionDao
import dev.bsolutions.bsloteria.data.local.dao.TicketDao
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object DatabaseModule {

    @Provides
    @Singleton
    fun provideDatabase(@ApplicationContext context: Context): AppDatabase =
        Room.databaseBuilder(context, AppDatabase::class.java, AppDatabase.NAME)
            .fallbackToDestructiveMigration()
            .build()

    @Provides fun provideDrawDao(db: AppDatabase): DrawDao = db.drawDao()
    @Provides fun provideTicketDao(db: AppDatabase): TicketDao = db.ticketDao()
    @Provides fun provideBetTypeDao(db: AppDatabase): BetTypeDao = db.betTypeDao()
    @Provides fun provideOfflineSessionDao(db: AppDatabase): OfflineSessionDao = db.offlineSessionDao()
}
