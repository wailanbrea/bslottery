<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineLimitAllocation extends Model
{
    protected $fillable = [
        'offline_session_id', 'company_id', 'branch_id',
        'lottery_id', 'draw_id', 'bet_type_id', 'number_value',
        'allocated_amount', 'used_amount',
    ];

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'used_amount'      => 'decimal:2',
        ];
    }

    public function offlineSession(): BelongsTo { return $this->belongsTo(OfflineSession::class); }
    public function lottery(): BelongsTo        { return $this->belongsTo(Lottery::class); }
    public function draw(): BelongsTo           { return $this->belongsTo(Draw::class); }
    public function betType(): BelongsTo        { return $this->belongsTo(BetType::class); }
}
