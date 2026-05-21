<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchMonitoringSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'alert_enabled',
        'loss_threshold',
        'minimum_expected_cash',
        'top_play_alert_amount',
    ];

    protected function casts(): array
    {
        return [
            'alert_enabled' => 'boolean',
            'loss_threshold' => 'decimal:2',
            'minimum_expected_cash' => 'decimal:2',
            'top_play_alert_amount' => 'decimal:2',
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
}
