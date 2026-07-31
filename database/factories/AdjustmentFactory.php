<?php

namespace Database\Factories;

use App\Models\Adjustment;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Adjustment>
 */
class AdjustmentFactory extends Factory
{
    protected $model = Adjustment::class;

    public function definition(): array
    {
        return [
            'code' => 'AD-'.fake()->unique()->numerify('####'),
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'reason' => fake()->randomElement(['erro', 'quebra', 'perda', 'roubo', 'vencimento', 'correcao']),
            'quantity' => fake()->randomFloat(4, -20, 20),
            'balance_before' => 0,
            'balance_after' => 0,
            'user_id' => User::factory(),
            'observation' => null,
        ];
    }

    public function loss(): static
    {
        return $this->state(fn () => ['quantity' => fake()->randomFloat(4, 1, 20) * -1]);
    }

    public function gain(): static
    {
        return $this->state(fn () => ['quantity' => fake()->randomFloat(4, 1, 20)]);
    }
}
