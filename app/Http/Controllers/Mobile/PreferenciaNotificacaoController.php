<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notificacao\AtualizarPreferenciasRequest;
use App\Models\PreferenciaNotificacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferenciaNotificacaoController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $preferencia = PreferenciaNotificacao::firstOrCreate(
            ['user_id' => $request->user()->id],
            PreferenciaNotificacao::padroes($request->user()->id),
        );

        return response()->json([
            'coleta' => $preferencia->coleta,
            'sync' => $preferencia->sync,
            'sistema' => $preferencia->sistema,
            'push' => $preferencia->push,
        ]);
    }

    public function update(AtualizarPreferenciasRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (empty($validated)) {
            return response()->json(['coleta' => true, 'sync' => true, 'sistema' => true, 'push' => true]);
        }

        $preferencia = PreferenciaNotificacao::firstOrCreate(
            ['user_id' => $request->user()->id],
            PreferenciaNotificacao::padroes($request->user()->id),
        );

        $preferencia->update($validated);

        return response()->json([
            'coleta' => $preferencia->coleta,
            'sync' => $preferencia->sync,
            'sistema' => $preferencia->sistema,
            'push' => $preferencia->push,
        ]);
    }
}
