<?php

namespace App\Http\Controllers\Auth;

use App\Enums\PerfilUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
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

    public function register(RegisterRequest $request): JsonResponse
    {
        $dados = $request->validated();

        $user = User::create([
            'name' => $dados['name'],
            'email' => $dados['email'],
            'password' => $dados['password'],
            'perfil' => PerfilUsuario::COLETOR,
            'classificacao' => $dados['classificacao'],
            'ativo' => true,
            'termos_aceitos_em' => $request->boolean('termos_aceitos') ? now() : null,
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

        return response()->json(['message' => __('success.logout')]);
    }

    /**
     * Troca o token atual por um novo (D5). O app chama ao abrir, antes de os 30 dias vencerem;
     * o token antigo deixa de valer.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json([
            'token' => $user->createToken(name: 'mobile', abilities: [$user->perfil->value])->plainTextToken,
        ]);
    }
}
