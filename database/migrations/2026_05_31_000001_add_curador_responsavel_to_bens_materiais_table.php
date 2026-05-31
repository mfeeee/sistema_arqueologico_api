<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bens_materiais', function (Blueprint $table) {
            $table->foreignUuid('curador_responsavel_id')
                ->nullable()
                ->after('coleta_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bens_materiais', function (Blueprint $table) {
            $table->dropForeign(['curador_responsavel_id']);
            $table->dropColumn('curador_responsavel_id');
        });
    }
};
