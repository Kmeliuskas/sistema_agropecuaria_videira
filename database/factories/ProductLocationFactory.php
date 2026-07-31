<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductLocation;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductLocation>
 */
class ProductLocationFactory extends Factory
{
    protected $model = ProductLocation::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::inRandomOrder()->first()?->id ?? Product::factory(),
            'warehouse_id' => Warehouse::inRandomOrder()->first()?->id ?? Warehouse::factory(),
            'aisle' => $this->faker->randomElement(['A', 'B', 'C', null]),
            'corridor' => $this->faker->randomElement(['01', '02', '03', null]),
            'shelf' => $this->faker->randomElement(['01', '02', '03', null]),
            'level' => $this->faker->randomElement(['1', '2', '3', null]),
            'position' => $this->faker->randomElement(['01', '02', '03', null]),
            'quantity' => $this->faker->randomFloat(4, 0, 1000),
            'is_primary' => $this->faker->boolean(30),
        ];
    }
}
