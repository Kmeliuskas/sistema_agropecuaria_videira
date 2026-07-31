<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Web\BrandController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\WarehouseTypeController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EntityGeneratorController;
use App\Http\Controllers\Web\ManufacturerController;
use App\Http\Controllers\Web\MaterialRequestController;
use App\Http\Controllers\Web\MovementController;
use App\Http\Controllers\Web\NfeController;
use App\Http\Controllers\Web\PermissionController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\SectorController;
use App\Http\Controllers\Web\StockController;
use App\Http\Controllers\Web\SubcategoryController;
use App\Http\Controllers\Web\SupplierController;
use App\Http\Controllers\Web\UnitController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\ProductLocationController;
use App\Http\Controllers\Web\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard/movements', [DashboardController::class, 'movementsChart'])->name('dashboard.movements.chart');

    Route::get('/produtos', [ProductController::class, 'index'])->name('products.index');
    Route::get('/produtos/novo', [ProductController::class, 'create'])->name('products.create');
    Route::post('/produtos', [ProductController::class, 'store'])->name('products.store');
    Route::get('/produtos/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/produtos/{product}/editar', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/produtos/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/produtos/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/almoxarifados', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::get('/almoxarifados/novo', [WarehouseController::class, 'create'])->name('warehouses.create');
    Route::post('/almoxarifados', [WarehouseController::class, 'store'])->name('warehouses.store');
    Route::get('/almoxarifados/{warehouse}', [WarehouseController::class, 'show'])->name('warehouses.show');
    Route::get('/almoxarifados/{warehouse}/editar', [WarehouseController::class, 'edit'])->name('warehouses.edit');
    Route::put('/almoxarifados/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('/almoxarifados/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');

    // Localização de produtos (CRUD)
    Route::get('/localizacoes', [ProductLocationController::class, 'index'])->name('product-locations.index');
    Route::get('/localizacoes/novo', [ProductLocationController::class, 'create'])->name('product-locations.create');
    Route::post('/localizacoes', [ProductLocationController::class, 'store'])->name('product-locations.store');
    Route::get('/localizacoes/{productLocation}/editar', [ProductLocationController::class, 'edit'])->name('product-locations.edit');
    Route::put('/localizacoes/{productLocation}', [ProductLocationController::class, 'update'])->name('product-locations.update');
    Route::delete('/localizacoes/{productLocation}', [ProductLocationController::class, 'destroy'])->name('product-locations.destroy');

    Route::get('/setores', [SectorController::class, 'index'])->name('sectors.index');
    Route::get('/setores/novo', [SectorController::class, 'create'])->name('sectors.create');
    Route::post('/setores', [SectorController::class, 'store'])->name('sectors.store');
    Route::get('/setores/{sector}/editar', [SectorController::class, 'edit'])->name('sectors.edit');
    Route::put('/setores/{sector}', [SectorController::class, 'update'])->name('sectors.update');
    Route::delete('/setores/{sector}', [SectorController::class, 'destroy'])->name('sectors.destroy');

    Route::get('/estoque', [StockController::class, 'index'])->name('stock.index');
    Route::get('/estoque/novo', [StockController::class, 'create'])->name('stock.create');
    Route::post('/estoque', [StockController::class, 'store'])->name('stock.store');
    Route::get('/estoque/{stockBalance}/editar', [StockController::class, 'edit'])->name('stock.edit');
    Route::put('/estoque/{stockBalance}', [StockController::class, 'update'])->name('stock.update');
    Route::delete('/estoque/{stockBalance}', [StockController::class, 'destroy'])->name('stock.destroy');

    // Solicitações de material
    Route::get('/solicitacoes', [MaterialRequestController::class, 'index'])->name('material-requests.index');
    Route::get('/solicitacoes/nova', [MaterialRequestController::class, 'create'])->name('material-requests.create');
    Route::post('/solicitacoes', [MaterialRequestController::class, 'store'])->name('material-requests.store');
    Route::get('/solicitacoes/{materialRequest}', [MaterialRequestController::class, 'show'])->name('material-requests.show');
    Route::post('/solicitacoes/{materialRequest}/aprovar', [MaterialRequestController::class, 'approve'])->name('material-requests.approve');
    Route::post('/solicitacoes/{materialRequest}/recusar', [MaterialRequestController::class, 'reject'])->name('material-requests.reject');
    Route::post('/solicitacoes/{materialRequest}/entregar', [MaterialRequestController::class, 'deliver'])->name('material-requests.deliver');
    Route::post('/solicitacoes/{materialRequest}/finalizar', [MaterialRequestController::class, 'finish'])->name('material-requests.finish');

    Route::get('/movimentacoes', [MovementController::class, 'index'])->name('movements.index');

    // NF-E
    Route::get('/nfe', [NfeController::class, 'index'])->name('nfe.index');
    Route::get('/nfe/nova', [NfeController::class, 'create'])->name('nfe.create');
    Route::post('/nfe', [NfeController::class, 'store'])->name('nfe.store');
    Route::get('/nfe/{nfe}', [NfeController::class, 'show'])->name('nfe.show');
    Route::get('/nfe/{nfe}/editar', [NfeController::class, 'edit'])->name('nfe.edit');
    Route::put('/nfe/{nfe}', [NfeController::class, 'update'])->name('nfe.update');
    Route::delete('/nfe/{nfe}', [NfeController::class, 'destroy'])->name('nfe.destroy');
    Route::post('/nfe/{nfe}/receber', [NfeController::class, 'receive'])->name('nfe.receive');
    Route::post('/nfe/{nfe}/cancelar', [NfeController::class, 'cancel'])->name('nfe.cancel');

    // Catálogos (CRUD individual por controller)
    // Categorias
    Route::get('/catalogos/categorias', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/catalogos/categorias/novo', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/catalogos/categorias', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/catalogos/categorias/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('/catalogos/categorias/{category}/editar', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/catalogos/categorias/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/catalogos/categorias/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Marcas
    Route::get('/catalogos/marcas', [BrandController::class, 'index'])->name('brands.index');
    Route::get('/catalogos/marcas/novo', [BrandController::class, 'create'])->name('brands.create');
    Route::post('/catalogos/marcas', [BrandController::class, 'store'])->name('brands.store');
    Route::get('/catalogos/marcas/{brand}', [BrandController::class, 'show'])->name('brands.show');
    Route::get('/catalogos/marcas/{brand}/editar', [BrandController::class, 'edit'])->name('brands.edit');
    Route::put('/catalogos/marcas/{brand}', [BrandController::class, 'update'])->name('brands.update');
    Route::delete('/catalogos/marcas/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

    // Fabricantes
    Route::get('/catalogos/fabricantes', [ManufacturerController::class, 'index'])->name('manufacturers.index');
    Route::get('/catalogos/fabricantes/novo', [ManufacturerController::class, 'create'])->name('manufacturers.create');
    Route::post('/catalogos/fabricantes', [ManufacturerController::class, 'store'])->name('manufacturers.store');
    Route::get('/catalogos/fabricantes/{manufacturer}', [ManufacturerController::class, 'show'])->name('manufacturers.show');
    Route::get('/catalogos/fabricantes/{manufacturer}/editar', [ManufacturerController::class, 'edit'])->name('manufacturers.edit');
    Route::put('/catalogos/fabricantes/{manufacturer}', [ManufacturerController::class, 'update'])->name('manufacturers.update');
    Route::delete('/catalogos/fabricantes/{manufacturer}', [ManufacturerController::class, 'destroy'])->name('manufacturers.destroy');

    // Fornecedores
    Route::get('/catalogos/fornecedores', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/catalogos/fornecedores/novo', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('/catalogos/fornecedores', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/catalogos/fornecedores/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::get('/catalogos/fornecedores/{supplier}/editar', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('/catalogos/fornecedores/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/catalogos/fornecedores/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // Unidades
    Route::get('/catalogos/unidades', [UnitController::class, 'index'])->name('units.index');
    Route::get('/catalogos/unidades/novo', [UnitController::class, 'create'])->name('units.create');
    Route::post('/catalogos/unidades', [UnitController::class, 'store'])->name('units.store');
    Route::get('/catalogos/unidades/{unit}', [UnitController::class, 'show'])->name('units.show');
    Route::get('/catalogos/unidades/{unit}/editar', [UnitController::class, 'edit'])->name('units.edit');
    Route::put('/catalogos/unidades/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::delete('/catalogos/unidades/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');

    // Subcategorias
    Route::get('/catalogos/subcategorias', [SubcategoryController::class, 'index'])->name('subcategories.index');
    Route::get('/catalogos/subcategorias/novo', [SubcategoryController::class, 'create'])->name('subcategories.create');
    Route::post('/catalogos/subcategorias', [SubcategoryController::class, 'store'])->name('subcategories.store');
    Route::get('/catalogos/subcategorias/{subcategory}', [SubcategoryController::class, 'show'])->name('subcategories.show');
    Route::get('/catalogos/subcategorias/{subcategory}/editar', [SubcategoryController::class, 'edit'])->name('subcategories.edit');
    Route::put('/catalogos/subcategorias/{subcategory}', [SubcategoryController::class, 'update'])->name('subcategories.update');
    Route::delete('/catalogos/subcategorias/{subcategory}', [SubcategoryController::class, 'destroy'])->name('subcategories.destroy');

    // Tipos de Almoxarifado
    Route::get('/tipos-de-almoxarifado', [WarehouseTypeController::class, 'index'])->name('warehouse-types.index');
    Route::get('/tipos-de-almoxarifado/novo', [WarehouseTypeController::class, 'create'])->name('warehouse-types.create');
    Route::post('/tipos-de-almoxarifado', [WarehouseTypeController::class, 'store'])->name('warehouse-types.store');
    Route::get('/tipos-de-almoxarifado/{warehouseType}', [WarehouseTypeController::class, 'show'])->name('warehouse-types.show');
    Route::get('/tipos-de-almoxarifado/{warehouseType}/editar', [WarehouseTypeController::class, 'edit'])->name('warehouse-types.edit');
    Route::put('/tipos-de-almoxarifado/{warehouseType}', [WarehouseTypeController::class, 'update'])->name('warehouse-types.update');
    Route::delete('/tipos-de-almoxarifado/{warehouseType}', [WarehouseTypeController::class, 'destroy'])->name('warehouse-types.destroy');

    // Administração (somente cargo 'administrador')
    Route::middleware('role:administrador')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::get('/usuarios/novo', [UserController::class, 'create'])->name('users.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/papeis', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/papeis/novo', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/papeis', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/papeis/{role}/editar', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/papeis/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/papeis/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('/permissoes', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissoes/nova', [PermissionController::class, 'create'])->name('permissions.create');
        Route::post('/permissoes', [PermissionController::class, 'store'])->name('permissions.store');
        Route::delete('/permissoes/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        // Gerador de entidades (scaffolder de CRUD)
        Route::get('/entidades/nova', [EntityGeneratorController::class, 'create'])->name('entities.create');
        Route::post('/entidades', [EntityGeneratorController::class, 'store'])->name('entities.store');
    });
});