<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    protected $fillable = [
        'company_id',
        'key',
        'value',
        'type',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function getBoolean(int $companyId, string $key, bool $default = false): bool
    {
        $setting = self::query()
            ->where('company_id', $companyId)
            ->where('key', $key)
            ->first();

        if (! $setting) {
            return $default;
        }

        return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function setBoolean(int $companyId, string $key, bool $value): self
    {
        return self::query()->updateOrCreate(
            [
                'company_id' => $companyId,
                'key' => $key,
            ],
            [
                'value' => $value ? '1' : '0',
                'type' => 'boolean',
            ],
        );
    }
}
