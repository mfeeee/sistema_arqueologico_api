<?php

namespace Tests\Feature\BemMaterial;

use App\Enums\AcaoResultanteCuradoria;
use App\Enums\StatusCuradoria;
use App\Models\BemMaterial;
use App\Models\BemResponsavel;
use App\Models\Coleta;
use App\Models\Curadoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColaboradoresTest extends TestCase
{
    use RefreshDatabase;

    private BemMaterial $bem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bem = BemMaterial::factory()->publicado()->create();
        $coletor = User::factory()->create();
        $coleta = Coleta::factory()->create(['usuario_id' => $coletor->id]);

        // Coleta aprovada para este sítio: o coletor vira colaborador.
        Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'bem_material_id' => $this->bem->id,
            'status' => StatusCuradoria::APROVADO,
            'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO,
        ]);

        BemResponsavel::factory()->create(['bem_material_id' => $this->bem->id, 'user_id' => $coletor->id]);
    }

    public function test_visitante_nao_recebe_email_dos_colaboradores(): void
    {
        $this->getJson("/api/v1/mobile/bens-materiais/{$this->bem->id}/colaboradores")
            ->assertOk()
            ->assertJsonCount(1, 'colaboradores')
            ->assertJsonMissingPath('colaboradores.0.email');
    }

    public function test_curador_recebe_email_dos_colaboradores(): void
    {
        $this->actingAs(User::factory()->curador()->create(), 'sanctum')
            ->getJson("/api/v1/mobile/bens-materiais/{$this->bem->id}/colaboradores")
            ->assertOk()
            ->assertJsonPath('colaboradores.0.email', fn ($email) => is_string($email) && $email !== '');
    }

    public function test_visitante_nao_recebe_email_dos_responsaveis_do_sitio(): void
    {
        $this->getJson("/api/v1/mobile/bens-materiais/{$this->bem->id}")
            ->assertOk()
            ->assertJsonPath('data.responsaveis.0.user.name', fn ($nome) => is_string($nome) && $nome !== '')
            ->assertJsonMissingPath('data.responsaveis.0.user.email');
    }
}
