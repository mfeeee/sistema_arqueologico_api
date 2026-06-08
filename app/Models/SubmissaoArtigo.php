<?php

namespace App\Models;

use App\Enums\TipoMencaoArtigo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissaoArtigo extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'submissoes_artigos';

    protected $fillable = [
        'usuario_id',
        'bem_material_id',
        'artigo_id',
        'doi',
        'titulo',
        'ano_publicacao',
        'periodico',
        'idioma',
        'resumo',
        'link_acesso',
        'tipo_mencao',
        'trecho_relevante',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tipo_mencao' => TipoMencaoArtigo::class,
            'ano_publicacao' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function bemMaterial(): BelongsTo
    {
        return $this->belongsTo(BemMaterial::class, 'bem_material_id');
    }

    public function artigo(): BelongsTo
    {
        return $this->belongsTo(ArtigoCientifico::class, 'artigo_id');
    }

    public function autores(): HasMany
    {
        return $this->hasMany(SubmissaoAutor::class, 'submissao_id')->orderBy('ordem');
    }
}
