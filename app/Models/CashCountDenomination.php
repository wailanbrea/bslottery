<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashCountDenomination extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_reconciliation_id',
        'company_id',
        'branch_id',
        'type',
        'denomination',
        'quantity',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'denomination' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(CashReconciliation::class, 'cash_reconciliation_id');
    }
}
