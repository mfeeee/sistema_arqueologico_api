<?php

namespace App\Models;

use App\Enums\PapelResponsavelBem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BemResponsavel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'bem_responsaveis';

    protected $fillable = [
        'bem_material_id',
        'user_id',
        'papel',
    ];

    protected $casts = [
        'papel' => PapelResponsavelBem::class,
    ];

    public function bemMaterial(): BelongsTo
    {
        return $this->belongsTo(BemMaterial::class, 'bem_material_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
