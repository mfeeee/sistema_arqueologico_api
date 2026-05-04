<?php

namespace App\Models;

use App\Enums\AcaoResultanteCuradoria;
use App\Enums\StatusCuradoria;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curadoria extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'curadorias';

    protected $fillable = [
        'coleta_id',
        'bem_material_id',
        'usuario_id',
        'status',
        'acao_resultante',
        'data_avaliacao',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data_avaliacao' => 'datetime',
            'status' => StatusCuradoria::class,
            'acao_resultante' => AcaoResultanteCuradoria::class,
        ];
    }

    public function coleta(): BelongsTo
    {
        return $this->belongsTo(Coleta::class);
    }

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
