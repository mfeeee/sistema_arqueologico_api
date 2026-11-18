<?php

namespace Tests\Feature\Coleta;

use App\Actions\RegistrarColetaAction;
use App\Enums\ArtefatoBem;
use App\Enums\NaturezaBem;
use App\Enums\PerfilUsuario;
use App\Enums\StatusColeta;
use App\Enums\StatusCuradoria;
use App\Jobs\ProcessarSincronizacao;
use App\Models\ArtefatoTipo;
use App\Models\Coleta;
use App\Models\Curadoria;
use App\Models\User;
use Database\Seeders\ArtefatoTipoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SincronizacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_enfileira_job_e_retorna_202(): void
    {
        Queue::fake();

        $user = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::COLETOR]);

        $payload = [
            'coletas' => [
                [
                    'id' => fake()->uuid(),
                    'data_coleta' => '2026-05-01 08:00:00',
                    'nome_bem' => 'Sítio Offline A',
                    'latitude' => -5.09,
                    'longitude' => -42.80,
                    'natureza' => NaturezaBem::ARQUEOLOGICO->value,
                    'versao' => 1,
                ],
            ],
        ];

        $this->actingAs($user)
            ->postJson('/api/v1/mobile/sync', $payload)
            ->assertStatus(202)
            ->assertJsonFragment(['total_itens' => 1]);

        Queue::assertPushed(ProcessarSincronizacao::class);
    }

    public function test_sync_falha_sem_autenticacao(): void
    {
        $this->postJson('/api/v1/mobile/sync', ['coletas' => []])
            ->assertStatus(401);
    }

    public function test_sync_rejeita_payload_invalido(): void
    {
        $user = User::factory()->create(['ativo' => true]);

        // latitude fora do range
        $this->actingAs($user)
            ->postJson('/api/v1/mobile/sync', [
                'coletas' => [[
                    'id' => fake()->uuid(),
                    'data_coleta' => '2026-05-01',
                    'nome_bem' => 'Sítio',
                    'latitude' => 999,
                    'longitude' => -42.80,
                    'versao' => 1,
                ]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['coletas.0.latitude']);
    }

    // ── Plano de correções, P15 (o job roda na hora: QUEUE_CONNECTION=sync) ───

    public function test_sync_grava_artefatos_fotos_e_localizacao_sem_abrir_curadoria(): void
    {
        Storage::fake('s3');
        $this->seed(ArtefatoTipoSeeder::class);
        $usuario = User::factory()->create();
        $ceramica = ArtefatoTipo::where('nome', ArtefatoBem::CERAMICA->label())->firstOrFail();
        $outros = ArtefatoTipo::where('nome', ArtefatoBem::OUTROS->label())->firstOrFail();
        $id = (string) Str::uuid();

        $this->actingAs($usuario)->postJson('/api/v1/mobile/sync', ['coletas' => [$this->coleta($id, [
            'localizacao' => ['municipio' => 'Teresina', 'cep' => '64000-000'],
            'artefato_tipos' => [
                ['id' => $ceramica->id, 'nome' => $ceramica->nome, 'novo_tipo' => false],
                ['id' => (string) Str::uuid(), 'nome' => 'Contas de vidro', 'novo_tipo' => true, 'descricao_nova' => 'Contas de vidro'],
            ],
            'dados_coletados' => ['foto_urls' => [
                Storage::disk('s3')->url('midias/foto1.jpg'),
                'https://outro-site.example/foto.jpg',
            ]],
        ])]])->assertAccepted();

        $coleta = Coleta::findOrFail($id);
        $this->assertEqualsCanonicalizing([$ceramica->id, $outros->id], $coleta->artefatoTipos()->pluck('artefato_tipo_id')->all());
        $this->assertSame('Contas de vidro', $coleta->artefatoTipos()->where('artefato_tipo_id', $outros->id)->value('descricao_nova'));
        $this->assertSame(['midias/foto1.jpg'], $coleta->midias()->pluck('storage_path')->all());
        $this->assertSame('Teresina', $coleta->localizacao->municipio);
        // Sincronizar só guarda: a curadoria começa quando o coletor envia.
        $this->assertDatabaseMissing('curadorias', ['entidade_tipo' => 'coleta', 'entidade_id' => $id]);
    }

    public function test_sync_nao_altera_coleta_de_outro_usuario(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create(['perfil' => PerfilUsuario::COLETOR]);
        $coleta = Coleta::factory()->create(['usuario_id' => $dono->id, 'versao' => 1, 'nome_bem' => 'Original']);

        $this->actingAs($outro)
            ->postJson('/api/v1/mobile/sync', ['coletas' => [$this->coleta($coleta->id, ['nome_bem' => 'Trocado', 'versao' => 9])]])
            ->assertForbidden();

        $coleta->refresh();
        $this->assertSame('Original', $coleta->nome_bem);
        $this->assertSame($dono->id, $coleta->usuario_id);
    }

    public function test_job_nao_altera_nem_marca_conflito_em_coleta_de_outro_usuario(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create(['perfil' => PerfilUsuario::COLETOR]);
        $coleta = Coleta::factory()->create(['usuario_id' => $dono->id, 'nome_bem' => 'Original', 'status_sincronizacao' => StatusColeta::SINCRONIZADO]);

        (new ProcessarSincronizacao($outro->id, [$this->coleta($coleta->id, ['nome_bem' => 'Trocado', 'versao' => 9])]))
            ->handle(app(RegistrarColetaAction::class));

        $coleta->refresh();
        $this->assertSame('Original', $coleta->nome_bem);
        $this->assertSame(StatusColeta::SINCRONIZADO, $coleta->status_sincronizacao);
    }

    public function test_sync_com_versao_antiga_responde_409_e_nao_altera(): void
    {
        $usuario = User::factory()->create();
        $coleta = Coleta::factory()->create(['usuario_id' => $usuario->id, 'versao' => 5, 'nome_bem' => 'Versão 5']);

        $this->actingAs($usuario)
            ->postJson('/api/v1/mobile/sync', ['coletas' => [$this->coleta($coleta->id, ['nome_bem' => 'Versão 2', 'versao' => 2])]])
            ->assertStatus(409)
            ->assertJsonPath('conflitos.0', $coleta->id);

        $this->assertSame('Versão 5', $coleta->fresh()->nome_bem);
    }

    public function test_coleta_sincronizada_pode_ser_editada_antes_de_ir_para_curadoria(): void
    {
        $usuario = User::factory()->create();
        $id = (string) Str::uuid();

        $this->actingAs($usuario)->postJson('/api/v1/mobile/sync', ['coletas' => [$this->coleta($id)]])->assertAccepted();
        $this->actingAs($usuario)
            ->postJson('/api/v1/mobile/sync', ['coletas' => [$this->coleta($id, ['nome_bem' => 'Editado'])]])
            ->assertAccepted();

        $this->assertSame('Editado', Coleta::findOrFail($id)->nome_bem);
        $this->assertSame(0, Curadoria::where('entidade_tipo', 'coleta')->where('entidade_id', $id)->count());
    }

    public function test_coleta_enviada_para_curadoria_nao_pode_mais_ser_editada(): void
    {
        $usuario = User::factory()->create();
        $id = (string) Str::uuid();

        $this->actingAs($usuario)->postJson('/api/v1/mobile/sync', ['coletas' => [$this->coleta($id)]])->assertAccepted();
        $original = Coleta::findOrFail($id)->nome_bem;
        $this->actingAs($usuario)->postJson("/api/v1/mobile/coletas/{$id}/enviar-curadoria")->assertOk();

        $this->actingAs($usuario)
            ->postJson('/api/v1/mobile/sync', ['coletas' => [$this->coleta($id, ['nome_bem' => 'Editado', 'versao' => 2])]])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('errors.coleta_ja_enviada'))
            ->assertJsonPath('enviadas_para_curadoria.0', $id);

        $this->assertSame($original, Coleta::findOrFail($id)->nome_bem);
    }

    public function test_job_tambem_recusa_edicao_de_coleta_ja_enviada(): void
    {
        $usuario = User::factory()->create();
        $coleta = Coleta::factory()->create(['usuario_id' => $usuario->id, 'nome_bem' => 'Original', 'versao' => 1]);
        Curadoria::factory()->create(['entidade_tipo' => 'coleta', 'entidade_id' => $coleta->id, 'status' => StatusCuradoria::PENDENTE->value]);

        (new ProcessarSincronizacao($usuario->id, [$this->coleta($coleta->id, ['nome_bem' => 'Trocado', 'versao' => 2])]))
            ->handle(app(RegistrarColetaAction::class));

        $this->assertSame('Original', $coleta->fresh()->nome_bem);
    }

    /**
     * @param  array<string, mixed>  $sobrescrever
     * @return array<string, mixed>
     */
    private function coleta(string $id, array $sobrescrever = []): array
    {
        return array_merge([
            'id' => $id,
            'data_coleta' => '2026-05-01 08:00:00',
            'nome_bem' => 'Sítio de teste',
            'latitude' => -5.09,
            'longitude' => -42.80,
            'versao' => 1,
        ], $sobrescrever);
    }
}
