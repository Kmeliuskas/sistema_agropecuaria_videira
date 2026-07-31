<?php

namespace App\Providers;

use App\Models\Adjustment;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Lot;
use App\Models\Manufacturer;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\Movement;
use App\Models\Product;
use App\Models\SerialNumber;
use App\Models\StockBalance;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Observers\AuditObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate limiter da API: 120 req/min por usuário (ou IP se deslogado).
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // Observadores de auditoria (append-only) para os modelos rastreados.
        // AuditLog NÃO se observa a si mesmo (append-only por design).
        foreach ([User::class, Warehouse::class, Unit::class, Category::class,
            Subcategory::class, Brand::class, Manufacturer::class, Supplier::class,
            Employee::class, Product::class, StockBalance::class, Movement::class,
            MaterialRequest::class, MaterialRequestItem::class, Inventory::class,
            InventoryItem::class, Lot::class, SerialNumber::class, Transfer::class,
            TransferItem::class, Adjustment::class] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
