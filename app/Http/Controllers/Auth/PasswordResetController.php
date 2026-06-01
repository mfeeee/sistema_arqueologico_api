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

        return response()->json(['message' => __('success.password_reset_sent')]);
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
            return response()->json(['message' => __('success.password_reset')]);
        }

        $mensagem = match ($status) {
            Password::INVALID_TOKEN => __('errors.invalid_token'),
            default => __('errors.password_reset_failed'),
        };

        return response()->json(['message' => $mensagem], 422);
    }
}
