<?php

namespace Database\Factories;

use App\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceProvider>
 */
class ServiceProviderFactory extends Factory
{
    protected $model = ServiceProvider::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'code' => Str::upper(Str::slug($name, '_')),
            'name' => $name,
        ];
    }

    public function withWaterRates(float $cold = 4.55, float $hot = 4.55): static
    {
        return $this->afterCreating(function (ServiceProvider $provider) use ($cold, $hot): void {
            $provider->rates()->createMany([
                ['service_code' => 'water_cold', 'price' => $cold],
                ['service_code' => 'water_hot', 'price' => $hot],
            ]);
        });
    }
}
