<?php

namespace App\Actions;

use App\Models\Auditoria;
use App\Models\BemResponsavel;
use App\Models\Notificacao;
use App\Models\PreferenciaNotificacao;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnonimizarUsuarioAction
{
    /**
     * Anonimiza os dados pessoais do usuário e realiza o soft-delete.
     *
     * Mantém o registro no banco para preservar a integridade das FK
     * (coletas, curadorias, auditorias), conforme LGPD art. 16 II.
     */
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Revogar todos os tokens Sanctum antes de qualquer alteração
            $user->tokens()->delete();

            // Sobrescrever campos pessoais identificadores
            $user->forceFill([
                'name' => 'Usuário excluído',
                'email' => 'anonimizado-'.Str::uuid().'@excluido.local',
                'password' => Str::random(64),
                'remember_token' => null,
                'avatar_url' => null,
                'email_verified_at' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'ativo' => false,
            ])->save();

            // Registrar a operação na auditoria (antes do soft-delete para garantir FK)
            Auditoria::create([
                'usuario_id' => $user->id,
                'entidade_tipo' => User::class,
                'entidade_id' => $user->id,
                'operacao' => 'Anonimização',
                'meio' => 'Portal',
                'data_hora' => now(),
                'valor_anterior' => null,
                'valor_novo' => ['motivo' => 'Exclusão de conta solicitada pelo titular (LGPD art. 18)'],
            ]);

            // Remover registros vinculados que não devem persistir após exclusão
            Notificacao::doUsuario($user->id)->delete();
            PreferenciaNotificacao::where('user_id', $user->id)->delete();
            BemResponsavel::where('user_id', $user->id)->delete();

            // Soft-delete: seta deleted_at, mantém o registro para integridade das FK
            $user->delete();
        });
    }
}
