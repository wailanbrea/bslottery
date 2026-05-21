<?php

declare(strict_types=1);

namespace App\Services\Licensing;

use App\DTO\Licensing\LicenseApiResult;
use App\Models\LicenseState;
use App\Models\LicenseValidationLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LicenseService
{
    private const VALID_ACTIVE_CODES = ['OK', 'LICENSE_ACTIVE'];

    private const HARD_BLOCK_CODES = [
        'PROJECT_NOT_FOUND',
        'PROJECT_INACTIVE',
        'LICENSE_NOT_FOUND',
        'LICENSE_PROJECT_MISMATCH',
        'LICENSE_EXPIRED',
        'LICENSE_SUSPENDED',
        'LICENSE_CANCELLED',
        'DEVICE_REVOKED',
        'DEVICE_BLOCKED',
        'OFFLINE_MODE_NOT_ALLOWED',
        'OFFLINE_GRACE_EXPIRED',
        'OFFLINE_LAUNCH_LIMIT_REACHED',
        'OFFLINE_OPERATION_LIMIT_REACHED',
        'OFFLINE_SALES_LIMIT_REACHED',
        'CLOCK_SKEW_DETECTED',
        'VALIDATION_REQUIRED',
    ];

    public function __construct(
        private readonly LicenseApiClient $client,
        private readonly LicenseFeatureManager $features,
        private readonly LicenseLimitManager $limits,
    ) {
    }

    public function current(): ?LicenseState
    {
        return LicenseState::query()->where('is_active', true)->latest('id')->first();
    }

    public function deviceFingerprint(): string
    {
        $path = (string) config('licensing.fingerprint_path');

        if (File::exists($path)) {
            $fingerprint = trim((string) File::get($path));

            if ($fingerprint !== '') {
                return $fingerprint;
            }
        }

        File::ensureDirectoryExists(dirname($path));

        $fingerprint = (string) Str::uuid();
        File::put($path, $fingerprint);

        return $fingerprint;
    }

    public function activate(string $activationCode, ?string $locationCode = null, ?string $domain = null): LicenseApiResult
    {
        $result = $this->client->activate([
            'project_code' => (string) config('licensing.project_code'),
            'activation_code' => $activationCode,
            'device_fingerprint' => $this->deviceFingerprint(),
            'device_name' => (string) config('licensing.device_name'),
            'device_type' => (string) config('licensing.device_type'),
            'client_location_code' => $locationCode ?: (string) config('licensing.default_location_code'),
            'domain' => $domain,
            'app_version' => (string) config('licensing.app_version'),
        ]);

        $state = $this->persistResult($result, $locationCode, $domain);
        $this->logResult('activation', $result, $state);

        return $result;
    }

    public function validateCurrent(?string $domain = null): LicenseApiResult
    {
        $state = $this->current();

        if (! $state || ! $state->license_key) {
            $result = LicenseApiResult::fromPayload([
                'success' => false,
                'valid' => false,
                'reason_code' => 'LICENSE_KEY_REQUIRED',
                'message' => 'El sistema no tiene una licencia activada.',
            ]);
            $this->logResult('validation', $result, $state);

            return $result;
        }

        $result = $this->client->validate([
            'project_code' => $state->project_code,
            'license_key' => $state->license_key,
            'device_fingerprint' => $state->device_fingerprint,
            'client_location_code' => $state->client_location_code,
            'domain' => $domain ?: $state->domain,
            'app_version' => (string) config('licensing.app_version'),
        ]);

        $state = $this->persistResult($result, $state->client_location_code, $domain ?: $state->domain);
        $this->logResult('validation', $result, $state);

        return $result;
    }

    /**
     * @return array{allowed: bool, mode: string, reason: string}
     */
    public function accessDecision(?LicenseState $state = null): array
    {
        $state ??= $this->current();

        if (! $state) {
            return ['allowed' => false, 'mode' => 'unactivated', 'reason' => 'LICENSE_REQUIRED'];
        }

        if ($this->clockMovedBackwards($state)) {
            if ($this->attemptOnlineRecovery($state)) {
                $state = $this->current();

                if ($state && $this->isOnlineValid($state)) {
                    return ['allowed' => true, 'mode' => 'online', 'reason' => 'LICENSE_ACTIVE'];
                }
            }

            $state->forceFill([
                'reason_code' => 'CLOCK_SKEW_DETECTED',
                'last_seen_system_time' => now(),
            ])->save();

            return ['allowed' => false, 'mode' => 'blocked', 'reason' => 'CLOCK_SKEW_DETECTED'];
        }

        if ($state->reason_code === 'CLOCK_SKEW_DETECTED') {
            if ($this->attemptOnlineRecovery($state)) {
                $state = $this->current();

                if ($state && $this->isOnlineValid($state)) {
                    return ['allowed' => true, 'mode' => 'online', 'reason' => 'LICENSE_ACTIVE'];
                }
            }

            return ['allowed' => false, 'mode' => 'blocked', 'reason' => 'CLOCK_SKEW_DETECTED'];
        }

        $state->forceFill(['last_seen_system_time' => now()])->save();

        if ($this->isOnlineValid($state)) {
            return ['allowed' => true, 'mode' => 'online', 'reason' => 'LICENSE_ACTIVE'];
        }

        if ($this->canUseOfflineGrace($state)) {
            return ['allowed' => true, 'mode' => 'offline_grace', 'reason' => 'OFFLINE_GRACE_ACTIVE'];
        }

        return ['allowed' => false, 'mode' => 'blocked', 'reason' => $state->reason_code ?: 'LICENSE_INVALID'];
    }

    private function persistResult(LicenseApiResult $result, ?string $locationCode, ?string $domain): LicenseState
    {
        $existing = $this->current();
        $valid = $this->isValidResult($result);
        $now = now();

        $state = LicenseState::query()->updateOrCreate(
            ['device_fingerprint' => $this->deviceFingerprint()],
            [
                'project_code' => (string) config('licensing.project_code'),
                'license_key' => $result->licenseKey ?: $existing?->license_key,
                'device_name' => (string) config('licensing.device_name'),
                'device_type' => (string) config('licensing.device_type'),
                'client_location_code' => $locationCode ?: $existing?->client_location_code ?: (string) config('licensing.default_location_code'),
                'domain' => $domain ?: $existing?->domain,
                'app_version' => (string) config('licensing.app_version'),
                'status' => $result->status ?: ($valid ? 'active' : ($existing?->status ?: 'invalid')),
                'reason_code' => $result->reasonCode,
                'message' => $result->message,
                'expires_at' => $result->expiresAt ? CarbonImmutable::parse($result->expiresAt) : $existing?->expires_at,
                'last_validation_success' => $valid,
                'last_validation_at' => $valid ? $now : $existing?->last_validation_at,
                'last_server_time' => $result->serverTime ? CarbonImmutable::parse($result->serverTime) : $existing?->last_server_time,
                'last_seen_system_time' => $now,
                'offline_grace_expires_at' => $valid ? $this->calculateOfflineGraceExpiration($result) : $existing?->offline_grace_expires_at,
                'features' => $result->features ?: $existing?->features,
                'limits' => $result->limits ?: $existing?->limits,
                'metadata' => $result->metadata ?: $existing?->metadata,
                'client' => $result->client ?: $existing?->client,
                'location' => $result->location ?: $existing?->location,
                'is_active' => true,
            ]
        );

        LicenseState::query()
            ->whereKeyNot($state->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return $state;
    }

    private function isValidResult(LicenseApiResult $result): bool
    {
        return $result->success
            && $result->valid
            && in_array($result->reasonCode, self::VALID_ACTIVE_CODES, true)
            && ! in_array($result->reasonCode, self::HARD_BLOCK_CODES, true);
    }

    private function isOnlineValid(LicenseState $state): bool
    {
        return $state->last_validation_success
            && $state->status === 'active'
            && ! in_array($state->reason_code, self::HARD_BLOCK_CODES, true)
            && (! $state->expires_at || $state->expires_at->isFuture());
    }

    private function canUseOfflineGrace(LicenseState $state): bool
    {
        return $state->last_validation_success
            && $state->status === 'active'
            && $this->features->enabled('offline_mode', $state)
            && (! $state->expires_at || $state->expires_at->isFuture())
            && $state->offline_grace_expires_at
            && $state->offline_grace_expires_at->isFuture();
    }

    private function clockMovedBackwards(LicenseState $state): bool
    {
        return $state->last_seen_system_time !== null
            && now()->lt($state->last_seen_system_time->subMinutes(5));
    }

    private function attemptOnlineRecovery(LicenseState $state): bool
    {
        $result = $this->validateCurrent($state->domain);

        return $this->isValidResult($result);
    }

    private function calculateOfflineGraceExpiration(LicenseApiResult $result): ?CarbonImmutable
    {
        $offlineMode = (bool) data_get($result->features, 'offline_mode', false);

        if (! $offlineMode) {
            return null;
        }

        $hours = (int) data_get($result->limits, 'offline_grace_hours', 0);

        if ($hours <= 0) {
            return null;
        }

        $reference = $result->serverTime ? CarbonImmutable::parse($result->serverTime) : CarbonImmutable::now();

        return $reference->addHours($hours);
    }

    private function logResult(string $eventType, LicenseApiResult $result, ?LicenseState $state): void
    {
        LicenseValidationLog::query()->create([
            'license_state_id' => $state?->id,
            'event_type' => $eventType,
            'project_code' => (string) config('licensing.project_code'),
            'license_key' => $result->licenseKey ?: $state?->license_key,
            'reason_code' => $result->reasonCode,
            'success' => $result->success,
            'valid' => $result->valid,
            'http_status' => $result->httpStatus,
            'message' => $result->message,
            'response_snapshot' => $result->payload,
            'error_message' => $result->errorMessage,
        ]);
    }
}
