<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'code' => 'INV-'.fake()->unique()->numerify('####'),
            'type' => 'general',
            'status' => 'draft',
            'description' => fake()->sentence(),
            'responsible_id' => User::factory(),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => 'in_progress']);
    }
}
