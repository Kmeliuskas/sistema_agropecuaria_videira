<?php

namespace Tests\Feature;

use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Factories\UserFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobre Dashboard (KPIs) e alertas de validade de lotes.
 */
class DashboardApiTest extends TestCase
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

    public function test_summary_returns_kpis_and_abc_curve(): void
    {
        $warehouse = Warehouse::factory()->create();
        Product::factory()->count(3)->create([
            'current_stock' => 10,
            'average_cost' => 5,
            'warehouse_id' => $warehouse->id,
        ]);

        $this->actingAs($this->admin, 'sanctum');
        $res = $this->getJson('/api/v1/dashboard')->assertStatus(200)->json('data');

        $this->assertArrayHasKey('totals', $res);
        $this->assertArrayHasKey('abc_curve', $res);
        $this->assertArrayHasKey('stock_alerts', $res);
        $this->assertArrayHasKey('classes', $res['abc_curve']);
        $this->assertEquals(3, $res['totals']['products']);
    }

    public function test_expiring_soon_and_expired_lots(): void
    {
        $warehouse = Warehouse::factory()->create();
        $near = Lot::factory()->create([
            'product_id' => Product::factory()->create()->id,
            'warehouse_id' => $warehouse->id,
        ]);
        $near->update(['expires_at' => now()->addDays(15)->toDateString()]);

        $exp = Lot::factory()->create([
            'product_id' => Product::factory()->create()->id,
            'warehouse_id' => $warehouse->id,
        ]);
        $exp->update(['expires_at' => now()->subDays(5)->toDateString()]);

        $this->actingAs($this->admin, 'sanctum');

        $soon = $this->getJson('/api/v1/dashboard/lots/expiring-soon')->assertStatus(200)->json('data');
        $this->assertNotEmpty($soon);
        $this->assertTrue(collect($soon)->contains('id', $near->id));

        $expired = $this->getJson('/api/v1/dashboard/lots/expired')->assertStatus(200)->json('data');
        $this->assertTrue(collect($expired)->contains('id', $exp->id));
    }
}
