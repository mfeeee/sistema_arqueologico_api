<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curadorias', function (Blueprint $table) {
            // Índice composto para lookups polimórficos (entidade_tipo + entidade_id)
            $table->index(['entidade_tipo', 'entidade_id'], 'curadorias_entidade_index');

            // Índice em bem_material_id (usado em porBemMaterial e colaboradores)
            $table->index('bem_material_id', 'curadorias_bem_material_id_index');

            // Recria FK de bem_material_id com cascadeOnDelete
            $table->dropForeign(['bem_material_id']);
            $table->foreign('bem_material_id')
                ->references('id')->on('bens_materiais')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('curadorias', function (Blueprint $table) {
            $table->dropIndex('curadorias_entidade_index');
            $table->dropIndex('curadorias_bem_material_id_index');

            $table->dropForeign(['bem_material_id']);
            $table->foreign('bem_material_id')
                ->references('id')->on('bens_materiais');
        });
    }
};
