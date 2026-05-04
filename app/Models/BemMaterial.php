<?php

namespace App\Models;

use App\Concerns\HasAuditoria;
use App\Enums\NaturezaBem;
use App\Enums\TipoBem;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BemMaterial extends Model
{
    use HasAuditoria, HasFactory, HasUuids;

    protected $table = 'bens_materiais';

    protected $fillable = [
        'coleta_id',
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
        'geojson',
        'ano_registro',
        'descricao_atualizacao',
        'geom',
    ];

    protected $casts = [
        'artefatos' => 'array',
        'geojson' => 'array',
        'natureza' => NaturezaBem::class,
        'tipo' => TipoBem::class,
        'artefatos' => 'array',
        'publicado' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'ano_registro' => 'integer',
        'deletado_em' => 'datetime',
    ];

    public function coleta(): BelongsTo
    {
        return $this->belongsTo(Coleta::class, 'coleta_id');
    }

    public function midias(): HasMany
    {
        return $this->hasMany(MidiaLink::class, 'bem_material_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function responsaveis(): HasMany
    {
        return $this->hasMany(ResponsavelSitio::class, 'bem_material_id');
    }

    protected function geom(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value,
        );
    }

    public function scopePublicados($query)
    {
        return $query->where('publicado', true)->whereNull('deletado_em');
    }

    public function scopeProximo($query, float $lat, float $lng, int $raioKm = 5)
    {
        $raioMetros = $raioKm * 1000;

        return $query->whereNotNull('geom')
            ->whereRaw(
                'ST_DWithin(geom::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$lng, $lat, $raioMetros]
            )
            ->selectRaw(
                '*, ST_Distance(geom::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distancia_metros',
                [$lng, $lat]
            )
            ->orderBy('distancia_metros');
    }
}
