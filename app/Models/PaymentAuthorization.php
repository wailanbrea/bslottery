<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAuthorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'lottery_id', 'draw_id', 'status',
        'total_winners', 'total_prize_amount',
        'authorized_by', 'authorized_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_prize_amount' => 'decimal:2',
            'authorized_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function lottery(): BelongsTo { return $this->belongsTo(Lottery::class); }
    public function draw(): BelongsTo { return $this->belongsTo(Draw::class); }
    public function authorizedBy(): BelongsTo { return $this->belongsTo(User::class, 'authorized_by'); }
}
