<?php

namespace Database\Seeders;

use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Caso F — Auditoria Manual.
 *
 * Simula 3 operações realizadas diretamente pelo painel de administração,
 * sem passar por coleta ou curadoria.
 *
 * Regras:
 *  - meio = 'Manual'
 *  - curadoria_id = null
 *  - Retrata alterações e exclusões feitas pelo Admin no painel.
 */
class AuditoriaManualSeeder extends Seeder
{
    /** @return array<string, mixed> */
    private function snapshot(BemMaterial $bem): array
    {
        return [
            'id' => $bem->id,
            'codigo_iphan' => $bem->codigo_iphan,
            'nome_bem' => $bem->nome_bem,
            'nomes_populares' => $bem->nomes_populares,
            'natureza' => $bem->natureza?->value ?? $bem->natureza,
            'tipo' => $bem->tipo?->value ?? $bem->tipo,
            'uf' => $bem->uf,
            'municipio' => $bem->municipio,
            'cep' => $bem->cep,
            'endereco' => $bem->endereco,
            'meios_acesso' => $bem->meios_acesso,
            'latitude' => (float) $bem->latitude,
            'longitude' => (float) $bem->longitude,
            'artefatos' => $bem->artefatos,
            'publicado' => $bem->publicado,
            'ano_registro' => $bem->ano_registro,
            'descricao_atualizacao' => $bem->descricao_atualizacao,
            'updated_at' => $bem->updated_at?->toIso8601String(),
        ];
    }

    public function run(): void
    {
        $admin = User::where('email', 'admin@arqueologia.test')->firstOrFail();

        $bens = BemMaterial::whereIn('codigo_iphan', [
            'PI-BASE-0001',
            'PI-BASE-0003',
            'PI-BASE-0005',
        ])->get()->keyBy('codigo_iphan');

        // ── F1 — Alteração de meios de acesso ───────────────────────────────────
        $bemF1 = $bens['PI-BASE-0001'];
        Auditoria::create([
            'usuario_id' => $admin->id,
            'entidade_tipo' => 'App\\Models\\BemMaterial',
            'entidade_id' => $bemF1->id,
            'curadoria_id' => null,
            'operacao' => 'Alteração',
            'meio' => 'Manual',
            'data_hora' => Carbon::now()->subDays(7),
            'valor_anterior' => $this->snapshot($bemF1),
            'valor_novo' => [
                'meios_acesso' => 'Acesso pela BR-020 até São Raimundo Nonato; nova trilha sinalizada desde jan/2026 — 6 km ao sítio. Entrada somente com guia cadastrado no PARNA.',
            ],
        ]);

        // ── F2 — Alteração de publicação e descrição ─────────────────────────────
        $bemF2 = $bens['PI-BASE-0003'];
        Auditoria::create([
            'usuario_id' => $admin->id,
            'entidade_tipo' => 'App\\Models\\BemMaterial',
            'entidade_id' => $bemF2->id,
            'curadoria_id' => null,
            'operacao' => 'Alteração',
            'meio' => 'Manual',
            'data_hora' => Carbon::now()->subDays(5),
            'valor_anterior' => $this->snapshot($bemF2),
            'valor_novo' => [
                'publicado' => true,
                'descricao_atualizacao' => 'Sítio validado pelo IPHAN em 2026. Publicação autorizada após revisão técnica completa.',
            ],
        ]);

        // ── F3 — Exclusão lógica de campo incorreto ───────────────────────────────
        $bemF3 = $bens['PI-BASE-0005'];
        Auditoria::create([
            'usuario_id' => $admin->id,
            'entidade_tipo' => 'App\\Models\\BemMaterial',
            'entidade_id' => $bemF3->id,
            'curadoria_id' => null,
            'operacao' => 'Exclusão',
            'meio' => 'Manual',
            'data_hora' => Carbon::now()->subDays(2),
            'valor_anterior' => [
                'id' => $bemF3->id,
                'codigo_iphan' => $bemF3->codigo_iphan,
                'nome_bem' => $bemF3->nome_bem,
                'natureza' => $bemF3->natureza?->value ?? $bemF3->natureza,
                'tipo' => $bemF3->tipo?->value ?? $bemF3->tipo,
                'uf' => $bemF3->uf,
                'municipio' => $bemF3->municipio,
                'latitude' => (float) $bemF3->latitude,
                'longitude' => (float) $bemF3->longitude,
                'artefatos' => $bemF3->artefatos,
                'publicado' => $bemF3->publicado,
                'ano_registro' => $bemF3->ano_registro,
                'descricao_atualizacao' => $bemF3->descricao_atualizacao,
                'updated_at' => $bemF3->updated_at?->toIso8601String(),
            ],
            'valor_novo' => null,
        ]);

        $this->command->info('AuditoriaManualSeeder: 3 auditorias manuais (Caso F) criadas.');
    }
}
