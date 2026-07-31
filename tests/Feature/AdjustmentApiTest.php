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
 * Fluxo de Ajuste de estoque (6 motivos):
 *  - perda (quantity < 0) consome estoque via ADJUST
 *  - ganho (quantity > 0) acrescenta estoque via ADJUST
 */
class AdjustmentApiTest extends TestCase
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

    public function test_guest_cannot_create_adjustment(): void
    {
        $this->postJson('/api/v1/adjustments', [])->assertStatus(401);
    }

    public function test_loss_adjustment_consumes_stock(): void
    {
        $this->authAdmin();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['current_stock' => 100, 'average_cost' => 10, 'last_cost' => 10]);
        StockBalance::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'current' => 100, 'available' => 100]);

        $res = $this->postJson('/api/v1/adjustments', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'reason' => 'quebra',
            'quantity' => -25,
            'observation' => 'Queda na esteira',
        ])->assertStatus(201)->json('data');

        $this->assertSame('quebra', $res['reason']);
        $this->assertTrue($res['is_loss']);
        $this->assertSame('100.0000', $res['balance_before']);
        $this->assertSame('75.0000', $res['balance_after']);

        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'current' => '75.0000',
        ]);
        $this->assertDatabaseHas('movements', [
            'product_id' => $product->id,
            'type' => 'adjust',
            'warehouse_id' => $warehouse->id,
            'quantity' => '-25.0000',
        ]);
    }

    public function test_gain_adjustment_adds_stock(): void
    {
        $this->authAdmin();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['current_stock' => 50, 'average_cost' => 10, 'last_cost' => 10]);
        StockBalance::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'current' => 50, 'available' => 50]);

        $res = $this->postJson('/api/v1/adjustments', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'reason' => 'correcao',
            'quantity' => 20,
        ])->assertStatus(201)->json('data');

        $this->assertFalse($res['is_loss']);
        $this->assertSame('70.0000', $res['balance_after']);

        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'current' => '70.0000',
        ]);
    }

    public function test_invalid_reason_is_rejected(): void
    {
        $this->authAdmin();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $this->postJson('/api/v1/adjustments', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'reason' => 'inexistente',
            'quantity' => 5,
        ])->assertStatus(422);
    }
}
