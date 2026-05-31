<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PerfilUsuario;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function curadores(): JsonResponse
    {
        $curadores = User::query()
            ->whereIn('perfil', [PerfilUsuario::CURADOR, PerfilUsuario::ADMIN])
            ->where('ativo', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'perfil']);

        return response()->json(['data' => $curadores]);
    }
}
