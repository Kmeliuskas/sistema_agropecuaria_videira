<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WarehouseType;

/**
 * RBAC para Tipos de Almoxarifado.
 * Permissões: warehouse-types.view/create/update/delete
 * Administrador sempre passa.
 */
class WarehouseTypePolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'warehouse-types.view');
    }

    public function view(User $user, WarehouseType $type): bool
    {
        return $this->has($user, 'warehouse-types.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'warehouse-types.create');
    }

    public function update(User $user, WarehouseType $type): bool
    {
        return $this->has($user, 'warehouse-types.update');
    }

    public function delete(User $user, WarehouseType $type): bool
    {
        return $this->has($user, 'warehouse-types.delete');
    }
}
