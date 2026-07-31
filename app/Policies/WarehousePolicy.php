<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

/**
 * RBAC para Almoxarifados. Permissões: warehouses.view/create/update/delete.
 * Administrador sempre passa.
 */
class WarehousePolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'warehouses.view');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $this->has($user, 'warehouses.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'warehouses.create');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $this->has($user, 'warehouses.update');
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $this->has($user, 'warehouses.delete');
    }
}
