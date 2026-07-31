<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Exibe o formulário de login.
     */
    public function show()
    {
        return view('auth.login');
    }

    /**
     * Autentica o usuário via sessão web tradicional (Blade + @csrf).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => ["Muitas tentativas. Tente novamente em {$seconds}s."],
            ]);
        }

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
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

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'login_blocked_inactive',
                'event' => 'Auth.login_blocked_inactive',
                'auditable_type' => User::class,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Acesso negado. Usuário inativo.'],
            ]);
        }

        RateLimiter::clear($key);
        $user->update(['last_login_at' => now()]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login_success',
            'event' => 'Auth.login_success',
            'auditable_type' => User::class,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Encerra a sessão.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'logout',
                'event' => 'Auth.logout',
                'auditable_type' => User::class,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
