<?php

namespace App\Http\Requests\BemMaterial;

use App\Enums\ArtefatoBem;
use App\Enums\NaturezaBem;
use App\Enums\TipoBem;
use App\Models\BemMaterial;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBemMaterialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', BemMaterial::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $bemMaterialId = $this->route('bemMaterial')?->id;

        return [
            'coleta_id'            => ['nullable', 'uuid', 'exists:coletas,id'],
            'codigo_iphan'         => ['nullable', 'string', Rule::unique('bens_materiais', 'codigo_iphan')->ignore($bemMaterialId)],
            'nome_bem'             => ['required', 'string', 'max:255'],
            'nomes_populares'      => ['nullable', 'string'],
            'natureza'             => ['required', Rule::enum(NaturezaBem::class)],
            'tipo'                 => ['required', Rule::enum(TipoBem::class)],
            'meios_acesso'         => ['nullable', 'string'],
            'artefatos'            => ['nullable', 'array'],
            'artefatos.*'          => [Rule::enum(ArtefatoBem::class)],
            'publicado'            => ['boolean'],
            'uf'                   => ['nullable', 'string', 'size:2'],
            'municipio'            => ['nullable', 'string'],
            'cep'                  => ['nullable', 'string', 'size:9'],
            'endereco'             => ['nullable', 'string'],
            'latitude'             => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'            => ['nullable', 'numeric', 'between:-180,180'],
            'geojson'              => ['nullable', 'json'],
            'ano_registro'         => ['nullable', 'integer', 'digits:4'],
            'descricao_atualizacao'=> ['nullable', 'string'],
        ];
    }
}
