<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('bens_materiais', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('coleta_id')->nullable()->constrained('coletas');
            $table->string('codigo_iphan')->nullable();
            $table->string('nome_bem');
            $table->string('nomes_populares')->nullable();
            $table->string('natureza');
            $table->string('tipo');
            $table->jsonb('artefatos')->default('[]');
            $table->string('meios_acesso');
            $table->boolean('publicado')->default(false);
            $table->char('uf', 2)->nullable();
            $table->string('municipio')->nullable();
            $table->string('cep')->nullable();
            $table->string('endereco')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('geojson')->nullable();
            $table->integer('ano_registro')->nullable();
            $table->text('descricao_atualizacao')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE bens_materiais ADD COLUMN geom geometry(Geometry, 4326) NULL');
        
        DB::statement('CREATE INDEX bens_materiais_geom_gist ON bens_materiais USING GIST (geom)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bens_materiais');
    }
};
