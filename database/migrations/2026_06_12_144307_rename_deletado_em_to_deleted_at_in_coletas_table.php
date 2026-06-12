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
            $table->renameColumn('deletado_em', 'deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coletas', function (Blueprint $table) {
            $table->renameColumn('deleted_at', 'deletado_em');
        });
    }
};
