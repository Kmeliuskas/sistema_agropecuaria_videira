@extends('layouts.app')

@php
    $isEdit = isset($warehouse);
@endphp

@section('title', ($isEdit ? 'Editar' : 'Novo') . ' almoxarifado — WMS')
@section('page_title', ($isEdit ? 'Editar' : 'Novo') . ' almoxarifado')

@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    <a href="{{ route('warehouses.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        ← Voltar
    </a>

    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST"
        action="{{ $isEdit ? route('warehouses.update', $warehouse) : route('warehouses.store') }}"
        class="space-y-5 card p-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Código</label>
                <input type="text" name="code" value="{{ old('code', $warehouse->code ?? '') }}"
                    placeholder="Ex.: CD"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error('code')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Nome <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $warehouse->name ?? '') }}"
                    placeholder="Ex.: Almoxarifado Central"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error('name')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-muted-foreground">Descrição</label>
            <textarea name="description" rows="3"
                class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">{{ old('description', $warehouse->description ?? '') }}</textarea>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Tipo</label>
                @include('catalogs.partials.field-select-search', ['field' => [
                    'name' => 'warehouse_type_id',
                    'options' => $warehouseTypes ?? [],
                    'value' => old('warehouse_type_id', $warehouse->warehouse_type_id ?? '')
                ], 'errors' => $errors])
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Responsável</label>
                <input type="text" name="responsible" value="{{ old('responsible', $warehouse->responsible ?? '') }}"
                    placeholder="Ex.: João Silva"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="sm:col-span-1">
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Documento (CNPJ)</label>
                <input type="text" name="document" value="{{ old('document', $warehouse->document ?? '') }}"
                    placeholder="00.000.000/0000-00"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Cidade</label>
                <input type="text" name="city" value="{{ old('city', $warehouse->city ?? '') }}"
                    placeholder="Ex.: Videira"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">UF</label>
                <input type="text" name="state" maxlength="2" value="{{ old('state', $warehouse->state ?? '') }}"
                    placeholder="SC"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm uppercase outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-muted-foreground">Endereço</label>
            <input type="text" name="address" value="{{ old('address', $warehouse->address ?? '') }}"
                placeholder="Rua, número, bairro"
                class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="flex items-center gap-2 text-sm text-muted-foreground">
                <input type="checkbox" name="is_active" value="1"
                    {{ old('is_active', $warehouse->is_active ?? true) ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-border text-foreground">
                Almoxarifado ativo
            </label>

            <label class="flex items-center gap-2 text-sm text-muted-foreground">
                <input type="checkbox" name="is_default" value="1"
                    {{ old('is_default', $warehouse->is_default ?? false) ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-border text-foreground">
                Almoxarifado padrão
            </label>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('warehouses.index') }}"
                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted">
                Cancelar
            </a>
            <button type="submit"
                class="btn-primary">
                {{ $isEdit ? 'Salvar alterações' : 'Criar almoxarifado' }}
            </button>
        </div>
    </form>
</div>
@endsection
