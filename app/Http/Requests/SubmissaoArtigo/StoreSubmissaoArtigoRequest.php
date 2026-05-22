<?php

namespace App\Http\Requests\SubmissaoArtigo;

use App\Enums\TipoMencaoArtigo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissaoArtigoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $artigoExistente = $this->filled('artigo_id');

        return [
            'bem_material_id' => ['required', 'uuid', 'exists:bens_materiais,id'],
            'artigo_id' => ['nullable', 'uuid', 'exists:artigos_cientificos,id'],
            'doi' => ['nullable', 'string', 'max:255'],
            // Obrigatórios apenas quando o artigo ainda não existe no sistema
            'titulo' => [Rule::requiredIf(! $artigoExistente), 'nullable', 'string', 'max:500'],
            'autores' => [Rule::requiredIf(! $artigoExistente), 'nullable', 'string', 'max:500'],
            'ano_publicacao' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'periodico' => ['nullable', 'string', 'max:255'],
            'idioma' => ['nullable', 'string', 'max:10'],
            'resumo' => ['nullable', 'string'],
            'link_acesso' => ['nullable', 'url', 'max:500'],
            'tipo_mencao' => ['required', Rule::enum(TipoMencaoArtigo::class)],
            'trecho_relevante' => ['nullable', 'string'],
        ];
    }
}
