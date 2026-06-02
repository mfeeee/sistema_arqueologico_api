<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE bens_materiais
            SET artefatos = REPLACE(artefatos::text, '\"ossosHumanosmanos\"', '\"ossosHumanos\"')::jsonb
            WHERE artefatos::text LIKE '%ossosHumanosmanos%'
        ");

        DB::statement("
            UPDATE coletas
            SET artefatos = REPLACE(artefatos::text, '\"ossosHumanosmanos\"', '\"ossosHumanos\"')::jsonb
            WHERE artefatos::text LIKE '%ossosHumanosmanos%'
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE bens_materiais
            SET artefatos = REPLACE(artefatos::text, '\"ossosHumanos\"', '\"ossosHumanosmanos\"')::jsonb
            WHERE artefatos::text LIKE '%ossosHumanos%'
        ");

        DB::statement("
            UPDATE coletas
            SET artefatos = REPLACE(artefatos::text, '\"ossosHumanos\"', '\"ossosHumanosmanos\"')::jsonb
            WHERE artefatos::text LIKE '%ossosHumanos%'
        ");
    }
};
