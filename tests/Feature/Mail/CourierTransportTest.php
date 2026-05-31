<?php

namespace Tests\Feature\Mail;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CourierTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.mailers.courier' => [
            'transport' => 'courier',
            'api_key' => 'test-courier-api-key',
        ]]);
    }

    public function test_courier_transport_envia_request_para_api(): void
    {
        Http::fake([
            'https://api.courier.com/send' => Http::response(['requestId' => 'abc-123'], 200),
        ]);

        Mail::mailer('courier')->raw('Corpo de teste em texto.', function ($message) {
            $message->to('destinatario@teste.com', 'Destinatário Teste')
                ->subject('Assunto de Teste')
                ->from('noreply@sistema-arqueologico.local', 'Sistema Arqueológico');
        });

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.courier.com/send'
                && $request->hasHeader('Authorization', 'Bearer test-courier-api-key');
        });
    }

    public function test_courier_transport_envia_destinatario_correto(): void
    {
        Http::fake([
            'https://api.courier.com/send' => Http::response(['requestId' => 'abc-456'], 200),
        ]);

        Mail::mailer('courier')->raw('Corpo de teste.', function ($message) {
            $message->to('usuario@teste.com', 'Usuário Teste')
                ->subject('Recuperação de Senha')
                ->from('noreply@sistema-arqueologico.local', 'Sistema Arqueológico');
        });

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return $data['message']['to']['email'] === 'usuario@teste.com'
                && $data['message']['to']['name'] === 'Usuário Teste';
        });
    }

    public function test_courier_transport_envia_assunto_e_remetente_corretos(): void
    {
        Http::fake([
            'https://api.courier.com/send' => Http::response(['requestId' => 'abc-789'], 200),
        ]);

        Mail::mailer('courier')->raw('Corpo de teste.', function ($message) {
            $message->to('usuario@teste.com')
                ->subject('Recuperação de Senha — Sistema Arqueológico')
                ->from('noreply@sistema-arqueologico.local', 'Sistema Arqueológico');
        });

        Http::assertSent(function (Request $request) {
            $data = $request->data();
            $override = $data['message']['channels']['email']['override'];

            return $override['subject'] === 'Recuperação de Senha — Sistema Arqueológico'
                && $override['from']['email'] === 'noreply@sistema-arqueologico.local';
        });
    }

    public function test_courier_transport_lanca_excecao_em_erro_da_api(): void
    {
        Http::fake([
            'https://api.courier.com/send' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $this->expectException(RequestException::class);

        Mail::mailer('courier')->raw('Corpo de teste.', function ($message) {
            $message->to('usuario@teste.com')
                ->subject('Teste')
                ->from('noreply@sistema-arqueologico.local');
        });
    }
}
