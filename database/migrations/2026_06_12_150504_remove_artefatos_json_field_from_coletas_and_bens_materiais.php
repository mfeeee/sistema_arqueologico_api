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
        Schema::table('coletas', function (Blueprint $table) {
            $table->dropColumn('artefatos');
        });

        Schema::table('bens_materiais', function (Blueprint $table) {
            $table->dropColumn('artefatos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coletas', function (Blueprint $table) {
            $table->jsonb('artefatos')->default('[]');
        });

        Schema::table('bens_materiais', function (Blueprint $table) {
            $table->jsonb('artefatos')->default('[]');
        });
    }
};
