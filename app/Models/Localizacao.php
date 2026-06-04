<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Localizacao extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'localizacoes';

    protected $fillable = [
        'cep',
        'logradouro',
        'municipio',
        'uf',
        'geom',
    ];

    public function coletas(): HasMany
    {
        return $this->hasMany(Coleta::class, 'localizacao_id');
    }

    public function bensMateriais(): HasMany
    {
        return $this->hasMany(BemMaterial::class, 'localizacao_id');
    }
}
