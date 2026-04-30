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
            $table->string('entidade_tipo', 60);
            $table->string('entidade_id', 36);
            $table->foreignUuid('curadoria_id')->nullable()->constrained('curadorias');
            $table->string('operacao', 30);
            $table->string('meio', 30);
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
