<?php

namespace App\Policies;

use App\Enums\PerfilUsuario;
use App\Models\Auditoria;
use App\Models\User;

class AuditoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->perfil, [PerfilUsuario::ADMIN, PerfilUsuario::CURADOR]);
    }

    public function view(User $user, Auditoria $auditoria): bool
    {
        return in_array($user->perfil, [PerfilUsuario::ADMIN, PerfilUsuario::CURADOR]);
    }
}
