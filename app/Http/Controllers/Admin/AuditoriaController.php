<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\BemMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Auditoria::class);

        $query = Auditoria::with('usuario')
            ->orderByDesc('data_hora');

        if ($request->filled('entidade_tipo')) {
            $query->where('entidade_tipo', $request->entidade_tipo);
        }

        if ($request->filled('entidade_id')) {
            $query->where('entidade_id', $request->entidade_id);
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        return response()->json($query->paginate(50));
    }

    public function show(Auditoria $auditoria): JsonResponse
    {
        $this->authorize('view', $auditoria);

        return response()->json($auditoria->load('usuario', 'curadoria'));
    }

    public function restaurar(Auditoria $auditoria): JsonResponse
    {
        $this->authorize('view', $auditoria);

        if (! str_contains($auditoria->entidade_tipo, 'BemMaterial')) {
            return response()->json(['message' => 'Reversão disponível apenas para sítios arqueológicos.'], 422);
        }

        $operacoesRevertiveis = ['insercao', 'alteracao', 'Inserção', 'Alteração'];
        if (! in_array($auditoria->operacao, $operacoesRevertiveis)) {
            return response()->json(['message' => 'Reversão disponível apenas para operações de inserção ou alteração.'], 422);
        }

        $bem = BemMaterial::withTrashed()->find($auditoria->entidade_id);

        if (! $bem) {
            return response()->json(['message' => 'Registro não encontrado.'], 404);
        }

        if ($auditoria->valor_anterior === null) {
            // Inserção: reverter = excluir o registro (soft delete).
            $bem->delete();
        } else {
            // Alteração: reverter = restaurar estado anterior.
            $dados = array_intersect_key(
                $auditoria->valor_anterior,
                array_flip($bem->getFillable())
            );
            $bem->fill($dados)->save();

            // Sincroniza geom com as coordenadas restauradas, se presentes.
            if (isset($dados['latitude'], $dados['longitude'])) {
                DB::statement(
                    'UPDATE bens_materiais SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
                    [$dados['longitude'], $dados['latitude'], $bem->id]
                );
            }
        }

        return response()->json(['message' => 'Reversão realizada com sucesso.']);
    }
}
