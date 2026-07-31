# Plano: RBAC nas telas Web + CRUD editável de Papéis/Permissões

## Contexto

O sistema já possui RBAC completo no **código**: 6 papéis (`administrador`,
`supervisor`, `almoxarife`, `comprador`, `solicitante`, `consulta`) e permissões
granulares `recurso.ação` (ex.: `products.create`, `stock.move`), definidos em
`database/seeders/RolesAndPermissionsSeeder.php`. Existem **Policies**
(`app/Policies/*`) que checam `hasPermissionTo()` + `isAdministrator()`, e o
**Gate já as auto-descobriu** (testado: `consulta` é negado em `products.create`,
`admin` passa).

**Problema hoje:** os controllers **Web** (`ProductController`,
`MaterialRequestController`, etc.) NÃO chamam as Policies, e o front não esconde
botões. Qualquer usuário logado consegue abrir `/produtos/novo`, aprovar
solicitações, etc. A proteção só vale na API.

**Objetivo:**
1. Plugar as Policies nas rotas Web → quem não tem permissão leva 403/redirect.
2. Esconder botões e itens de menu conforme a permissão do usuário (`@can`).
3. Tornar a aba **Papéis** um CRUD completo: criar/editar/excluir papéis e
   marcar permissões granularmente, além de criar novas permissões — tudo pela
   tela, sem mexer no seeder.

Decisões alinhadas: bloqueio nas rotas + esconder botões; CRUD completo de
papéis + permissões.

## Mapeamento de permissões → ações (do seeder + Policies)

| Recurso | Permissões | Onde checar (Policy) |
|---------|-----------|----------------------|
| products | view, create, update, delete | ProductPolicy |
| categories | view, create, update, delete | (sem Policy própria — usar gate direto) |
| warehouses | view, create, update, delete | (gate direto) |
| stock | view, move, adjust, transfer | AdjustmentPolicy/TransferPolicy |
| movements | view | (gate direto) |
| suppliers | view, create, update, delete | (gate direto) |
| requests | view, create, approve, separate, deliver | MaterialRequestPolicy |
| inventory | view, create, execute | InventoryPolicy |
| reports | view | (gate direto) |
| audit | view | (gate direto) |
| users | view, create, update, delete | (gate direto — já protegido por `role:administrador`) |
| roles | view, assign | (gate direto) |

Obs.: `categories/warehouses/suppliers/movements/reports/audit/users/roles`
não têm Policy própria; usaremos `$request->user()->can('X.y')` (Gate direto,
que cai no `HasPermissions` do spatie + `administrador` wildcard).

## Parte 1 — Plugando Policies/Bloqueio nas rotas Web

### Controllers Web (adicionar `$this->authorize()` nas ações mutáveis/visualização)

Padrão: no início de cada ação, ` $this->authorize('viewAny', Model::class)`
ou `->authorize('create', Model::class)`, etc. O Controller base já tem
`AuthorizesRequests`. Isso joga `AuthorizationException` → Laravel responde 403
(em web) ou redirect p/ login se não autenticado.

- **ProductController**: `index/show` → `authorize('viewAny', Product::class)`;
  `create/store` → `authorize('create', Product::class)`; `edit/update` →
  `authorize('update', $product)`; `destroy` → `authorize('delete', $product)`.
