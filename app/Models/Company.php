<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'external_code',
        'name',
        'legal_name',
        'rnc',
        'phone',
        'email',
        'address',
        'logo_path',
        'status',
        'timezone',
        'currency',
        'big_prize_threshold',
    ];

    protected function casts(): array
    {
        return [
            'big_prize_threshold' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            $company->uuid ??= (string) Str::uuid();
        });
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
