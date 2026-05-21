<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseState extends Model
{
    protected $fillable = [
        'project_code',
        'license_key',
        'device_fingerprint',
        'device_name',
        'device_type',
        'client_location_code',
        'domain',
        'app_version',
        'status',
        'reason_code',
        'message',
        'expires_at',
        'last_validation_success',
        'last_validation_at',
        'last_server_time',
        'last_seen_system_time',
        'offline_grace_expires_at',
        'offline_launch_count',
        'offline_operation_count',
        'offline_sales_count',
        'features',
        'limits',
        'metadata',
        'client',
        'location',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_validation_success' => 'boolean',
            'last_validation_at' => 'datetime',
            'last_server_time' => 'datetime',
            'last_seen_system_time' => 'datetime',
            'offline_grace_expires_at' => 'datetime',
            'features' => 'array',
            'limits' => 'array',
            'metadata' => 'array',
            'client' => 'array',
            'location' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function validationLogs(): HasMany
    {
        return $this->hasMany(LicenseValidationLog::class);
    }
}
