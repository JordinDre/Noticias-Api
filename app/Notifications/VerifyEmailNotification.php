<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifica tu dirección de correo electrónico')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Gracias por registrarte. Por favor haz clic en el botón de abajo para verificar tu dirección de correo electrónico.')
            ->action('Verificar Email', $verificationUrl)
            ->line('Este enlace expirará en 60 minutos.')
            ->line('Si no creaste una cuenta, puedes ignorar este correo.')
            ->salutation('Saludos, ' . config('app.name'));
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable): string
    {
        // Crear el hash de verificación
        $hash = sha1($notifiable->getEmailForVerification());
        
        // URL del backend
        $backendUrl = config('app.url') . '/api/auth/email/verify/' . $notifiable->getKey() . '/' . $hash;
        
        // URL del frontend con query parameters
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173') 
            . '/email/verify?id=' . $notifiable->getKey() 
            . '&hash=' . $hash;

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
