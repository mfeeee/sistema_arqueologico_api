<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\PerfilUsuario;
use App\Enums\ClassificacaoUsuario;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Notifiable, HasFactory, HasApiTokens;

    public $incrementing = false;
    protected $keyType = 'string';

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

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->id)) {
                $user->id = (string) Str::uuid();
            }
        });
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