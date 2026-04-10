<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $userLocale = 'fr',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url');
        $url = "{$frontendUrl}/reset-password?token={$this->token}&email=" . urlencode($notifiable->email);

        $translations = $this->translations();

        return (new MailMessage)
            ->subject($translations['subject'])
            ->line($translations['intro'])
            ->action($translations['action'], $url)
            ->line($translations['expire'])
            ->line($translations['outro']);
    }

    private function translations(): array
    {
        $minutes = config('auth.passwords.users.expire');

        if ($this->userLocale === 'fr') {
            return [
                'subject' => 'Réinitialisation du mot de passe',
                'intro' => 'Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.',
                'action' => 'Réinitialiser le mot de passe',
                'expire' => "Ce lien de réinitialisation expirera dans {$minutes} minutes.",
                'outro' => "Si vous n'avez pas demandé de réinitialisation, aucune action n'est requise.",
            ];
        }

        return [
            'subject' => 'Reset Password',
            'intro' => 'You are receiving this email because we received a password reset request for your account.',
            'action' => 'Reset Password',
            'expire' => "This password reset link will expire in {$minutes} minutes.",
            'outro' => 'If you did not request a password reset, no further action is required.',
        ];
    }
}
