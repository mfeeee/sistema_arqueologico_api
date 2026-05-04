<?php

namespace App\Http\Requests\Curadoria;

use App\Enums\AcaoResultanteCuradoria;
use App\Enums\StatusCuradoria;
use App\Models\Curadoria;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvaliarCuradoriaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('avaliar', Curadoria::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(StatusCuradoria::class)],
            'acao_resultante' => ['required', Rule::enum(AcaoResultanteCuradoria::class)],
            'bem_material_id' => [
                Rule::requiredIf(
                    fn () => $this->input('acao_resultante') === AcaoResultanteCuradoria::ATUALIZAR_SITIO->value
                ),
                'nullable', 'uuid', 'exists:bens_materiais,id',
            ],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
