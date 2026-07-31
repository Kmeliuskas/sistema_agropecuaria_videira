@extends('layouts.app')

@php
    $isEdit = true;
@endphp

@section('title', 'Editar ' . Str::singular($title) . ' — WMS')
@section('page_title', 'Editar ' . Str::singular($title))

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route($catalog . '.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            ← Voltar
        </a>
        <a href="{{ route($catalog . '.show', $item) }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            Ver detalhes
        </a>
    </div>

    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST"
        action="{{ route($catalog . '.update', $item) }}"
        class="space-y-5 card p-6">
        @csrf
        @method('PUT')

        @foreach ($fields as $field)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @include('catalogs.partials.field-' . $field['type'], ['field' => $field, 'errors' => $errors, 'extraData' => $extraData ?? []])
            </div>
        @endforeach

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route($catalog . '.index') }}"
                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted">
                Cancelar
            </a>
            <button type="submit"
                class="btn-primary">
                Salvar alterações
            </button>
        </div>
    </form>
</div>
@endsection