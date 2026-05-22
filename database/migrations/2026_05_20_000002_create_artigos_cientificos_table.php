<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artigos_cientificos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('adicionado_por')->constrained('users');
            $table->string('titulo');
            $table->string('doi')->nullable()->unique();
            $table->string('link_acesso')->nullable();
            $table->string('autores');
            $table->integer('ano_publicacao')->nullable();
            $table->string('periodico')->nullable();
            $table->string('idioma')->default('pt');
            $table->text('resumo')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artigos_cientificos');
    }
};
