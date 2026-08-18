<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if ((int) auth()->user()->role !== (int) $role) {
            abort(403, 'Unauthorized Access');
        }

        return $next($request);
    }
}