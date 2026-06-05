<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Converte entidade_id de varchar(36) para uuid (todos os valores já são UUIDs válidos)
        DB::statement('ALTER TABLE auditorias ALTER COLUMN entidade_id TYPE uuid USING entidade_id::uuid');

        Schema::table('auditorias', function (Blueprint $table) {
            $table->index(['entidade_tipo', 'entidade_id'], 'auditorias_entidade_index');
            $table->index('curadoria_id', 'auditorias_curadoria_id_index');
            $table->index('usuario_id', 'auditorias_usuario_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropIndex('auditorias_entidade_index');
            $table->dropIndex('auditorias_curadoria_id_index');
            $table->dropIndex('auditorias_usuario_id_index');
        });

        DB::statement('ALTER TABLE auditorias ALTER COLUMN entidade_id TYPE character varying(36) USING entidade_id::text');
    }
};
