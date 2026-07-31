<?php

namespace Database\Factories;

use App\Models\Lot;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lot>
 */
class LotFactory extends Factory
{
    protected $model = Lot::class;

    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 10, 500);

        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'lot_number' => 'LOT-'.fake()->unique()->numerify('#####'),
            'quantity' => $qty,
            'remaining' => $qty,
            'manufactured_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'expires_at' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'status' => 'open',
        ];
    }

    public function expiringSoon(): static
    {
        return $this->state(fn () => [
            'expires_at' => fake()->dateTimeBetween('+7 days', '+90 days')->format('Y-m-d'),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => fake()->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }
}
