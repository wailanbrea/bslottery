<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LimitRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'lottery_id',
        'draw_id',
        'bet_type_id',
        'rule_type',
        'scope',
        'number_value',
        'number_from',
        'number_to',
        'numbers_json',
        'max_amount_per_number',
        'max_total_amount',
        'policy',
        'effective_from',
        'effective_to',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'max_amount_per_number' => 'decimal:2',
            'max_total_amount' => 'decimal:2',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'approved_at' => 'datetime',
            'numbers_json' => 'array',
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
