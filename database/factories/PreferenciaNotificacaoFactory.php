<?php

namespace Database\Factories;

use App\Models\PreferenciaNotificacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PreferenciaNotificacao>
 */
class PreferenciaNotificacaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'coleta' => true,
            'sync' => true,
            'sistema' => true,
            'push' => true,
        ];
    }

    public function comColetaDesativada(): static
    {
        return $this->state(['coleta' => false]);
    }

    public function comPushDesativado(): static
    {
        return $this->state(['push' => false]);
    }

    public function todasDesativadas(): static
    {
        return $this->state([
            'coleta' => false,
            'sync' => false,
            'sistema' => false,
            'push' => false,
        ]);
    }
}
