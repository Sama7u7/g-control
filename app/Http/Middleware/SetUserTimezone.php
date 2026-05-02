<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
public function handle(Request $request, Closure $next)
{
    // Prioridad 1: Usuario autenticado
    if (auth()->check()) {
        config(['app.timezone' => auth()->user()->timezone]);
    }
    // Prioridad 2: Sesión de invitado
    elseif (session()->has('user_timezone')) {
        config(['app.timezone' => session('user_timezone')]);
    }
    // Prioridad 3: Default (Tuxtla/CDMX)
    else {
        config(['app.timezone' => 'America/Mexico_City']);
    }

    date_default_timezone_set(config('app.timezone'));
    return $next($request);
}
}
