<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\PerfilUsuario;
use App\Enums\ClassificacaoUsuario;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'perfil',
        'classificacao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
            'perfil' => PerfilUsuario::class,
            'classificacao' => ClassificacaoUsuario::class,
        ];
    }

    public function coletas(): HasMany
    {
        return $this->hasMany(Coleta::class, 'usuario_id');
    }

    public function curadorias(): HasMany
    {
        return $this->hasMany(Curadoria::class, 'usuario_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }
}