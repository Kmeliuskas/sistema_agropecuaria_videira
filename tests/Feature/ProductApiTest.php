<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testa o fluxo REST de Produtos com RBAC (Sanctum cookie/SPA).
 * Valida criação, leitura, atualização, exclusão e regra de disponível.
 */
class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('administrador');
    }

    public function test_guest_cannot_access_products(): void
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
    }

    public function test_admin_can_list_products(): void
    {
        Product::factory()->count(3)->create();
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/products?per_page=2')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_create_product(): void
    {
        $payload = [
            'internal_code' => 'PROD-TEST-1',
            'name' => 'Parafuso Hex 10mm',
            'unit_id' => Unit::factory()->create()->id,
            'min_stock' => 5,
            'max_stock' => 100,
            'last_cost' => 1.5,
            'average_cost' => 1.5,
            'sale_price' => 3.0,
        ];

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $payload)
            ->assertCreated()
            ->assertJsonPath('data.internal_code', 'PROD-TEST-1');

        $this->assertDatabaseHas('products', ['internal_code' => 'PROD-TEST-1']);
    }

    public function test_internal_code_must_be_unique(): void
    {
        Product::factory()->create(['internal_code' => 'DUP-1']);
        $payload = [
            'internal_code' => 'DUP-1',
            'name' => 'Duplicado',
            'unit_id' => Unit::factory()->create()->id,
            'min_stock' => 0, 'max_stock' => 0, 'last_cost' => 0, 'average_cost' => 0, 'sale_price' => 0,
        ];

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $payload)
            ->assertUnprocessable();
    }

    public function test_available_stock_equals_current_minus_reserved(): void
    {
        $product = Product::factory()->create([
            'current_stock' => 100,
            'reserved_stock' => 30,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.available_stock', '70.0000')
            ->assertJsonPath('data.is_below_min', false);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::factory()->create();
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertOk();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
