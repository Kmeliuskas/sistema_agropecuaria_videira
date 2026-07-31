<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OAT;

/**
 * Autenticação SPA com Sanctum (cookie de sessão + CSRF).
 * O SPA Angular faz POST /login (com cookie XSRF-TOKEN), depois usa o cookie
 * de sessão. Logout revoga o token e registra auditoria.
 */
class AuthController extends Controller
{
    #[OAT\Post(
        path: '/api/v1/login',
        summary: 'Autenticar usuário (SPA Sanctum cookie)',
        tags: ['Autenticação'],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OAT\Property(property: 'email', type: 'string', example: 'admin@wms.local'),
                    new OAT\Property(property: 'password', type: 'string', example: 'Admin@123456'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Autenticado', content: new OAT\JsonContent(ref: '#/components/schemas/User')),
            new OAT\Response(response: 422, description: 'Credenciais inválidas'),
            new OAT\Response(response: 429, description: 'Muitas tentativas'),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Muitas tentativas. Tente novamente em '.$seconds.'s.',
                'error' => 'too_many_attempts',
            ], 429);
        }

        if (! Auth::attempt($request->only('email', 'password'), true)) {
            RateLimiter::hit($key);
            AuditLog::create([
                'user_id' => User::where('email', $request->email)->value('id'),
                'action' => 'login_failed',
                'event' => 'Auth.login_failed',
                'auditable_type' => User::class,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'after' => ['email' => $request->email],
            ]);

            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        RateLimiter::clear($key);
        $user = Auth::user();
        $user->update(['last_login_at' => now()]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'event' => 'Auth.login',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'after' => ['email' => $user->email],
        ]);

        return response()->json([
            'message' => 'Autenticado com sucesso.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => 'logout',
            'event' => 'Auth.logout',
            'auditable_type' => User::class,
            'auditable_id' => $user?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout realizado.']);
    }

    #[OAT\Get(
        path: '/api/v1/me',
        summary: 'Perfil do usuário autenticado (roles + permissões)',
        tags: ['Autenticação'],
        security: [['sanctum' => []]],
        responses: [
            new OAT\Response(response: 200, description: 'Perfil', content: new OAT\JsonContent(ref: '#/components/schemas/User')),
            new OAT\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
