<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoice extends Model
{
    protected $fillable = [
        'service_provider_id',
        'year',
        'month',
        'cold_m3',
        'cold_amount',
        'hot_m3',
        'hot_amount',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'cold_m3' => 'decimal:3',
            'cold_amount' => 'decimal:2',
            'hot_m3' => 'decimal:3',
            'hot_amount' => 'decimal:2',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
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
