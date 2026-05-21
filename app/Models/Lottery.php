<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lottery extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'country',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function draws(): HasMany
    {
        return $this->hasMany(Draw::class);
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
