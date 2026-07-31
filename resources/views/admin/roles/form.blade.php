@extends('layouts.app')

@php
    $isEdit = isset($role);
    $title = $isEdit ? "Editar Papel — {$role->name}" : 'Novo Papel — WMS';
    $pageTitle = $isEdit ? "Editar Papel" : "Novo Papel";
    $selected = $isEdit ? $role->permissions->pluck('id')->all() : old('permissions', []);
@endphp

@section('title', $title)
@section('page_title', $pageTitle)

@section('content')
<div class="mx-auto max-w-4xl" x-data="{
    toggleModule(module, check) {
        const checkboxes = this.$el.querySelectorAll(`[data-module='${module}']`);
        checkboxes.forEach(cb => cb.checked = check);
    }
}">
    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <form method="POST"
        action="{{ $isEdit ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
        class="space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="card p-6">
            <h2 class="mb-2 text-base font-semibold text-foreground">Identificação do Papel (Cargo)</h2>
            <p class="mb-4 text-xs text-muted-foreground">Defina o nome único do cargo/função que os usuários receberão.</p>
            
            <div class="max-w-md">
                <label class="mb-1 block text-sm font-medium text-foreground">Nome do Papel *</label>
                <input type="text" name="name" required
                    value="{{ old('name', $role->name ?? '') }}"
                    placeholder="Ex: supervisor, almoxarife, auditor"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error ('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="card p-6">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-2 border-b border-border pb-4">
                <div>
                    <h2 class="text-base font-semibold text-foreground">Permissões de Acesso por Módulo</h2>
                    <p class="text-xs text-muted-foreground">Marque o que os usuários atribuídos a este papel poderão visualizar, criar ou modificar no sistema.</p>
                </div>
            </div>

            <div class="space-y-6 divide-y divide-border">
                @foreach ($grouped as $module => $info)
                    <div class="pt-4 first:pt-0">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-primary flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-primary"></span>
                                {{ $info['label'] }}
                            </h3>
                            <div class="flex items-center gap-2 text-xs">
                                <button type="button" @click="toggleModule('{{ $module }}', true)" class="text-primary hover:underline font-medium">Marcar todos</button>
                                <span class="text-border">•</span>
                                <button type="button" @click="toggleModule('{{ $module }}', false)" class="text-muted-foreground hover:underline">Desmarcar</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($info['items'] as $permId => $permName)
                                @php
                                    $p = $allPermissions->firstWhere('name', $permId);
                                @endphp
                                @if ($p)
                                    <label class="flex items-start gap-3 rounded-lg border border-border p-3 hover:bg-muted/40 transition-colors cursor-pointer select-none">
                                        <input type="checkbox" name="permissions[]" value="{{ $p->id }}" data-module="{{ $module }}"
                                            {{ in_array($p->id, $selected) ? 'checked' : '' }}
                                            class="mt-0.5 h-4 w-4 rounded border-border text-primary focus:ring-primary/30">
                                        <div class="text-xs">
                                            <span class="font-medium text-foreground block">{{ $permName }}</span>
                                            <span class="text-muted-foreground font-mono text-[10px]">{{ $p->name }}</span>
                                        </div>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @error ('permissions') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted">Cancelar</a>
            <button type="submit" class="btn-primary">
                {{ $isEdit ? 'Salvar alterações' : 'Criar papel' }}
            </button>
        </div>
    </form>
</div>
@endsection
