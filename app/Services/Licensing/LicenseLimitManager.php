<?php

declare(strict_types=1);

namespace App\Services\Licensing;

use App\Models\LicenseState;

class LicenseLimitManager
{
    public function get(string $limit, int|float|string|null $default = null, ?LicenseState $state = null): int|float|string|null
    {
        $state ??= LicenseState::query()->where('is_active', true)->latest('id')->first();

        if (! $state) {
            return $default;
        }

        return data_get($state->limits ?? [], $limit, $default);
    }

    public function integer(string $limit, int $default = 0, ?LicenseState $state = null): int
    {
        return (int) $this->get($limit, $default, $state);
    }
}
