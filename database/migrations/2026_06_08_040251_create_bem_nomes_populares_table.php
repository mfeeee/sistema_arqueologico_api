<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bem_nomes_populares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bem_material_id')->constrained('bens_materiais')->cascadeOnDelete();
            $table->string('nome');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bem_nomes_populares');
    }
};
