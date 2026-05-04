<?php

namespace App\Models;

use App\Enums\NaturezaBem;
use App\Enums\StatusColeta;
use App\Enums\TipoBem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Coleta extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'coletas';

    protected $fillable = [
        'usuario_id',
        'data_coleta',
        'latitude',
        'longitude',
        'nome_bem',
        'natureza_bem',
        'tipo_bem',
        'artefatos',
        'status_sync',
        'uf',
        'versao',
        'dados_coletados',
        'deletado_em',
    ];

    protected $casts = [
        'data_coleta' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'natureza_bem' => NaturezaBem::class,
        'tipo_bem' => TipoBem::class,
        'artefatos' => 'array',
        'status_sync' => StatusColeta::class,
        'versao' => 'integer',
        'dados_coletados' => 'array',
        'deletado_em' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function bemMaterial(): HasOne
    {
        return $this->hasOne(BemMaterial::class, 'coleta_uuid');
    }

    public function scopeDoUsuario($query, string $usuarioId): mixed
    {
        return $query->where('usuario_id', $usuarioId);
    }
}
