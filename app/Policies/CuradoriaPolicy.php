<?php

namespace App\Policies;

use App\Enums\PerfilUsuario;
use App\Models\Curadoria;
use App\Models\User;

class CuradoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->perfil, [PerfilUsuario::CURADOR, PerfilUsuario::ADMIN]);
    }

    public function view(User $user, Curadoria $curadoria): bool
    {
        return $user->id === $curadoria->usuario_id
            || in_array($user->perfil, [PerfilUsuario::CURADOR, PerfilUsuario::ADMIN]);
    }

    public function avaliar(User $user): bool
    {
        return in_array($user->perfil, [PerfilUsuario::CURADOR, PerfilUsuario::ADMIN]);
    }
}