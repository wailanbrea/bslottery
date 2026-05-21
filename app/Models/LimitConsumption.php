<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LimitConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'lottery_id',
        'draw_id',
        'bet_type_id',
        'number_value',
        'sold_amount',
        'reserved_offline_amount',
        'cancelled_amount',
    ];

    protected function casts(): array
    {
        return [
            'sold_amount' => 'decimal:2',
            'reserved_offline_amount' => 'decimal:2',
            'cancelled_amount' => 'decimal:2',
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
}
