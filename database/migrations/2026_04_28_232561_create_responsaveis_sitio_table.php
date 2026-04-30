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
        Schema::create('responsaveis_sitio', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bem_material_id')->constrained('bens_materiais')->onDelete('cascade');
            $table->string('contato_nome');
            $table->string('contato_email');
            $table->string('contato_telefone', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('responsaveis_sitio');
    }
};
