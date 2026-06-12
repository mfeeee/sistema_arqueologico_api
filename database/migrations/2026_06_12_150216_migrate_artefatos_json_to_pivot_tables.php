<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $mapping = [
            'faianca' => '019eaf4f-7316-7245-935e-da1dc0a2fedf',
            'ceramica' => '019eaf4f-7760-737c-8c00-12636aec7f23',
            'litico' => '019eaf4f-7bb5-721e-8e15-acac3bad7f44',
            'madeira' => '019eaf4f-7ff7-70c5-ad01-e467b00f018a',
            'malacologico' => '019eaf4f-843c-73e5-9a6e-538199fdf817',
            'semente' => '019eaf4f-887e-7341-b35b-a4af2fe916ae',
            'ossosFaunisticos' => '019eaf4f-8cc5-71f4-883f-a7b8812ef47b',
            'plastico' => '019eaf4f-9106-7236-ae25-2d577dc7d066',
            'gres' => '019eaf4f-954b-71fa-a64f-fc10ab6f752e',
            'carvao' => '019eaf4f-998e-70c4-9b35-7a5a3468c2f2',
            'faiancaFina' => '019eaf4f-9dd2-72c0-afb4-38305a350459',
            'porcelana' => '019eaf4f-a215-73b3-b9fe-f7b2dc0c610a',
            'textil' => '019eaf4f-a65f-71f9-bd72-39ddb3ceb700',
            'fibraVegetal' => '019eaf4f-aaa5-732e-9ada-d52c939aad53',
            'vitreo' => '019eaf4f-aeeb-7386-891f-f9265f366480',
            'borracha' => '019eaf4f-b32e-702d-9b19-dcf87e300ec0',
            'sedimento' => '019eaf4f-b773-7237-a45e-ea6eb34295fc',
            'ceramicaVidrada' => '019eaf4f-bbb5-7188-a0ee-1ee49dca40de',
            'metalico' => '019eaf4f-bffa-72d5-8961-04907ee60a1c',
            'ossosHumanos' => '019eaf4f-c456-7169-97ee-ddb908afebbb',
            'outros' => '019eaf4f-c8ad-7239-a26d-d874d333b9c8',
        ];

        // 1. Migrar coletas
        $coletas = DB::table('coletas')->whereNotNull('artefatos')->get();
        foreach ($coletas as $coleta) {
            $artefatos = json_decode($coleta->artefatos, true);
            if (is_array($artefatos)) {
                foreach ($artefatos as $tipo) {
                    if (isset($mapping[$tipo])) {
                        DB::table('coleta_artefato_tipos')->insertOrIgnore([
                            'id' => Str::uuid(),
                            'coleta_id' => $coleta->id,
                            'artefato_tipo_id' => $mapping[$tipo],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // 2. Migrar bens_materiais
        $bens = DB::table('bens_materiais')->whereNotNull('artefatos')->get();
        foreach ($bens as $bem) {
            $artefatos = json_decode($bem->artefatos, true);
            if (is_array($artefatos)) {
                foreach ($artefatos as $tipo) {
                    if (isset($mapping[$tipo])) {
                        DB::table('bem_artefato_tipos')->insertOrIgnore([
                            'id' => Str::uuid(),
                            'bem_material_id' => $bem->id,
                            'artefato_tipo_id' => $mapping[$tipo],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as data stays in pivot tables
    }
};
