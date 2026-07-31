<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\PermissionLabels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** Papéis protegidos que não podem ser excluídos/editados livremente. */
    protected const PROTECTED_ROLES = ['administrador'];

    /**
     * Lista os papéis (cargos) com contagem de usuários e permissões agrupadas.
     */
    public function index(): View
    {
        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Formulário de novo papel.
     */
    public function create(): View
    {
        return view('admin.roles.form', $this->formData());
    }

    /**
     * Armazena um novo papel e atribui as permissões marcadas.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => strtolower(trim($data['name'])),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($this->permissionIds($request));

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Papel {$role->name} criado com sucesso.");
    }

    /**
     * Formulário de edição de papel.
     */
    public function edit(Role $role): View
    {
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', "O papel {$role->name} é protegido e não pode ser editado.");
        }

        return view('admin.roles.form', $this->formData() + ['role' => $role]);
    }

    /**
     * Atualiza o papel e suas permissões.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', "O papel {$role->name} é protegido e não pode ser editado.");
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'unique:roles,name,' . $role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update(['name' => strtolower(trim($data['name']))]);
        $role->syncPermissions($this->permissionIds($request));

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Papel {$role->name} atualizado.");
    }

    /**
     * Remove um papel (exceto os protegidos).
     */
    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', "O papel {$role->name} é protegido e não pode ser excluído.");
        }

        $name = $role->name;
        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Papel {$name} removido.");
    }

    /**
     * Dados para o formulário: todas as permissões agrupadas por módulo,
     * com rótulos amigáveis.
     */
    protected function formData(): array
    {
        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'grouped' => PermissionLabels::groupByModule($permissions),
            'allPermissions' => $permissions,
        ];
    }

    /**
     * Converte os IDs de permissões (strings dos checkboxes) em inteiros.
     */
    protected function permissionIds(Request $request): array
    {
        return array_map(
            'intval',
            array_filter((array) $request->input('permissions', []))
        );
    }
}
