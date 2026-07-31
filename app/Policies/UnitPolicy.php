<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Unit;

/**
 * RBAC para catálogo de units. Permissões: units.view/create/update/delete.
 * Administrador sempre passa.
 */
class UnitPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'units.view');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $this->has($user, 'units.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'units.create');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $this->has($user, 'units.update');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $this->has($user, 'units.delete');
    }
}