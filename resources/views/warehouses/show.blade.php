@extends('layouts.app')

@section('title', $warehouse->name . ' — WMS')
@section('page_title', 'Almoxarifado: ' . $warehouse->name)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('warehouses.index') }}" class="text-sm text-muted-foreground transition hover:text-foreground">← Voltar</a>
        <div class="flex gap-2">
            @can('warehouses.update')
            <a href="{{ route('warehouses.edit', $warehouse) }}"
               class="rounded-lg border border-border px-3 py-1 text-xs font-medium text-muted-foreground transition hover:bg-muted">
                Editar
            </a>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card p-6">
        <div class="flex items-start justify-between">
            <div>
                @if ($warehouse->code)
                    <p class="font-mono text-xs text-muted-foreground">{{ $warehouse->code }}</p>
                @endif
                <h2 class="text-xl font-semibold text-foreground">{{ $warehouse->name }}</h2>
                @if ($warehouse->description)
                    <p class="mt-1 text-sm text-muted-foreground">{{ $warehouse->description }}</p>
                @endif
            </div>
            <div class="flex flex-col items-end gap-2">
                @if ($warehouse->is_active)
                    <span class="badge-success">Ativo</span>
                @else
                    <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">Inativo</span>
                @endif
                @if ($warehouse->is_default)
                    <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">Padrão</span>
                @endif
            </div>
        </div>

        <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @if ($warehouse->warehouseType)
                <div>
                    <dt class="text-sm font-medium text-muted-foreground">Tipo</dt>
                    <dd class="mt-1 text-foreground">{{ $warehouse->warehouseType->name }}</dd>
                </div>
            @endif
            @if ($warehouse->responsible)
                <div>
                    <dt class="text-sm font-medium text-muted-foreground">Responsável</dt>
                    <dd class="mt-1 text-foreground">{{ $warehouse->responsible }}</dd>
                </div>
            @endif
            @if ($warehouse->document)
                <div>
                    <dt class="text-sm font-medium text-muted-foreground">Documento</dt>
                    <dd class="mt-1 text-foreground">{{ $warehouse->document }}</dd>
                </div>
            @endif
            @if ($warehouse->address)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-muted-foreground">Endereço</dt>
                    <dd class="mt-1 text-foreground">{{ $warehouse->address }}</dd>
                </div>
            @endif
            @if ($warehouse->city || $warehouse->state)
                <div>
                    <dt class="text-sm font-medium text-muted-foreground">Cidade/Estado</dt>
                    <dd class="mt-1 text-foreground">{{ $warehouse->city ?? '' }}{{ $warehouse->city && $warehouse->state ? '/' : '' }}{{ $warehouse->state ?? '' }}</dd>
                </div>
            @endif
        </dl>

        <div class="mt-6 border-t border-border pt-4 text-xs text-muted-foreground">
            <p>Criado em: {{ $warehouse->created_at?->format('d/m/Y H:i') ?? '—' }}</p>
            <p>Atualizado em: {{ $warehouse->updated_at?->format('d/m/Y H:i') ?? '—' }}</p>
        </div>
    </div>

    @if ($warehouse->products->isNotEmpty())
        <div class="card p-6">
            <h3 class="mb-4 font-semibold text-foreground">Produtos neste almoxarifado</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Código</th>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3 text-right">Estoque</th>
                            <th class="px-4 py-3 text-right">Mín.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($warehouse->products as $product)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $product->internal_code }}</td>
                                <td class="px-4 py-3 text-foreground">{{ $product->name }}</td>
                                <td class="px-4 py-3 text-right text-foreground">{{ number_format($product->current_stock, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($product->min_stock, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
