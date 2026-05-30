<?php

namespace App\Models;

use Database\Factories\PreferenciaNotificacaoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreferenciaNotificacao extends Model
{
    /** @use HasFactory<PreferenciaNotificacaoFactory> */
    use HasFactory;

    protected $table = 'preferencias_notificacoes';

    protected $fillable = [
        'user_id',
        'coleta',
        'sync',
        'sistema',
        'push',
    ];

    protected function casts(): array
    {
        return [
            'coleta' => 'boolean',
            'sync' => 'boolean',
            'sistema' => 'boolean',
            'push' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function padroes(string $userId): array
    {
        return [
            'user_id' => $userId,
            'coleta' => true,
            'sync' => true,
            'sistema' => true,
            'push' => true,
        ];
    }
}
