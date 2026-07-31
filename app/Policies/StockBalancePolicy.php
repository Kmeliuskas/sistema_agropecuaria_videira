<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockBalance;

/**
 * RBAC para Posição de Estoque. Permissão: stock.view (somente leitura).
 * Administrador sempre passa.
 */
class StockBalancePolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'stock.view');
    }

    public function view(User $user, StockBalance $stockBalance): bool
    {
        return $this->has($user, 'stock.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'stock.adjust') || $this->has($user, 'stock.move');
    }

    public function update(User $user, StockBalance $stockBalance): bool
    {
        return $this->has($user, 'stock.adjust') || $this->has($user, 'stock.move');
    }

    public function delete(User $user, StockBalance $stockBalance): bool
    {
        return $this->has($user, 'stock.adjust');
    }
}
