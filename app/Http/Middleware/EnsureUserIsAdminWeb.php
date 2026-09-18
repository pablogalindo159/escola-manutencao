<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdminWeb
{
    /**
     * Bloqueia acesso ao painel admin (rotas web/sessão) pra quem não é
     * admin ou instructor. Diferente do CheckAdminRole (que só funciona
     * pra rotas de API com JWT), este usa o guard de sessão padrão.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, ['admin', 'instructor'])) {
            abort(403, 'Você não tem permissão para acessar esta área.');
        }

        return $next($request);
    }
}
