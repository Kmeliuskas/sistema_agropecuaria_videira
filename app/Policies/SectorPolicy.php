<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Sector;

/**
 * RBAC para Setores. Permissões: sectors.view/create/update/delete.
 * Administrador sempre passa.
 */
class SectorPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'sectors.view');
    }

    public function view(User $user, Sector $sector): bool
    {
        return $this->has($user, 'sectors.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'sectors.create');
    }

    public function update(User $user, Sector $sector): bool
    {
        return $this->has($user, 'sectors.update');
    }

    public function delete(User $user, Sector $sector): bool
    {
        return $this->has($user, 'sectors.delete');
    }
}
