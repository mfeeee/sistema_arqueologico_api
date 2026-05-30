<?php

namespace App\Http\Requests\Notificacao;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AtualizarPreferenciasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'coleta' => ['sometimes', 'boolean'],
            'sync' => ['sometimes', 'boolean'],
            'sistema' => ['sometimes', 'boolean'],
            'push' => ['sometimes', 'boolean'],
        ];
    }
}
