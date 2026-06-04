<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArtefatoTipo extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'nome',
        'descricao',
    ];

    public function coletaArtefatoTipos(): HasMany
    {
        return $this->hasMany(ColetaArtefatoTipo::class, 'artefato_tipo_id');
    }
}
