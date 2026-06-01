<?php

namespace App\Mail;

class PasswordResetEmail
{
    public function __construct(
        private readonly string $name,
        private readonly string $token,
        private readonly string $resetUrl,
    ) {}

    public function toPayload(string $recipientEmail): array
    {
        return [
            'message' => [
                'to' => ['email' => $recipientEmail],
                'content' => [
                    'title' => 'Recuperação de Senha — Sistema Arqueológico',
                    'body' => $this->buildHtml(),
                ],
                'routing' => [
                    'method' => 'single',
                    'channels' => ['email'],
                ],
            ],
        ];
    }

    private function buildHtml(): string
    {
        $name = e($this->name);
        $token = e($this->token);
        $resetUrl = e($this->resetUrl);
        $tokenBox = $this->buildTokenBox($token);
        $ctaButton = $this->buildCtaButton($resetUrl);
        $footerNotes = $this->buildFooterNotes();

        return <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background-color:#f3f0ec;font-family:Arial,Helvetica,sans-serif;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f0ec;padding:40px 16px;">
            <tr><td align="center">
              <table width="560" cellpadding="0" cellspacing="0" style="background-color:#f9f8f5;border:1px solid #dcd9d5;border-radius:12px;overflow:hidden;max-width:560px;width:100%;">
                <tr>
                  <td style="background-color:#01696f;padding:24px 32px;text-align:center;">
                    <p style="margin:0;color:#ffffff;font-size:22px;font-weight:bold;letter-spacing:0.5px;">🏺 Sistema Arqueológico</p>
                  </td>
                </tr>
                <tr>
                  <td style="padding:32px;">
                    <p style="margin:0 0 16px;font-size:18px;color:#1a1a1a;font-weight:600;">Olá, {$name}!</p>
                    <p style="margin:0 0 28px;font-size:15px;color:#444444;line-height:1.7;">
                      Recebemos uma solicitação para redefinir a senha da sua conta.<br>
                      Use o código abaixo no aplicativo para criar uma nova senha:
                    </p>
                    {$tokenBox}
                    {$ctaButton}
                    {$footerNotes}
                  </td>
                </tr>
                <tr>
                  <td style="padding:16px 32px;border-top:1px solid #dcd9d5;text-align:center;">
                    <p style="margin:0;font-size:11px;color:#cccccc;">© Sistema Arqueológico</p>
                  </td>
                </tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML;
    }

    private function buildTokenBox(string $token): string
    {
        return <<<HTML
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
          <tr>
            <td style="background-color:#e6e4df;border-radius:8px;padding:20px 24px;text-align:center;">
              <p style="margin:0 0 8px;font-size:11px;color:#777777;letter-spacing:1.5px;text-transform:uppercase;">Seu token</p>
              <p style="margin:0;font-family:'Courier New',Courier,monospace;font-size:24px;letter-spacing:6px;color:#01696f;font-weight:bold;">{$token}</p>
            </td>
          </tr>
        </table>
        HTML;
    }

    private function buildCtaButton(string $resetUrl): string
    {
        return <<<HTML
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
          <tr>
            <td align="center">
              <a href="{$resetUrl}" style="display:inline-block;background-color:#01696f;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;padding:12px 28px;border-radius:8px;">
                Ou clique aqui para redefinir
              </a>
            </td>
          </tr>
        </table>
        HTML;
    }

    private function buildFooterNotes(): string
    {
        return <<<'HTML'
        <p style="margin:0 0 8px;font-size:13px;color:#888888;text-align:center;">Este link expira em 60 minutos.</p>
        <p style="margin:0;font-size:12px;color:#aaaaaa;text-align:center;">Se você não solicitou a recuperação de senha, ignore este e-mail.</p>
        HTML;
    }
}
