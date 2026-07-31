<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Manufacturer;

/**
 * RBAC para catálogo de manufacturers. Permissões: manufacturers.view/create/update/delete.
 * Administrador sempre passa.
 */
class ManufacturerPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'manufacturers.view');
    }

    public function view(User $user, Manufacturer $manufacturer): bool
    {
        return $this->has($user, 'manufacturers.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'manufacturers.create');
    }

    public function update(User $user, Manufacturer $manufacturer): bool
    {
        return $this->has($user, 'manufacturers.update');
    }

    public function delete(User $user, Manufacturer $manufacturer): bool
    {
        return $this->has($user, 'manufacturers.delete');
    }
}