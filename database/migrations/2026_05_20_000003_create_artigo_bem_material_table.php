<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('artigo_bem_material')) {
            Schema::create('artigo_bem_material', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('artigo_id')->constrained('artigos_cientificos')->cascadeOnDelete();
                $table->foreignUuid('bem_material_id')->constrained('bens_materiais')->cascadeOnDelete();
                $table->string('tipo_mencao');
                $table->text('trecho_relevante')->nullable();
                $table->timestamps();

                $table->unique(['artigo_id', 'bem_material_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('artigo_bem_material');
    }
};
