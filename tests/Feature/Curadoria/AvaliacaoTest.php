<?php

namespace Tests\Feature\Curadoria;

use App\Enums\AcaoResultanteCuradoria;
use App\Enums\PerfilUsuario;
use App\Enums\StatusCuradoria;
use App\Enums\TipoMidia;
use App\Models\ArtefatoTipo;
use App\Models\BemArtefatoTipo;
use App\Models\BemMaterial;
use App\Models\Coleta;
use App\Models\Curadoria;
use App\Models\Midia;
use App\Models\Notificacao;
use App\Models\PreferenciaNotificacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvaliacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $curador;

    private User $coletor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->curador = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::CURADOR]);
        $this->coletor = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::COLETOR]);
    }

    public function test_curador_pode_aprovar_e_criar_sitio(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $response = $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO->value,
                'observacao' => 'Sítio validado em campo.',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('curadorias', [
            'id' => $curadoria->id,
            'status' => StatusCuradoria::APROVADO->value,
        ]);

        $this->assertDatabaseHas('bens_materiais', [
            'coleta_id' => $coleta->id,
        ]);

        // P19: a curadoria não mexe no estado da sincronização.
        $this->assertSame($coleta->status_sincronizacao, $coleta->fresh()->status_sincronizacao);
        $this->assertDatabaseHas('notificacoes', ['usuario_id' => $this->coletor->id, 'titulo' => 'Coleta aprovada']);
    }

    public function test_coletor_nao_pode_avaliar_curadoria(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->coletor)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO->value,
            ])
            ->assertStatus(403);
    }

    // ── atualizarSitio ─────────────────────────────────────────────────────────

    public function test_curador_pode_aprovar_e_atualizar_sitio(): void
    {
        $bem = BemMaterial::factory()->create(['nome_bem' => 'Nome Original']);
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);

        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
            'bem_material_id' => null,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::ATUALIZAR_SITIO->value,
                'bem_material_id' => $bem->id,
                'campos' => ['nome_bem' => 'Nome Atualizado pelo Curador'],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('curadorias', [
            'id' => $curadoria->id,
            'status' => StatusCuradoria::APROVADO->value,
            'acao_resultante' => AcaoResultanteCuradoria::ATUALIZAR_SITIO->value,
        ]);

        $this->assertDatabaseHas('bens_materiais', [
            'id' => $bem->id,
            'nome_bem' => 'Nome Atualizado pelo Curador',
        ]);
    }

    public function test_atualizar_sitio_requer_bem_material_id(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::ATUALIZAR_SITIO->value,
                // sem bem_material_id
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bem_material_id']);
    }

    // ── auditoria integration ──────────────────────────────────────────────────

    public function test_aprovacao_criar_sitio_gera_auditoria_insercao(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO->value,
            ])
            ->assertStatus(200);

        $bem = BemMaterial::where('coleta_id', $coleta->id)->firstOrFail();

        $this->assertDatabaseHas('auditorias', [
            'entidade_tipo' => BemMaterial::class,
            'entidade_id' => $bem->id,
            'curadoria_id' => $curadoria->id,
            'operacao' => 'Inserção',
            'meio' => 'Curadoria',
        ]);
    }

    public function test_aprovacao_atualizar_sitio_gera_auditoria_alteracao(): void
    {
        $bem = BemMaterial::factory()->create(['nome_bem' => 'Nome Original']);
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);

        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
            'bem_material_id' => null,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::ATUALIZAR_SITIO->value,
                'bem_material_id' => $bem->id,
                'campos' => ['descricao_atualizacao' => 'Descrição revisada em campo.'],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('auditorias', [
            'entidade_tipo' => BemMaterial::class,
            'entidade_id' => $bem->id,
            'curadoria_id' => $curadoria->id,
            'operacao' => 'Alteração',
            'meio' => 'Curadoria',
        ]);
    }

    public function test_rejeicao_gera_auditoria_rejeicao(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::REJEITADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::REJEITAR->value,
                'observacao' => 'Localização imprecisa.',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('auditorias', [
            'entidade_tipo' => Curadoria::class,
            'entidade_id' => $curadoria->id,
            'curadoria_id' => $curadoria->id,
            'operacao' => 'Rejeição',
            'meio' => 'Curadoria',
        ]);
    }

    // ── listing / show ─────────────────────────────────────────────────────────

    public function test_curador_pode_listar_curadorias_pendentes(): void
    {
        Curadoria::factory()->count(3)->create([
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->getJson('/api/v1/admin/curadorias')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_curador_pode_ver_curadoria_individual(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
        ]);

        $this->actingAs($this->curador)
            ->getJson("/api/v1/admin/curadorias/{$curadoria->id}")
            ->assertStatus(200)
            ->assertJsonPath('id', $curadoria->id)
            ->assertJsonPath('entidade_tipo', 'coleta');
    }

    public function test_coletor_nao_pode_listar_curadorias(): void
    {
        $this->actingAs($this->coletor)
            ->getJson('/api/v1/admin/curadorias')
            ->assertStatus(403);
    }

    public function test_curador_pode_rejeitar_coleta(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::REJEITADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::REJEITAR->value,
                'observacao' => 'Dados insuficientes.',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('curadorias', [
            'id' => $curadoria->id,
            'status' => StatusCuradoria::REJEITADO->value,
        ]);

        // Nenhum bem material deve ter sido criado
        $this->assertDatabaseMissing('bens_materiais', [
            'coleta_id' => $coleta->id,
        ]);
    }

    // ── Plano de correções, Fase 1 ─────────────────────────────────────────────

    public function test_curadoria_ja_avaliada_nao_pode_ser_avaliada_de_novo(): void
    {
        $curadoria = $this->curadoriaPendente();
        $curadoria->update(['status' => StatusCuradoria::APROVADO]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO->value,
            ])
            ->assertStatus(422);

        $this->assertSame(0, BemMaterial::where('coleta_id', $curadoria->entidade_id)->count());
    }

    public function test_status_e_acao_precisam_combinar(): void
    {
        $curadoria = $this->curadoriaPendente();

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::REJEITAR->value,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['acao_resultante']);
    }

    public function test_avaliacao_nao_aceita_status_pendente(): void
    {
        $curadoria = $this->curadoriaPendente();

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::PENDENTE->value,
                'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO->value,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_aprovar_atualizacao_sem_publicado_mantem_o_sitio_publicado(): void
    {
        $bem = BemMaterial::factory()->publicado()->create();
        $curadoria = $this->curadoriaPendente();

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::ATUALIZAR_SITIO->value,
                'bem_material_id' => $bem->id,
            ])
            ->assertOk();

        $this->assertTrue($bem->fresh()->publicado);
    }

    public function test_criar_sitio_grava_o_curador_responsavel(): void
    {
        $curadoria = $this->curadoriaPendente();

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO->value,
            ])
            ->assertOk();

        $this->assertSame(
            $this->curador->id,
            BemMaterial::where('coleta_id', $curadoria->entidade_id)->value('curador_responsavel_id'),
        );
    }

    public function test_atualizar_sitio_preenche_curador_responsavel_vazio(): void
    {
        $bem = BemMaterial::factory()->create(['curador_responsavel_id' => null]);
        $curadoria = $this->curadoriaPendente();

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::ATUALIZAR_SITIO->value,
                'bem_material_id' => $bem->id,
            ])
            ->assertOk();

        $this->assertSame($this->curador->id, $bem->fresh()->curador_responsavel_id);
    }

    public function test_avaliar_coleta_excluida_responde_422(): void
    {
        $curadoria = $this->curadoriaPendente();
        Coleta::findOrFail($curadoria->entidade_id)->delete();

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::REJEITADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::REJEITAR->value,
            ])
            ->assertStatus(422);
    }

    // ── Plano de correções, P17 e P18 ──────────────────────────────────────────

    public function test_revisao_mostra_artefatos_e_fotos_da_coleta(): void
    {
        $curadoria = $this->curadoriaPendente();
        $coleta = Coleta::findOrFail($curadoria->entidade_id);
        $this->artefato($coleta, 'Cerâmica');
        $this->foto($coleta, 'https://fotos.example/coleta.jpg');

        $this->actingAs($this->curador)
            ->getJson("/api/v1/admin/curadorias/{$curadoria->id}")
            ->assertOk()
            ->assertJsonPath('coleta.artefato_tipos.0.artefato_tipo.nome', 'Cerâmica')
            ->assertJsonPath('coleta.midias.0.url', 'https://fotos.example/coleta.jpg');
    }

    public function test_criar_sitio_leva_artefatos_nomes_e_fotos_da_coleta(): void
    {
        $curadoria = $this->curadoriaPendente();
        $coleta = Coleta::findOrFail($curadoria->entidade_id);
        $coleta->update(['dados_coletados' => ['nomes_populares' => 'Toca da Onça, Pedra Furada']]);
        $this->artefato($coleta, 'Cerâmica');
        $this->artefato($coleta, 'Lítico');
        $this->foto($coleta, 'https://fotos.example/coleta.jpg');

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO->value,
            ])
            ->assertOk();

        $bem = BemMaterial::where('coleta_id', $coleta->id)->firstOrFail();
        $this->assertEqualsCanonicalizing(['Cerâmica', 'Lítico'], $bem->artefatoTipos()->with('artefatoTipo')->get()->pluck('artefatoTipo.nome')->all());
        $this->assertEqualsCanonicalizing(['Toca da Onça', 'Pedra Furada'], $bem->nomesPopulares()->pluck('nome')->all());
        $this->assertSame(['https://fotos.example/coleta.jpg'], $bem->midias()->pluck('storage_path')->all());
    }

    public function test_atualizar_sitio_soma_os_artefatos_da_coleta(): void
    {
        $bem = BemMaterial::factory()->create();
        BemArtefatoTipo::factory()->create([
            'bem_material_id' => $bem->id,
            'artefato_tipo_id' => ArtefatoTipo::factory()->create(['nome' => 'Carvão'])->id,
        ]);
        $curadoria = $this->curadoriaPendente();
        $this->artefato(Coleta::findOrFail($curadoria->entidade_id), 'Cerâmica');

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::ATUALIZAR_SITIO->value,
                'bem_material_id' => $bem->id,
            ])
            ->assertOk();

        $this->assertEqualsCanonicalizing(['Carvão', 'Cerâmica'], $bem->artefatoTipos()->with('artefatoTipo')->get()->pluck('artefatoTipo.nome')->all());
    }

    // ── Plano de correções, P19 ────────────────────────────────────────────────

    public function test_coletor_ve_a_rejeicao_e_o_motivo_e_recebe_notificacao(): void
    {
        $curadoria = $this->curadoriaPendente();

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::REJEITADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::REJEITAR->value,
                'observacao' => 'Foto sem escala',
            ])
            ->assertOk();

        $this->actingAs($this->coletor)
            ->getJson('/api/v1/mobile/coletas')
            ->assertOk()
            ->assertJsonPath('data.0.curadoria.status', 'rejeitado')
            ->assertJsonPath('data.0.curadoria.observacao', 'Foto sem escala');

        $notificacoes = Notificacao::where('usuario_id', $this->coletor->id)->get();
        $this->assertCount(1, $notificacoes);
        $this->assertSame('Coleta rejeitada', $notificacoes->first()->titulo);
        $this->assertStringContainsString('Foto sem escala', $notificacoes->first()->corpo);
    }

    public function test_sem_notificacao_quando_o_coletor_desligou_a_preferencia(): void
    {
        PreferenciaNotificacao::factory()->create(['user_id' => $this->coletor->id, 'coleta' => false]);
        $curadoria = $this->curadoriaPendente();

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO->value,
            ])
            ->assertOk();

        $this->assertSame(0, Notificacao::where('usuario_id', $this->coletor->id)->count());
    }

    public function test_acao_de_artigo_nao_vale_para_coleta(): void
    {
        $curadoria = $this->curadoriaPendente();

        // "aprovar" é de artigo: em coleta caía no ramo de rejeição com status "aprovado".
        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::APROVAR->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('acao_resultante');

        $this->assertSame(StatusCuradoria::PENDENTE, $curadoria->fresh()->status);
    }

    private function artefato(Coleta $coleta, string $nome): void
    {
        $tipo = ArtefatoTipo::firstOrCreate(['nome' => $nome]);
        $coleta->artefatoTipos()->create(['artefato_tipo_id' => $tipo->id]);
    }

    private function foto(Coleta $coleta, string $url): void
    {
        Midia::create([
            'mediable_type' => Coleta::class,
            'mediable_id' => $coleta->id,
            'storage_disk' => 'external',
            'storage_path' => $url,
            'mime_type' => 'image/jpeg',
            'tipo' => TipoMidia::IMAGEM,
        ]);
    }

    private function curadoriaPendente(): Curadoria
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);

        return Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'bem_material_id' => null,
            'usuario_id' => $this->coletor->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);
    }
}
