@extends('layouts.app')

@php
    $isEdit = isset($user);
    $title = $isEdit ? "Editar Usuário — {$user->name}" : 'Novo Usuário — WMS';
    $pageTitle = $isEdit ? "Editar Usuário" : "Novo Usuário";
@endphp

@section('title', $title)
@section('page_title', $pageTitle)

@section('content')
<div class="mx-auto max-w-2xl">
    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <form method="POST"
        action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}"
        class="space-y-8">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="card p-6">
            <h2 class="mb-4 font-semibold text-foreground">Dados do usuário</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Nome *</label>
                    <input type="text" name="name" required
                        value="{{ old('name', $user->name ?? '') }}"
                        class="input">
                    @error ('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">E-mail *</label>
                    <input type="email" name="email" required
                        value="{{ old('email', $user->email ?? '') }}"
                        class="input">
                    @error ('email') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">
                        Senha {{ $isEdit ? '(deixe em branco para manter)' : '*' }}
                    </label>
                    <input type="password" name="password"
                        {{ $isEdit ? '' : 'required' }}
                        autocomplete="new-password"
                        class="input">
                    @error ('password') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Confirmar senha</label>
                    <input type="password" name="password_confirmation"
                        autocomplete="new-password"
                        class="input">
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                    {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-border text-foreground">
                <label class="text-sm text-foreground">Usuário ativo</label>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="mb-4 font-semibold text-foreground">Papéis (cargos)</h2>
            <p class="mb-4 text-sm text-muted-foreground">
                Selecione um ou mais papéis. O papel define as permissões do usuário no sistema.
            </p>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach ($roles as $role)
                    <label class="flex items-center gap-2 rounded-lg border border-border px-3 py-2 text-sm">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                            {{ (isset($user) && $user->roles->contains('id', $role->id)) || in_array($role->id, old('roles', [])) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-border text-foreground">
                        <span class="text-foreground">{{ ucfirst($role->name) }}</span>
                    </label>
                @endforeach
            </div>
            @error ('roles') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.users.index') }}"
                class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">
                {{ $isEdit ? 'Salvar alterações' : 'Criar usuário' }}
            </button>
        </div>
    </form>
</div>
@endsection
