# Plano: Gerador de Entidades (Scaffolder via UI)

## Objetivo

Adicionar, dentro do submenu **Administração**, uma opção **"Criar Entidade"** que,
a partir de um nome (ex.: `Filiais`) e uma lista de colunas, gera de verdade:

1. Model (padrão do projeto: `#[Fillable]`, `Auditable`, `SoftDeletes`, `casts()`)
2. Migration (com as colunas informadas)
3. Controller Web resource (padrão `ProductController`: `authorize()` em cada método)
4. Policy (padrão `CategoryPolicy`: método `has()` + `isAdministrator()`)
5. Views (`index`, `form`, `show`) reutilizáveis
6. Permissões granulares no banco (`filiais.view/create/update/delete`)
7. Entrada no `PermissionLabels` (label + módulo)
8. Rota em `routes/web.php`
9. Item de menu em `layouts/app.blade.php`
10. `composer dump-autoload` + `optimize:clear` no final

Isso economiza ~90% do trabalho manual de criar um CRUD. O desenvolvedor só
preenche as colunas reais da migration e ajusta o formulário depois.

## Decisões já confirmadas com o usuário

- Abordagem: **Gerador real (A)** — roda `Artisan::call('make:*')` de verdade e
  escreve arquivos no disco em runtime.
- Schema: **Com colunas** — a tela tem campo para listar colunas
  (`nome:string, codigo:string, ativo:bool, ...`).

## Padrões do projeto que o gerador deve imitar (verificados)

- **Model**: `app/Models/Category.php` — usa `#[Fillable([...])]`, `use Auditable;`,
  `use SoftDeletes;`, método `casts(): array`.
- **Policy**: `app/Policies/CategoryPolicy.php` — método privado
  `has(User $u, string $p)` que retorna `$u->hasPermissionTo($p) || $u->isAdministrator()`;
  métodos `viewAny/view/view?/create/update/delete`.
- **Controller Web**: `app/Http/Controllers/Web/ProductController.php` — `authorize()`
  em cada ação, rotas nomeadas `recurso.index/show/create/store/edit/update/destroy`,
  `RedirectResponse`/`View`, retorna `view('recurso.index', [...])`.
- **PermissionLabels**: `app/Support/PermissionLabels.php` — `moduleLabel()` (label
  "Filiais"), `modules()` (registra prefixo `filiais`), `actionLabel()` já tem view/create/update/delete.
- **Menu**: `layouts/app.blade.php` — item com `@can('filiais.view')`.
- **`isAdministrator()`**: `app/Models/User.php:64` → `hasRole('administrador')`.
- **Policies**: auto-discovery do Laravel 13 (sem `AuthServiceProvider`).
- **Composer**: disponível em `/c/composer/composer` (via `php ...`).

## Arquivos a criar

### 1. `app/Http/Controllers/Web/EntityGeneratorController.php` (NOVO)
Controller com uma única ação `store(Request $request)`:
- Valida:
  - `name`: required, string, regex `^[A-Za-z][A-Za-z0-9_]*$` (nome da entidade em
    PascalCase, ex.: `Filial` → singular; pluralização via `Str::plural`/`Str::snake`).
  - `columns`: string com linhas `campo:tipo` (ex.: `nome:string`, `codigo:string:60`,
    `ativo:boolean`, `descricao:text`, `categoria_id:foreignId`).
- Normaliza nomes:
  - `$singular = ucfirst(Str::camel($name))` → ex.: `Filial`
  - `$table = Str::snake(Str::plural($name))` → ex.: `filiais`
  - `$module = Str::snake($name)` → ex.: `filiais` (prefixo de permissão)
  - `$viewDir = Str::plural(Str::snake($name))` → ex.: `filiais`
- Executa (em ordem, capturando output p/ log):
  1. `Artisan::call('make:model', ['name' => $singular, '--factory' => true, '--soft-deletes' => true])`
  2. `Artisan::call('make:migration', ['name' => "create_{$table}_table", '--create' => $table])`
  3. `Artisan::call('make:controller', ['name' => "Web/{$singular}Controller", '--model' => $singular, '--resource' => true])`
  4. `Artisan::call('make:policy', ['name' => "{$singular}Policy", '--model' => $singular])`
- **Pós-geração (edição de arquivos escritos)**:
  - Lê a migration recém-criada e **injeta as colunas** dentro do `Schema::create`,
    usando mapa de tipos (`string`→`$table->string()`, `text`→`text`, `boolean`→
    `boolean`, `integer`→`integer`, `decimal`→`decimal(10,2)`, `date`→`date`,
    `foreignId`→`foreignId(...)->constrained()`, `timestamps` já incluso).
  - Lê o Model e **injeta** `#[Fillable([...])]` + `use Auditable/SoftDeletes` + `casts()`
    (apenas se o campo for `boolean`/`decimal`/`date`/`integer`).
    - *Abordagem segura*: reescreve o arquivo do model com template próprio
    (em vez de regex frágil), já que o `make:model` gera um stub mínimo.
  - Lê a Policy e **substitui** o stub padrão pelo padrão `CategoryPolicy`
    (`has()` + `isAdministrator()` + permissões `$module.*`).
  - Lê o Controller e **injeta `authorize()`** em cada método (ou reescreve com template
    próprio padrão `ProductController` simplificado: index/show/create/store/edit/update/destroy).
