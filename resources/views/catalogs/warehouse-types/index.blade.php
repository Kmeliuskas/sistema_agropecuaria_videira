@extends('layouts.app')

@section('title', 'Tipos de Almoxarifado — WMS')
@section('page_title', 'Tipos de Almoxarifado')

@section('content')
<div class="space-y-4">
    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h2 class="text-sm font-medium text-muted-foreground">Cadastro de tipos de almoxarifado</h2>
        @can('warehouses.create')
        <a href="{{ route('warehouse-types.create') }}" class="btn-primary">
            + Novo tipo
        </a>
        @endcan
    </div>

    <form method="GET" class="flex flex-wrap items-end gap-3 card p-4">
        <div class="flex-1 min-w-[200px]">
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome ou código"
                class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Situação</label>
            <select name="active"
                class="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                <option value="" {{ request('active') === '' || request('active') === null ? 'selected' : '' }}>Ativos</option>
                <option value="all" {{ request('active') === 'all' ? 'selected' : '' }}>Todos</option>
                <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inativos</option>
            </select>
        </div>
        <button type="submit" class="btn-primary">Filtrar</button>
        @if (request()->anyFilled(['search', 'active']))
            <a href="{{ route('warehouse-types.index') }}" class="rounded-lg border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted">Limpar</a>
        @endif
    </form>

    <div class="overflow-x-auto card">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Nome</th>
                    <th class="px-4 py-3">Descrição</th>
                    <th class="px-4 py-3">Situação</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($types as $type)
                    <tr id="{{ dom_id($type) }}">
                        <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $type->code }}</td>
                        <td class="px-4 py-3 font-medium text-foreground">{{ $type->name }}</td>
                        <td class="px-4 py-3 text-sm text-muted-foreground">{{ $type->description ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($type->is_active)
                                <span class="badge-success">Ativo</span>
                            @else
                                <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">Inativo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                @can('warehouses.update')
                                <a href="{{ route('warehouse-types.edit', $type) }}"
                                   class="rounded-lg border border-border px-3 py-1 text-xs font-medium text-muted-foreground transition hover:bg-muted">
                                    Editar
                                </a>
                                @endcan
                                @can('warehouses.delete')
                                <form method="POST" action="{{ route('warehouse-types.destroy', $type) }}"
                                      onsubmit="return confirm('Remover este tipo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="rounded-lg border border-danger/30 px-3 py-1 text-xs font-medium text-danger transition hover:bg-danger/10">
                                        Remover
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground/70">
                            Nenhum tipo de almoxarifado encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $types->links() }}
</div>
@endsection
