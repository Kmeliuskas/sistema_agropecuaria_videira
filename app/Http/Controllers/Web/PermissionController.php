<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\PermissionLabels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Lista todas as permissões agrupadas por módulo.
     */
    public function index(): View
    {
        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.permissions.index', [
            'grouped' => PermissionLabels::groupByModule($permissions),
        ]);
    }

    /**
     * Formulário de nova permissão.
     */
    public function create(): View
    {
        return view('admin.permissions.form');
    }

    /**
     * Armazena uma nova permissão no padrão "recurso.acao".
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+\\.[a-z0-9_]+$/', 'unique:permissions,name'],
        ]);

        Permission::firstOrCreate([
            'name' => strtolower(trim($data['name'])),
            'guard_name' => 'web',
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', "Permissão {$data['name']} criada com sucesso.");
    }

    /**
     * Remove uma permissão.
     */
    public function destroy(Permission $permission): RedirectResponse
    {
        $name = $permission->name;
        $permission->delete();

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', "Permissão {$name} removida.");
    }
}
