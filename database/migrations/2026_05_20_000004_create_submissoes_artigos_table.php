<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('submissoes_artigos')) {
            Schema::create('submissoes_artigos', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('usuario_id')->constrained('users');
                $table->foreignUuid('bem_material_id')->constrained('bens_materiais');
                // Preenchido automaticamente quando o DOI já existe no sistema
                $table->foreignUuid('artigo_id')->nullable()->constrained('artigos_cientificos')->nullOnDelete();
                $table->string('doi')->nullable();
                // Obrigatórios apenas quando artigo_id for nulo (artigo novo)
                $table->string('titulo')->nullable();
                $table->string('autores')->nullable();
                $table->integer('ano_publicacao')->nullable();
                $table->string('periodico')->nullable();
                $table->string('idioma')->default('pt');
                $table->text('resumo')->nullable();
                $table->string('link_acesso')->nullable();
                $table->string('tipo_mencao');
                $table->text('trecho_relevante')->nullable();
                $table->string('status')->default('pendente');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('submissoes_artigos');
    }
};
