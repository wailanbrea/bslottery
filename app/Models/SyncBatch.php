<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SyncBatch extends Model
{
    protected $fillable = [
        'uuid', 'offline_session_id', 'company_id', 'branch_id', 'device_id',
        'status', 'submitted_at', 'processed_at',
        'total_tickets', 'accepted_tickets', 'rejected_tickets',
        'payload_hash', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at'  => 'datetime',
            'processed_at'  => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SyncBatch $b): void {
            $b->uuid ??= (string) Str::uuid();
        });
    }

    public function offlineSession(): BelongsTo { return $this->belongsTo(OfflineSession::class); }
    public function company(): BelongsTo        { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo         { return $this->belongsTo(Branch::class); }
    public function device(): BelongsTo         { return $this->belongsTo(Device::class); }
    public function conflicts(): HasMany        { return $this->hasMany(SyncConflict::class); }
}
