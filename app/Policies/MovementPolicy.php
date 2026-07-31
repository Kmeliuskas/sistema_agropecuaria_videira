<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Movement;

/**
 * RBAC para Movimentações (Kardex). Permissão: movements.view (somente leitura).
 * Administrador sempre passa.
 */
class MovementPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'movements.view');
    }

    public function view(User $user, Movement $movement): bool
    {
        return $this->has($user, 'movements.view');
    }
}
