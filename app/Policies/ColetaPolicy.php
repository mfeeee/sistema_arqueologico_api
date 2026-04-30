<?php

namespace App\Policies;

use App\Enums\PerfilUsuario;
use App\Models\Coleta;
use App\Models\User;

class ColetaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Coleta $coleta): bool
    {
        return $user->id == $coleta->usuario_id || in_array($user->perfil, [PerfilUsuario::CURADOR, PerfilUsuario::ADMIN]);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Coleta $coleta): bool
    {
        return $user->id === $coleta->usuario_id || $user->perfil === PerfilUsuario::ADMIN;
    }

    public function delete(User $user, Coleta $coleta): bool
    {
        return $user->id === $coleta->usuario_id || $user->perfil === PerfilUsuario::ADMIN;
    }
}