<?php

namespace App\Models;

use App\Enums\TipoNotificacao;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacao extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notificacoes';

    protected $fillable = [
        'usuario_id',
        'titulo',
        'corpo',
        'tipo',
        'lida',
        'lida_em',
    ];

    protected $casts = [
        'tipo' => TipoNotificacao::class,
        'lida' => 'boolean',
        'lida_em' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function scopeDoUsuario($query, string $usuarioId): mixed
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopePorTipo($query, string $tipo): mixed
    {
        return $query->where('tipo', $tipo);
    }
}
