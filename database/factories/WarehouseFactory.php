<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('WH-####'),
            'name' => fake()->company().' Almoxarifado',
            'description' => fake()->sentence(),
            'warehouse_type_id' => \App\Models\WarehouseType::inRandomOrder()->first()?->id,
            'is_active' => true,
            'is_default' => false,
        ];
    }
}
