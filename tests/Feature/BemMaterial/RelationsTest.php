<?php

namespace Tests\Feature\BemMaterial;

use App\Enums\PapelResponsavelBem;
use App\Enums\TipoMidia;
use App\Models\BemArtefatoTipo;
use App\Models\BemMaterial;
use App\Models\BemResponsavel;
use App\Models\Localizacao;
use App\Models\Midia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationsTest extends TestCase
{
    use RefreshDatabase;

    // ── localizacao_id ─────────────────────────────────────────────────────────

    public function test_bem_sem_localizacao_tem_localizacao_id_nulo(): void
    {
        $bem = BemMaterial::factory()->create();

        $this->assertNull($bem->localizacao_id);
        $this->assertNull($bem->localizacao);
    }

    public function test_bem_com_localizacao_persiste_e_carrega_relacionamento(): void
    {
        $localizacao = Localizacao::factory()->create([
            'municipio' => 'Piracuruca',
            'uf' => 'PI',
        ]);

        $bem = BemMaterial::factory()->create(['localizacao_id' => $localizacao->id]);

        $this->assertDatabaseHas('bens_materiais', [
            'id' => $bem->id,
            'localizacao_id' => $localizacao->id,
        ]);

        $this->assertTrue($bem->localizacao->is($localizacao));
        $this->assertSame('Piracuruca', $bem->localizacao->municipio);
    }

    public function test_factory_state_com_localizacao_cria_localizacao_associada(): void
    {
        $bem = BemMaterial::factory()->comLocalizacao()->create();

        $this->assertNotNull($bem->localizacao_id);
        $this->assertDatabaseHas('localizacoes', ['id' => $bem->localizacao_id]);
        $this->assertNotNull($bem->localizacao);
    }

    public function test_multiplos_bens_podem_compartilhar_a_mesma_localizacao(): void
    {
        $localizacao = Localizacao::factory()->create();
        BemMaterial::factory()->count(3)->create(['localizacao_id' => $localizacao->id]);

        $this->assertDatabaseCount('bens_materiais', 3);
        $this->assertCount(3, BemMaterial::where('localizacao_id', $localizacao->id)->get());
    }

    // ── bem_artefato_tipos ─────────────────────────────────────────────────────

    public function test_bem_com_artefato_tipos_vinculados_retorna_contagem_correta(): void
    {
        $bem = BemMaterial::factory()->create();
        BemArtefatoTipo::factory()->count(3)->create(['bem_material_id' => $bem->id]);

        $this->assertCount(3, $bem->fresh()->artefatoTipos);
    }

    public function test_bem_artefato_tipo_com_novo_tipo_registrado(): void
    {
        $bem = BemMaterial::factory()->create();
        BemArtefatoTipo::factory()
            ->novoTipo('Fragmento de obsidiana translúcida')
            ->create(['bem_material_id' => $bem->id]);

        $this->assertDatabaseHas('bem_artefato_tipos', [
            'bem_material_id' => $bem->id,
            'novo_tipo' => true,
            'descricao_nova' => 'Fragmento de obsidiana translúcida',
        ]);
    }

    // ── bem_responsaveis ───────────────────────────────────────────────────────

    public function test_bem_com_responsaveis_retorna_contagem_correta(): void
    {
        $bem = BemMaterial::factory()->create();
        BemResponsavel::factory()->count(2)->create(['bem_material_id' => $bem->id]);

        $this->assertCount(2, $bem->fresh()->responsaveis);
    }

    public function test_bem_responsavel_com_papel_pesquisador(): void
    {
        $bem = BemMaterial::factory()->create();
        $usuario = User::factory()->create();

        BemResponsavel::factory()->pesquisador()->create([
            'bem_material_id' => $bem->id,
            'user_id' => $usuario->id,
        ]);

        $responsavel = $bem->responsaveis()->first();

        $this->assertSame(PapelResponsavelBem::PESQUISADOR, $responsavel->papel);
        $this->assertTrue($responsavel->usuario->is($usuario));
    }

    // ── midias ─────────────────────────────────────────────────────────────────

    public function test_bem_com_midias_retorna_contagem_correta(): void
    {
        $bem = BemMaterial::factory()->create();
        Midia::factory()->count(4)->create([
            'mediable_type' => BemMaterial::class,
            'mediable_id' => $bem->id,
        ]);

        $this->assertCount(4, $bem->fresh()->midias);
    }

    public function test_bem_midia_external_disk_e_url_como_path(): void
    {
        $bem = BemMaterial::factory()->create();
        $midia = Midia::factory()->create([
            'mediable_type' => BemMaterial::class,
            'mediable_id' => $bem->id,
            'storage_disk' => 'external',
            'storage_path' => 'https://arqueologia.example.com/fotos/teste.jpg',
            'tipo' => TipoMidia::IMAGEM,
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertDatabaseHas('midias', [
            'id' => $midia->id,
            'storage_disk' => 'external',
            'mime_type' => 'image/jpeg',
        ]);
    }

    // ── integração ─────────────────────────────────────────────────────────────

    public function test_bem_agrega_todas_as_relacoes_simultaneamente(): void
    {
        $localizacao = Localizacao::factory()->create(['uf' => 'PI']);
        $bem = BemMaterial::factory()->create(['localizacao_id' => $localizacao->id]);

        BemArtefatoTipo::factory()->count(2)->create(['bem_material_id' => $bem->id]);
        BemResponsavel::factory()->count(1)->create(['bem_material_id' => $bem->id]);
        Midia::factory()->count(3)->create([
            'mediable_type' => BemMaterial::class,
            'mediable_id' => $bem->id,
        ]);

        $bem = $bem->fresh()->load(['localizacao', 'artefatoTipos', 'responsaveis', 'midias']);

        $this->assertNotNull($bem->localizacao);
        $this->assertSame('PI', $bem->localizacao->uf);
        $this->assertCount(2, $bem->artefatoTipos);
        $this->assertCount(1, $bem->responsaveis);
        $this->assertCount(3, $bem->midias);
    }
}
