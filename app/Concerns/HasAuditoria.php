<?php

namespace App\Concerns;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;

trait HasAuditoria
{
    public static function bootHasAuditoria(): void
    {
        static::created(function ($model) {
            static::registrarAuditoria($model, 'insercao', null, $model->toArray());
        });

        static::updated(function ($model) {
            static::registrarAuditoria(
                $model,
                'alteracao',
                $model->getOriginal(),
                $model->getChanges()
            );
        });

        static::deleted(function ($model) {
            static::registrarAuditoria($model, 'exclusao', $model->toArray(), null);
        });
    }

    private static function registrarAuditoria(
        $model,
        string $operacao,
        ?array $valorAnterior,
        ?array $valorNovo
    ): void {
        if (! Auth::check()) {
            return;
        }

        Auditoria::create([
            'usuario_id' => Auth::id(),
            'entidade_tipo' => $model::class,
            'entidade_id' => $model->getKey(),
            'operacao' => $operacao,
            'meio' => 'app_sync',
            'valor_anterior' => $valorAnterior,
            'valor_novo' => $valorNovo,
        ]);
    }
}
