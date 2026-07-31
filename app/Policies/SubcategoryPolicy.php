<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Subcategory;

/**
 * RBAC para catálogo de subcategories. Permissões: subcategories.view/create/update/delete.
 * Administrador sempre passa.
 */
class SubcategoryPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'subcategories.view');
    }

    public function view(User $user, Subcategory $subcategory): bool
    {
        return $this->has($user, 'subcategories.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'subcategories.create');
    }

    public function update(User $user, Subcategory $subcategory): bool
    {
        return $this->has($user, 'subcategories.update');
    }

    public function delete(User $user, Subcategory $subcategory): bool
    {
        return $this->has($user, 'subcategories.delete');
    }
}