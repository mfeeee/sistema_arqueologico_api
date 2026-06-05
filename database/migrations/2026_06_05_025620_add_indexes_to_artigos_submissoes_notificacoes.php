<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificacoes', function (Blueprint $table) {
            $table->index('usuario_id', 'notificacoes_usuario_id_index');
        });

        Schema::table('submissoes_artigos', function (Blueprint $table) {
            $table->index('usuario_id', 'submissoes_artigos_usuario_id_index');
            $table->index('bem_material_id', 'submissoes_artigos_bem_material_id_index');
            $table->index('status', 'submissoes_artigos_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('notificacoes', function (Blueprint $table) {
            $table->dropIndex('notificacoes_usuario_id_index');
        });

        Schema::table('submissoes_artigos', function (Blueprint $table) {
            $table->dropIndex('submissoes_artigos_usuario_id_index');
            $table->dropIndex('submissoes_artigos_bem_material_id_index');
            $table->dropIndex('submissoes_artigos_status_index');
        });
    }
};
