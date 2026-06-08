<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orquestrador principal dos seeders do Stratigraphy Manager.
 *
 * Ordem de execução obrigatória (respeita dependências de FK):
 *
 *  1. ArtefatoTipoSeeder      — Popula catálogo de tipos de artefato (derivado de ArtefatoBem).
 *  2. UserSeeder              — Cria os 3 usuários fixos (admin, curador, coletor).
 *  3. NotificacaoSeeder       — Preferências de notificação (1/usuário) + 6 notificações de exemplo.
 *  4. BemMaterialSeeder       — Cria 6 sítios base do Piauí + mídias + responsáveis.
 *  5. ColetaECuradoriaSeeder  — Gera os cenários A–F de curadoria (entidade_tipo=coleta):
 *       A: coletas pendentes (intenção: criarSitio quando avaliado)
 *       B: aprovação → criarSitio   + auditoria Inserção
 *       C: aprovação → atualizarSitio preenchendo campo que era NULL + auditoria Alteração
 *       D: aprovação → atualizarSitio modificando campo que já tinha valor + auditoria Alteração
 *       E: aprovação → atualizarSitio múltiplos campos (null→valor + valor→valor) + auditoria
 *       F: rejeição (sem bem_material, sem auditoria de bem)
 *  5. MidiaSeeder             — Cria mídias para coletas (via dados_coletados) e bens (idempotente).
 *  6. AuditoriaManualSeeder              — Gera o cenário G: auditorias com meio = 'Manual'.
 *  7. CuradoriaAtualizacaoPendenteSeeder — 3 curadorias pendentes de atualizarSitio
 *       (bem_material_id preenchido; testa o fluxo de atualização de campos pelo curador).
 *  8. ArtigoCientificoSeeder  — Gera cenários de artigos (entidade_tipo=submissao_artigo):
 *       Artigo-A: 3 artigos aprovados + vínculos + auditorias (visíveis na aba Artigos)
 *       Artigo-B: 2 submissões pendentes de artigo novo (fila de curadoria)
 *       Artigo-C: 1 submissão pendente com artigo já existente (cenário A da API)
 *       Artigo-D: 1 submissão rejeitada (histórico)
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
            ArtefatoTipoSeeder::class,
            UserSeeder::class,
            NotificacaoSeeder::class,
            BemMaterialSeeder::class,
            ColetaECuradoriaSeeder::class,
            MidiaSeeder::class,
            AuditoriaManualSeeder::class,
            CuradoriaAtualizacaoPendenteSeeder::class,
            ArtigoCientificoSeeder::class,
        ]);
    }
}
