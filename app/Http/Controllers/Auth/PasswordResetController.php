<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmarResetRequest;
use App\Http\Requests\Auth\SolicitarResetRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function solicitar(SolicitarResetRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        // Resposta genérica para não expor se o e-mail existe ou não.
        return response()->json(['message' => 'Se este e-mail estiver cadastrado, você receberá as instruções em breve.']);
    }

    public function confirmar(ConfirmarResetRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Senha redefinida com sucesso.']);
        }

        $mensagem = match ($status) {
            Password::INVALID_TOKEN => 'Token inválido ou expirado.',
            Password::INVALID_USER => 'Não foi possível redefinir a senha.',
            default => 'Não foi possível redefinir a senha.',
        };

        return response()->json(['message' => $mensagem], 422);
    }
}
