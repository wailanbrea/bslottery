<?php

declare(strict_types=1);

namespace App\Services\Licensing;

use App\Models\LicenseState;

class LicenseFeatureManager
{
    public function enabled(string $feature, ?LicenseState $state = null): bool
    {
        $state ??= LicenseState::query()->where('is_active', true)->latest('id')->first();

        if (! $state) {
            return false;
        }

        return (bool) data_get($state->features ?? [], $feature, false);
    }
}
