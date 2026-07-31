<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('UN-###'),
            'name' => fake()->word(),
            'symbol' => fake()->randomLetter(),
            'is_active' => true,
        ];
    }
}
