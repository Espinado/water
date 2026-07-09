<?php

namespace App\Models;

use Database\Factories\ServiceProviderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProvider extends Model
{
    /** @use HasFactory<ServiceProviderFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(ProviderServiceRate::class);
    }

    public function rateFor(string $serviceCode): ?ProviderServiceRate
    {
        return $this->rates()->where('service_code', $serviceCode)->first();
    }
}
