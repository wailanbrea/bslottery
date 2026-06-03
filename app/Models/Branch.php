<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'external_code',
        'code',
        'name',
        'phone',
        'address',
        'manager_name',
        'status',
        'can_sell_online',
        'can_sell_offline',
        'offline_max_minutes',
        'offline_total_limit',
        'cash_control_enabled',
        'accounting_enabled',
        'payroll_enabled',
        'default_printer_id',
        'connector_enroll_token',
    ];

    protected function casts(): array
    {
        return [
            'can_sell_online' => 'boolean',
            'can_sell_offline' => 'boolean',
            'cash_control_enabled' => 'boolean',
            'accounting_enabled' => 'boolean',
            'payroll_enabled' => 'boolean',
            'offline_total_limit' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Branch $branch): void {
            $branch->uuid ??= (string) Str::uuid();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
