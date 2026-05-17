<?php

namespace App\Policies;

use App\Enums\PerfilUsuario;
use App\Models\BemMaterial;
use App\Models\User;

class BemMaterialPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, BemMaterial $bem): bool
    {
        return $bem->publicado
            || ($user && in_array($user->perfil, [PerfilUsuario::COLETOR, PerfilUsuario::CURADOR, PerfilUsuario::ADMIN]));
    }

    public function create(User $user): bool
    {
        return in_array($user->perfil, [PerfilUsuario::CURADOR, PerfilUsuario::ADMIN]);
    }

    public function update(User $user, BemMaterial $bem): bool
    {
        return in_array($user->perfil, [PerfilUsuario::CURADOR, PerfilUsuario::ADMIN]);
    }

    public function delete(User $user): bool
    {
        return $user->perfil === PerfilUsuario::ADMIN;
    }
}
