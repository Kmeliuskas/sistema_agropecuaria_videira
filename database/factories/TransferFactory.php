<?php

namespace Database\Factories;

use App\Models\Transfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    public function definition(): array
    {
        return [
            'code' => 'TR-'.fake()->unique()->numerify('####'),
            'origin_warehouse_id' => Warehouse::factory(),
            'destination_warehouse_id' => Warehouse::factory(),
            'status' => 'pending',
            'requester_id' => User::factory(),
            'observation' => null,
        ];
    }

    public function inTransit(): static
    {
        return $this->state(fn () => ['status' => 'in_transit']);
    }
}
