<?php

namespace Tests\Feature;

use App\Application\Services\StockService;
use App\Domain\Enums\MovementType;
use App\Models\Movement;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testa as regras centrais de estoque: Kardex, saldo consolidado,
 * saldo por almoxarifado, custo médio e disponível.
 */
class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stock = app(StockService::class);
        // usuário autenticado para auditoria/movement.user_id
        $this->actingAs(User::factory()->create());
    }

    public function test_entry_increases_stock_and_creates_kardex(): void
    {
        $product = Product::factory()->create(['current_stock' => 0]);
        $wh = Warehouse::factory()->create();

        $this->stock->apply([
            'product_id' => $product->id,
            'type' => MovementType::ENTRY->value,
            'quantity' => 50,
            'warehouse_id' => $wh->id,
            'unit_cost' => 10,
            'source_type' => 'purchase',
        ]);

        $product->refresh();
        $this->assertEquals(50, $product->current_stock);
        $this->assertEquals(50, $product->available_stock);
        $this->assertDatabaseHas('movements', [
            'product_id' => $product->id,
            'type' => 'entry',
            'quantity' => 50,
            'balance_before' => 0,
            'balance_after' => 50,
        ]);

        $balance = StockBalance::where(['product_id' => $product->id, 'warehouse_id' => $wh->id])->first();
        $this->assertEquals(50, $balance->current);
    }

    public function test_exit_decreases_stock(): void
    {
        $product = Product::factory()->create(['current_stock' => 100]);
        $wh = Warehouse::factory()->create();

        $this->stock->apply([
            'product_id' => $product->id,
            'type' => MovementType::EXIT->value,
            'quantity' => 30,
            'warehouse_id' => $wh->id,
            'reason' => 'consumo',
        ]);

        $product->refresh();
        $this->assertEquals(70, $product->current_stock);
    }

    public function test_average_cost_is_recalculated_on_entries(): void
    {
        $product = Product::factory()->create(['current_stock' => 10, 'average_cost' => 5]);
        $wh = Warehouse::factory()->create();

        // compra de 10 un a 15 -> médio = (10*5 + 10*15)/20 = 10
        $this->stock->apply([
            'product_id' => $product->id,
            'type' => MovementType::ENTRY->value,
            'quantity' => 10,
            'warehouse_id' => $wh->id,
            'unit_cost' => 15,
        ]);

        $product->refresh();
        $this->assertEquals(20, $product->current_stock);
        $this->assertEquals(10, $product->average_cost);
    }

    public function test_stock_never_goes_negative(): void
    {
        $product = Product::factory()->create(['current_stock' => 5]);
        $wh = Warehouse::factory()->create();

        $this->stock->apply([
            'product_id' => $product->id,
            'type' => MovementType::EXIT->value,
            'quantity' => 999,
            'warehouse_id' => $wh->id,
        ]);

        $product->refresh();
        $this->assertEquals(0, $product->current_stock);
    }

    public function test_transfer_moves_between_warehouses(): void
    {
        $product = Product::factory()->create(['current_stock' => 100]);
        $origin = Warehouse::factory()->create();
        $dest = Warehouse::factory()->create();

        $this->stock->apply([
            'product_id' => $product->id,
            'type' => MovementType::TRANSFER_OUT->value,
            'quantity' => 40,
            'warehouse_id' => $origin->id,
            'warehouse_destination_id' => $dest->id,
        ]);

        $originBal = StockBalance::where(['product_id' => $product->id, 'warehouse_id' => $origin->id])->first();
        $destBal = StockBalance::where(['product_id' => $product->id, 'warehouse_id' => $dest->id])->first();
        // sem saldo inicial nos almoxarifados: origin fica em 0 (foi só transferência out), dest recebe 40
        $this->assertEquals(0, $originBal->current);
        $this->assertEquals(40, $destBal->current);
    }
}
