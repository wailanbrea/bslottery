<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrizePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'branch_id', 'ticket_id', 'winner_ticket_id',
        'cash_session_id', 'amount', 'paid_by', 'paid_at', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function winnerTicket(): BelongsTo { return $this->belongsTo(WinnerTicket::class); }
    public function cashSession(): BelongsTo { return $this->belongsTo(CashSession::class); }
    public function paidBy(): BelongsTo { return $this->belongsTo(User::class, 'paid_by'); }
}
