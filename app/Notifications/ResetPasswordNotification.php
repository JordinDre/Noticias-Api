<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Recuperar Contraseña')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Has recibido este correo porque solicitaste recuperar tu contraseña.')
            ->action('Restablecer Contraseña', $resetUrl)
            ->line('Este enlace expirará en 60 minutos.')
            ->line('Si no solicitaste recuperar tu contraseña, puedes ignorar este correo. Tu contraseña no será cambiada.')
            ->salutation('Saludos, ' . config('app.name'));
    }

    /**
     * Get the reset URL for the given notifiable.
     */
    protected function resetUrl($notifiable): string
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173') 
            . '/password/reset?token=' . $this->token 
            . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return $frontendUrl;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
