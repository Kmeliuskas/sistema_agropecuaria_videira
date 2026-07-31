@extends('layouts.app')

@php
    $isEdit = isset($item);
@endphp

@section('title', ($isEdit ? 'Editar' : 'Nova') . ' ' . Str::singular($title) . ' — WMS')
@section('page_title', ($isEdit ? 'Editar' : 'Nova') . ' ' . Str::singular($title))

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <a href="{{ route($catalog . '.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        ← Voltar
    </a>

    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST"
        action="{{ $isEdit ? route($catalog . '.update', $item) : route($catalog . '.store') }}"
        class="space-y-5 card p-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($fields as $field)
            @include('catalogs.partials.field-' . $field['type'], ['field' => $field, 'errors' => $errors, 'extraData' => $extraData ?? []])
        @endforeach
    </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route($catalog . '.index') }}"
                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted">
                Cancelar
            </a>
            <button type="submit"
                class="btn-primary">
                {{ $isEdit ? 'Salvar alterações' : 'Criar ' . Str::singular($title) }}
            </button>
        </div>
    </form>
</div>
@endsection