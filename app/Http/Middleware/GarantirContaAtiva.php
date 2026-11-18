<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GarantirContaAtiva
{
    /**
     * Token de conta desativada deixa de valer na hora, não só no próximo login (D5).
     * O app procura o texto "desativada"/"deactivated" num 401 para sair da conta.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null && ! $request->user()->ativo) {
            return response()->json(['message' => __('errors.account_disabled')], 401);
        }

        return $next($request);
    }
}
