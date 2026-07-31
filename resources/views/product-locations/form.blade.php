@extends('layouts.app')

@php
    $isEdit = isset($location);
@endphp

@section('title', ($isEdit ? 'Editar' : 'Nova') . ' localização — WMS')
@section('page_title', ($isEdit ? 'Editar' : 'Nova') . ' localização')

@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    <a href="{{ route('product-locations.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        ← Voltar
    </a>

    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST"
        action="{{ $isEdit ? route('product-locations.update', $location) : route('product-locations.store') }}"
        class="space-y-5 card p-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Produto <span class="text-danger">*</span></label>
                <select name="product_id"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                    <option value="">Selecione um produto</option>
                    @foreach ($products as $p)
                        <option value="{{ $p['id'] }}"
                            {{ old('product_id', $location->product_id ?? '') == $p['id'] ? 'selected' : '' }}>
                            {{ $p['name'] }} ({{ $p['code'] }})
                        </option>
                    @endforeach
                </select>
                @error('product_id')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Almoxarifado <span class="text-danger">*</span></label>
                <select name="warehouse_id"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                    <option value="">Selecione um almoxarifado</option>
                    @foreach ($warehouses as $id => $name)
                        <option value="{{ $id }}"
                            {{ old('warehouse_id', $location->warehouse_id ?? '') == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                @error('warehouse_id')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Rua</label>
                <input type="text" name="aisle" value="{{ old('aisle', $location->aisle ?? '') }}"
                    placeholder="Ex.: A"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error('aisle')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Corredor</label>
                <input type="text" name="corridor" value="{{ old('corridor', $location->corridor ?? '') }}"
                    placeholder="Ex.: 01"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error('corridor')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Prateleira</label>
                <input type="text" name="shelf" value="{{ old('shelf', $location->shelf ?? '') }}"
                    placeholder="Ex.: 03"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error('shelf')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Nível</label>
                <input type="text" name="level" value="{{ old('level', $location->level ?? '') }}"
                    placeholder="Ex.: 2"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error('level')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Posição</label>
                <input type="text" name="position" value="{{ old('position', $location->position ?? '') }}"
                    placeholder="Ex.: 05"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error('position')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Quantidade</label>
                <input type="number" name="quantity" value="{{ old('quantity', $location->quantity ?? 0) }}"
                    min="0" step="0.0001"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error('quantity')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center gap-2">
            <label class="flex items-center gap-2 text-sm text-muted-foreground">
                <input type="checkbox" name="is_primary" value="1"
                    {{ old('is_primary', $location->is_primary ?? false) ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-border text-foreground">
                Localização primária deste produto
            </label>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('product-locations.index') }}"
                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted">
                Cancelar
            </a>
            <button type="submit" class="btn-primary">
                {{ $isEdit ? 'Salvar alterações' : 'Criar localização' }}
            </button>
        </div>
    </form>
</div>
@endsection
