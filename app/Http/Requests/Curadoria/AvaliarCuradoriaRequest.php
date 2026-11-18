<?php

namespace App\Http\Requests\Curadoria;

use App\Enums\AcaoResultanteCuradoria;
use App\Enums\StatusCuradoria;
use App\Enums\TipoBem;
use App\Models\Curadoria;
use Closure;
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
            // Avaliar é decidir: "pendente" não é uma decisão.
            'status' => ['required', Rule::enum(StatusCuradoria::class)->except([StatusCuradoria::PENDENTE])],
            'acao_resultante' => [
                'required',
                Rule::enum(AcaoResultanteCuradoria::class),
                function (string $atributo, mixed $valor, Closure $falha): void {
                    $rejeitar = $valor === AcaoResultanteCuradoria::REJEITAR->value;
                    $rejeitado = $this->input('status') === StatusCuradoria::REJEITADO->value;

                    if ($rejeitar !== $rejeitado) {
                        $falha('A ação precisa combinar com o status: "rejeitar" só com "rejeitado".');
                    }

                    // Coleta vira (ou atualiza) sítio; artigo só é aprovado ou rejeitado.
                    $acoesValidas = $this->route('curadoria')?->entidade_tipo === 'submissao_artigo'
                        ? [AcaoResultanteCuradoria::APROVAR, AcaoResultanteCuradoria::REJEITAR]
                        : [AcaoResultanteCuradoria::CRIAR_SITIO, AcaoResultanteCuradoria::ATUALIZAR_SITIO, AcaoResultanteCuradoria::REJEITAR];

                    if (! in_array(AcaoResultanteCuradoria::tryFrom((string) $valor), $acoesValidas, true)) {
                        $falha('Esta ação não vale para este tipo de curadoria.');
                    }
                },
            ],
            'bem_material_id' => [
                Rule::requiredIf(
                    fn () => $this->input('acao_resultante') === AcaoResultanteCuradoria::ATUALIZAR_SITIO->value
                ),
                'nullable', 'uuid', Rule::exists('bens_materiais', 'id')->whereNull('deleted_at'),
            ],
            // D2: o tipo do sítio novo é decisão do curador; o tipo_bem da coleta é só sugestão.
            'tipo' => [
                Rule::requiredIf(fn (): bool => $this->input('acao_resultante') === AcaoResultanteCuradoria::CRIAR_SITIO->value),
                'nullable',
                Rule::enum(TipoBem::class),
            ],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'publicado' => ['sometimes', 'nullable', 'boolean'],
            'campos' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
