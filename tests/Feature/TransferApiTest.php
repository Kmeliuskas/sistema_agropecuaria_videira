<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Fluxo de Transferência entre almoxarifados:
 *  - cria (pending)
 *  - ship debita origem (TRANSFER_OUT)
 *  - receive credita destino (TRANSFER_IN) e fecha
 */
class TransferApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        Artisan::call('db:seed', ['--class' => 'AdminUserSeeder']);
    }

    private function authAdmin(): User
    {
        $user = User::where('email', 'admin@wms.local')->firstOrFail();
        $user->assignRole('administrador');
        $this->actingAs($user);

        return $user;
    }

    public function test_guest_cannot_list_transfers(): void
    {
        $this->getJson('/api/v1/transfers')->assertStatus(401);
    }

    public function test_create_transfer_consumes_origin_on_ship(): void
    {
        $this->authAdmin();
        $origin = Warehouse::factory()->create();
        $dest = Warehouse::factory()->create();
        $product = Product::factory()->create(['current_stock' => 100, 'average_cost' => 10, 'last_cost' => 10]);
        StockBalance::create(['product_id' => $product->id, 'warehouse_id' => $origin->id, 'current' => 100, 'available' => 100]);
        StockBalance::create(['product_id' => $product->id, 'warehouse_id' => $dest->id, 'current' => 0, 'available' => 0]);

        $create = $this->postJson('/api/v1/transfers', [
            'origin_warehouse_id' => $origin->id,
            'destination_warehouse_id' => $dest->id,
            'items' => [['product_id' => $product->id, 'quantity' => 30]],
        ])->assertStatus(201)->json('data');

        $id = $create['id'];
        $this->assertSame('pending', $create['status']);

        $shipped = $this->postJson("/api/v1/transfers/{$id}/ship")->assertStatus(200)->json('data');
        $this->assertSame('in_transit', $shipped['status']);

        // Origem deve ter debitado 30 (100 -> 70)
        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'warehouse_id' => $origin->id,
            'current' => '70.0000',
        ]);
        $this->assertDatabaseHas('movements', [
            'product_id' => $product->id,
            'type' => 'transfer_out',
            'warehouse_id' => $origin->id,
            'quantity' => '30.0000',
        ]);
        // Destino ainda não recebeu
        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'warehouse_id' => $dest->id,
            'current' => '0.0000',
        ]);
    }

    public function test_receive_credits_destination_and_closes(): void
    {
        $this->authAdmin();
        $origin = Warehouse::factory()->create();
        $dest = Warehouse::factory()->create();
        $product = Product::factory()->create(['current_stock' => 100, 'average_cost' => 10, 'last_cost' => 10]);
        StockBalance::create(['product_id' => $product->id, 'warehouse_id' => $origin->id, 'current' => 100, 'available' => 100]);
        StockBalance::create(['product_id' => $product->id, 'warehouse_id' => $dest->id, 'current' => 0, 'available' => 0]);

        $id = $this->postJson('/api/v1/transfers', [
            'origin_warehouse_id' => $origin->id,
            'destination_warehouse_id' => $dest->id,
            'items' => [['product_id' => $product->id, 'quantity' => 30]],
        ])->json('data.id');

        $this->postJson("/api/v1/transfers/{$id}/ship")->assertStatus(200);

        $received = $this->postJson("/api/v1/transfers/{$id}/receive")->assertStatus(200)->json('data');
        $this->assertSame('received', $received['status']);
        $this->assertSame('30.0000', $received['items'][0]['quantity_received']);

        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'warehouse_id' => $dest->id,
            'current' => '30.0000',
        ]);
        $this->assertDatabaseHas('movements', [
            'product_id' => $product->id,
            'type' => 'transfer_in',
            'warehouse_id' => $dest->id,
            'quantity' => '30.0000',
        ]);
    }

    public function test_cannot_ship_from_same_warehouse(): void
    {
        $this->authAdmin();
        $wh = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $this->postJson('/api/v1/transfers', [
            'origin_warehouse_id' => $wh->id,
            'destination_warehouse_id' => $wh->id,
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ])->assertStatus(422);
    }

    public function test_cannot_receive_without_ship(): void
    {
        $this->authAdmin();
        $origin = Warehouse::factory()->create();
        $dest = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $id = $this->postJson('/api/v1/transfers', [
            'origin_warehouse_id' => $origin->id,
            'destination_warehouse_id' => $dest->id,
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ])->json('data.id');

        $this->postJson("/api/v1/transfers/{$id}/receive")->assertStatus(422);
    }
}
