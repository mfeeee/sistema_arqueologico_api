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
            ->subject(__('Recuperação de Senha'))
            ->line(__('Você recebeu este e-mail porque foi solicitada a recuperação de senha para sua conta.'))
            ->action(__('Redefinir Senha'), $resetUrl)
            ->line(__('Este link expirará em :count minutos.', ['count' => config('auth.passwords.users.expire', 60)]))
            ->line(__('Se você não solicitou a recuperação de senha, nenhuma ação é necessária.'));
    }

    private function buildResetUrl(string $email): string
    {
        $base = config('app.password_reset_url', 'arqueopi://reset-password');

        return $base.'?'.http_build_query([
            'token' => $this->token,
            'email' => $email,
        ]);
    }
}
