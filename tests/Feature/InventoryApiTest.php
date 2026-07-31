<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Movement;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Database\Factories\UserFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobre Inventário: criação (gera itens por modalidade), contagem com
 * diferença e ajuste automático de estoque no finalize.
 */
class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = UserFactory::new()->create(['is_active' => true]);
        $this->admin->assignRole('administrador');
    }

    public function test_create_general_inventory_generates_items(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['current_stock' => 30, 'warehouse_id' => $warehouse->id]);
        StockBalance::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'current' => 30, 'available' => 30,
            'reserved' => 0, 'blocked' => 0, 'in_conferencia' => 0, 'in_transit' => 0,
        ]);

        $this->actingAs($this->admin, 'sanctum');
        $res = $this->postJson('/api/v1/inventories', [
            'type' => 'general',
            'warehouse_id' => $warehouse->id,
        ]);
        $res->assertStatus(201);
        $id = $res->json('data.id');
        $this->assertDatabaseHas('inventory_items', [
            'inventory_id' => $id,
            'product_id' => $product->id,
            'book_quantity' => '30.0000',
        ]);
    }

    public function test_count_and_finalize_applies_auto_adjustment(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['current_stock' => 30, 'warehouse_id' => $warehouse->id]);
        StockBalance::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'current' => 30, 'available' => 30,
            'reserved' => 0, 'blocked' => 0, 'in_conferencia' => 0, 'in_transit' => 0,
        ]);

        $this->actingAs($this->admin, 'sanctum');

        $inv = $this->postJson('/api/v1/inventories', [
            'type' => 'general',
            'warehouse_id' => $warehouse->id,
        ])->json('data.id');

        $this->postJson("/api/v1/inventories/{$inv}/start")->assertStatus(200);

        // Conta 25 (book 30) -> diferença -5
        $this->postJson("/api/v1/inventories/{$inv}/count", [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'counted_quantity' => 25,
        ])->assertStatus(200)->assertJsonPath('data.difference', '-5.0000');

        $this->postJson("/api/v1/inventories/{$inv}/finalize")->assertStatus(200)->assertJsonPath('data.status', 'finalized');

        $product->refresh();
        $this->assertEquals(25, (float) $product->current_stock);
        $this->assertDatabaseHas('movements', [
            'product_id' => $product->id,
            'type' => 'adjust',
            'source_type' => 'inventory',
        ]);
    }

    public function test_cannot_count_before_start(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['warehouse_id' => $warehouse->id]);
        $inv = Inventory::create(['code' => 'INV-T', 'type' => 'general', 'status' => 'draft', 'responsible_id' => $this->admin->id]);

        $this->actingAs($this->admin, 'sanctum');
        $this->postJson("/api/v1/inventories/{$inv->id}/count", [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'counted_quantity' => 10,
        ])->assertStatus(422);
    }
}
