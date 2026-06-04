<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bem_artefato_tipos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bem_material_id')->constrained('bens_materiais')->cascadeOnDelete();
            $table->foreignUuid('artefato_tipo_id')->constrained('artefato_tipos')->cascadeOnDelete();
            $table->text('descricao_nova')->nullable();
            $table->boolean('novo_tipo')->default(false);
            $table->timestamps();

            $table->unique(['bem_material_id', 'artefato_tipo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bem_artefato_tipos');
    }
};
