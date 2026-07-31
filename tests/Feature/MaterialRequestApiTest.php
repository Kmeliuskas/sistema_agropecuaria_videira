<?php

namespace Tests\Feature;

use App\Models\MaterialRequest;
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
 * Cobre o ciclo de 6 etapas da Solicitação de Materiais e o consumo de
 * estoque (Kardex) no estágio "entregue".
 */
class MaterialRequestApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = UserFactory::new()->create(['is_active' => true]);
        $this->admin->assignRole('administrador');

        $this->requester = UserFactory::new()->create(['is_active' => true]);
        $this->requester->assignRole('solicitante');
    }

    public function test_guest_cannot_list(): void
    {
        $this->getJson('/api/v1/material-requests')->assertStatus(401);
    }

    public function test_requester_can_create_and_flow_consumes_stock(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create([
            'current_stock' => 100,
            'warehouse_id' => $warehouse->id,
            'available_stock' => 100,
        ]);
        // saldo por almoxarifado
        StockBalance::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'current' => 100,
            'available' => 100,
            'reserved' => 0, 'blocked' => 0, 'in_conferencia' => 0, 'in_transit' => 0,
        ]);

        $this->actingAs($this->requester, 'sanctum');

        $create = $this->postJson('/api/v1/material-requests', [
            'items' => [
                ['product_id' => $product->id, 'quantity_requested' => 20, 'warehouse_id' => $warehouse->id],
            ],
        ]);
        $create->assertStatus(201);
        $mrId = $create->json('data.id');
        $this->assertEquals('solicitado', $create->json('data.status'));

        // Aprovar
        $this->actingAs($this->admin, 'sanctum');
        $this->postJson("/api/v1/material-requests/{$mrId}/approve")->assertStatus(200)->assertJsonPath('data.status', 'aprovado');
        // Separar
        $this->postJson("/api/v1/material-requests/{$mrId}/separate")->assertStatus(200)->assertJsonPath('data.status', 'separado');
        // Conferir
        $this->postJson("/api/v1/material-requests/{$mrId}/check")->assertStatus(200)->assertJsonPath('data.status', 'conferido');
        // Entregar -> consome estoque
        $this->postJson("/api/v1/material-requests/{$mrId}/deliver")->assertStatus(200)->assertJsonPath('data.status', 'entregue');

        $product->refresh();
        $this->assertEquals(80, (float) $product->current_stock);

        $this->assertDatabaseHas('movements', [
            'product_id' => $product->id,
            'type' => 'exit',
            'source_type' => 'material_request',
        ]);

        // Finalizar
        $this->postJson("/api/v1/material-requests/{$mrId}/finish")->assertStatus(200)->assertJsonPath('data.status', 'finalizado');
    }

    public function test_approve_with_insufficient_stock_fails(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create([
            'current_stock' => 5,
            'warehouse_id' => $warehouse->id,
            'available_stock' => 5,
        ]);
        StockBalance::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'current' => 5, 'available' => 5,
            'reserved' => 0, 'blocked' => 0, 'in_conferencia' => 0, 'in_transit' => 0,
        ]);

        $mr = MaterialRequest::create(['code' => 'MR-T', 'requester_id' => $this->requester->id, 'status' => 'solicitado']);
        $mr->items()->create([
            'product_id' => $product->id,
            'quantity_requested' => 50,
            'warehouse_id' => $warehouse->id,
        ]);

        $this->actingAs($this->admin, 'sanctum');
        $this->postJson("/api/v1/material-requests/{$mr->id}/approve")->assertStatus(422);
    }
}
