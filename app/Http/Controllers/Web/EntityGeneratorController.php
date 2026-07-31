<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\PermissionLabels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * Gera uma entidade completa (CRUD) a partir de um nome e colunas.
 *
 * Cria de verdade: model, migration, controller Web resource, policy,
 * views (index/form/show), permissões granulares, entrada no
 * PermissionLabels, rota e item de menu.
 *
 * ⚠️ Escreve arquivos em disco em runtime. Use apenas em ambiente de
 * desenvolvimento.
 */
class EntityGeneratorController extends Controller
{
    /** Tipos de coluna suportados e seu método de schema. */
    protected const COLUMN_TYPES = [
        'string' => ['schema' => "string('{col}', {len})", 'cast' => null],
        'text' => ['schema' => "text('{col}')", 'cast' => null],
        'integer' => ['schema' => "integer('{col}')", 'cast' => 'integer'],
        'decimal' => ['schema' => "decimal('{col}', 12, 2)", 'cast' => 'decimal:2'],
        'boolean' => ['schema' => "boolean('{col}')->default(false)", 'cast' => 'boolean'],
        'date' => ['schema' => "date('{col}')", 'cast' => 'date'],
        'datetime' => ['schema' => "dateTime('{col}')", 'cast' => 'datetime'],
        'time' => ['schema' => "time('{col}')", 'cast' => 'string'],
        'email' => ['schema' => "string('{col}', 191)", 'cast' => null],
        'foreignid' => ['schema' => "foreignId('{col}')->constrained()->cascadeOnDelete()", 'cast' => null],
        'uuid' => ['schema' => "uuid('{col}')", 'cast' => null],
    ];

    /**
     * Exibe o formulário de criação de entidade.
     */
    public function create(): \Illuminate\View\View
    {
        return view('admin.entities.create');
    }

    /**
     * Processa o formulário e gera a entidade.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'regex:/^[A-Za-z][A-Za-z0-9_ ]*$/'],
            'columns' => ['required', 'string'],
            'submenu' => ['required', 'in:catalogos,cadastros,estoque,administracao,new'],
            'novo_submenu' => ['nullable', 'string', 'max:40', 'regex:/^[\p{L}0-9 ]+$/u'],
        ]);

        if ($data['submenu'] === 'new' && empty(trim($data['novo_submenu'] ?? ''))) {
            return redirect()->back()->withInput()->with('error', 'Informe o nome do novo submenu.');
        }

        $meta = $this->parseName($data['name']);
        $columns = $this->parseColumns($data['columns']);

        // Submenu de destino.
        $meta['submenu'] = $data['submenu'];
        if ($data['submenu'] === 'new') {
            $meta['novoSubmenu'] = trim($data['novo_submenu']);
            $meta['novoSubmenuSnake'] = Str::snake($meta['novoSubmenu']);
        }

        // Validações de conflito antes de tocar em arquivos.
        $errors = $this->validateConflicts($meta, $columns);
        if ($errors) {
            return redirect()->back()->withInput()->with('error', $errors);
        }

        $log = [];

        try {
            $this->runArtisanScaffold($meta, $log);
            $this->writeModel($meta, $columns, $log);
            $this->writeMigrationColumns($meta, $columns, $log);
            $this->writeController($meta, $columns, $log);
            $this->writePolicy($meta, $log);
            $this->writeViews($meta, $columns, $log);
            $this->createPermissions($meta, $log);
            $this->registerPermissionLabels($meta, $log);
            $this->registerRoute($meta, $log);
            $this->registerMenu($meta, $log);

            Artisan::call('optimize:clear');
            $this->composerDumpAutoload($log);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error',
                'Falha ao gerar entidade: ' . $e->getMessage() . "\n\nLog:\n" . implode("\n", $log));
        }

        return redirect()->route('dashboard')
            ->with('success', "Entidade {$meta['label']} gerada com sucesso.\n\n" . implode("\n", $log));
    }

    /**
     * Normaliza o nome da entidade em suas variantes.
     *
     * O usuário deve informar o SINGULAR (ex.: "Filial"). O plural é gerado
     * segundo regras de português (o Laravel não conhece plurais em PT-BR).
     */
    protected function parseName(string $raw): array
    {
        $singular = trim($raw);
        $studly = Str::studly($singular);          // Filial
        $snake = Str::snake($singular);            // filial
        $table = Str::snake($this->pluralPt($singular)); // filiais
        $viewDir = $table;                         // filiais

        return [
            'raw' => $raw,
            'studly' => $studly,
            'snake' => $snake,
            'table' => $table,
            'viewDir' => $viewDir,
            'singularSnake' => $snake,
            'routeBase' => $viewDir,
            'label' => Str::title(str_replace('_', ' ', $snake)),
            'labelPlural' => Str::title(str_replace('_', ' ', $table)),
        ];
    }

