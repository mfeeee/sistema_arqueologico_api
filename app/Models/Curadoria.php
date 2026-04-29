<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curadoria extends Model
{
    use HasUuids;

    protected $table = 'curadorias';

    protected $fillable = [
        'bem_material_id',
        'curador_id',
        'status',
        'observacao',
        'data_revisao',
    ];

    protected $casts = [
        'data_revisao' => 'datetime',
    ];

    public function bemMaterial(): BelongsTo
    {
        return $this->belongsTo(BemMaterial::class, 'bem_material_id');
    }

    public function curador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'curador_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'curadoria_id');
    }
}
