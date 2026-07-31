@extends('layouts.app')

@php
    $pageTitle = 'Papéis';
@endphp

@section('title', 'Papéis — WMS')
@section('page_title', $pageTitle)

@section('content')
<div class="mx-auto max-w-4xl">
    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <div class="card p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold text-foreground">Papéis do sistema</h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.permissions.index') }}" class="btn-ghost">Permissões</a>
                <a href="{{ route('admin.roles.create') }}" class="btn-primary">Novo papel</a>
            </div>
        </div>

        <div class="h-[calc(100vh-240px)] overflow-y-auto">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($roles as $role)
                    @php
                        $protected = $role->name === 'administrador';
                        $grouped = \App\Support\PermissionLabels::groupByModule($role->permissions);
                    @endphp
                    <div class="rounded-xl border border-border bg-surface p-5 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow">
                        <div>
                            <div class="mb-3 flex items-center justify-between border-b border-border/60 pb-3">
                                <div>
                                    <h3 class="font-semibold text-base text-foreground capitalize">{{ $role->name }}</h3>
                                    <span class="text-xs text-muted-foreground">{{ $role->permissions->count() }} permissão(ões) ativas</span>
                                </div>
                                <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                    {{ $role->users_count }} usuário(s)
                                </span>
                            </div>

                            <div class="mb-4 space-y-2 max-h-40 overflow-y-auto pr-1">
                                @forelse ($grouped as $module => $info)
                                    <div class="text-xs">
                                        <span class="font-semibold text-foreground">{{ $info['label'] }}:</span>
                                        <span class="text-muted-foreground">{{ implode(', ', $info['items']) }}</span>
                                    </div>
                                @empty
                                    <span class="text-xs italic text-muted-foreground">Nenhuma permissão concedida</span>
                                @endforelse
                            </div>
                        </div>

                        <div class="pt-3 border-t border-border/60 flex items-center justify-between">
                            @if (! $protected)
                                <span class="text-xs text-muted-foreground">Personalizado</span>
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted transition-colors">Editar</a>
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                        onsubmit="return confirm('Excluir o papel {{ $role->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-border px-3 py-1 text-xs font-medium text-danger hover:bg-danger/10 transition-colors">Excluir</button>
                                    </form>
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-500 bg-amber-500/10 px-2.5 py-1 rounded-md">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    Papel Protegido (Acesso Total)
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
