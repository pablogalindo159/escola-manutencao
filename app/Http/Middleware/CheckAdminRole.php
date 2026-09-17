<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth('api')->user();

        if (!$user || !in_array($user->role, ['admin', 'instructor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para acessar este recurso',
            ], 403);
        }

        return $next($request);
    }
}
