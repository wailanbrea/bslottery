<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TicketDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'company_id',
        'branch_id',
        'lottery_id',
        'draw_id',
        'transferred_from_draw_id',
        'transferred_at',
        'transferred_by',
        'bet_type_id',
        'number_value',
        'normalized_number',
        'amount',
        'payout_rule_id',
        'payout_multiplier',
        'possible_prize',
        'limit_rule_id',
        'result_position',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payout_multiplier' => 'decimal:2',
            'possible_prize' => 'decimal:2',
            'transferred_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
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

    public function transferredFromDraw(): BelongsTo
    {
        return $this->belongsTo(Draw::class, 'transferred_from_draw_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function betType(): BelongsTo
    {
        return $this->belongsTo(BetType::class);
    }

    public function payoutRule(): BelongsTo
    {
        return $this->belongsTo(PayoutRule::class);
    }

    public function limitRule(): BelongsTo
    {
        return $this->belongsTo(LimitRule::class);
    }

    public function winnerTicket(): HasOne
    {
        return $this->hasOne(WinnerTicket::class);
    }
}
