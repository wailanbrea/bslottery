<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'branch_id',
        'user_id',
        'device_id',
        'cash_session_id',
        'ticket_number',
        'external_offline_number',
        'sale_mode',
        'total_amount',
        'total_possible_prize',
        'status',
        'printed_at',
        'print_count',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'paid_at',
        'idempotency_key',
        'sold_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'total_possible_prize' => 'decimal:2',
            'printed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
            'sold_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            $ticket->uuid ??= (string) Str::uuid();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(TicketDetail::class);
    }

    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }

    public function winnerTickets(): HasMany
    {
        return $this->hasMany(WinnerTicket::class);
    }

    public function prizePayments(): HasMany
    {
        return $this->hasMany(PrizePayment::class);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['ACTIVE'], true);
    }

    public function isReprintable(): bool
    {
        return in_array($this->status, ['ACTIVE', 'WINNER', 'LOSER', 'PARTIALLY_PAID', 'PAID'], true);
    }
}
