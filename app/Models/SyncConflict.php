<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncConflict extends Model
{
    protected $fillable = [
        'sync_batch_id', 'offline_session_id', 'company_id', 'branch_id',
        'conflict_type', 'status', 'ticket_data', 'conflict_reason',
        'resolution', 'resolved_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'ticket_data'  => 'array',
            'resolved_at'  => 'datetime',
        ];
    }

    public function syncBatch(): BelongsTo      { return $this->belongsTo(SyncBatch::class); }
    public function offlineSession(): BelongsTo { return $this->belongsTo(OfflineSession::class); }
    public function company(): BelongsTo        { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo         { return $this->belongsTo(Branch::class); }
    public function resolvedBy(): BelongsTo     { return $this->belongsTo(User::class, 'resolved_by'); }
}
