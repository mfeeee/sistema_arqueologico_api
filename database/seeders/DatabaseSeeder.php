<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orquestrador principal dos seeders do Stratigraphy Manager.
 *
 * Ordem de execução obrigatória (respeita dependências de FK):
 *
 *  1. UserSeeder              — Cria os 3 usuários fixos (admin, curador, coletor).
 *  2. BemMaterialSeeder       — Cria 6 sítios base do Piauí + mídias + responsáveis.
 *  3. ColetaECuradoriaSeeder  — Gera os cenários A–F de curadoria:
 *       A: coletas pendentes (intenção: criarSitio quando avaliado)
 *       B: aprovação → criarSitio   + auditoria Inserção
 *       C: aprovação → atualizarSitio preenchendo campo que era NULL + auditoria Alteração
 *       D: aprovação → atualizarSitio modificando campo que já tinha valor + auditoria Alteração
 *       E: aprovação → atualizarSitio múltiplos campos (null→valor + valor→valor) + auditoria
 *       F: rejeição (sem bem_material, sem auditoria de bem)
 *  4. AuditoriaManualSeeder              — Gera o cenário G: auditorias com meio = 'Manual'.
 *  5. CuradoriaAtualizacaoPendenteSeeder — 3 curadorias pendentes de atualizarSitio
 *       (bem_material_id preenchido; testa o fluxo de atualização de campos pelo curador).
 *
 * Usuários de teste disponíveis após o seed:
 *  - admin@arqueologia.test   / password  (perfil: admin)
 *  - curador@arqueologia.test / password  (perfil: curador)
 *  - coletor@arqueologia.test / password  (perfil: coletor)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            BemMaterialSeeder::class,
            ColetaECuradoriaSeeder::class,
            AuditoriaManualSeeder::class,
            CuradoriaAtualizacaoPendenteSeeder::class,
        ]);
    }
}
