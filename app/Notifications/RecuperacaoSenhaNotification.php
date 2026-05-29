<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecuperacaoSenhaNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->buildResetUrl($notifiable->email);

        return (new MailMessage)
            ->subject('Recuperação de Senha — Sistema Arqueológico')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta.')
            ->line('Use o código abaixo no aplicativo para criar uma nova senha:')
            ->line('**Token:** '.$this->token)
            ->action('Ou clique aqui para redefinir', $resetUrl)
            ->line('Este link expira em 60 minutos.')
            ->line('Se você não solicitou a recuperação de senha, ignore este e-mail.');
    }

    private function buildResetUrl(string $email): string
    {
        $base = config('app.flutter_reset_url', 'arqueologico://reset-password');

        return $base.'?'.http_build_query([
            'token' => $this->token,
            'email' => $email,
        ]);
    }
}
