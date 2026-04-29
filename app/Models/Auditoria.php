<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    use HasUuids;

    protected $table = 'auditorias';

    protected $fillable = [
        'usuario_id',
        'entidade_tipo',
        'entidade_id',
        'curadoria_id',
        'operacao',
        'meio',
        'data_hora',
        'valor_anterior',
        'valor_novo',
    ];

    protected function casts(): array
    {
        return [
            'data_hora' => 'datetime',
            'valor_anterior' => 'array',
            'valor_novo' => 'array',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function curadoria(): BelongsTo
    {
        return $this->belongsTo(Curadoria::class, 'curadoria_id');
    }
}
