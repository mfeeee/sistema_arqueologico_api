<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localizacoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('cep')->nullable();
            $table->string('logradouro')->nullable();
            $table->string('municipio')->nullable();
            $table->char('uf', 2)->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE localizacoes ADD COLUMN geom geometry(Point, 4326) NULL');
        DB::statement('CREATE INDEX localizacoes_geom_gist ON localizacoes USING GIST (geom)');
    }

    public function down(): void
    {
        Schema::dropIfExists('localizacoes');
    }
};
