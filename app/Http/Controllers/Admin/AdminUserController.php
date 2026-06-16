<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PerfilUsuario;
use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->orderBy('name');

        if ($request->filled('q')) {
            $q = $request->string('q')->trim()->toString();
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'ilike', "%{$q}%")
                    ->orWhere('email', 'ilike', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        if ($request->filled('perfil')) {
            $query->where('perfil', $request->perfil);
        }

        return response()->json(
            $query->paginate(20, ['id', 'name', 'email', 'perfil', 'classificacao', 'ativo', 'created_at'])
        );
    }

    public function curadores(): JsonResponse
    {
        $curadores = User::query()
            ->whereIn('perfil', [PerfilUsuario::CURADOR, PerfilUsuario::ADMIN])
            ->where('ativo', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'perfil']);

        return response()->json(['data' => $curadores]);
    }

    public function updatePerfil(Request $request, User $user): JsonResponse
    {
        $request->validate(['perfil' => ['required', new Enum(PerfilUsuario::class)]]);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Não é possível alterar o próprio perfil.'], 403);
        }

        if ($user->perfil === PerfilUsuario::ADMIN) {
            return response()->json(['message' => 'Não é possível alterar o perfil de um administrador.'], 403);
        }

        $perfilAnterior = $user->perfil->value;

        $user->update(['perfil' => $request->perfil]);

        Auditoria::create([
            'usuario_id' => $request->user()->id,
            'entidade_tipo' => User::class,
            'entidade_id' => $user->id,
            'operacao' => 'Alteração',
            'meio' => 'Curadoria',
            'data_hora' => now(),
            'valor_anterior' => [
                'id' => $user->id,
                'nome' => $user->name,
                'email' => $user->email,
                'perfil' => $perfilAnterior,
            ],
            'valor_novo' => ['perfil' => $user->perfil->value],
        ]);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'perfil' => $user->perfil,
            'classificacao' => $user->classificacao,
            'ativo' => $user->ativo,
        ]);
    }
}
