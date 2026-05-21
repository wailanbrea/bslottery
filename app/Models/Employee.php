<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'branch_id',
        'user_id',
        'name',
        'document_number',
        'phone',
        'address',
        'position',
        'salary_type',
        'base_salary',
        'commission_percent',
        'status',
        'hired_at',
        'terminated_at',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'hired_at' => 'date',
            'terminated_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee): void {
            $employee->uuid ??= (string) Str::uuid();
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
}
