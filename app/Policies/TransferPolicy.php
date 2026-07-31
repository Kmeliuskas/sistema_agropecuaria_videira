<?php

namespace App\Policies;

use App\Models\Transfer;
use App\Models\User;

/**
 * RBAC por recurso para Transferências.
 * Permissões: stock.view (listar) / stock.transfer (criar + executar).
 */
class TransferPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'stock.view');
    }

    public function view(User $user, Transfer $transfer): bool
    {
        return $this->has($user, 'stock.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'stock.transfer');
    }

    public function ship(User $user, Transfer $transfer): bool
    {
        return $this->has($user, 'stock.transfer');
    }

    public function receive(User $user, Transfer $transfer): bool
    {
        return $this->has($user, 'stock.transfer');
    }
}
