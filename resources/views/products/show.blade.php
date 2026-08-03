@extends('layouts.app')

@section('title', $product->name . ' — WMS')
@section('page_title', 'Produto')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('products.index') }}" class="text-sm text-muted-foreground transition hover:text-foreground">← Voltar</a>
        @can('products.update')
        <a href="{{ route('products.edit', $product) }}"
           class="rounded-lg border border-border px-3 py-1 text-xs font-medium text-muted-foreground transition hover:bg-muted">
            Editar
        </a>
        @endcan
    </div>

    <div class="card p-6">
        <div class="flex items-start justify-between">
            <div>
                <p class="font-mono text-xs text-muted-foreground">{{ $product->internal_code }}</p>
                <h2 class="text-xl font-semibold text-foreground">{{ $product->name }}</h2>
                @if ($product->description)
                    <p class="mt-1 text-sm text-muted-foreground">{{ $product->description }}</p>
                @endif
            </div>
            @if ($product->active)
                <span class="badge badge-success">Ativo</span>
            @else
                <span class="badge badge-muted">Inativo</span>
            @endif
        </div>

        <dl class="mt-6 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
            <div><dt class="text-muted-foreground">Categoria</dt><dd class="font-medium text-foreground">{{ $product->category?->name ?? '—' }}</dd></div>
            <div><dt class="text-muted-foreground">Subcategoria</dt><dd class="font-medium text-foreground">{{ $product->subcategory?->name ?? '—' }}</dd></div>
            <div><dt class="text-muted-foreground">Marca</dt><dd class="font-medium text-foreground">{{ $product->brand?->name ?? '—' }}</dd></div>
            <div><dt class="text-muted-foreground">Fabricante</dt><dd class="font-medium text-foreground">{{ $product->manufacturer?->name ?? '—' }}</dd></div>
            <div><dt class="text-muted-foreground">Unidade</dt><dd class="font-medium text-foreground">{{ $product->unit?->symbol ?? '—' }}</dd></div>
            <div><dt class="text-muted-foreground">Estoque atual</dt><dd id="product_{{ $product->id }}" class="font-medium text-foreground">{{ number_format($product->current_stock, 0, ',', '.') }}</dd></div>
            <div><dt class="text-muted-foreground">Estoque mín.</dt><dd class="font-medium text-foreground">{{ number_format($product->min_stock, 0, ',', '.') }}</dd></div>
            <div><dt class="text-muted-foreground">Custo médio</dt><dd class="font-medium text-foreground">R$ {{ number_format($product->average_cost, 2, ',', '.') }}</dd></div>
            @if ($product->control_expiry)
            <div><dt class="text-muted-foreground">Validade</dt><dd class="font-medium text-foreground">
                @if ($product->expiry_date)
                    {{ \Carbon\Carbon::parse($product->expiry_date)->format('d/m/Y') }}
                    @if ($product->expiry_date < now()->toDateString())
                        <span class="badge badge-danger">VENCIDO</span>
                    @endif
                @else —
                @endif
            </dd></div>
            <div><dt class="text-muted-foreground">Alerta de vencimento</dt><dd class="font-medium text-foreground">{{ $product->expiry_alert_days ?? 30 }} dias</dd></div>
            @endif
        </dl>

        @if ($product->attributes->isNotEmpty())
        <div class="card p-6 mt-4">
            <h3 class="mb-4 font-semibold text-foreground">Atributos específicos</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                @foreach ($product->attributes as $attr)
                    @php
                        $val = $attr->pivot?->value ?? '—';
                        $decoded = json_decode($val, true);
                    @endphp
                    <div>
                        <dt class="text-muted-foreground">{{ $attr->name }}</dt>
                        <dd class="font-medium text-foreground mt-1">
                            @if(is_array($decoded))
                                <div class="flex flex-wrap gap-1">
                                    @foreach($decoded as $itemVal)
                                        <span class="inline-flex items-center rounded-md bg-muted px-2 py-0.5 text-xs font-medium text-foreground border border-border">
                                            {{ $itemVal }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                {{ $val }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
        @endif
    </div>

    <div class="card p-6">
        <h3 class="mb-4 font-semibold text-foreground">Saldo por almoxarifado</h3>
        @if ($product->stockBalances->isEmpty())
            <p class="text-sm text-muted-foreground">Sem saldo registrado.</p>
        @else
            {{-- Desktop: tabela tradicional --}}
            <div class="hidden sm:block">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="py-2">Almoxarifado</th>
                            <th class="py-2 text-right">Atual</th>
                            <th class="py-2 text-right">Reservado</th>
                            <th class="py-2 text-right">Disponível</th>
                            <th class="py-2 text-right min-w-[140px]">Última movimentação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($product->stockBalances as $sb)
                            <tr>
                                <td class="py-2 text-foreground">{{ $sb->warehouse?->name ?? '—' }}</td>
                                <td class="py-2 text-right text-foreground">{{ number_format($sb->current, 0, ',', '.') }}</td>
                                <td class="py-2 text-right text-muted-foreground">{{ number_format($sb->reserved, 0, ',', '.') }}</td>
                                <td class="py-2 text-right text-foreground">{{ number_format($sb->available, 0, ',', '.') }}</td>
                                <td class="py-2 text-right text-muted-foreground">{{ $sb->updated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: cards empilhados com scroll --}}
            <div class="sm:hidden">
                @php
                    $hasMore = $product->stockBalances->count() > 3;
                @endphp

                <div class="space-y-3" style="{{ $hasMore ? 'max-height: 320px; overflow-y: auto;' : '' }}">
                    @foreach ($product->stockBalances as $sb)
                        <div class="rounded-lg border border-border p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="font-medium text-foreground">{{ $sb->warehouse?->name ?? '—' }}</span>
                                <span class="text-xs text-muted-foreground">{{ $sb->updated_at?->format('d/m/Y H:i') ?? '—' }}</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <p class="text-xs text-muted-foreground">Atual</p>
                                    <p class="font-medium text-foreground">{{ number_format($sb->current, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Reservado</p>
                                    <p class="font-medium text-muted-foreground">{{ number_format($sb->reserved, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Disponível</p>
                                    <p class="font-medium text-foreground">{{ number_format($sb->available, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
