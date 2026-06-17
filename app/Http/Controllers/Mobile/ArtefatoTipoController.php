<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ArtefatoTipo;
use Illuminate\Http\JsonResponse;

class ArtefatoTipoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            ArtefatoTipo::select('id', 'nome')->orderBy('nome')->get()
        );
    }
}
