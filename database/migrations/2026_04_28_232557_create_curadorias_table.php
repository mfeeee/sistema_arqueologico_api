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
        Schema::create('curadorias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('coleta_id')->constrained('coletas');
            $table->foreignUuid('bem_material_id')->nullable()->constrained('bens_materiais');
            $table->foreignUuid('usuario_id')->constrained('users');
            $table->enum('status', ['pendente', 'aprovado', 'rejeitado'])->default('pendente');
            $table->enum('acao_resultante', ['criarSitio', 'atualizarSitio', 'rejeitar'])->nullable();
            $table->timestamp('data_avaliacao')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curadorias');
    }
};
