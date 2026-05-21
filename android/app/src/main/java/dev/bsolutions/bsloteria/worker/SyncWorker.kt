package dev.bsolutions.bsloteria.worker

import android.content.Context
import androidx.hilt.work.HiltWorker
import androidx.work.*
import dagger.assisted.Assisted
import dagger.assisted.AssistedInject
import dev.bsolutions.bsloteria.data.repository.SyncRepository
import dev.bsolutions.bsloteria.util.Result
import timber.log.Timber
import java.util.concurrent.TimeUnit

@HiltWorker
class SyncWorker @AssistedInject constructor(
    @Assisted appContext: Context,
    @Assisted workerParams: WorkerParameters,
    private val syncRepository: SyncRepository
) : CoroutineWorker(appContext, workerParams) {

    override suspend fun doWork(): Result {
        Timber.d("SyncWorker started")

        val catalogResult = syncRepository.syncCatalog()
        if (catalogResult is dev.bsolutions.bsloteria.util.Result.Error) {
            Timber.w("Catalog sync failed: ${catalogResult.message}")
        }

        val uploadResult = syncRepository.uploadPendingTickets()
        if (uploadResult is dev.bsolutions.bsloteria.util.Result.Error) {
            Timber.w("Ticket upload failed: ${uploadResult.message}")
            return if (runAttemptCount < 3) Result.retry() else Result.failure()
        }

        val summary = (uploadResult as? dev.bsolutions.bsloteria.util.Result.Success)?.data
        Timber.d("SyncWorker done: ${summary?.synced} synced, ${summary?.failed} failed")
        return Result.success()
    }

    companion object {
        const val WORK_NAME = "bs_sync_periodic"
        const val WORK_NAME_IMMEDIATE = "bs_sync_now"

        fun periodicRequest(): PeriodicWorkRequest =
            PeriodicWorkRequestBuilder<SyncWorker>(15, TimeUnit.MINUTES)
                .setConstraints(
                    Constraints.Builder()
                        .setRequiredNetworkType(NetworkType.CONNECTED)
                        .build()
                )
                .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 1, TimeUnit.MINUTES)
                .build()

        fun immediateRequest(): OneTimeWorkRequest =
            OneTimeWorkRequestBuilder<SyncWorker>()
                .setConstraints(
                    Constraints.Builder()
                        .setRequiredNetworkType(NetworkType.CONNECTED)
                        .build()
                )
                .build()
    }
}
