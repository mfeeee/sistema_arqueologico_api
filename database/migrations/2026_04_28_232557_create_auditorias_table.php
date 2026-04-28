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
        Schema::create('auditorias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('usuario_id')->constrained('users');
            $table->string('entidade_tipo');
            $table->uuid('entidade_id');
            $table->foreignUuid('curadoria_id')->nullable()->constrained('curadorias');
            $table->enum('operacao', ['alteracao', 'insercao', 'exclusao']);
            $table->enum('meio', ['manual', 'app_sync']);
            $table->timestamp('data_hora')->useCurrent();
            $table->jsonb('valor_anterior')->nullable();
            $table->jsonb('valor_novo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
