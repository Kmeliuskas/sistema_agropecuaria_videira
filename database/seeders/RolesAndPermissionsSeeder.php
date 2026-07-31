<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Cria os 6 níveis de acesso e as permissões modulares configuráveis.
 * Permissões no padrão "recurso.acao" (products.view, stock.move, ...).
 * Níveis: administrador, supervisor, almoxarife, comprador, solicitante, consulta.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /** Módulos e ações que compõem as permissões. */
    protected array $modules = [
        'products' => ['view', 'create', 'update', 'delete'],
        'categories' => ['view', 'create', 'update', 'delete'],
        'subcategories' => ['view', 'create', 'update', 'delete'],
        'brands' => ['view', 'create', 'update', 'delete'],
        'manufacturers' => ['view', 'create', 'update', 'delete'],
        'units' => ['view', 'create', 'update', 'delete'],
        'warehouses' => ['view', 'create', 'update', 'delete'],
        'warehouse-types' => ['view', 'create', 'update', 'delete'],
        'sectors' => ['view', 'create', 'update', 'delete'],
        'stock' => ['view', 'move', 'adjust', 'transfer'],
        'movements' => ['view'],
        'suppliers' => ['view', 'create', 'update', 'delete'],
        'nfe' => ['view', 'create', 'update', 'delete'],
        'requests' => ['view', 'create', 'approve', 'separate', 'deliver'],
        'inventory' => ['view', 'create', 'execute'],
        'reports' => ['view'],
        'audit' => ['view'],
        'users' => ['view', 'create', 'update', 'delete'],
        'roles' => ['view', 'assign'],
    ];

    /** Mapeamento nível -> permissões concedidas. */
    protected array $rolePermissions = [
        'administrador' => ['*'], // wildcard: todas
        'supervisor' => [
            'products.*', 'categories.*', 'warehouses.*', 'sectors.*', 'stock.*', 'movements.*',
            'suppliers.*', 'nfe.*', 'requests.*', 'inventory.*', 'reports.*', 'audit.*',
            'users.view', 'users.create', 'users.update',
        ],
        'almoxarife' => [
            'products.view', 'products.create', 'products.update', 'categories.view',
            'warehouses.view', 'sectors.view', 'stock.*', 'movements.view', 'suppliers.view',
            'requests.view', 'requests.separate', 'requests.deliver', 'inventory.*',
            'reports.view', 'audit.view',
        ],
        'comprador' => [
            'products.view', 'products.create', 'products.update', 'categories.view',
            'suppliers.*', 'nfe.*', 'stock.view', 'movements.view', 'reports.view',
        ],
        'solicitante' => [
            'products.view', 'categories.view', 'warehouses.view', 'stock.view',
            'movements.view', 'requests.view', 'requests.create', 'reports.view',
        ],
        'consulta' => [
            'products.view', 'categories.view', 'warehouses.view', 'stock.view',
            'movements.view', 'reports.view', 'audit.view',
        ],
    ];

    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        // 1. Cria todas as permissões granulares
        $created = [];
        foreach ($this->modules as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";
                $created[$name] = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }
            // permissão curinga por módulo
            $created["{$module}.*"] = Permission::firstOrCreate(['name' => "{$module}.*", 'guard_name' => 'web']);
        }

        // 2. Cria papéis e atribui permissões
        foreach ($this->rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if (in_array('*', $perms, true)) {
                $role->syncPermissions(Permission::all());

                continue;
            }

            $resolved = [];
            foreach ($perms as $perm) {
                if (isset($created[$perm])) {
                    $resolved[] = $created[$perm];
                } elseif (str_ends_with($perm, '.*')) {
                    $module = str_replace('.*', '', $perm);
                    foreach ($this->modules[$module] ?? [] as $action) {
                        $resolved[] = $created["{$module}.{$action}"];
                    }
                }
            }
            $role->syncPermissions(array_filter($resolved));
        }

        // 3. Garante leitura dos catálogos a todos os papéis não-admin, para
        // não quebrar o acesso às telas de catálogo já existentes.
        $catalogViews = ['brands.view', 'manufacturers.view', 'units.view', 'subcategories.view'];
        foreach (Role::where('name', '<>', 'administrador')->get() as $role) {
            $role->givePermissionTo(
                array_filter($catalogViews, fn ($p) => isset($created[$p]))
            );
        }
    }
}
