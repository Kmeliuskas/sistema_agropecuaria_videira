<?php

namespace App\Policies;

use App\Models\Adjustment;
use App\Models\User;

/**
 * RBAC por recurso para Ajustes.
 * Permissões: stock.view (listar) / stock.adjust (criar).
 */
class AdjustmentPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'stock.view');
    }

    public function view(User $user, Adjustment $adjustment): bool
    {
        return $this->has($user, 'stock.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'stock.adjust');
    }
}
