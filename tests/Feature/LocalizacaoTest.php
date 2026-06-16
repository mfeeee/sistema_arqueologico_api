<?php

namespace Tests\Feature;

use App\Http\Resources\LocalizacaoResource;
use App\Models\Localizacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LocalizacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_localizacao_accessors_return_correct_coordinates(): void
    {
        $lat = -3.9264;
        $lng = -41.4683;

        /** @var Localizacao $localizacao */
        $localizacao = Localizacao::factory()->create([
            'uf' => 'PI',
        ]);

        // Use the model instance to update geom instead of factory create with same ID
        DB::statement(
            'UPDATE localizacoes SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$lng, $lat, $localizacao->id]
        );

        // Reload to ensure we get the geom from DB
        $localizacao->refresh();

        $this->assertEquals($lat, $localizacao->lat);
        $this->assertEquals($lng, $localizacao->lng);
    }

    public function test_localizacao_resource_returns_correct_json_structure(): void
    {
        $lat = -3.9264;
        $lng = -41.4683;

        /** @var Localizacao $localizacao */
        $localizacao = Localizacao::factory()->create([
            'cep' => '64000-000',
            'logradouro' => 'Rua Teste',
            'municipio' => 'Teresina',
            'uf' => 'PI',
        ]);

        DB::statement(
            'UPDATE localizacoes SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$lng, $lat, $localizacao->id]
        );

        $localizacao->refresh();

        $resource = new LocalizacaoResource($localizacao);
        $response = $resource->response()->getData(true);

        $this->assertEquals([
            'id' => $localizacao->id,
            'cep' => '64000-000',
            'logradouro' => 'Rua Teste',
            'municipio' => 'Teresina',
            'uf' => 'PI',
            'lat' => $lat,
            'lng' => $lng,
        ], $response['data']);
    }

    public function test_localizacao_accessors_return_null_when_geom_is_null(): void
    {
        /** @var Localizacao $localizacao */
        $localizacao = Localizacao::factory()->create(['geom' => null]);

        $this->assertNull($localizacao->lat);
        $this->assertNull($localizacao->lng);
    }
}
