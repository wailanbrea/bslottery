<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class CashSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'branch_id',
        'user_id',
        'opened_by',
        'closed_by',
        'opening_amount',
        'expected_cash',
        'counted_cash',
        'sales_total',
        'cancellations_total',
        'prizes_paid_total',
        'cash_in_total',
        'cash_out_total',
        'expenses_total',
        'shortage_amount',
        'surplus_amount',
        'status',
        'active_lock',
        'opened_at',
        'closed_at',
        'confirmed_by',
        'confirmed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_amount' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'sales_total' => 'decimal:2',
            'cancellations_total' => 'decimal:2',
            'prizes_paid_total' => 'decimal:2',
            'cash_in_total' => 'decimal:2',
            'cash_out_total' => 'decimal:2',
            'expenses_total' => 'decimal:2',
            'shortage_amount' => 'decimal:2',
            'surplus_amount' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CashSession $session): void {
            $session->uuid ??= (string) Str::uuid();
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

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function reconciliation(): HasOne
    {
        return $this->hasOne(CashReconciliation::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(CashIncident::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function isOpen(): bool
    {
        // REOPENED tambien es una caja "viva": un cierre reabierto debe poder recibir movimientos
        // de correccion y volver a cerrarse. Sin esto la caja reabierta quedaba huerfana (no se
        // podia mover ni re-cerrar desde el flujo normal de caja).
        return in_array($this->status, ['OPEN', 'REOPENED'], true);
    }

    public function recalculateExpectedCash(): void
    {
        $this->expected_cash = $this->opening_amount
            + $this->sales_total
            + $this->cash_in_total
            - $this->cancellations_total
            - $this->prizes_paid_total
            - $this->cash_out_total
            - $this->expenses_total;
    }
}