- Gera **views** em `resources/views/{$viewDir}/` (`index.blade.php`, `form.blade.php`,
  `show.blade.php`) via `File::put` com templates Blade reutilizáveis (tabela genérica +
  formulário baseado nas colunas).
- **Permissões no banco**: cria `filiais.view/create/update/delete` (guard `web`)
  via `Permission::firstOrCreate`.
- **Registra no `PermissionLabels`**: edita `modules()` para adicionar `'filiais' => true`
  e `moduleLabel()` para adicionar `'filiais' => 'Filiais'` (edição de arquivo via
  inserção no array — posição segura antes do `default`/fechamento).
- **Rota**: edita `routes/web.php` inserindo bloco de rotas resource dentro do grupo
  `auth` (segue padrão `products.*`).
- **Menu**: edita `layouts/app.blade.php` inserindo item `@can('filiais.view')` no
  submenu Catálogos (ou novo submenu "Cadastros" — definir no plano de execução).
- **Pós-processamento**: `Artisan::call('optimize:clear')` e `composer dump-autoload`
  (via `exec`/`proc_open` com o composer do PATH).
- Redireciona de volta com `success` listando o que foi criado, e `error` se algo falhou
  (captura exceções por estágio, mostra mensagem clara).

### 2. `resources/views/admin/entities/create.blade.php` (NOVO)
Formulário com:
- Nome da entidade (ex.: `Filiais` ou `Filial`)
- Textarea de colunas (`nome:string`, `codigo:string:60`, `ativo:boolean`, ...)
- Botão "Gerar entidade"
- Helper visual mostrando o que será criado.

### 3. `routes/web.php` (EDITAR)
- Adicionar `use App\Http\Controllers\Web\EntityGeneratorController;`
- Adicionar rota `POST /admin/entidades` → `EntityGeneratorController@store` (protegida
  por `role:administrador` + `@can('roles.assign')` ou similar).

### 4. `layouts/app.blade.php` (EDITAR)
- Adicionar item "Criar Entidade" no submenu Administração (com `@can` adequado).

### 5. `PermissionLabels.php` (EDITAR via gerador)
- Adicionar `filiais` a `modules()` e `moduleLabel()`.

## Riscos e mitigações

- **Escrita em disco em runtime**: o servidor (PHP) precisa de permissão de escrita em
  `app/`, `database/migrations/`, `resources/views/`. Em dev local (Windows/PHP 8.4)
  isso é ok. Em produção/hospedagem compartilhada NÃO recomendado — avisar o usuário.
- **Edição de stubs gerados**: em vez de regex frágil, o gerador **reescreve** model,
  policy e controller com templates próprios (conhecemos os padrões exatos do projeto),
  reduzindo risco de corromper arquivo.
- **Tipos de coluna**: mapa fechado de tipos suportados; qualquer tipo fora do mapa
  vira `string` com aviso.
- **Nome duplicado**: validar que a tabela/model ainda não existe antes de gerar.
- **Composer dump-autoload**: pode falhar se o composer não estiver no PATH do processo
  PHP — tentar `exec('composer dump-autoload')` e logar; se falhar, instruir o usuário a
  rodar manualmente.

## Plano de execução (ordem)

1. Criar `EntityGeneratorController.php` com validação + helpers de normalização.
2. Implementar geração de arquivos (model/migration/controller/policy via Artisan).
3. Implementar pós-processamento (injetar colunas na migration, templates em model/policy/controller, views).
4. Implementar criação de permissões + edição de `PermissionLabels`.
5. Implementar edição de `routes/web.php` + menu.
6. Criar view `admin/entities/create.blade.php`.
7. Registrar rota + item de menu.
8. Testar ponta a ponta: gerar entidade "Filiais" com colunas de exemplo, verificar
   arquivos criados, permissões no banco, item de menu visível p/ admin, e rodar
   `php artisan migrate` (ou `migrate:status`) para confirmar migration válida.

## Validação

- Gerar "Filiais" via UI; checar:
  - `app/Models/Filial.php`, `database/migrations/*_create_filiais_table.php`,
    `app/Http/Controllers/Web/FilialController.php`, `app/Policies/FilialPolicy.php`,
    `resources/views/filiais/*.blade.php` existem.
  - `php artisan migrate:status` mostra a migration como pendente (ou rodar migrate em dev).
  - Permissões `filiais.view/create/update/delete` no banco.
  - Item "Filiais" aparece no menu p/ admin; some p/ papel sem `filiais.view`.
  - `php artisan route:list | grep filiais` lista as rotas.
