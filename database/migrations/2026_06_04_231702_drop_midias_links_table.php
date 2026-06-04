<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('midias_links');
    }

    public function down(): void
    {
        Schema::create('midias_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bem_material_id')->constrained('bens_materiais')->onDelete('cascade');
            $table->string('tipo', 20);
            $table->text('url');
            $table->text('descricao')->nullable();
            $table->timestamps();
        });
    }
};
