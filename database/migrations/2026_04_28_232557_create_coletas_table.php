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
        Schema::create('coletas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('usuario_uuid')->constrained('users');
            $table->timestamp('data_coleta');
            $table->double('latitude');
            $table->double('longitude');
            $table->text('nome_bem');
            $table->enum('natureza_bem', ['bemArqueologico', 'bemPaleontologico']);
            $table->enum('tipo_bem', ['acervoOuColecao', 'bemOuConjunto', 'colecao', 'sitio']);
            $table->enum('status_sync', ['pendente', 'sincronizado', 'conflito'])->default('pendente');
            $table->integer('versao')->default(1);
            $table->jsonb('dados_coletados');
            $table->timestamp('deletado_em')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coletas');
    }
};
