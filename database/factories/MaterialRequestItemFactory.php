<?php

namespace Database\Factories;

use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialRequestItem>
 */
class MaterialRequestItemFactory extends Factory
{
    protected $model = MaterialRequestItem::class;

    public function definition(): array
    {
        return [
            'material_request_id' => MaterialRequest::factory(),
            'product_id' => Product::factory(),
            'quantity_requested' => fake()->randomFloat(2, 1, 50),
            'quantity_approved' => 0,
            'quantity_delivered' => 0,
            'warehouse_id' => Warehouse::factory(),
            'observation' => null,
        ];
    }
}
