<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Brand;

/**
 * RBAC para catálogo de brands. Permissões: brands.view/create/update/delete.
 * Administrador sempre passa.
 */
class BrandPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'brands.view');
    }

    public function view(User $user, Brand $brand): bool
    {
        return $this->has($user, 'brands.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'brands.create');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $this->has($user, 'brands.update');
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $this->has($user, 'brands.delete');
    }
}