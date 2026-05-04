<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usuarios = [
            [
                'name'          => 'Admin Teste',
                'email'         => 'admin@arqueologia.test',
                'password'      => Hash::make('password'),
                'perfil'        => 'admin',
                'classificacao' => 'arqueologo',
                'ativo'         => true,
            ],
            [
                'name'          => 'Curador Teste',
                'email'         => 'curador@arqueologia.test',
                'password'      => Hash::make('password'),
                'perfil'        => 'curador',
                'classificacao' => 'arqueologo',
                'ativo'         => true,
            ],
            [
                'name'          => 'Coletor Teste',
                'email'         => 'coletor@arqueologia.test',
                'password'      => Hash::make('password'),
                'perfil'        => 'coletor',
                'classificacao' => 'estudante',
                'ativo'         => true,
            ],
        ];

        foreach ($usuarios as $dados) {
            User::updateOrCreate(
                ['email' => $dados['email']],
                $dados
            );
        }
    }
}
