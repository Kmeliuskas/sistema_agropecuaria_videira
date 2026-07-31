@extends('layouts.app')

@php
    $pageTitle = 'Nova Permissão';
@endphp

@section('title', 'Nova Permissão — WMS')
@section('page_title', $pageTitle)

@section('content')
<div class="mx-auto max-w-xl">
    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.permissions.store') }}" class="space-y-8">
        @csrf

        <div class="card p-6">
            <h2 class="mb-4 font-semibold text-foreground">Nova permissão</h2>
            <div>
                <label class="label">Nome da permissão *</label>
                <input type="text" name="name" required
                    value="{{ old('name') }}"
                    placeholder="ex.: relatorios.export"
                    class="input font-mono">
                @error ('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-muted-foreground">
                    Formato: <code class="rounded bg-muted px-1">recurso.acao</code> (letras minúsculas,
                    separadas por ponto). Ex.: <code class="rounded bg-muted px-1">estoque.transferir</code>.
                </p>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.permissions.index') }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">Criar permissão</button>
        </div>
    </form>
</div>
@endsection
