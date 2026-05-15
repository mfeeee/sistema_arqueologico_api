<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adiciona coluna geoespacial PostGIS à tabela de coletas.
     *
     * A tabela já possui latitude/longitude como decimais. Esta migration
     * acrescenta a coluna `geom` (geometry Point, SRID 4326) e o índice
     * GiST correspondente, espelhando o padrão adotado em bens_materiais.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE coletas ADD COLUMN IF NOT EXISTS geom geometry(Point, 4326) NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS coletas_geom_gist ON coletas USING GIST (geom)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS coletas_geom_gist');
        DB::statement('ALTER TABLE coletas DROP COLUMN IF EXISTS geom');
    }
};
