<?php

namespace App\Models;

use Database\Factories\ApartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends Model
{
    /** @use HasFactory<ApartmentFactory> */
    use HasFactory;

    protected $fillable = [
        'building_id',
        'number',
        'area_m2',
    ];

    protected function casts(): array
    {
        return [
            'area_m2' => 'decimal:2',
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function label(): string
    {
        $name = $this->relationLoaded('building')
            ? $this->building->name
            : ($this->building()->value('name') ?? '');

        return $name.' — кв. '.$this->number;
    }
}