    /**
     * Pluraliza uma palavra em português (regras básicas PT-BR).
     */
    protected function pluralPt(string $word): string
    {
        if (preg_match('/ão$/i', $word)) {
            return preg_replace('/ão$/i', 'ões', $word);
        }
        if (preg_match('/al$/i', $word)) {
            return preg_replace('/al$/i', 'ais', $word); // Filial -> Filiais
        }
        if (preg_match('/el$/i', $word)) {
            return preg_replace('/el$/i', 'eis', $word); // Papel -> Papeis
        }
        if (preg_match('/ol$/i', $word)) {
            return preg_replace('/ol$/i', 'ois', $word); // Solo -> Solos (aprox.)
        }
        if (preg_match('/il$/i', $word)) {
            return preg_replace('/il$/i', 'is', $word);  // Email -> Emails
        }
        if (preg_match('/m$/i', $word)) {
            return preg_replace('/m$/i', 'ns', $word);   // Homem -> Homens
        }
        if (preg_match('/[rsz]$/i', $word)) {
            return $word . 'es';                        // Setor -> Setores
        }
        if (preg_match('/[aeiou]s$/i', $word)) {
            return $word;                               // já plural
        }
        return $word . 's';                            // Categoria -> Categorias
    }

    /**
     * Converte o textarea "campo:tipo:tamanho" em array estruturado.
     */
    protected function parseColumns(string $raw): array
    {
        $result = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode(':', $line));
            $name = Str::snake($parts[0]);
            $type = strtolower($parts[1] ?? 'string');
            $len = isset($parts[2]) && is_numeric($parts[2]) ? (int) $parts[2] : null;

            if (! array_key_exists($type, self::COLUMN_TYPES)) {
                $type = 'string';
            }

