<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artigos_cientificos', function (Blueprint $table) {
            $table->dropColumn('autores');
        });

        Schema::table('submissoes_artigos', function (Blueprint $table) {
            $table->dropColumn('autores');
        });
    }

    public function down(): void
    {
        Schema::table('artigos_cientificos', function (Blueprint $table) {
            $table->string('autores')->after('link_acesso');
        });

        Schema::table('submissoes_artigos', function (Blueprint $table) {
            $table->string('autores')->nullable()->after('titulo');
        });
    }
};
