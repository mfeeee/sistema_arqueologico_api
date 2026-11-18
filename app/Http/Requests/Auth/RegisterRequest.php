<?php

namespace App\Http\Requests\Auth;

use App\Enums\ClassificacaoUsuario;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            // Mesma política de senha definida em AppServiceProvider::configureDefaults().
            'password' => ['required', 'confirmed', Password::defaults()],
            'classificacao' => ['required', new Enum(ClassificacaoUsuario::class)],
            // shortcut: opcional porque o app ainda não tem a caixa de aceite (A4); vira 'required' quando ele estiver nas lojas.
            'termos_aceitos' => ['sometimes', 'accepted'],
        ];
    }
}
