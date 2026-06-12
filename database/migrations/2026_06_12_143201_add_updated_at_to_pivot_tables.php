<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bem_nomes_populares', function (Blueprint $table) {
            if (! Schema::hasColumn('bem_nomes_populares', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        Schema::table('artigo_autores', function (Blueprint $table) {
            if (! Schema::hasColumn('artigo_autores', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        Schema::table('submissao_autores', function (Blueprint $table) {
            if (! Schema::hasColumn('submissao_autores', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bem_nomes_populares', function (Blueprint $table) {
            if (Schema::hasColumn('bem_nomes_populares', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });

        Schema::table('artigo_autores', function (Blueprint $table) {
            if (Schema::hasColumn('artigo_autores', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });

        Schema::table('submissao_autores', function (Blueprint $table) {
            if (Schema::hasColumn('submissao_autores', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
