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
</div>
@endsection