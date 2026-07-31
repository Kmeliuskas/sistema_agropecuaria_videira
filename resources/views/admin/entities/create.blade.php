@extends('layouts.app')

@php
    $pageTitle = 'Criar Entidade';
@endphp

@section('title', 'Criar Entidade — WMS')
@section('page_title', $pageTitle)

@section('content')
<div class="mx-auto max-w-3xl">
    @if (session('success'))
        <div class="alert-success mb-4 whitespace-pre-line">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert-danger mb-4 whitespace-pre-line">{{ session('error') }}</div>
    @endif

    <div class="card p-6">
        <h2 class="mb-1 font-semibold text-foreground">Gerador de entidade (CRUD completo)</h2>
        <p class="mb-6 text-sm text-muted-foreground">
            Informe o nome da entidade (no singular) e suas colunas. O sistema cria model, migration,
            controller, policy, views, permissões e o item de menu automaticamente.
            Em seguida rode <code class="rounded bg-muted px-1">php artisan migrate</code> para criar a tabela.
        </p>

        <form method="POST" action="{{ route('admin.entities.store') }}" class="space-y-6">
            @csrf

            <div>
                <label class="label">Nome da entidade (no singular) *</label>
                <input type="text" name="name" required value="{{ old('name') }}"
                    placeholder="ex.: Filial (não digite o plural)"
                    class="input">
                @error('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-muted-foreground">Digite no <strong>singular</strong> (ex.: "Filial"). O sistema pluraliza corretamente em português.</p>
            </div>

            <div>
                <label class="label">Colunas *</label>
                <textarea name="columns" rows="10" required
                    class="input font-mono text-sm">{{ old('columns', "nome:string\ncodigo:string:60\nativo:boolean\ndescricao:text") }}</textarea>
                @error('columns') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-muted-foreground">
                    Um por linha no formato <code class="rounded bg-muted px-1">campo:tipo:tamanho</code>.<br>
                    Tipos: string, text, integer, decimal, boolean, date, datetime, time, email, foreignId, uuid.<br>
                    Ex.: <code class="rounded bg-muted px-1">categoria_id:foreignId</code>
                </p>
            </div>

            <div>
                <label class="label">Submenu do menu *</label>
                <select name="submenu" required class="input" onchange="document.getElementById('novo_submenu_box').style.display = (this.value === 'new') ? 'block' : 'none'">
                    <option value="catalogos" @selected(old('submenu') === 'catalogos')>Catálogos</option>
                    <option value="cadastros" @selected(old('submenu') === 'cadastros')>Cadastros</option>
                    <option value="estoque" @selected(old('submenu') === 'estoque')>Estoque</option>
                    <option value="administracao" @selected(old('submenu') === 'administracao')>Administração</option>
                    <option value="new" @selected(old('submenu') === 'new')>Criar novo submenu…</option>
                </select>
                @error('submenu') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

                <div id="novo_submenu_box" class="mt-3" style="display: {{ old('submenu') === 'new' ? 'block' : 'none' }};">
                    <label class="label">Nome do novo submenu *</label>
                    <input type="text" name="novo_submenu" value="{{ old('novo_submenu') }}"
                        placeholder="ex.: Relatórios"
                        class="input">
                    @error('novo_submenu') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-muted-foreground">O novo submenu será criado no menu e ficará visível apenas para quem tiver a permissão de visualizar a entidade.</p>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.roles.index') }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">Gerar entidade</button>
            </div>
        </form>
    </div>
</div>
@endsection
