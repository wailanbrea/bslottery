<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OfflineSession extends Model
{
    protected $fillable = [
        'uuid', 'company_id', 'branch_id', 'user_id', 'device_id',
        'status', 'opened_at', 'expires_at', 'closed_at',
        'allocated_tickets_limit', 'used_tickets_count', 'allocated_amount',
        'notes', 'authorized_by', 'authorized_at',
    ];

    protected function casts(): array
    {
        return [
            'opened_at'     => 'datetime',
            'expires_at'    => 'datetime',
            'closed_at'     => 'datetime',
            'authorized_at' => 'datetime',
            'allocated_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OfflineSession $s): void {
            $s->uuid ??= (string) Str::uuid();
        });
    }

    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function device(): BelongsTo    { return $this->belongsTo(Device::class); }
    public function authorizedBy(): BelongsTo { return $this->belongsTo(User::class, 'authorized_by'); }

    public function allocations(): HasMany { return $this->hasMany(OfflineLimitAllocation::class); }
    public function batches(): HasMany     { return $this->hasMany(SyncBatch::class); }
    public function conflicts(): HasMany   { return $this->hasMany(SyncConflict::class); }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
