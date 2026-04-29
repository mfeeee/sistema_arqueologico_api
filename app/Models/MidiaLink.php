<?php

namespace App\Models;

use App\Enums\TipoMidia;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MidiaLink extends Model
{
    use HasUuids;

    protected $table = 'midias_links';

    protected $fillable = [
        'bem_material_id',
        'tipo' => TipoMidia::class,
        'url',
        'descricao',
    ];

    public function bemMaterial(): BelongsTo
    {
        return $this->belongsTo(BemMaterial::class, 'bem_material_id');
    }
}
