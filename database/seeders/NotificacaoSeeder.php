<?php

namespace Database\Seeders;

use App\Enums\TipoNotificacao;
use App\Models\Notificacao;
use App\Models\PreferenciaNotificacao;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Popula preferências de notificação (uma por usuário) e notificações de exemplo.
 *
 * Preferências:
 *  - admin   : coleta=true, sync=false, sistema=true, push=false
 *  - curador : coleta=true, sync=true,  sistema=true, push=true
 *  - coletor : coleta=true, sync=true,  sistema=false, push=true
 *
 * Notificações (cenários para testes da UI):
 *  - coletor : 2 coleta  (1 lida, 1 não lida)  + 1 sync não lida
 *  - curador : 2 sistema (1 lida, 1 não lida)
 *  - admin   : 1 sistema não lida
 */
class NotificacaoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@arqueologia.test')->firstOrFail();
        $curador = User::where('email', 'curador@arqueologia.test')->firstOrFail();
        $coletor = User::where('email', 'coletor@arqueologia.test')->firstOrFail();

        $this->seedPreferencias($admin, $curador, $coletor);
        $this->seedNotificacoes($admin, $curador, $coletor);
    }

    private function seedPreferencias(User $admin, User $curador, User $coletor): void
    {
        $configuracoes = [
            $admin->id => ['coleta' => true, 'sync' => false, 'sistema' => true,  'push' => false],
            $curador->id => ['coleta' => true, 'sync' => true,  'sistema' => true,  'push' => true],
            $coletor->id => ['coleta' => true, 'sync' => true,  'sistema' => false, 'push' => true],
        ];

        foreach ($configuracoes as $userId => $prefs) {
            PreferenciaNotificacao::updateOrCreate(
                ['user_id' => $userId],
                $prefs
            );
        }

        $this->command->info('NotificacaoSeeder [preferências]: 3 registros criados/atualizados.');
    }

    private function seedNotificacoes(User $admin, User $curador, User $coletor): void
    {
        $notificacoes = [
            // ── coletor ─────────────────────────────────────────────────────────
            [
                'usuario_id' => $coletor->id,
                'tipo' => TipoNotificacao::Coleta,
                'titulo' => 'Coleta aprovada',
                'corpo' => 'Sua coleta "Sítio Boqueirão da Pedra Furada" foi aprovada pelo curador e um novo sítio foi registrado.',
                'lida' => true,
                'lida_em' => Carbon::now()->subDays(5),
                'created_at' => Carbon::now()->subDays(6),
            ],
            [
                'usuario_id' => $coletor->id,
                'tipo' => TipoNotificacao::Coleta,
                'titulo' => 'Coleta em análise',
                'corpo' => 'Sua coleta "Abrigo da Anta — Prospecção 2026" está sendo analisada pela equipe de curadoria.',
                'lida' => false,
                'lida_em' => null,
                'created_at' => Carbon::now()->subDays(2),
            ],
            [
                'usuario_id' => $coletor->id,
                'tipo' => TipoNotificacao::Sync,
                'titulo' => 'Sincronização concluída',
                'corpo' => '3 coletas sincronizadas com sucesso. Nenhum conflito detectado.',
                'lida' => false,
                'lida_em' => null,
                'created_at' => Carbon::now()->subHours(4),
            ],

            // ── curador ─────────────────────────────────────────────────────────
            [
                'usuario_id' => $curador->id,
                'tipo' => TipoNotificacao::Sistema,
                'titulo' => 'Nova coleta para revisão',
                'corpo' => 'A coleta "Gruta das Serras — Levantamento Inicial" aguarda sua avaliação.',
                'lida' => true,
                'lida_em' => Carbon::now()->subDays(1),
                'created_at' => Carbon::now()->subDays(2),
            ],
            [
                'usuario_id' => $curador->id,
                'tipo' => TipoNotificacao::Sistema,
                'titulo' => '2 coletas pendentes de revisão',
                'corpo' => 'Existem 2 coletas aguardando avaliação de curadoria na fila.',
                'lida' => false,
                'lida_em' => null,
                'created_at' => Carbon::now()->subHours(1),
            ],

            // ── admin ────────────────────────────────────────────────────────────
            [
                'usuario_id' => $admin->id,
                'tipo' => TipoNotificacao::Sistema,
                'titulo' => 'Resumo semanal do sistema',
                'corpo' => '6 bens materiais cadastrados, 18 coletas processadas, 15 auditorias registradas na última semana.',
                'lida' => false,
                'lida_em' => null,
                'created_at' => Carbon::now()->subHours(2),
            ],
        ];

        foreach ($notificacoes as $dados) {
            Notificacao::create($dados);
        }

        $this->command->info('NotificacaoSeeder [notificações]: 6 registros criados.');
    }
}
