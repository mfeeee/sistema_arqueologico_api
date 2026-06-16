<?php

namespace Tests\Feature\Coleta;

use App\Enums\ArtefatoBem;
use App\Enums\NaturezaBem;
use App\Enums\PerfilUsuario;
use App\Models\Coleta;
use App\Models\ColetaArtefatoTipo;
use App\Models\Localizacao;
use App\Models\User;
use Database\Seeders\ArtefatoTipoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ColetaCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $coletor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ArtefatoTipoSeeder::class);

        $this->coletor = User::factory()->create([
            'ativo' => true,
            'perfil' => PerfilUsuario::COLETOR,
        ]);
    }

    public function test_coletor_pode_criar_coleta(): void
    {
        $response = $this->actingAs($this->coletor)
            ->postJson('/api/v1/mobile/coletas', [
                'data_coleta' => '2026-05-01 10:00:00',
                'nome_bem' => 'Sítio Teste',
                'latitude' => -5.0892,
                'longitude' => -42.8016,
                'natureza' => NaturezaBem::ARQUEOLOGICO->value,
                'versao' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['nome_bem' => 'Sítio Teste']);

        $this->assertDatabaseHas('coletas', [
            'nome_bem' => 'Sítio Teste',
            'usuario_id' => $this->coletor->id,
        ]);
    }

    public function test_coletor_nao_pode_ver_coleta_de_outro_usuario(): void
    {
        $outro = User::factory()->create(['ativo' => true]);
        $coleta = Coleta::factory()->create(['usuario_id' => $outro->id]);

        $this->actingAs($this->coletor)
            ->getJson("/api/v1/mobile/coletas/{$coleta->id}")
            ->assertStatus(403);
    }

    public function test_coletor_pode_ver_proprias_coletas(): void
    {
        Coleta::factory()->count(3)->create(['usuario_id' => $this->coletor->id]);

        $this->actingAs($this->coletor)
            ->getJson('/api/v1/mobile/coletas')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    // ── localizacao_id ─────────────────────────────────────────────────────────

    public function test_coleta_criada_via_api_tem_localizacao_id_automaticamente(): void
    {
        $response = $this->actingAs($this->coletor)
            ->postJson('/api/v1/mobile/coletas', [
                'data_coleta' => '2026-05-01 10:00:00',
                'nome_bem' => 'Sítio Com Localização Automática',
                'latitude' => -8.4823,
                'longitude' => -42.6065,
                'natureza' => NaturezaBem::ARQUEOLOGICO->value,
                'versao' => 1,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('coletas', [
            'nome_bem' => 'Sítio Com Localização Automática',
        ]);

        $coleta = Coleta::where('nome_bem', 'Sítio Com Localização Automática')->first();
        $this->assertNotNull($coleta->localizacao_id);
    }

    public function test_coleta_com_localizacao_persiste_e_carrega_relacao(): void
    {
        $localizacao = Localizacao::factory()->create([
            'municipio' => 'São Raimundo Nonato',
            'uf' => 'PI',
        ]);

        $coleta = Coleta::factory()->create([
            'usuario_id' => $this->coletor->id,
            'localizacao_id' => $localizacao->id,
        ]);

        $this->assertDatabaseHas('coletas', [
            'id' => $coleta->id,
            'localizacao_id' => $localizacao->id,
        ]);

        $this->assertTrue($coleta->localizacao->is($localizacao));
        $this->assertSame('São Raimundo Nonato', $coleta->localizacao->municipio);
    }

    public function test_factory_state_com_localizacao_cria_localizacao_associada(): void
    {
        $coleta = Coleta::factory()->comLocalizacao()->create([
            'usuario_id' => $this->coletor->id,
        ]);

        $this->assertNotNull($coleta->localizacao_id);
        $this->assertNotNull($coleta->localizacao);
        $this->assertDatabaseHas('localizacoes', ['id' => $coleta->localizacao_id]);
    }

    // ── coleta_artefato_tipos ──────────────────────────────────────────────────

    public function test_payload_com_artefatos_salva_no_campo_json(): void
    {
        $artefatos = [ArtefatoBem::LITICO->value, ArtefatoBem::CERAMICA->value];

        $this->actingAs($this->coletor)
            ->postJson('/api/v1/mobile/coletas', [
                'data_coleta' => '2026-05-01 10:00:00',
                'nome_bem' => 'Sítio com Artefatos',
                'latitude' => -8.4823,
                'longitude' => -42.6065,
                'natureza' => NaturezaBem::ARQUEOLOGICO->value,
                'artefatos' => $artefatos,
                'versao' => 1,
            ])
            ->assertStatus(201);

        $coleta = Coleta::where('nome_bem', 'Sítio com Artefatos')->firstOrFail();

        $this->assertCount(count($artefatos), $coleta->artefatoTipos);
    }

    public function test_coleta_com_artefato_tipos_vinculados_retorna_contagem_correta(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        ColetaArtefatoTipo::factory()->count(3)->create(['coleta_id' => $coleta->id]);

        $this->assertCount(3, $coleta->fresh()->artefatoTipos);
    }

    public function test_artefatos_invalidos_sao_rejeitados_pela_validacao(): void
    {
        $this->actingAs($this->coletor)
            ->postJson('/api/v1/mobile/coletas', [
                'data_coleta' => '2026-05-01 10:00:00',
                'nome_bem' => 'Sítio Inválido',
                'latitude' => -8.4823,
                'longitude' => -42.6065,
                'artefatos' => ['invalido', 'tambem_invalido'],
                'versao' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['artefatos.0', 'artefatos.1']);
    }

    public function test_coleta_show_returns_localizacao_com_coordenadas(): void
    {
        $lat = -3.9264;
        $lng = -41.4683;

        /** @var Localizacao $localizacao */
        $localizacao = Localizacao::factory()->create([
            'municipio' => 'Piracuruca',
            'uf' => 'PI',
        ]);

        DB::statement(
            'UPDATE localizacoes SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$lng, $lat, $localizacao->id]
        );

        $coleta = Coleta::factory()->create([
            'usuario_id' => $this->coletor->id,
            'localizacao_id' => $localizacao->id,
        ]);

        $response = $this->actingAs($this->coletor)
            ->getJson("/api/v1/mobile/coletas/{$coleta->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.localizacao.lat', $lat)
            ->assertJsonPath('data.localizacao.lng', $lng)
            ->assertJsonPath('data.localizacao.municipio', 'Piracuruca');
    }

    public function test_soft_delete_nao_retorna_coleta_deletada(): void
    {
        $coleta = Coleta::factory()->create([
            'usuario_id' => $this->coletor->id,
        ]);

        $coleta->delete();

        $this->actingAs($this->coletor)
            ->getJson('/api/v1/mobile/coletas')
            ->assertJsonMissing(['id' => $coleta->id]);

        $this->assertSoftDeleted('coletas', ['id' => $coleta->id]);
    }
}
