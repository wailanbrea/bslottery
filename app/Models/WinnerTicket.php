<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WinnerTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'branch_id', 'ticket_id', 'ticket_detail_id',
        'lottery_id', 'draw_id', 'bet_type_id', 'number_value',
        'matched_position', 'amount_played', 'payout_multiplier',
        'prize_amount', 'status', 'paid_at', 'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_played' => 'decimal:2',
            'payout_multiplier' => 'decimal:2',
            'prize_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function ticketDetail(): BelongsTo { return $this->belongsTo(TicketDetail::class); }
    public function draw(): BelongsTo { return $this->belongsTo(Draw::class); }
    public function betType(): BelongsTo { return $this->belongsTo(BetType::class); }
    public function prizePayments(): HasMany { return $this->hasMany(PrizePayment::class); }
}
