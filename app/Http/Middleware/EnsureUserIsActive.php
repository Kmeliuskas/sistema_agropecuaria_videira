<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia o acesso de usuários inativos ou excluídos (soft delete),
 * mesmo que possuam token válido. Usado no grupo de rotas autenticadas.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            return response()->json([
                'message' => 'Acesso negado. Usuário inativo ou inexistente.',
                'error' => 'user_inactive',
            ], 403);
        }

        return $next($request);
    }
}
