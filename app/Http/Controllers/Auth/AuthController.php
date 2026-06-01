<?php

namespace App\Http\Controllers\Auth;

use App\Enums\PerfilUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => __('errors.invalid_credentials')], 401);
        }

        $user = Auth::user();

        if (! $user->ativo) {
            Auth::logout();

            return response()->json(['message' => __('errors.account_disabled')], 403);
        }

        $token = $user->createToken(
            name: 'mobile',
            abilities: [$user->perfil->value],
        )->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'perfil' => $user->perfil,
                'classificacao' => $user->classificacao,
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'perfil' => PerfilUsuario::COLETOR,
            'classificacao' => $request->classificacao,
            'ativo' => true,
        ]);

        $token = $user->createToken(
            name: 'mobile',
            abilities: [$user->perfil->value],
        )->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'perfil' => $user->perfil,
                'classificacao' => $user->classificacao,
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
