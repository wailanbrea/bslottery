<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoan extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'approved_by',
        'principal',
        'balance',
        'installment',
        'status',
        'notes',
        'started_at',
    ];

    protected function casts(): array
    {
        return [
            'principal'   => 'decimal:2',
            'balance'     => 'decimal:2',
            'installment' => 'decimal:2',
            'started_at'  => 'date',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE' && $this->balance > 0;
    }

    /** Returns amount to deduct this period (min of installment and remaining balance). */
    public function currentInstallment(): string
    {
        return min((float) $this->installment, (float) $this->balance);
    }
}
