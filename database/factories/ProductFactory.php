<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'internal_code' => 'PROD-'.fake()->unique()->numerify('####'),
            'barcode' => fake()->ean13(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'brand_id' => Brand::factory(),
            'warehouse_id' => Warehouse::factory(),
            'min_stock' => fake()->randomFloat(2, 0, 10),
            'max_stock' => fake()->randomFloat(2, 50, 500),
            'current_stock' => fake()->randomFloat(2, 0, 100),
            'reserved_stock' => 0,
            'available_stock' => 0,
            'last_cost' => fake()->randomFloat(2, 1, 100),
            'average_cost' => fake()->randomFloat(2, 1, 100),
            'sale_price' => fake()->randomFloat(2, 1, 200),
            'ncm' => fake()->numerify('####.##.##'),
            'cfop' => '5102',
            'cst' => '000',
            'control_batch' => false,
            'control_expiry' => false,
            'serialized' => false,
            'active' => true,
        ];
    }
}
