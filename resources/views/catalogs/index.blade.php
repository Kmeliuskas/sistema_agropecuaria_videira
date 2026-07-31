@extends('layouts.app')

@section('title', $title . ' — WMS')
@section('page_title', $title)

@section('content')
<div class="space-y-4">
    <turbo-echo-stream-source type="private" channel="catalogs.{{ $catalog }}"></turbo-echo-stream-source>

    <div class="flex items-center justify-between">
        <h2 class="text-sm font-medium text-muted-foreground">Cadastro de {{ strtolower($title) }}</h2>
        @can("{$catalog}.create")
        <a href="{{ route($catalog . '.create') }}"
            class="btn-primary">
            + Nova {{ Str::singular($title) }}
        </a>
        @endcan
    </div>

    {{-- Filtros --}}
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
            <a href="{{ route($catalog . '.index') }}" class="rounded-lg border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted">Limpar</a>
        @endif
    </form>

    <div class="card overflow-x-auto">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                    @foreach ($columns as $key => $label)
                        <th class="px-4 py-3">{{ $label }}</th>
                    @endforeach
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($items as $item)
                    <tr class="hover:bg-muted">
                        @foreach ($columns as $key => $label)
                            @if ($key === 'is_active')
                                <td class="px-4 py-3">
                                    @if ($item->is_active)
                                        <span class="badge badge-success">Ativo</span>
                                    @else
                                        <span class="badge badge-muted">Inativo</span>
                                    @endif
                                </td>
                            @elseif ($key === 'category_id')
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ $item->category?->name ?? '—' }}
                                </td>
                            @elseif ($key === 'color')
                                <td class="px-4 py-3">
                                    @if ($item->color)
                                        <span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:{{ $item->color }};border:1px solid #ccc;"></span>
                                        {{ $item->color }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @else
                                <td class="px-4 py-3 text-foreground">{{ $item->$key ?? '—' }}</td>
                            @endif
                        @endforeach
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                @can("{$catalog}.view")
                                    <a href="{{ route($catalog . '.show', $item->id) }}"
                                       class="rounded-lg border border-border px-3 py-1 text-xs font-medium text-muted-foreground transition hover:bg-muted">
                                        Ver
                                    </a>
                                @endcan

                                @can("{$catalog}.update")
                                    <a href="{{ route($catalog . '.edit', $item->id) }}"
                                       class="rounded-lg border border-border px-3 py-1 text-xs font-medium text-muted-foreground transition hover:bg-muted">
                                        Editar
                                    </a>
                                @endcan

                                @can("{$catalog}.delete")
                                    <form method="POST" action="{{ route($catalog . '.destroy', $item->id) }}"
                                          onsubmit="return confirm('Tem certeza que deseja excluir este registro?');">
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
                        <td colspan="{{ count($columns) + 1 }}" class="px-4 py-8 text-center text-muted-foreground">
                            Nenhum registro encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}
</div>
@endsection