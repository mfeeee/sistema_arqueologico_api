<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    protected $appends = ['lat', 'lng'];

    public function getLatAttribute(): ?float
    {
        if (! $this->geom) {
            return null;
        }

        $result = DB::selectOne(
            'SELECT ST_Y(geom::geometry) as lat FROM localizacoes WHERE id = ?',
            [$this->id]
        );

        return $result ? (float) $result->lat : null;
    }

    public function getLngAttribute(): ?float
    {
        if (! $this->geom) {
            return null;
        }

        $result = DB::selectOne(
            'SELECT ST_X(geom::geometry) as lng FROM localizacoes WHERE id = ?',
            [$this->id]
        );

        return $result ? (float) $result->lng : null;
    }

    public function coletas(): HasMany
    {
        return $this->hasMany(Coleta::class, 'localizacao_id');
    }

    public function bensMateriais(): HasMany
    {
        return $this->hasMany(BemMaterial::class, 'localizacao_id');
    }
}
