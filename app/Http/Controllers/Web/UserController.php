<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Lista os usuários do sistema.
     */
    public function index(): View
    {
        $query = User::query()->with('roles')->latest();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (request('active') === '0') {
            $query->onlyTrashed();
        } elseif (request('active') !== 'all') {
            $query->where('is_active', true);
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    /**
     * Formulário de novo usuário.
     */
    public function create(): View
    {
        return view('admin.users.form', $this->formData());
    }

    /**
     * Armazena um novo usuário.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
            'password_changed_at' => now(),
        ]);

        $user->syncRoles($this->roleIds($request));

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuário {$user->name} criado com sucesso.");
    }

    /**
     * Formulário de edição.
     */
    public function edit(User $user): View
    {
        return view('admin.users.form', $this->formData() + ['user' => $user]);
    }

    /**
     * Atualiza um usuário.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        // O próprio administrador não pode se desativar.
        if ($user->id === Auth::id() && empty($data['is_active'])) {
            return redirect()
                ->route('admin.users.edit', $user)
                ->with('error', 'Você não pode desativar a própria conta.');
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->is_active = $data['is_active'] ?? true;

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
            $user->password_changed_at = now();
        }

        $user->save();
        $user->syncRoles($this->roleIds($request));

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuário {$user->name} atualizado.");
    }

    /**
     * Remove (soft delete) um usuário.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Você não pode excluir a própria conta.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuário {$name} removido.");
    }

    /**
     * Dados para o formulário (papéis disponíveis).
     */
    protected function formData(): array
    {
        return [
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * Converte os IDs de papéis vindos do formulário (strings dos checkboxes)
     * em inteiros. O syncRoles do spatie confunde strings numéricas ("3")
     * com nomes de papel, por isso o cast explícito para int é necessário.
     */
    protected function roleIds(Request $request): array
    {
        return array_map(
            'intval',
            array_filter((array) $request->input('roles', []))
        );
    }
}
