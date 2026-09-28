package dev.bsolutions.bsloteria.worker

import android.content.Context
import androidx.hilt.work.HiltWorker
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import dagger.assisted.Assisted
import dagger.assisted.AssistedInject
import dev.bsolutions.bsloteria.data.licensing.LicenseCheckResult
import dev.bsolutions.bsloteria.data.licensing.LicenseRepository

@HiltWorker
class LicenseValidationWorker @AssistedInject constructor(
    @Assisted appContext: Context,
    @Assisted workerParams: WorkerParameters,
    private val repository: LicenseRepository,
) : CoroutineWorker(appContext, workerParams) {
    override suspend fun doWork(): Result = when (repository.validate()) {
        LicenseCheckResult.Valid,
        LicenseCheckResult.OfflineGrace ->
            Result.success()
        LicenseCheckResult.NeedsActivation -> Result.failure()
        is LicenseCheckResult.Blocked -> Result.failure()
    }
}
