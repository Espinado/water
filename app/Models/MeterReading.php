<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReading extends Model
{
    protected $fillable = [
        'apartment_id',
        'year',
        'month',
        'cold_m3',
        'hot_m3',
        'recorded_by_user_id',
        'entered_by_manager',
    ];

    protected function casts(): array
    {
        return [
            'cold_m3' => 'decimal:3',
            'hot_m3' => 'decimal:3',
            'entered_by_manager' => 'boolean',
        ];
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function periodLabel(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}
