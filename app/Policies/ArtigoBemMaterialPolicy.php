<?php

namespace App\Policies;

use App\Enums\PerfilUsuario;
use App\Models\ArtigoBemMaterial;
use App\Models\User;

class ArtigoBemMaterialPolicy
{
    public function delete(User $user, ArtigoBemMaterial $artigoBemMaterial): bool
    {
        return in_array($user->perfil, [PerfilUsuario::CURADOR, PerfilUsuario::ADMIN]);
    }
}
