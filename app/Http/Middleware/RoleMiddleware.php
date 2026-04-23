<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! auth()->check()) {
            abort(403);
        }

        if (! in_array(auth()->user()->role->value ?? auth()->user()->role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
