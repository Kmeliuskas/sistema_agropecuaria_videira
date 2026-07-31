<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        $book = fake()->randomFloat(2, 10, 100);

        return [
            'inventory_id' => Inventory::factory(),
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'book_quantity' => $book,
            'counted_quantity' => null,
            'difference' => 0,
            'is_counted' => false,
        ];
    }
}
