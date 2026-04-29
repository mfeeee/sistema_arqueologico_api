<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponsavelSitio extends Model
{
    use HasUuids;

    protected $table = 'responsaveis_sitio';

    protected $fillable = [
        'bem_material_id',
        'contato_nome',
        'contato_email',
        'contato_telefone',
    ];

    public function bemMaterial(): BelongsTo
    {
        return $this->belongsTo(BemMaterial::class, 'bem_material_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
