<?php

namespace App\Policies;

use App\Enums\PerfilUsuario;
use App\Models\User;

class AuditoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->perfil === PerfilUsuario::ADMIN;
    }
}