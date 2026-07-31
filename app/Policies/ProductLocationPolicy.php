<?php

namespace App\Policies;

use App\Models\ProductLocation;
use App\Models\User;

/**
 * RBAC para Localização de Produtos.
 * Permissões: products.view/create/update/delete
 * Administrador sempre passa.
 */
class ProductLocationPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'products.view');
    }

    public function view(User $user, ProductLocation $location): bool
    {
        return $this->has($user, 'products.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'products.create');
    }

    public function update(User $user, ProductLocation $location): bool
    {
        return $this->has($user, 'products.update');
    }

    public function delete(User $user, ProductLocation $location): bool
    {
        return $this->has($user, 'products.delete');
    }
}
