<?php

namespace Database\Factories;

use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\Curadoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Auditoria>
 */
class AuditoriaFactory extends Factory
{
    protected $model = Auditoria::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'entidade_tipo' => 'App\\Models\\BemMaterial',
            'entidade_id' => BemMaterial::factory(),
            'curadoria_id' => null,
            'operacao' => fake()->randomElement(['Inserção', 'Alteração', 'Exclusão']),
            'meio' => 'Auditoria',
            'data_hora' => fake()->dateTimeBetween('-3 months', 'now'),
            'valor_anterior' => null,
            'valor_novo' => null,
        ];
    }

    /**
     * Auditoria de inserção: gerada quando um BemMaterial é criado via curadoria.
     * valor_anterior = null, valor_novo = snapshot completo do bem criado.
     */
    public function insercao(?array $valorNovo = null): static
    {
        return $this->state(fn () => [
            'operacao' => 'Inserção',
            'meio' => 'Auditoria',
            'valor_anterior' => null,
            'valor_novo' => $valorNovo,
        ]);
    }

    /**
     * Auditoria de alteração simples: apenas o campo alterado em valor_novo.
     * valor_anterior = snapshot completo do estado anterior.
     */
    public function alteracaoSimples(?array $valorAnterior = null, ?array $valorNovo = null): static
    {
        return $this->state(fn () => [
            'operacao' => 'Alteração',
            'meio' => 'Auditoria',
            'valor_anterior' => $valorAnterior,
            'valor_novo' => $valorNovo,
        ]);
    }

    /**
     * Auditoria de alteração complexa: múltiplos campos em valor_novo.
     */
    public function alteracaoComplexa(?array $valorAnterior = null, ?array $valorNovo = null): static
    {
        return $this->alteracaoSimples($valorAnterior, $valorNovo);
    }

    /**
     * Auditoria manual: gerada pelo painel admin sem passar por curadoria.
     * meio = 'Manual', curadoria_id = null.
     */
    public function manual(?array $valorAnterior = null, ?array $valorNovo = null): static
    {
        return $this->state(fn () => [
            'operacao' => 'Alteração',
            'meio' => 'Manual',
            'curadoria_id' => null,
            'valor_anterior' => $valorAnterior,
            'valor_novo' => $valorNovo,
        ]);
    }

    /** Vincula a auditoria a uma curadoria específica. */
    public function paraCuradoria(Curadoria $curadoria): static
    {
        return $this->state(fn () => [
            'curadoria_id' => $curadoria->id,
        ]);
    }
}
