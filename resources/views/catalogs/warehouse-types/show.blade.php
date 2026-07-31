@extends('layouts.app')

@section('title', 'Tipo de Almoxarifado: {{ $type->name }} — WMS')
@section('page_title', 'Tipo de Almoxarifado: {{ $type->name }}')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('warehouse-types.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            ← Voltar
        </a>
        @can('warehouses.update')
        <a href="{{ route('warehouse-types.edit', $type) }}"
           class="rounded-lg border border-border px-3 py-1 text-xs font-medium text-muted-foreground transition hover:bg-muted">
            Editar
        </a>
        @endcan
    </div>

    <div class="card p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-muted-foreground">Código</label>
                <p class="mt-1 font-mono text-sm text-foreground">{{ $type->code }}</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-muted-foreground">Nome</label>
                <p class="mt-1 text-sm text-foreground">{{ $type->name }}</p>
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-xs font-medium text-muted-foreground">Descrição</label>
            <p class="mt-1 text-sm text-muted-foreground">{{ $type->description ?? '—' }}</p>
        </div>

        <div class="mt-4">
            <label class="block text-xs font-medium text-muted-foreground">Situação</label>
            <p class="mt-1">
                @if ($type->is_active)
                    <span class="badge-success">Ativo</span>
                @else
                    <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">Inativo</span>
                @endif
            </p>
        </div>

        <div class="mt-4 text-xs text-muted-foreground">
            <p>Criado em: {{ $type->created_at?->format('d/m/Y H:i') ?? '—' }}</p>
            <p>Atualizado em: {{ $type->updated_at?->format('d/m/Y H:i') ?? '—' }}</p>
        </div>
    </div>
</div>
@endsection
