<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated. Please log in.'], 401);
        }

        if (!Auth::user()->is_active) {
            Auth::logout();
            return response()->json(['error' => 'Account deactivated.'], 403);
        }

        if ($permission && !Auth::user()->hasPermission($permission)) {
            return response()->json(['error' => "Insufficient permissions: {$permission} required."], 403);
        }

        return $next($request);
    }
}
