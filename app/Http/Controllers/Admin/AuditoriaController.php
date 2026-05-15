<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
