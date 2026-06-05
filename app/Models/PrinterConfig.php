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
        'is_default',
        'name',
        'printer_type',
        'connection_type',
        'paper_width',
        'printing_mode',
        'auto_cut',
        'show_logo',
        'show_qr',
        'show_phone',
        'show_address',
        'show_potential_prize',
        'footer_text',
        'open_cash_drawer',
        'print_copies',
        'printer_identifier',
        'status',
        'last_test_at',
    ];

    protected function casts(): array
    {
        return [
            'auto_cut' => 'boolean',
            'is_default' => 'boolean',
            'show_logo' => 'boolean',
            'show_qr' => 'boolean',
            'show_phone' => 'boolean',
            'show_address' => 'boolean',
            'show_potential_prize' => 'boolean',
            'open_cash_drawer' => 'boolean',
            'print_copies' => 'integer',
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
