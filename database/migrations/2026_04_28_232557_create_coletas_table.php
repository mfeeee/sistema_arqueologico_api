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
            $table->foreignUuid('usuario_id')->constrained('users');
            $table->timestamp('data_coleta');
            $table->double('latitude');
            $table->double('longitude');
            $table->string('nome_bem');
            $table->string('natureza_bem', 30);
            $table->string('tipo_bem', 30);
            $table->string('status_sync', 20)->default('pendente');
            $table->char('uf', 2)->nullable();
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
