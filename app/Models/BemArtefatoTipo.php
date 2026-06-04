<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BemArtefatoTipo extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'bem_artefato_tipos';

    protected $fillable = [
        'bem_material_id',
        'artefato_tipo_id',
        'descricao_nova',
        'novo_tipo',
    ];

    protected $casts = [
        'novo_tipo' => 'boolean',
    ];

    public function bemMaterial(): BelongsTo
    {
        return $this->belongsTo(BemMaterial::class, 'bem_material_id');
    }

    public function artefatoTipo(): BelongsTo
    {
        return $this->belongsTo(ArtefatoTipo::class, 'artefato_tipo_id');
    }
}
