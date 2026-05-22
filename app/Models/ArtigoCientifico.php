<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArtigoCientifico extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'artigos_cientificos';

    protected $fillable = [
        'adicionado_por',
        'titulo',
        'doi',
        'link_acesso',
        'autores',
        'ano_publicacao',
        'periodico',
        'idioma',
        'resumo',
        'verificado',
    ];

    protected function casts(): array
    {
        return [
            'verificado' => 'boolean',
            'ano_publicacao' => 'integer',
        ];
    }

    public function adicionadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adicionado_por');
    }

    public function bensMateriais(): BelongsToMany
    {
        return $this->belongsToMany(BemMaterial::class, 'artigo_bem_material')
            ->withPivot(['tipo_mencao', 'trecho_relevante'])
            ->withTimestamps();
    }

    public function vinculos(): HasMany
    {
        return $this->hasMany(ArtigoBemMaterial::class, 'artigo_id');
    }
}
