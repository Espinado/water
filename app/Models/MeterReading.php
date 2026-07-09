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
        'cold_consumption_m3',
        'hot_consumption_m3',
        'cold_price_per_m3',
        'hot_price_per_m3',
        'cold_cost',
        'hot_cost',
        'total_water_cost',
        'recorded_by_user_id',
        'entered_by_manager',
    ];

    protected function casts(): array
    {
        return [
            'cold_m3' => 'decimal:3',
            'hot_m3' => 'decimal:3',
            'cold_consumption_m3' => 'decimal:3',
            'hot_consumption_m3' => 'decimal:3',
            'cold_price_per_m3' => 'decimal:2',
            'hot_price_per_m3' => 'decimal:2',
            'cold_cost' => 'decimal:2',
            'hot_cost' => 'decimal:2',
            'total_water_cost' => 'decimal:2',
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
