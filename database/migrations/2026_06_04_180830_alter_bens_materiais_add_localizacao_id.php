<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bens_materiais', function (Blueprint $table) {
            $table->foreignUuid('localizacao_id')->nullable()->constrained('localizacoes');
        });
    }

    public function down(): void
    {
        Schema::table('bens_materiais', function (Blueprint $table) {
            $table->dropForeign(['localizacao_id']);
            $table->dropColumn('localizacao_id');
        });
    }
};
