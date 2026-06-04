<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('mediable');
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->string('tipo', 20);
            $table->text('descricao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midias');
    }
};
