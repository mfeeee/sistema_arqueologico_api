<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MidiaLink extends Model
{
    use HasUuids;

    protected $table = 'midias_links';

    protected $fillable = [
        'bem_material_id',
        'tipo',
        'url',
        'descricao',
    ];

    public function bemMaterial(): BelongsTo
    {
        return $this->belongsTo(BemMaterial::class, 'bem_material_id');
    }
}
