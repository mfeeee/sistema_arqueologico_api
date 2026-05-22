<?php

namespace App\Models;

use App\Enums\TipoMencaoArtigo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtigoBemMaterial extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'artigo_bem_material';

    protected $fillable = [
        'artigo_id',
        'bem_material_id',
        'tipo_mencao',
        'trecho_relevante',
    ];

    protected function casts(): array
    {
        return [
            'tipo_mencao' => TipoMencaoArtigo::class,
        ];
    }

    public function artigo(): BelongsTo
    {
        return $this->belongsTo(ArtigoCientifico::class, 'artigo_id');
    }

    public function bemMaterial(): BelongsTo
    {
        return $this->belongsTo(BemMaterial::class, 'bem_material_id');
    }
}
