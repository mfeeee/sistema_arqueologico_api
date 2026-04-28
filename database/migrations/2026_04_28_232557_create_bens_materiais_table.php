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
        Schema::create('bens_materiais', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('coleta_uuid')->nullable()->constrained('coletas');
            $table->text('codigo_iphan')->nullable();
            $table->text('nome_bem');
            $table->text('nomes_populares');
            $table->enum('natureza', ['bemArqueologico', 'bemPaleontologico']);
            $table->enum('tipo', ['acervoOuColecao', 'bemOuConjunto', 'colecao', 'sitio']);
            $table->enum('artefatos', [
                'fainca', 'malacologico', 'semente', 'ossosFaunisticos', 'ceramica', 
                'plastico', 'gres', 'carvao', 'faincaFina', 'madeira', 'porcelana', 
                'textil', 'litico', 'fibraVegetal', 'vitreo', 'borracha', 'sedimento', 
                'ceramicaVidrada', 'metalico', 'ossosHumanos', 'outros'
            ]);
            $table->text('meios_acesso');
            $table->boolean('publicado')->default(false);
            $table->text('uf');
            $table->text('municipio');
            $table->text('cep');
            $table->text('endereco');
            $table->double('latitude');
            $table->double('longitude');
            $table->geometry('geom')->nullable();
            $table->timestamp('deletado_em')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bens_materiais');
    }
};