- **SectorController**: igual com `Sector::class` (permissões `warehouses.*`
  reutilizadas? NÃO — criar `sectors.view/create/update/delete`? Para não
  mexer no seeder agora, mapear Setores → `warehouses.view/create/update/delete`
  ou adicionar módulo `sectors`. **Decisão:** adicionar módulo `sectors` no
  seeder (view/create/update/delete) e dar aos papéis apropriados; manter
  simples: `sectors.*` segue o mesmo perfil de `warehouses.*`.
- **WarehouseController**: `index` → `authorize('viewAny', Warehouse::class)`
  (permissão `warehouses.view`). Sem create/edit hoje (tela é leitura) — manter
  leitura protegida.
- **MaterialRequestController**: `index/show` → `authorize('viewAny',
  MaterialRequest::class)`; `create/store` → `authorize('create', ...)`;
  `approve` → `authorize('approve', $mr)`; `reject` → `authorize('approve',
  $mr)` (rejeitar é privilege de aprovador); `deliver` → `authorize('deliver',
  $mr)`; `finish` → `authorize('deliver', $mr)` (só quem entrega finaliza — ou
  criar `requests.finish`? Usar `deliver` por ora, documentar).
- **StockController / MovementController / CatalogController / DashboardController**:
  `index` → `authorize('viewAny', ...)` com o Model correspondente (StockBalance,
  Movement, Category, ou gate `reports.view` p/ dashboard). Dashboard usa
  `authorize` via um Model "fake"? Melhor: `auth()->user()->can('reports.view')`
  ou simplesmente proteger o que faz sentido. **Decisão:** Dashboard protegido
  por `reports.view` (todo mundo ativo tem). Catálogos: cada catálogo mapeia
  para `categories.view`/`brands.view`/etc. → usar `authorize('viewAny',
  Category::class)` etc. (reutilizando policies de produto).

Para os recursos sem Policy (Warehouse, Sector, Movement, StockBalance,
Category), criar Policies mínimas OU usar Gate direto. **Decisão:** criar
Policies enxutas para `Warehouse`, `Sector`, `Movement`, `StockBalance`,
`Category` seguindo o mesmo padrão `has() + isAdministrator()`, mapeando para as
permissões do seeder. Isso mantém consistência e o `@can` funciona igual.

### Esconder botões no front (`@can`)

- **Menu lateral** (`layouts/app.blade.php`): envolver cada grupo/subitem em
  `@can`. Ex.: grupo Cadastros só aparece se `auth()->user()->can('products.view')
  || can('warehouses.view') || ...`. Itens individuais com sua permissão.
- **products/index.blade.php**: botão "Novo produto" em `@can('products.create')`;
  ações editar/excluir por linha em `@can('products.update'/'products.delete')`.
- **sectors/index.blade.php**: igual com `warehouses.*` (ou `sectors.*`).
- **material_requests/index.blade.php** e **show.blade.php**: botões
  Aprovar/Recusar em `@can('requests.approve')`; Entregar em
  `@can('requests.deliver')`; Finalizar em `@can('requests.deliver')`;
  "Nova solicitação" em `@can('requests.create')`.
- **products/show.blade.php**: botões editar/excluir em `@can`.

### Rótulos amigáveis de permissão

Criar `app/Support/PermissionLabels.php` (ou trait) com mapa
`products.create => 'Criar produtos'`, etc., para a tela de Papéis e qualquer
listagem. Usado no RoleController/index e nos formulários.

## Parte 2 — CRUD de Papéis + Permissões pela tela

### Rotas (`routes/web.php`, grupo `role:administrador`, prefixo admin)

```
GET    admin/papeis                  admin.roles.index     (listar)
GET    admin/papeis/novo             admin.roles.create
POST   admin/papeis                  admin.roles.store
GET    admin/papeis/{role}/editar    admin.roles.edit
PUT    admin/papeis/{role}           admin.roles.update
DELETE admin/papeis/{role}           admin.roles.destroy
GET    admin/permissoes              admin.permissions.index   (listar permissões)
GET    admin/permissoes/nova         admin.permissions.create
POST   admin/permissoes              admin.permissions.store
DELETE admin/permissoes/{permission} admin.permissions.destroy
```

Nota: `{role}` e `{permission}` usam `Spatie\Permission\Models\Role` e
`...Permission` (route model binding pelo `id`).

### Controllers

- **RoleController** (expandir): `index` (com contagem + permissões),
  `create`/`edit` (form com todas as permissões em checkboxes, agrupadas por
  módulo, com rótulo amigável), `store` (valida `name` único, cria Role,
  `syncPermissions`), `update` (`syncPermissions`), `destroy` (bloqueia excluir
  `administrador`; soft delete ou delete simples — spatie Role tem SoftDeletes?
  NÃO por padrão → usar `delete()` e impedir exclusão de papéis em uso? Permitir
  mas avisar. **Decisão:** impedir exclusão do papel `administrador`; demais,
  permitir `delete()`).
- **PermissionController** (novo): `index` (lista permissões agrupadas),
  `create`/`store` (valida `name` no padrão `recurso.acao`, `guard_name=web`,
  `firstOrCreate`), `destroy` (impede excluir permissões do sistema? Permitir
  excluir as criadas pelo usuário; as do seeder podem ser removidas também, mas
  avisar que o seeder recria). **Decisão:** permitir excluir qualquer, com
  confirmação.

### Views (`resources/views/admin/roles/*` e `admin/permissions/*`)

- `roles/index.blade.php` (reformular): grid de papéis; cada papel com badge de
  contagem de usuários, botões Editar/Excluir, e indicador de permissões por
  módulo (ex.: "Produtos: ver/criar/editar/excluir").
- `roles/form.blade.php` (novo): nome do papel + checkboxes de permissões
  agrupadas por módulo, com rótulo amigável e descrição curta.
- `permissions/index.blade.php` (novo): tabela de permissões (nome, módulo,
  rótulo) + botão Nova + excluir.
- `permissions/form.blade.php` (novo): campo `name` (recurso.ação) + rótulo
  amigável opcional.

### Seeders / módulo `sectors`

Adicionar `'sectors' => ['view','create','update','delete']` ao `$modules` do
`RolesAndPermissionsSeeder` e dar `sectors.*` aos papéis que já têm
`warehouses.*` (supervisor, almoxarife). Rodar seeder após deploy.

## Validação / Testes

1. `php artisan route:list | grep admin` confirma novas rotas.
2. Login como `consulta` (só view): não vê botões Novo/Editar/Excluir; ao forçar
   `POST /produtos` via curl → 403.
3. Login como `almoxarife`: vê Estoque/Movimentações/Solicitações(ver/separar/
   entregar), mas NÃO Produtos(criar) nem Usuários.
4. Admin cria papel "Auditor" com só `audit.view`+`reports.view` → usuário com
   esse papel só enxerga Relatórios/Auditoria.
5. Admin cria permissão `relatorios.export` e atribui a um papel → aparece no
   gate (`$user->can('relatorios.export')`).
6. Teste feature (em wms_test migrado): admin acessa; não-admin 403; guest
   redirect login.

## Arquivos novos/alterados (resumo)

- `routes/web.php` — rotas de papéis/permissões + (sem mudança de rota p/ authorize).
- `app/Http/Controllers/Web/RoleController.php` — expandir (CRUD).
- `app/Http/Controllers/Web/PermissionController.php` — novo.
- `app/Http/Controllers/Web/{Product,Sector,Warehouse,MaterialRequest,Stock,Movement,Catalog,Dashboard}Controller.php` — `$this->authorize()`.
- `app/Policies/{Warehouse,Sector,Movement,StockBalance,Category}Policy.php` — novas (padrão has+isAdministrator).
- `app/Support/PermissionLabels.php` — rótulos amigáveis.
- `database/seeders/RolesAndPermissionsSeeder.php` — módulo `sectors`.
- Views: `layouts/app.blade.php` (menu @can), `products/{index,show}`,
  `sectors/index`, `material_requests/{index,show}`, `admin/roles/*`,
  `admin/permissions/*`.
- `bootstrap/app.php` — já tem alias `role`/`permission` (feito antes).

## Fora do escopo

- Herança de papéis, papéis por setor, escopo por almoxarifado (row-level).
- Auditoria de mudança de permissões (já coberta por trait Auditable? Roles não
  usam; deixar para depois).
- Tradução da interface.
