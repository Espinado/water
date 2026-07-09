<?php

namespace App\Models;

use App\Enums\ServiceCalcType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'code';

    protected $fillable = [
        'code',
        'name_ru',
        'name_lv',
        'unit',
        'calc_type',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'calc_type' => ServiceCalcType::class,
            'sort_order' => 'integer',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ProviderServiceRate::class, 'service_code', 'code');
    }

    public function displayName(): string
    {
        return $this->name_ru;
    }

    public function unitLabel(): string
    {
        return match ($this->unit) {
            'm3' => '€/м³',
            'm2' => '€/м²',
            'pcs' => '€',
            default => '€',
        };
    }
}
