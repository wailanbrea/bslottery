<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrinterConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'device_id',
        'terminal_key',
        'terminal_name',
        'name',
        'printer_type',
        'connection_type',
        'paper_width',
        'printing_mode',
        'auto_cut',
        'printer_identifier',
        'status',
        'last_test_at',
    ];

    protected function casts(): array
    {
        return [
            'auto_cut' => 'boolean',
            'last_test_at' => 'datetime',
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

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
