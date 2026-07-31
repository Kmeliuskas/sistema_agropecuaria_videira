@extends('layouts.app')

@section('title', 'Permissões — WMS')
@section('page_title', 'Permissões do Sistema')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-foreground">Permissões do Sistema</h2>
            <p class="text-sm text-muted-foreground">
                Gerencie as permissões disponíveis para cada papel. Cada permissão segue o padrão
                <code class="rounded bg-muted px-1 text-xs">recurso.ação</code>.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.roles.index') }}" class="btn-secondary">Papéis</a>
            <a href="{{ route('admin.permissions.create') }}" class="btn-primary">+ Nova permissão</a>
        </div>
    </div>

    {{-- Legenda --}}
    <div class="flex items-center gap-4 text-xs text-muted-foreground">
        <span class="inline-flex items-center gap-1">
            <span class="h-2 w-2 rounded-full bg-success"></span>
            Criada pelo sistema
        </span>
        <span class="inline-flex items-center gap-1">
            <span class="h-2 w-2 rounded-full bg-primary"></span>
            Criada manualmente
        </span>
    </div>

    {{-- Permissões agrupadas por módulo --}}
    <div class="h-[calc(100vh-200px)] overflow-y-auto">
        <div class="space-y-4">
            @foreach ($grouped as $module => $info)
                @php
                    $isSystemModule = array_key_exists($module, \App\Support\PermissionLabels::modules());
                    $titleColor = $isSystemModule ? 'text-success' : 'text-primary';
                    $dotColor = $isSystemModule ? 'bg-success' : 'bg-primary';
                @endphp
                <div class="card p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-semibold {{ $titleColor }}">
                            <span class="h-2 w-2 rounded-full {{ $dotColor }}"></span>
                            {{ $info['label'] }}
                        </h3>
                        <span class="text-xs text-muted-foreground">{{ count($info['items']) }} permissão(ões)</span>
                    </div>

                    <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($info['items'] as $permName => $label)
                            <div class="flex items-center justify-between rounded-lg border border-border bg-surface px-3 py-1.5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-foreground">{{ $label }}</span>
                                    <code class="text-xs text-muted-foreground">{{ $permName }}</code>
                                </div>
                                <form method="POST" action="{{ route('admin.permissions.destroy', $permName) }}"
                                    onsubmit="return confirm('Remover a permissão {{ $permName }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="text-muted-foreground hover:text-danger"
                                        title="Remover">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
