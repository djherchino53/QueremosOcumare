<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de un usuario que fue desactivado mientras estaba conectado.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_active === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Se arma a mano porque Livewire reemplaza el helper redirect() dentro de sus peticiones.
            return (new RedirectResponse(route('login')))
                ->setSession($request->session())
                ->withErrors(['form.email' => 'Tu usuario está desactivado. Contacta al administrador.']);
        }

        return $next($request);
    }
}
