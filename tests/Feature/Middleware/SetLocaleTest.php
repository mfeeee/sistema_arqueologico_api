<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simula request sem header Accept-Language (cliente não envia preferência).
     * O Symfony test client injeta 'en-us,en;q=0.5' por padrão, então precisamos
     * sobrescrever com string vazia para testar o fallback real.
     */
    private function withNoLanguageHeader(): static
    {
        return $this->withHeaders(['Accept-Language' => '']);
    }

    public function test_defaults_to_pt_b_r_when_no_header(): void
    {
        $response = $this->withNoLanguageHeader()
            ->getJson('/api/v1/mobile/coletas');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Não autorizado. Faça login novamente.']);
    }

    public function test_uses_pt_b_r_when_header_is_pt_br(): void
    {
        $response = $this->withHeader('Accept-Language', 'pt-BR')
            ->getJson('/api/v1/mobile/coletas');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Não autorizado. Faça login novamente.']);
    }

    public function test_uses_en_locale_when_header_is_en_us(): void
    {
        $response = $this->withHeader('Accept-Language', 'en-US')
            ->getJson('/api/v1/mobile/coletas');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized. Please sign in again.']);
    }

    public function test_uses_en_locale_when_header_is_en(): void
    {
        $response = $this->withHeader('Accept-Language', 'en')
            ->getJson('/api/v1/mobile/coletas');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized. Please sign in again.']);
    }

    public function test_uses_pt_b_r_when_header_is_pt(): void
    {
        $response = $this->withHeader('Accept-Language', 'pt')
            ->getJson('/api/v1/mobile/coletas');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Não autorizado. Faça login novamente.']);
    }

    public function test_defaults_to_pt_b_r_for_unknown_locale(): void
    {
        $response = $this->withHeader('Accept-Language', 'fr-FR')
            ->getJson('/api/v1/mobile/coletas');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Não autorizado. Faça login novamente.']);
    }

    public function test_picks_first_supported_locale_from_quality_list(): void
    {
        $response = $this->withHeader('Accept-Language', 'en-US,pt;q=0.9')
            ->getJson('/api/v1/mobile/coletas');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized. Please sign in again.']);
    }

    public function test_login_returns_pt_b_r_invalid_credentials(): void
    {
        $response = $this->withHeader('Accept-Language', 'pt-BR')
            ->postJson('/api/auth/login', [
                'email' => 'inexistente@teste.com',
                'password' => 'senhaerrada',
            ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Credenciais inválidas.']);
    }

    public function test_login_returns_english_invalid_credentials(): void
    {
        $response = $this->withHeader('Accept-Language', 'en-US')
            ->postJson('/api/auth/login', [
                'email' => 'inexistente@teste.com',
                'password' => 'senhaerrada',
            ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_forbidden_returns_pt_b_r_message(): void
    {
        $response = $this->withHeader('Accept-Language', 'pt-BR')
            ->getJson('/api/v1/admin/curadorias');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Não autorizado. Faça login novamente.']);
    }

    public function test_forbidden_returns_english_message(): void
    {
        $response = $this->withHeader('Accept-Language', 'en')
            ->getJson('/api/v1/admin/curadorias');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized. Please sign in again.']);
    }
}
