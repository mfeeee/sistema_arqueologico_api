<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Para cada coleta sem localizacao_id mas com lat/lng válidos,
        // cria uma Localizacao e faz o link
        DB::statement('
            INSERT INTO localizacoes (id, uf, geom, created_at, updated_at)
            SELECT
                gen_random_uuid(),
                uf,
                ST_SetSRID(ST_MakePoint(longitude, latitude), 4326),
                NOW(),
                NOW()
            FROM coletas
            WHERE localizacao_id IS NULL
            AND latitude <> 0
            AND longitude <> 0
        ');

        // Vincula cada coleta à localizacao recém-criada pelo geom
        DB::statement('
            UPDATE coletas c
            SET localizacao_id = l.id
            FROM localizacoes l
            WHERE c.localizacao_id IS NULL
            AND c.latitude <> 0
            AND c.longitude <> 0
            AND ST_Equals(l.geom, ST_SetSRID(ST_MakePoint(c.longitude, c.latitude), 4326))
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coletas', function (Blueprint $table) {
            //
        });
    }
};
