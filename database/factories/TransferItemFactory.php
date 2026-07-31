<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Transfer;
use App\Models\TransferItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransferItem>
 */
class TransferItemFactory extends Factory
{
    protected $model = TransferItem::class;

    public function definition(): array
    {
        return [
            'transfer_id' => Transfer::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->randomFloat(4, 1, 50),
            'quantity_received' => 0,
            'observation' => null,
        ];
    }
}
