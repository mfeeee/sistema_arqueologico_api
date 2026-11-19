<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Garante no banco uma única curadoria pendente por entidade:
     * duas sincronizações simultâneas da mesma coleta não abrem duas revisões.
     */
    public function up(): void
    {
        DB::statement("CREATE UNIQUE INDEX curadorias_uma_pendente_por_entidade ON curadorias (entidade_tipo, entidade_id) WHERE status = 'pendente'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS curadorias_uma_pendente_por_entidade');
    }
};
