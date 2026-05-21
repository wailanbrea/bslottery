<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Draw extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'lottery_id',
        'name',
        'draw_date',
        'open_time',
        'scheduled_time',
        'close_time',
        'closed_at',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'ticket_resolution_policy',
        'transferred_to_draw_id',
        'status',
        'last_ticket_at',
        'result_registered_at',
        'result_confirmed_at',
        'winners_calculated_at',
        'payments_released_at',
    ];

    protected function casts(): array
    {
        return [
            'draw_date' => 'date',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'last_ticket_at' => 'datetime',
            'result_registered_at' => 'datetime',
            'result_confirmed_at' => 'datetime',
            'winners_calculated_at' => 'datetime',
            'payments_released_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Draw $draw): void {
            $draw->uuid ??= (string) Str::uuid();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lottery(): BelongsTo
    {
        return $this->belongsTo(Lottery::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function transferredToDraw(): BelongsTo
    {
        return $this->belongsTo(self::class, 'transferred_to_draw_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(Result::class);
    }

    public function ticketDetails(): HasMany
    {
        return $this->hasMany(TicketDetail::class);
    }

    public function winnerTickets(): HasMany
    {
        return $this->hasMany(WinnerTicket::class);
    }

    public function limitConsumptions(): HasMany
    {
        return $this->hasMany(LimitConsumption::class);
    }

    public function payoutRules(): HasMany
    {
        return $this->hasMany(PayoutRule::class);
    }

    public function limitRules(): HasMany
    {
        return $this->hasMany(LimitRule::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'OPEN';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['CLOSED', 'RESULT_PENDING', 'RESULT_REGISTERED', 'RESULT_CONFIRMED', 'CALCULATING_WINNERS', 'WINNERS_CALCULATED', 'PAYMENTS_RELEASED', 'FINALIZED'], true);
    }
}
