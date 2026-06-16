<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Adiciona as novas colunas polimórficas (nullable para permitir migração dos dados)
        Schema::table('curadorias', function (Blueprint $table) {
            if (! Schema::hasColumn('curadorias', 'entidade_tipo')) {
                $table->string('entidade_tipo')->nullable()->after('id');
            }
            if (! Schema::hasColumn('curadorias', 'entidade_id')) {
                $table->uuid('entidade_id')->nullable()->after('entidade_tipo');
            }
        });

        // 2. Migra os dados existentes: toda curadoria atual é do tipo 'coleta'
        if (Schema::hasColumn('curadorias', 'coleta_id')) {
            DB::statement("UPDATE curadorias SET entidade_tipo = 'coleta', entidade_id = coleta_id WHERE coleta_id IS NOT NULL");
        }

        // 3. Torna as colunas obrigatórias após a migração dos dados
        Schema::table('curadorias', function (Blueprint $table) {
            $table->string('entidade_tipo')->nullable(false)->change();
            $table->uuid('entidade_id')->nullable(false)->change();
        });

        // 4. Remove a coluna coleta_id (FK direta que foi substituída pelo polimorfismo)
        if (Schema::hasColumn('curadorias', 'coleta_id')) {
            Schema::table('curadorias', function (Blueprint $table) {
                $table->dropForeign(['coleta_id']);
                $table->dropColumn('coleta_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('curadorias', function (Blueprint $table) {
            $table->uuid('coleta_id')->nullable()->after('id');
            $table->foreign('coleta_id')->references('id')->on('coletas');
        });

        DB::statement("UPDATE curadorias SET coleta_id = entidade_id WHERE entidade_tipo = 'coleta'");

        Schema::table('curadorias', function (Blueprint $table) {
            $table->dropColumn(['entidade_tipo', 'entidade_id']);
        });
    }
};
