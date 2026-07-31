@extends('layouts.app')

@php
    $isEdit = isset($sector);
@endphp

@section('title', ($isEdit ? 'Editar' : 'Novo') . ' setor — WMS')
@section('page_title', ($isEdit ? 'Editar' : 'Novo') . ' setor')

@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    <a href="{{ route('sectors.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        ← Voltar
    </a>

    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST"
        action="{{ $isEdit ? route('sectors.update', $sector) : route('sectors.store') }}"
        class="space-y-5 card p-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Código</label>
                <input type="text" name="code" value="{{ old('code', $sector->code ?? '') }}"
                    placeholder="Ex.: SEC-PROD"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error('code')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Nome <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $sector->name ?? '') }}"
                    placeholder="Ex.: Produção"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error('name')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-muted-foreground">Descrição</label>
            <textarea name="description" rows="3"
                class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">{{ old('description', $sector->description ?? '') }}</textarea>
        </div>

        <label class="flex items-center gap-2 text-sm text-muted-foreground">
            <input type="checkbox" name="is_active" value="1"
                {{ old('is_active', $sector->is_active ?? true) ? 'checked' : '' }}
                class="h-4 w-4 rounded border-border text-foreground">
            Setor ativo
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('sectors.index') }}"
                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted">
                Cancelar
            </a>
            <button type="submit"
                class="btn-primary">
                {{ $isEdit ? 'Salvar alterações' : 'Criar setor' }}
            </button>
        </div>
    </form>
</div>
@endsection
