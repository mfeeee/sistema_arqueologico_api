<?php

namespace App\Http\Requests\Sincronizacao;

use App\Enums\ArtefatoBem;
use App\Enums\NaturezaBem;
use App\Enums\TipoBem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SincronizarColetasRequest extends FormRequest
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
            'coletas' => ['required', 'array', 'min:1'],
            'coletas.*.id' => ['required', 'uuid'],
            'coletas.*.data_coleta' => ['required', 'date'],
            'coletas.*.nome_bem' => ['required', 'string', 'max:255'],
            'coletas.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'coletas.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'coletas.*.natureza' => ['nullable', Rule::enum(NaturezaBem::class)],
            'coletas.*.tipo' => ['nullable', Rule::enum(TipoBem::class)],
            'coletas.*.uf' => ['nullable', 'string', 'size:2'],
            'coletas.*.artefatos' => ['nullable', 'array'],
            'coletas.*.artefatos.*' => [Rule::enum(ArtefatoBem::class)],
            'coletas.*.versao' => ['required', 'integer', 'min:1'],
            'coletas.*.dados_coletados' => ['nullable', 'array'],
        ];
    }
}
