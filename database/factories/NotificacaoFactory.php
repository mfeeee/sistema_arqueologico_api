<?php

namespace Database\Factories;

use App\Enums\TipoNotificacao;
use App\Models\Notificacao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notificacao>
 */
class NotificacaoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'titulo' => fake()->sentence(4),
            'corpo' => fake()->paragraph(),
            'tipo' => fake()->randomElement(TipoNotificacao::cases())->value,
            'lida' => false,
            'lida_em' => null,
        ];
    }

    public function lida(): static
    {
        return $this->state(fn (array $attributes) => [
            'lida' => true,
            'lida_em' => now(),
        ]);
    }

    public function tipo(TipoNotificacao $tipo): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => $tipo->value,
        ]);
    }
}
