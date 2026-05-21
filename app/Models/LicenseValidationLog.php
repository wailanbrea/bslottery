<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseValidationLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'license_state_id',
        'event_type',
        'project_code',
        'license_key',
        'reason_code',
        'success',
        'valid',
        'http_status',
        'message',
        'response_snapshot',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'valid' => 'boolean',
            'response_snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function licenseState(): BelongsTo
    {
        return $this->belongsTo(LicenseState::class);
    }
}
