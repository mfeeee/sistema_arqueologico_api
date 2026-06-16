<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\TipoNotificacao;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificacaoResource;
use App\Models\Notificacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rules\Enum;

class NotificacaoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'tipo' => ['sometimes', 'string', new Enum(TipoNotificacao::class)],
        ]);

        $query = Notificacao::doUsuario($request->user()->id)
            ->orderByDesc('created_at');

        if ($request->has('tipo')) {
            $query->porTipo($request->string('tipo'));
        }

        return NotificacaoResource::collection($query->paginate(20));
    }

    public function marcarComoLida(Request $request, Notificacao $notificacao): JsonResponse
    {
        if ($notificacao->usuario_id !== $request->user()->id) {
            abort(403);
        }

        $notificacao->update([
            'lida' => true,
            'lida_em' => $notificacao->lida_em ?? now(),
        ]);

        return response()->json(new NotificacaoResource($notificacao));
    }

    public function vincularToken(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        $request->user()->update(['fcm_token' => $request->token]);

        return response()->json(['message' => 'Token vinculado com sucesso.']);
    }
}
