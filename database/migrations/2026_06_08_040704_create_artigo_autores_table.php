<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artigo_autores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('artigo_id')->constrained('artigos_cientificos')->cascadeOnDelete();
            $table->string('nome_autor');
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artigo_autores');
    }
};
