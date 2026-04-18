<?php

namespace App\Models;

use Database\Factories\BuildingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
    ];

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class);
    }
}
