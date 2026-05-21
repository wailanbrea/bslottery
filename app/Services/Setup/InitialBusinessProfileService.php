<?php

declare(strict_types=1);

namespace App\Services\Setup;

use App\Models\LicenseState;

class InitialBusinessProfileService
{
    /**
     * @return array{
     *     company: array{name: string, legal_name: ?string, external_code: ?string, rnc: ?string, phone: ?string, address: ?string},
     *     branch: array{name: string, code: string, external_code: ?string, phone: ?string, address: ?string},
     *     missing: array<int, string>
     * }
     */
    public function fromLicense(LicenseState $state): array
    {
        $metadata = $state->metadata ?? [];
        $missing = [];

        $companyName = data_get($metadata, 'trade_name')
            ?: data_get($metadata, 'company_name')
            ?: data_get($metadata, 'customer_name');

        if (! $companyName) {
            $companyName = 'Empresa pendiente de configuración';
            $missing[] = 'company_name';
        }

        $branchName = data_get($metadata, 'branch_name');

        if (! $branchName) {
            $branchName = 'Sucursal Principal Pendiente de Configuración';
            $missing[] = 'branch_name';
        }

        return [
            'company' => [
                'name' => (string) $companyName,
                'legal_name' => data_get($metadata, 'company_name'),
                'external_code' => data_get($metadata, 'company_id'),
                'rnc' => data_get($metadata, 'rnc'),
                'phone' => data_get($metadata, 'phone'),
                'address' => data_get($metadata, 'address'),
            ],
            'branch' => [
                'name' => (string) $branchName,
                'code' => (string) (data_get($metadata, 'branch_id') ?: $state->client_location_code),
                'external_code' => data_get($metadata, 'branch_id'),
                'phone' => data_get($metadata, 'phone'),
                'address' => data_get($metadata, 'address'),
            ],
            'missing' => $missing,
        ];
    }
}
