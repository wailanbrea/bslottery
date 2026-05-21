<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id',
        'company_id',
        'branch_id',
        'employee_id',
        'base_salary',
        'commission',
        'bonus',
        'gross_pay',
        'advance_deduction',
        'loan_deduction',
        'cash_shortage',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_salary'      => 'decimal:2',
            'commission'       => 'decimal:2',
            'bonus'            => 'decimal:2',
            'gross_pay'        => 'decimal:2',
            'advance_deduction'=> 'decimal:2',
            'loan_deduction'   => 'decimal:2',
            'cash_shortage'    => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay'          => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
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
}
