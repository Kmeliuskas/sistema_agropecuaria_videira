<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attribute;

/**
 * RBAC para Atributos. Permissões: attributes.view/create/update/delete.
 * Administrador sempre passa.
 */
class AttributePolicy
{
    protected function has(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission) || $user->isAdministrator();
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'attributes.view');
    }

    public function view(User $user, Attribute $attribute): bool
    {
        return $this->has($user, 'attributes.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'attributes.create');
    }

    public function update(User $user, Attribute $attribute): bool
    {
        return $this->has($user, 'attributes.update');
    }

    public function delete(User $user, Attribute $attribute): bool
    {
        return $this->has($user, 'attributes.delete');
    }
}
