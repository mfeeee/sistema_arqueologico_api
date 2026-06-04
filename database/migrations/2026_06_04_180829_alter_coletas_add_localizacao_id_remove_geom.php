<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS coletas_geom_gist');
        DB::statement('ALTER TABLE coletas DROP COLUMN IF EXISTS geom');

        Schema::table('coletas', function (Blueprint $table) {
            $table->foreignUuid('localizacao_id')->nullable()->constrained('localizacoes');
        });
    }

    public function down(): void
    {
        Schema::table('coletas', function (Blueprint $table) {
            $table->dropForeign(['localizacao_id']);
            $table->dropColumn('localizacao_id');
        });

        DB::statement('ALTER TABLE coletas ADD COLUMN geom geometry(Point, 4326) NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS coletas_geom_gist ON coletas USING GIST (geom)');
    }
};
