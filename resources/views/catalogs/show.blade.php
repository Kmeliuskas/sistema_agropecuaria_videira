@extends('layouts.app')

@section('title', 'Ver ' . Str::singular($title) . ' — WMS')
@section('page_title', Str::singular($title))

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route($catalog . '.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            ← Voltar
        </a>
        <div class="flex gap-2">
            @can("{$catalog}.update")
                <a href="{{ route($catalog . '.edit', $item) }}"
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

    <div class="card p-6 space-y-6">
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($fields as $field)
                <div class="space-y-1">
                    <dt class="text-sm font-medium text-muted-foreground">{{ $field['label'] }}</dt>
                    <dd class="text-foreground">{!! $field['value'] !!}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    {{-- Gerenciamento de atributos (apenas para categorias) --}}
    @if ($catalog === 'categories')
    <div class="card p-6">
        <h3 class="mb-4 font-semibold text-foreground">Atributos desta categoria</h3>
        @if ($item->attributes->isNotEmpty())
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach ($item->attributes as $attr)
                <span class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-1.5 text-sm">
                    {{ $attr->name }}
                    <span class="text-xs text-muted-foreground">({{ $attr->type }})</span>
                    @can('categories.update')
                    <form method="POST" action="{{ route('categories.unassign-attribute', [$item, $attr]) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-danger hover:text-danger/80" title="Remover">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </form>
                    @endcan
                </span>
            @endforeach
        </div>
        @else
            <p class="mb-4 text-sm text-muted-foreground">Nenhum atributo associado. Associe abaixo:</p>
        @endif

        @can('categories.update')
        <form method="POST" action="{{ route('categories.assign-attributes', $item) }}" class="flex gap-3">
            @csrf
            <select name="attribute_ids[]" multiple class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 min-h-[100px]">
                @foreach (\App\Models\Attribute::where('is_active', true)->orderBy('sort_order')->get() as $attr)
                    <option value="{{ $attr->id }}"
                        {{ $item->attributes->contains($attr->id) ? 'selected' : '' }}>
                        {{ $attr->name }} ({{ $attr->type }})
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary">Salvar associações</button>
        </form>
        @endcan
    </div>
    @endif

    {{-- Gerenciamento de categorias (apenas para atributos) --}}
    @if ($catalog === 'attributes')
    <div class="card p-6">
        <h3 class="mb-2 font-semibold text-foreground">Categorias relacionadas a este atributo</h3>
        <p class="mb-4 text-xs text-muted-foreground">Estas são as categorias nas quais este atributo é exigido/preenchido no cadastro de produtos.</p>
        
        @if ($item->categories->isNotEmpty())
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach ($item->categories as $cat)
                <span class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-1.5 text-sm bg-muted text-foreground">
                    {{ $cat->name }}
                    @can('attributes.update')
                    <form method="POST" action="{{ route('categories.unassign-attribute', [$cat, $item]) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-danger hover:text-danger/80" title="Remover desta categoria">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </form>
                    @endcan
                </span>
            @endforeach
        </div>
        @else
            <p class="mb-4 text-sm text-muted-foreground">Nenhuma categoria associada a este atributo ainda.</p>
        @endif
    </div>
    @endif
</div>
@endsection