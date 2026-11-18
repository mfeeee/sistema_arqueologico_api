<?php

namespace App\Models;

use App\Enums\ClassificacaoUsuario;
use App\Enums\PerfilUsuario;
use App\Notifications\RecuperacaoSenhaNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'avatar_url',
        'password',
        'perfil',
        'classificacao',
        'ativo',
        'fcm_token',
    ];

    /**
     * Campos que nunca saem da API.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'fcm_token',
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

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new RecuperacaoSenhaNotification($token));
    }

    /**
     * Caminho do avatar no S3, a partir da URL salva.
     */
    public function caminhoDoAvatar(): ?string
    {
        return $this->avatar_url
            ? str_replace(Storage::disk('s3')->url(''), '', $this->avatar_url)
            : null;
    }
}
