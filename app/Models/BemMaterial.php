<?php

namespace App\Models;

use App\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BemMaterial extends Model
{
    use HasAuditoria, HasUuids;

    protected $table = 'bens_materiais';

    protected $fillable = [
        'coleta_uuid',
        'codigo_iphan',
        'nome_bem',
        'nomes_populares',
        'natureza',
        'tipo',
        'artefatos',
        'meios_acesso',
        'publicado',
        'uf',
        'municipio',
        'cep',
        'endereco',
        'latitude',
        'longitude',
        'geom',
        'deletado_em',
    ];

    protected $casts = [
        'publicado' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'deletado_em' => 'datetime',
    ];

    public function coleta(): BelongsTo
    {
        return $this->belongsTo(Coleta::class, 'coleta_uuid');
    }

    public function midias(): HasMany
    {
        return $this->hasMany(MidiaLink::class, 'bem_material_id');
    }

    public function responsaveis(): HasMany
    {
        return $this->hasMany(ResponsavelSitio::class, 'bem_material_id');
    }

    public function scopePublicados($query)
    {
        return $query->where('publicado', true)->whereNull('deletado_em');
    }

    public function scopeProximo($query, float $lat, float $lng, int $raioKm = 5)
    {
        $raioMetros = $raioKm * 1000;

        return $query->whereRaw(
            'ST_DWithin(geom::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$lng, $lat, $raioMetros]
        );
    }
}
