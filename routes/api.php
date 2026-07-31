<?php

use Illuminate\Support\Facades\Route;

/*
 * Rotas de API. A autenticação SPA (Sanctum) foi removida em favor de
 * telas Blade server-rendered com fluxo web tradicional (sessão + @csrf).
 * Mantém-se apenas um health check para compatibilidade de monitoramento.
 */

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
