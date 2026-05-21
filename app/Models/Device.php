<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'branch_id',
        'user_id',
        'name',
        'device_type',
        'platform',
        'device_fingerprint',
        'app_version',
        'status',
        'last_seen_at',
        'authorized_by',
        'authorized_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'authorized_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Device $device): void {
            $device->uuid ??= (string) Str::uuid();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
