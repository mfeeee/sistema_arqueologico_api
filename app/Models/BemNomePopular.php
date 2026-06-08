<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BemNomePopular extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'bem_nomes_populares';

    public $timestamps = false;

    protected $fillable = ['bem_material_id', 'nome'];

    public function bemMaterial(): BelongsTo
    {
        return $this->belongsTo(BemMaterial::class, 'bem_material_id');
    }
}
