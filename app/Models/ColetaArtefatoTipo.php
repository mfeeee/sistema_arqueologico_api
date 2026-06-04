<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColetaArtefatoTipo extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'coleta_artefato_tipos';

    protected $fillable = [
        'coleta_id',
        'artefato_tipo_id',
        'descricao_nova',
        'novo_tipo',
    ];

    protected $casts = [
        'novo_tipo' => 'boolean',
    ];

    public function coleta(): BelongsTo
    {
        return $this->belongsTo(Coleta::class, 'coleta_id');
    }

    public function artefatoTipo(): BelongsTo
    {
        return $this->belongsTo(ArtefatoTipo::class, 'artefato_tipo_id');
    }
}