            $result[$name] = ['type' => $type, 'len' => $len];
        }

        return $result;
    }

    /**
     * Verifica se já existe model/tabela/permissão com o mesmo nome.
     */
    protected function validateConflicts(array $meta, array $columns): ?string
    {
        if (empty($columns)) {
            return 'Informe ao menos uma coluna.';
        }

        $modelPath = app_path("Models/{$meta['studly']}.php");
        if (File::exists($modelPath)) {
            return "O model {$meta['studly']} já existe.";
        }

        if (Permission::where('name', "{$meta['snake']}.view")->exists()) {
            return "Já existem permissões para o módulo {$meta['snake']}.";
        }

        // 'id', 'created_at', 'updated_at', 'deleted_at' são reservados.
        foreach (array_keys($columns) as $col) {
            if (in_array($col, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                return "A coluna '{$col}' é reservada e não pode ser declarada.";
            }
        }

        return null;
    }

    /**
     * Dispara os comandos make:* do Laravel (cria stubs base).
     */
    protected function runArtisanScaffold(array $meta, array &$log): void
    {
        Artisan::call('make:model', [
            'name' => $meta['studly'],
            '--factory' => true,
        ]);
        $log[] = "Model base criado: app/Models/{$meta['studly']}.php";

        Artisan::call('make:migration', [
            'name' => "create_{$meta['table']}_table",
            '--create' => $meta['table'],
        ]);
        $log[] = "Migration criada: database/migrations/*_create_{$meta['table']}_table.php";

        Artisan::call('make:controller', [
            'name' => "Web/{$meta['studly']}Controller",
            '--model' => $meta['studly'],
            '--resource' => true,
        ]);
        $log[] = "Controller criado: app/Http/Controllers/Web/{$meta['studly']}Controller.php";

        Artisan::call('make:policy', [
            'name' => "{$meta['studly']}Policy",
            '--model' => $meta['studly'],
        ]);
        $log[] = "Policy criada: app/Policies/{$meta['studly']}Policy.php";
    }

    /**
     * Reescreve o model com o padrão do projeto (Fillable, Auditable, SoftDeletes, casts).
     */
    protected function writeModel(array $meta, array $columns, array &$log): void
    {
        $fillable = array_keys($columns);
        $casts = [];
        foreach ($columns as $col => $cfg) {
            $cast = self::COLUMN_TYPES[$cfg['type']]['cast'];
            if ($cast) {
                $casts[$col] = $cast;
            }
        }

        $castsBlock = $casts
            ? "    protected function casts(): array\n    {\n        return [" .
              implode(', ', array_map(fn ($c, $t) => "'{$c}' => '{$t}'", array_keys($casts), $casts)) .
              "];\n    }\n"
            : '';

        $content = <<<PHP
        <?php

        namespace App\Models;

        use App\Traits\Auditable;
        use Illuminate\Database\Eloquent\Attributes\Fillable;
        use Illuminate\Database\Eloquent\Factories\HasFactory;
        use Illuminate\Database\Eloquent\Model;
        use Illuminate\Database\Eloquent\SoftDeletes;

        #[Fillable([{$this->arrayToList($fillable)}])]
        class {$meta['studly']} extends Model
        {
            use Auditable;
            use HasFactory;
            use SoftDeletes;

            protected \$table = '{$meta['table']}';

        {$castsBlock}
        }
        PHP;

        File::put(app_path("Models/{$meta['studly']}.php"), $this->trimHeredoc($content));
        $log[] = "Model reescrito com padrão do projeto (Fillable, casts).";
    }

    /**
     * Injeta as colunas dentro do Schema::create da migration gerada.
     */
    protected function writeMigrationColumns(array $meta, array $columns, array &$log): void
    {
        $migrationPath = database_path('migrations');
        $file = collect(File::files($migrationPath))
            ->first(fn ($f) => str_contains($f->getFilename(), "create_{$meta['table']}_table"));

        if (! $file) {
            $log[] = "Atenção: migration não encontrada para injetar colunas manualmente.";
            return;
        }

        $lines = [];
        foreach ($columns as $col => $cfg) {
            $schema = self::COLUMN_TYPES[$cfg['type']]['schema'];
            $schema = str_replace('{col}', $col, $schema);
            if ($cfg['len'] && str_contains($schema, "{len}")) {
                $schema = str_replace('{len}', (string) $cfg['len'], $schema);
            } else {
                // Remove a vírgula e o placeholder vazios (ex.: string('nome', ) → string('nome')).
                $schema = str_replace('{len}', '', $schema);
                $schema = preg_replace("/,\s*$/", '', $schema);
            }
            $lines[] = "            \$table->{$schema};";
        }

        $body = implode("\n", $lines);
        $content = File::get($file->getPathname());
        $content = preg_replace(
            '/\$table->id\(\);(.*?)\$table->timestamps\(\);/s',
            "\$table->id();\n{$body}\n            \$table->softDeletes();\n            \$table->timestamps();",
            $content
        );
        File::put($file->getPathname(), $content);
        $log[] = "Colunas injetadas na migration (" . count($columns) . " coluna(s)).";
    }

    /**
     * Reescreve o controller com o padrão Web resource (authorize em cada ação).
     */
    protected function writeController(array $meta, array $columns, array &$log): void
    {
        $model = $meta['studly'];
        $var = Str::camel($meta['studly']);        // filial
        $varPlural = Str::camel($meta['table']);   // filiais
        $module = $meta['snake'];
        $viewDir = $meta['viewDir'];
        $searchable = collect($columns)->filter(fn ($c) => in_array($c['type'], ['string', 'text', 'email']))
            ->keys()->take(3)->all();

        $searchBlock = '';
        if ($searchable) {
            $ors = implode("\n                    ", array_map(
                fn ($c) => "\$q->orWhere('{$c}', 'like', \"%{\$search}%\");",
                $searchable
            ));
            $searchBlock = <<<PHP
                if (\$search = request('search')) {
                    \$query->where(function (\$q) use (\$search) {
                        {$ors}
                    });
                }

        PHP;
        }

        $fillable = array_keys($columns);
        $rules = $this->validationRules($columns);

        $content = <<<PHP
        <?php

        namespace App\Http\Controllers\Web;

        use App\Http\Controllers\Controller;
        use App\Models\\{$model};
        use Illuminate\Http\RedirectResponse;
        use Illuminate\Http\Request;
        use Illuminate\View\View;

        class {$model}Controller extends Controller
        {
            public function index(): View
            {
                \$this->authorize('viewAny', {$model}::class);

                \$query = {$model}::query()->latest();

        {$searchBlock}
                \${$varPlural} = \$query->paginate(20)->withQueryString();

                return view('{$viewDir}.index', [
                    '{$varPlural}' => \${$varPlural},
                ]);
            }

            public function create(): View
            {
                \$this->authorize('create', {$model}::class);

                return view('{$viewDir}.form', [
                    '{$var}' => null,
                ]);
            }

            public function store(Request \$request): RedirectResponse
            {
                \$this->authorize('create', {$model}::class);

                \$data = \$request->validate({$rules});

                \${$var} = {$model}::create(\$data);

                return redirect()
                    ->route('{$module}.index')
                    ->with('success', "{$meta['label']} criado(a) com sucesso.");
            }

            public function show({$model} \${$var}): View
            {
                \$this->authorize('view', \${$var});

                return view('{$viewDir}.show', [
                    '{$var}' => \${$var},
                ]);
            }

            public function edit({$model} \${$var}): View
            {
                \$this->authorize('update', \${$var});

                return view('{$viewDir}.form', [
                    '{$var}' => \${$var},
                ]);
            }

            public function update(Request \$request, {$model} \${$var}): RedirectResponse
            {
                \$this->authorize('update', \${$var});

                \$data = \$request->validate({$rules});

                \${$var}->update(\$data);

                return redirect()
                    ->route('{$module}.index')
                    ->with('success', "{$meta['label']} atualizado(a).");
            }

            public function destroy({$model} \${$var}): RedirectResponse
            {
                \$this->authorize('delete', \${$var});

                \${$var}->delete();

                return redirect()
                    ->route('{$module}.index')
                    ->with('success', "{$meta['label']} removido(a).");
            }
        }
        PHP;

        File::put(app_path("Http/Controllers/Web/{$model}Controller.php"), $this->trimHeredoc($content));
        $log[] = "Controller reescrito com authorize() em cada ação.";
    }

    /**
     * Reescreve a policy com o padrão do projeto (has + isAdministrator).
     */
    protected function writePolicy(array $meta, array &$log): void
    {
        $model = $meta['studly'];
        $module = $meta['snake'];
        $var = Str::camel($meta['studly']);

        $content = <<<PHP
        <?php

        namespace App\Policies;

        use App\Models\User;
        use App\Models\\{$model};

        /**
         * RBAC para {$meta['label']}. Permissões: {$module}.view/create/update/delete.
         * Administrador sempre passa.
         */
        class {$model}Policy
        {
            protected function has(User \$user, string \$permission): bool
            {
                return \$user->hasPermissionTo(\$permission) || \$user->isAdministrator();
            }

            public function viewAny(User \$user): bool
            {
                return \$this->has(\$user, '{$module}.view');
            }

            public function view(User \$user, {$model} \${$var}): bool
            {
                return \$this->has(\$user, '{$module}.view');
            }

            public function create(User \$user): bool
            {
                return \$this->has(\$user, '{$module}.create');
            }

            public function update(User \$user, {$model} \${$var}): bool
            {
                return \$this->has(\$user, '{$module}.update');
            }

            public function delete(User \$user, {$model} \${$var}): bool
            {
                return \$this->has(\$user, '{$module}.delete');
            }
        }
        PHP;

        File::put(app_path("Policies/{$model}Policy.php"), $this->trimHeredoc($content));
        $log[] = "Policy reescrita com padrão RBAC (has + isAdministrator).";
    }

    /**
     * Cria as views genéricas (index, form, show).
     */
    protected function writeViews(array $meta, array $columns, array &$log): void
    {
        $viewDir = resource_path("views/{$meta['viewDir']}");
        File::ensureDirectoryExists($viewDir);

        $varPlural = Str::camel($meta['table']);   // filiais
        $var = Str::camel($meta['studly']);        // filial
        $module = $meta['snake'];                  // filial

        File::put("{$viewDir}/index.blade.php", $this->trimHeredoc(<<<BLADE
        @extends('layouts.app')

        @section('title', '{$meta['labelPlural']} — WMS')
        @section('page_title', '{$meta['labelPlural']}')

        @section('content')
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <form method="GET" class="card flex flex-wrap items-end gap-3 p-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Buscar</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar..." class="input">
                    </div>
                    <button type="submit" class="btn-primary">Filtrar</button>
                </form>
                @can('{$module}.create')
                <a href="{{ route('{$module}.create') }}" class="btn-primary">Novo(a) {$meta['label']}</a>
                @endcan
            </div>

            <div class="card overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            @foreach (\${$varPlural}->first() ? array_keys(\${$varPlural}->first()->toArray()) : [] as \$field)
                                @if (! in_array(\$field, ['id', 'created_at', 'updated_at', 'deleted_at']))
                                    <th class="px-4 py-3">{{ \Str::title(str_replace('_', ' ', \$field)) }}</th>
                                @endif
                            @endforeach
                            <th class="px-4 py-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse (\${$varPlural} as \${$var})
                            <tr class="hover:bg-muted">
                                <td class="px-4 py-3 text-muted-foreground">{{ \${$var}->id }}</td>
                                @foreach (\${$var}->toArray() as \$field => \$value)
                                    @if (! in_array(\$field, ['id', 'created_at', 'updated_at', 'deleted_at']))
                                        <td class="px-4 py-3 text-foreground">
                                            @if (is_bool(\$value))
                                                {{ \$value ? 'Sim' : 'Não' }}
                                            @else
                                                {{ \$value ?? '—' }}
                                            @endif
                                        </td>
                                    @endif
                                @endforeach
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        @can('{$module}.view')
                                        <a href="{{ route('{$module}.show', \${$var}) }}" class="btn-ghost px-2 py-1">Ver</a>
                                        @endcan
                                        @can('{$module}.update')
                                        <a href="{{ route('{$module}.edit', \${$var}) }}" class="btn-ghost px-2 py-1">Editar</a>
                                        @endcan
                                        @can('{$module}.delete')
                                        <form method="POST" action="{{ route('{$module}.destroy', \${$var}) }}" onsubmit="return confirm('Remover?');">
                                            @csrf @method('DELETE')
                                            <button class="text-danger px-2 py-1">Excluir</button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="20" class="px-4 py-8 text-center text-muted-foreground">Nenhum registro encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ \${$varPlural}->links() }}
        </div>
        @endsection
        BLADE));

        File::put("{$viewDir}/form.blade.php", $this->trimHeredoc(<<<BLADE
        @extends('layouts.app')

        @php
            \$isEdit = ! empty(\${$var});
            \$title = \$isEdit ? "Editar {$meta['label']}" : "Novo(a) {$meta['label']}";
        @endphp

        @section('title', \$title . ' — WMS')
        @section('page_title', \$title)

        @section('content')
        <div class="mx-auto max-w-3xl">
            @if (session('success'))<div class="alert-success mb-4">{{ session('success') }}</div>@endif

            <form method="POST"
                action="{{ \$isEdit ? route('{$module}.update', \${$var}) : route('{$module}.store') }}"
                class="card space-y-4 p-6">
                @csrf
                @if (\$isEdit) @method('PUT') @endif

        {$this->formFields($columns, $var)}

                <div class="flex justify-end gap-3">
                    <a href="{{ route('{$module}.index') }}" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">{{ \$isEdit ? 'Salvar' : 'Criar' }}</button>
                </div>
            </form>
        </div>
        @endsection
        BLADE));

        File::put("{$viewDir}/show.blade.php", $this->trimHeredoc(<<<BLADE
        @extends('layouts.app')

        @section('title', '{$meta['label']}' . ' — WMS')
        @section('page_title', '{$meta['label']}')

        @section('content')
        <div class="mx-auto max-w-3xl">
            <a href="{{ route('{$module}.index') }}" class="btn-ghost mb-4">← Voltar</a>
            <div class="card space-y-3 p-6">
                @foreach (\${$var}->toArray() as \$field => \$value)
                    @if (! in_array(\$field, ['id', 'created_at', 'updated_at', 'deleted_at']))
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">{{ \Str::title(str_replace('_', ' ', \$field)) }}</span>
                            <span class="text-foreground">
                                @if (is_bool(\$value)){{ \$value ? 'Sim' : 'Não' }}@else{{ \$value ?? '—' }}@endif
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endsection
        BLADE));

        $log[] = "Views criadas em resources/views/{$meta['viewDir']}/ (index, form, show).";
    }

    /**
     * Cria as 4 permissões granulares no banco.
     */
    protected function createPermissions(array $meta, array &$log): void
    {
        $module = $meta['snake'];
        $created = [];
        foreach (['view', 'create', 'update', 'delete'] as $action) {
            $perm = Permission::firstOrCreate([
                'name' => "{$module}.{$action}",
                'guard_name' => 'web',
            ]);
            $created[] = $perm->name;
        }
        $log[] = 'Permissões criadas: ' . implode(', ', $created);

        // Garante que o administrador (papel protegido) também receba as
        // novas permissões, senão o menu não aparece para ele.
        $admin = \Spatie\Permission\Models\Role::where('name', 'administrador')->first();
        if ($admin) {
            $admin->givePermissionTo($created);
            $log[] = "Permissões atribuídas ao papel 'administrador'.";
        }
    }

    /**
     * Registra o módulo e o label em PermissionLabels.php.
     */
    protected function registerPermissionLabels(array $meta, array &$log): void
    {
        $path = app_path('Support/PermissionLabels.php');
        $content = File::get($path);

        // moduleLabel: inserir antes do 'default =>' (última linha do match).
        $content = preg_replace(
            "/('roles' => 'Papéis',\n)/",
            "\$1            '{$meta['snake']}' => '{$meta['labelPlural']}',\n",
            $content
        );

        // modules(): inserir antes do fechamento do array (']' após 'roles' => true).
        $content = preg_replace(
            "/('roles' => true,\n)/",
            "\$1            '{$meta['snake']}' => true,\n",
            $content
        );

        File::put($path, $content);
        $log[] = "Registrado '{$meta['snake']}' em PermissionLabels (label + módulo).";
    }

    /**
     * Adiciona o grupo de rotas resource em routes/web.php.
     */
    protected function registerRoute(array $meta, array &$log): void
    {
        $path = base_path('routes/web.php');
        $content = File::get($path);

        $module = $meta['snake'];
        $studly = $meta['studly'];
        $snakePlural = $meta['table'];

        $paramSingular = $meta['singularSnake'];

        $block = <<<PHP

            // Entidade gerada: {$meta['labelPlural']}
            Route::resource('{$snakePlural}', \\App\\Http\\Controllers\\Web\\{$studly}Controller::class)
                ->names('{$module}')
                ->parameter('{$snakePlural}', '{$paramSingular}')
                ->middleware('can:{$module}.view');

        PHP;

        // Insere antes do fechamento do grupo auth (último "});" de nível do grupo).
        // Inserção simples: antes do comentário de Catálogos para manter organização.
        $content = preg_replace(
            "/(\/\/ Catálogos \(leitura\)\n)/",
            "{$block}\n\$1",
            $content,
            1,
            $count
        );

        if (! $count) {
            // Fallback: adiciona antes do fechamento do grupo auth.
            $content = preg_replace('/(});\s*$)/', "{$block}\n\$1", $content);
        }

        File::put($path, $content);
        $log[] = "Rota resource '{$snakePlural}' registrada (names: {$module}.*).";
    }

    /**
     * Adiciona o item de menu no submenu escolhido (layouts/app.blade.php).
     *
     * Submenus existentes: catalogos, cadastros, estoque, administracao.
     * Se $meta['submenu'] === 'new', cria um novo submenu colapsável
     * (visível somente para quem tem a permissão de visualizar a entidade).
     */
    protected function registerMenu(array $meta, array &$log): void
    {
        $path = resource_path('views/layouts/app.blade.php');
        $content = File::get($path);

        $module = $meta['snake'];
        $routeName = $meta['snake'] . '.index';   // ex.: filial.index (Route::resource ->names('filial'))
        $label = $meta['labelPlural'];

        $item = <<<BLADE
                        @can('{$module}.view')
                        <a href="{{ route('{$routeName}') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('{$module}.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16M9 3v18" />
                            </svg>
                            {$label}
                        </a>
                        @endcan

BLADE;

        if (($meta['submenu'] ?? 'catalogos') === 'new') {
            $content = $this->insertNewSubmenu($content, $meta, $item, $log);
        } else {
            $content = $this->insertIntoSubmenu($content, $meta, $item, $log);
        }

        File::put($path, $content);
    }

    /**
     * Insere o item dentro do <div x-show="open"> do submenu existente.
     */
    protected function insertIntoSubmenu(string $content, array $meta, string $item, array &$log): string
    {
        $guards = [
            'catalogos'    => "@canany(['categories.view', 'brands.view', 'manufacturers.view', 'suppliers.view', 'units.view', 'subcategories.view', 'filial.view'])",
            'cadastros'    => "@canany(['products.view', 'warehouses.view', 'sectors.view'])",
            'estoque'      => "@canany(['stock.view', 'requests.view'])",
            'administracao'=> "@canany(['users.view', 'roles.view', 'roles.assign'])",
        ];

        $guard = $guards[$meta['submenu']] ?? $guards['catalogos'];

        // Garante que a permissão da entidade esteja no guard do submenu.
        if (! str_contains($guard, "'{$meta['snake']}.view'")) {
            $newGuard = preg_replace("/('roles\.assign'|'subcategories\.view')(\])/", "$1, '{$meta['snake']}.view'$2", $guard, 1, $count);
            if ($count) {
                $content = str_replace($guard, $newGuard, $content);
                $guard = $newGuard;
                $log[] = "Permissão '{$meta['snake']}.view' adicionada ao guard do submenu.";
            }
        }

        // Localiza o @canany do submenu e insere o item antes do </div></div>@endcan final.
        $pos = strpos($content, $guard);
        if ($pos === false) {
            $log[] = "Atenção: submenu não encontrado para inserir o item manualmente.";
            return $content;
        }

        $tail = substr($content, $pos);
        if (preg_match("/(\s*<\/div>\n\s*<\/div>\n\s*@endcan)/", $tail, $m, PREG_OFFSET_CAPTURE)) {
            $insertAt = $pos + $m[1][1];
            $content = substr($content, 0, $insertAt) . $item . substr($content, $insertAt);
            $log[] = "Item de menu '{$meta['labelPlural']}' adicionado no submenu {$meta['submenu']}.";
        } else {
            $log[] = "Atenção: não foi possível localizar o fim do submenu para inserir o item.";
        }

        return $content;
    }

    /**
     * Cria um novo submenu colapsável antes do comentário de Administração.
     */
    protected function insertNewSubmenu(string $content, array $meta, string $item, array &$log): string
    {
        $name = $meta['novoSubmenu'];
        $module = $meta['snake'];
        $routePattern = $module . '.*';

        $block = <<<BLADE
                {{-- Submenu gerado: {$name} --}}
                @canany(['{$module}.view'])
                <div x-data="{ open: {{ request()->routeIs('{$routePattern}') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 font-medium text-muted-foreground transition-colors duration-200 hover:bg-muted hover:text-foreground">
                        <span class="flex items-center gap-3">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16M9 3v18" />
                            </svg>
                            {$name}
                        </span>
                        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="mt-1 space-y-1">
{$item}                    </div>
                </div>
                @endcan

BLADE;

        $content = preg_replace(
            "/(\s*{{\-\- Administração \(somente quem tem acesso a algum item\) \-\-}})/",
            "{$block}\$1",
            $content,
            1
        );

        $log[] = "Novo submenu '{$name}' criado com o item '{$meta['labelPlural']}'.";
        return $content;
    }

    /**
     * Gera as regras de validação do controller.
     */
    protected function validationRules(array $columns): string
    {
        $rules = [];
        foreach ($columns as $col => $cfg) {
            $type = $cfg['type'];
            if ($type === 'integer') {
                $rule = ['nullable', 'integer'];
            } elseif ($type === 'decimal') {
                $rule = ['nullable', 'numeric', 'min:0'];
            } elseif ($type === 'boolean') {
                $rule = ['boolean'];
            } elseif (in_array($type, ['date', 'datetime', 'time'], true)) {
                $rule = ['nullable', 'date'];
            } elseif ($type === 'email') {
                $rule = ['nullable', 'email', 'max:191'];
            } elseif ($type === 'foreignid') {
                $ref = $this->pluralPt(Str::replaceLast('_id', '', $col));
                $rule = ["nullable", "exists:{$ref},id"];
            } else {
                $rule = ['nullable', 'string', 'max:' . ($cfg['len'] ?? 255)];
            }
            $rules[$col] = '[' . implode(', ', array_map(fn ($r) => "'{$r}'", $rule)) . ']';
        }

        return "[\n            " . implode(",\n            ", array_map(fn ($c, $r) => "'{$c}' => {$r}", array_keys($rules), $rules)) . ",\n        ]";
    }

    /**
     * Gera os campos do formulário Blade.
     */
    protected function formFields(array $columns, string $var): string
    {
        $out = [];
        foreach ($columns as $col => $cfg) {
            $label = Str::title(str_replace('_', ' ', $col));
            $value = "\${$var}->{$col} ?? old('{$col}')";
            if (in_array($cfg['type'], ['text', 'string', 'email'], true)) {
                $out[] = <<<BLADE
                <div>
                    <label class="label">{$label}</label>
                    <input type="text" name="{$col}" value="{{ {$value} }}"
                        class="input">
                    @error('{$col}')<p class="mt-1 text-sm text-danger">{{ \$message }}</p>@enderror
                </div>
                BLADE;
            } elseif ($cfg['type'] === 'boolean') {
                $out[] = <<<BLADE
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="{$col}" value="1" {{ {$value} ? 'checked' : '' }} class="h-4 w-4">
                        <span class="label">{$label}</span>
                    </label>
                </div>
                BLADE;
            } elseif (in_array($cfg['type'], ['integer', 'decimal'], true)) {
                $out[] = <<<BLADE
                <div>
                    <label class="label">{$label}</label>
                    <input type="number" name="{$col}" value="{{ {$value} }}"
                        class="input">
                    @error('{$col}')<p class="mt-1 text-sm text-danger">{{ \$message }}</p>@enderror
                </div>
                BLADE;
            } elseif ($cfg['type'] === 'date') {
                $out[] = <<<BLADE
                <div>
                    <label class="label">{$label}</label>
                    <input type="date" name="{$col}" value="{{ {$value} }}"
                        class="input">
                </div>
                BLADE;
            } else {
                $out[] = <<<BLADE
                <div>
                    <label class="label">{$label}</label>
                    <input type="text" name="{$col}" value="{{ {$value} }}"
                        class="input">
                </div>
                BLADE;
            }
        }

        return implode("\n", $out);
    }

    /**
     * Helper: transforma array em lista " 'a', 'b' ".
     */
    protected function arrayToList(array $items): string
    {
        return implode(', ', array_map(fn ($i) => "'{$i}'", $items));
    }

    /**
     * Remove a indentação extra introduzida por heredocs com margin.
     */
    protected function trimHeredoc(string $content): string
    {
        // Remove as 8 spaces de margem que o heredoc indentado adiciona.
        $lines = explode("\n", $content);
        $lines = array_map(fn ($l) => preg_replace('/^        /', '', $l), $lines);

        return trim(implode("\n", $lines)) . "\n";
    }

    /**
     * Tenta rodar composer dump-autoload (necessário p/ novos classes).
     */
    protected function composerDumpAutoload(array &$log): void
    {
        $composer = 'composer';
        if (PHP_OS_FAMILY === 'Windows') {
            $composer = 'composer.bat';
        }

        $output = [];
        $code = 0;
        exec("{$composer} dump-autoload 2>&1", $output, $code);

        if ($code === 0) {
            $log[] = 'composer dump-autoload executado.';
        } else {
            $log[] = 'Atenção: não foi possível rodar composer dump-autoload automaticamente. ' .
                'Rode manualmente: composer dump-autoload';
        }
    }
}
