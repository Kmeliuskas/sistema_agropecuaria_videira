<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Category;

/**
 * RBAC para Categorias (catálogos). Permissões: categories.view/create/update/delete.
 * Administrador sempre passa.
 */
class CategoryPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $this->has($user, 'categories.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $this->has($user, 'categories.update');
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->has($user, 'categories.delete');
    }
}
