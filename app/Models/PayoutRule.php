<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'lottery_id',
        'draw_id',
        'bet_type_id',
        'position',
        'match_type',
        'payout_multiplier',
        'effective_from',
        'effective_to',
        'inherit_from_parent',
        'requires_approval',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'payout_multiplier' => 'decimal:2',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'approved_at' => 'datetime',
            'inherit_from_parent' => 'boolean',
            'requires_approval' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lottery(): BelongsTo
    {
        return $this->belongsTo(Lottery::class);
    }

    public function draw(): BelongsTo
    {
        return $this->belongsTo(Draw::class);
    }

    public function betType(): BelongsTo
    {
        return $this->belongsTo(BetType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
