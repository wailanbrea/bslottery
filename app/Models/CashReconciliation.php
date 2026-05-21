<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'cash_session_id',
        'closed_by',
        'opening_amount',
        'cash_sales_total',
        'transfer_sales_total',
        'cash_prizes_total',
        'transfer_prizes_total',
        'cash_in_total',
        'cash_out_total',
        'expenses_total',
        'cancellations_total',
        'expected_cash',
        'counted_cash',
        'difference_amount',
        'shortage_amount',
        'surplus_amount',
        'status',
        'notes',
        'closed_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_amount' => 'decimal:2',
            'cash_sales_total' => 'decimal:2',
            'transfer_sales_total' => 'decimal:2',
            'cash_prizes_total' => 'decimal:2',
            'transfer_prizes_total' => 'decimal:2',
            'cash_in_total' => 'decimal:2',
            'cash_out_total' => 'decimal:2',
            'expenses_total' => 'decimal:2',
            'cancellations_total' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'shortage_amount' => 'decimal:2',
            'surplus_amount' => 'decimal:2',
            'closed_at' => 'datetime',
            'reviewed_at' => 'datetime',
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

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function denominations(): HasMany
    {
        return $this->hasMany(CashCountDenomination::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(CashIncident::class);
    }
}
