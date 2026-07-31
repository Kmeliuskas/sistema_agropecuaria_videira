<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Nfe;

/**
 * RBAC para NF-E. Permissões: nfe.view/create/update/delete.
 * Administrador sempre passa.
 */
class NfePolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'nfe.view');
    }

    public function view(User $user, Nfe $nfe): bool
    {
        return $this->has($user, 'nfe.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'nfe.create');
    }

    public function update(User $user, Nfe $nfe): bool
    {
        return $this->has($user, 'nfe.update');
    }

    public function delete(User $user, Nfe $nfe): bool
    {
        return $this->has($user, 'nfe.delete');
    }
}
