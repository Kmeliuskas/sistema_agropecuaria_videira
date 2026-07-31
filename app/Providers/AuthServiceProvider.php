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
use App\Models\Nfe;
use App\Models\Product;
use App\Models\SerialNumber;
use App\Models\StockBalance;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\ProductLocation;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use App\Policies\AdjustmentPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\ManufacturerPolicy;
use App\Policies\MaterialRequestPolicy;
use App\Policies\MovementPolicy;
use App\Policies\NfePolicy;
use App\Policies\ProductPolicy;
use App\Policies\SectorPolicy;
use App\Policies\StockBalancePolicy;
use App\Policies\SubcategoryPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\TransferPolicy;
use App\Policies\UnitPolicy;
use App\Policies\WarehousePolicy;
use App\Policies\WarehouseTypePolicy;
use App\Policies\ProductLocationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Category::class => CategoryPolicy::class,
        Brand::class => BrandPolicy::class,
        Manufacturer::class => ManufacturerPolicy::class,
        Supplier::class => SupplierPolicy::class,
        Unit::class => UnitPolicy::class,
        Subcategory::class => SubcategoryPolicy::class,
        Warehouse::class => WarehousePolicy::class,
        WarehouseType::class => WarehouseTypePolicy::class,
        ProductLocation::class => ProductLocationPolicy::class,
        Sector::class => SectorPolicy::class,
        Product::class => ProductPolicy::class,
        Movement::class => MovementPolicy::class,
        Nfe::class => NfePolicy::class,
        StockBalance::class => StockBalancePolicy::class,
        MaterialRequest::class => MaterialRequestPolicy::class,
        MaterialRequestItem::class => MaterialRequestPolicy::class,
        Inventory::class => InventoryPolicy::class,
        InventoryItem::class => InventoryPolicy::class,
        Lot::class => InventoryPolicy::class,
        SerialNumber::class => InventoryPolicy::class,
        Transfer::class => TransferPolicy::class,
        TransferItem::class => TransferPolicy::class,
        Adjustment::class => AdjustmentPolicy::class,
        User::class => \App\Policies\UserPolicy::class,
        Employee::class => \App\Policies\EmployeePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}