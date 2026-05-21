<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BetType extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'digits_count',
        'min_numbers',
        'max_numbers',
        'requires_position',
        'numbers_count',
        'is_cross_lottery',
        'status',
        'max_amount',
    ];

    protected function casts(): array
    {
        return [
            'requires_position' => 'boolean',
            'is_cross_lottery' => 'boolean',
            'digits_count' => 'integer',
            'min_numbers' => 'integer',
            'max_numbers' => 'integer',
            'numbers_count' => 'integer',
            'max_amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payoutRules(): HasMany
    {
        return $this->hasMany(PayoutRule::class);
    }

    public function limitRules(): HasMany
    {
        return $this->hasMany(LimitRule::class);
    }

    public function ticketDetails(): HasMany
    {
        return $this->hasMany(TicketDetail::class);
    }
}
