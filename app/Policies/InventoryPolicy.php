<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

/**
 * RBAC por recurso para Inventário.
 * Permissões: inventory.view / inventory.create / inventory.execute.
 */
class InventoryPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'inventory.view');
    }

    public function view(User $user, Inventory $inventory): bool
    {
        return $this->has($user, 'inventory.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'inventory.create');
    }

    public function execute(User $user, Inventory $inventory): bool
    {
        return $this->has($user, 'inventory.execute');
    }
}
