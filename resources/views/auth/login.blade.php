@extends('layouts.auth')

@section('content')
<div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-semibold text-foreground">WMS</h1>
            <p class="mt-1 text-sm text-muted-foreground">Gestão de Almoxarifado</p>
        </div>

        <div class="card p-8">
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-5">
                    <label for="email" class="mb-1.5 block text-sm font-medium text-foreground">E-mail</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="input"
                        placeholder="voce@empresa.com">
                </div>

                <div class="mb-6">
                    <label for="password" class="mb-1.5 block text-sm font-medium text-foreground">Senha</label>
                    <input id="password" type="password" name="password" required
                        class="input"
                        placeholder="••••••••">
                </div>

                <label class="mb-6 flex items-center gap-2 text-sm text-muted-foreground">
                    <input type="checkbox" name="remember" value="1"
                        class="h-4 w-4 rounded border-border text-primary focus:ring-ring">
                    Lembrar de mim
                </label>

                <button type="submit"
                    class="btn-primary w-full">
                    Entrar
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-muted-foreground">
            Acesso restrito a usuários autorizados.
        </p>
    </div>
@endsection
