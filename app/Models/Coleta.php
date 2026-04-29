<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Coleta extends Model
{
    use HasUuids;

    protected $table = 'coletas';

    protected $fillable = [
        'usuario_uuid',
        'data_coleta',
        'latitude',
        'longitude',
        'nome_bem',
        'natureza_bem',
        'tipo_bem',
        'status_sync',
        'versao',
        'dados_coletados',
        'deletado_em',
    ];

    protected $casts = [
        'data_coleta' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'versao' => 'integer',
        'dados_coletados' => 'array',
        'deletado_em' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_uuid');
    }

    public function bemMaterial(): HasOne
    {
        return $this->hasOne(BemMaterial::class, 'coleta_uuid');
    }

    public function scopeDoUsuario($query, string $usuarioUuid)
    {
        return $query->where('usuario_uuid', $usuarioUuid);
    }
}
