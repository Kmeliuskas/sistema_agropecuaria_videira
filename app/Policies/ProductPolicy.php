<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * RBAC por recurso. As permissões (spatie) são verificadas no Gate; aqui
 * mapeamos ações de CRUD/movimentação. Administrador/Supervisor/Almoxarife
 * com permissão 'products.*' passam.
 */
class ProductPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $this->has($user, 'products.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $this->has($user, 'products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->has($user, 'products.delete');
    }

    public function move(User $user): bool
    {
        return $this->has($user, 'stock.move');
    }
}
