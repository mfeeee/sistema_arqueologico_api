<?php

namespace App\Http\Requests\Coleta;

use App\Enums\ArtefatoBem;
use App\Enums\NaturezaBem;
use App\Enums\TipoBem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreColetaRequest extends FormRequest
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
            'data_coleta' => ['required', 'date'],
            'nome_bem' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'natureza' => ['nullable', Rule::enum(NaturezaBem::class)],
            'tipo' => ['nullable', Rule::enum(TipoBem::class)],
            'uf' => ['nullable', 'string', 'size:2'],
            'artefatos' => ['nullable', 'array'],
            'artefatos.*' => [Rule::enum(ArtefatoBem::class)],
            'versao' => ['nullable', 'integer', 'min:1'],
            'dados_coletados' => ['nullable', 'array'],
        ];
    }
}
