<?php

namespace App\Notifications;

use App\Channels\CourierChannel;
use App\Mail\PasswordResetEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RecuperacaoSenhaNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return [CourierChannel::class];
    }

    public function toCourier(object $notifiable): array
    {
        $resetUrl = $this->buildResetUrl($notifiable->email);

        return (new PasswordResetEmail(
            name: $notifiable->name,
            token: $this->token,
            resetUrl: $resetUrl,
        ))->toPayload($notifiable->email);
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
