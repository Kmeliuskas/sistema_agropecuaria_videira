# Plano: Módulo de Administração (Usuários + Papéis)

## Contexto

O sistema WMS já possui RBAC via `spatie/laravel-permission` com cargos
(`administrador`, `supervisor`, `almoxarife`, ...) e permissões granulares
(`users.*`, `roles.*`, etc.) criados em `RolesAndPermissionsSeeder`. O admin
inicial (`admin@wms.local`) já recebe o cargo `administrador`.

Hoje **não existe nenhuma tela** para gerenciar usuários ou papéis — tudo é
feito via seeder/tinker. O objetivo é criar uma área de **Administração**
acessível **somente a quem tem o cargo `administrador`**, com:

- **Usuários**: listar, criar, editar, excluir (soft delete) e ativar/inativar.
- **Papéis**: listar papéis existentes e **atribuir/alterar o cargo** de um usuário.

Decisões alinhadas com o usuário:
- Escopo v1 = Usuários + Papéis (não edição granular de permissões).
- Proteção = cargo `administrador` (wildcard `*`).

## Padrões do projeto a seguir

- Controllers em `app/Http/Controllers/Web/`, views em `resources/views/`,
  rotas em `routes/web.php` (grupo `auth`).
- Layout `resources/views/layouts/app.blade.php` (menu lateral com grupos
  `x-data`, ícones SVG inline, classes `card`/`btn-*`, dark mode).
- `ProductController` é o modelo de CRUD (validação inline, `normalizeNumeric`,
  `redirect()->route(...)->with('success', ...)`, soft delete).
- `SectorController` é o modelo de CRUD simples (create/edit/form único).
- Trait `Auditable` + `SoftDeletes` já presentes em `User`.
- Tailwind v4 utilities já definidos (`card`, `btn-primary`, `btn-danger`,
  `input`, `label`, `badge-*`, `alert-success`, `alert-danger`).

## Arquivos a criar / alterar

### 1. `routes/web.php`
Adicionar grupo protegido por cargo dentro do grupo `auth`:
```php
Route::middleware('role:administrador')->prefix('admin')->name('admin.')->group(function () {
    // Usuários
    Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
    Route::get('/usuarios/novo', [UserController::class, 'create'])->name('users.create');
    Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
    Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    // Papéis (somente leitura da lista + atribuição via usuário)
    Route::get('/papeis', [RoleController::class, 'index'])->name('roles.index');
});
```
`middleware('role:administrador')` vem do spatie (alias `role`).

### 2. `app/Http/Controllers/Web/UserController.php` (novo)
- `index()`: lista usuários com `->with('roles')`, filtro por busca (name/email)
  e paginação (15). Passa `$roles` (todos os papéis) para o form.
- `create()`: view do form com `$roles`.
- `store(Request)`: valida `name`, `email` (unique), `password` (min 8,
  confirmed), `is_active` (boolean), `roles` (array, exists roles). Cria usuário
  com `bcrypt`, `password_changed_at = now()`, `assignRole($roles)`.
- `edit(User)`: view do form com `$user` (com roles) e `$roles`.
- `update(Request, User)`: valida email unique ignorando o próprio, password
  opcional (min 8 confirmed), syncRoles. Não permite o admin desativar a si
  mesmo (guarda).
- `destroy(User)`: soft delete; bloqueia auto-exclusão do próprio usuário logado.
- Reaproveita padrão de redirect + `with('success')` do ProductController.

### 3. `app/Http/Controllers/Web/RoleController.php` (novo)
- `index()`: lista papéis com `->withCount('users')` e suas permissões
  (`$role->permissions`), para exibição somente-leitura. Reutiliza
  `RolesAndPermissionsSeeder::$modules` só para rótulos amigáveis se necessário
  (ou mostra permissões cruas).

### 4. Views
- `resources/views/admin/users/index.blade.php` — tabela de usuários (nome,
  email, cargo(s) como badges, ativo/inativo, ações editar/excluir), botão
  "Novo usuário", filtro de busca. Estende `layouts.app`.
- `resources/views/admin/users/form.blade.php` — form reutilizável p/ create e
  edit: name, email, password (+ confirm), checkbox ativo, multi-select de
  papéis (checkboxes). Mantém máscara/estilo dos inputs existentes.
- `resources/views/admin/roles/index.blade.php` — lista de papéis com contagem
  de usuários e permissões (badges).

### 5. `resources/views/layouts/app.blade.php`
Adicionar grupo "Administração" no menu lateral (após Catálogos), visível
somente para `administrador` (`@can('users.view')` ou
`Auth::user()->hasRole('administrador')`), com sub-itens "Usuários" e "Papéis",
usando `routeIs('admin.users.*', 'admin.roles.*')` para manter o grupo aberto.

## Validação / Testes manuais
1. `php artisan route:list | grep admin` confirma rotas.
2. Logar como `admin@wms.local` → menu "Administração" aparece.
3. Criar usuário `joao@wms.local` com cargo `almoxarife` → aparece na lista.
4. Editar → trocar cargo para `supervisor` → reflete.
5. Excluir usuário (soft delete) → some da lista ativa.
6. Logar como usuário **não-admin** (ex.: almoxarife) → acessar
   `/admin/usuarios` direto → deve dar 403 (middleware `role`).
7. `php artisan tinker` → `User::withTrashed()->find(...)` confirma soft delete.

## Fora do escopo (v1)
- Edição granular de permissões (users.create, stock.move...).
- Tela de criação de novos papéis (só listagem + atribuição).
- Reset de senha por e-mail (não há mailer configurado).
