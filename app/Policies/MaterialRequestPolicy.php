<?php

namespace App\Policies;

use App\Models\MaterialRequest;
use App\Models\User;

/**
 * RBAC por recurso para Solicitação de Materiais.
 * Permissões: requests.view / requests.create / requests.approve /
 * requests.separate / requests.deliver. Administrador sempre passa.
 */
class MaterialRequestPolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'requests.view');
    }

    public function view(User $user, MaterialRequest $request): bool
    {
        return $this->has($user, 'requests.view')
            || $request->requester_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'requests.create');
    }

    public function approve(User $user, MaterialRequest $request): bool
    {
        return $this->has($user, 'requests.approve');
    }

    public function separate(User $user, MaterialRequest $request): bool
    {
        return $this->has($user, 'requests.separate');
    }

    public function deliver(User $user, MaterialRequest $request): bool
    {
        return $this->has($user, 'requests.deliver');
    }
}
