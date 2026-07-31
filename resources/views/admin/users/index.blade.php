@extends('layouts.app')

@php
    $pageTitle = 'Usuários';
@endphp

@section('title', 'Usuários — WMS')
@section('page_title', $pageTitle)

@section('content')
<div class="mx-auto max-w-5xl">
    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <div class="card p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-foreground">Usuários do sistema</h2>
            <a href="{{ route('admin.users.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Novo usuário
            </a>
        </div>

        <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="label">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Nome ou e-mail"
                    class="input">
            </div>
            <div>
                <label class="label">Situação</label>
                <select name="active" class="input">
                    <option value="" {{ request('active', '') === '' ? 'selected' : '' }}>Ativos</option>
                    <option value="all" {{ request('active') === 'all' ? 'selected' : '' }}>Todos</option>
                    <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inativos</option>
                </select>
            </div>
            <button type="submit" class="btn-secondary">Filtrar</button>
            <a href="{{ route('admin.users.index') }}" class="btn-ghost">Limpar</a>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left text-muted-foreground">
                        <th class="py-2 pr-4 font-medium">Nome</th>
                        <th class="py-2 pr-4 font-medium">E-mail</th>
                        <th class="py-2 pr-4 font-medium">Papéis</th>
                        <th class="py-2 pr-4 font-medium">Status</th>
                        <th class="py-2 font-medium text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-b border-border">
                            <td class="py-3 pr-4 font-medium text-foreground">{{ $user->name }}</td>
                            <td class="py-3 pr-4 text-muted-foreground">{{ $user->email }}</td>
                            <td class="py-3 pr-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->roles as $role)
                                        <span class="badge-primary">{{ $role->name }}</span>
                                    @empty
                                        <span class="badge-muted">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3 pr-4">
                                @if ($user->is_active)
                                    <span class="badge-success">Ativo</span>
                                @else
                                    <span class="badge-danger">Inativo</span>
                                @endif
                            </td>
                            <td class="py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                        class="btn-ghost px-3 py-1.5" title="Editar">Editar</a>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                        onsubmit="return confirm('Excluir o usuário {{ $user->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger px-3 py-1.5">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-muted-foreground">
                                Nenhum usuário encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
