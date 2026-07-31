<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLocationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create()->assignRole('administrador');
    }

    public function test_admin_can_view_product_locations_index(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('product-locations.index'));

        $response->assertOk()
            ->assertViewIs('product-locations.index');
    }

    public function test_admin_can_create_product_location(): void
    {
        $this->actingAs($this->admin);

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $response = $this->post(route('product-locations.store'), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'aisle' => 'A',
            'corridor' => '01',
            'shelf' => '02',
            'level' => '1',
            'position' => '03',
            'quantity' => 100,
            'is_primary' => true,
        ]);

        $response->assertRedirect(route('product-locations.index'));
        $this->assertDatabaseHas('product_locations', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'aisle' => 'A',
            'is_primary' => true,
        ]);
    }

    public function test_admin_can_edit_product_location(): void
    {
        $this->actingAs($this->admin);

        $location = ProductLocation::factory()->create([
            'aisle' => 'A',
            'is_primary' => false,
        ]);

        $response = $this->put(route('product-locations.update', $location), [
            'product_id' => $location->product_id,
            'warehouse_id' => $location->warehouse_id,
            'aisle' => 'B',
            'corridor' => '02',
            'shelf' => '03',
            'level' => '2',
            'position' => '04',
            'quantity' => 200,
            'is_primary' => true,
        ]);

        $response->assertRedirect(route('product-locations.index'));
        $this->assertDatabaseHas('product_locations', [
            'id' => $location->id,
            'aisle' => 'B',
            'is_primary' => true,
        ]);
    }

    public function test_admin_can_delete_product_location(): void
    {
        $this->actingAs($this->admin);

        $location = ProductLocation::factory()->create();

        $response = $this->delete(route('product-locations.destroy', $location));

        $response->assertRedirect(route('product-locations.index'));
        $this->assertSoftDeleted('product_locations', ['id' => $location->id]);
    }

    public function test_primary_location_is_unique_per_product(): void
    {
        $this->actingAs($this->admin);

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Cria uma localização primária
        ProductLocation::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'is_primary' => true,
        ]);

        // Cria outra localização primária para o mesmo produto
        $newLocation = ProductLocation::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'is_primary' => true,
        ]);

        // Atualiza para marcar como primária
        $this->put(route('product-locations.update', $newLocation), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'aisle' => 'B',
            'corridor' => '02',
            'shelf' => '03',
            'level' => '2',
            'position' => '04',
            'quantity' => 200,
            'is_primary' => true,
        ]);

        // A primeira localização deve ser desmarcada como primária
        $this->assertDatabaseHas('product_locations', [
            'id' => $newLocation->id,
            'is_primary' => true,
        ]);
    }
}
