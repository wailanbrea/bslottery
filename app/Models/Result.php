<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'lottery_id',
        'draw_id',
        'first_number',
        'second_number',
        'third_number',
        'status',
        'registered_by',
        'registered_at',
        'confirmed_by',
        'confirmed_at',
        'confirmation_notes',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function lottery(): BelongsTo { return $this->belongsTo(Lottery::class); }
    public function draw(): BelongsTo { return $this->belongsTo(Draw::class); }
    public function registeredBy(): BelongsTo { return $this->belongsTo(User::class, 'registered_by'); }
    public function confirmedBy(): BelongsTo { return $this->belongsTo(User::class, 'confirmed_by'); }
}
