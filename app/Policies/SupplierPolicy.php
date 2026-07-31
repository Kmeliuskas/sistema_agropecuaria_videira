<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Supplier;

/**
 * RBAC para catálogo de suppliers. Permissões: suppliers.view/create/update/delete.
 * Administrador sempre passa.
 */
class SupplierPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'suppliers.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $this->has($user, 'suppliers.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'suppliers.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->has($user, 'suppliers.update');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $this->has($user, 'suppliers.delete');
    }
}