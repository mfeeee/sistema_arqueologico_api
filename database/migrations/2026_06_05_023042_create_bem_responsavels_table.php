<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bem_responsaveis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bem_material_id')->constrained('bens_materiais')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('papel', 30);
            $table->timestamps();

            $table->unique(['bem_material_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bem_responsaveis');
    }
};
