<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends Notification
{
    /**
     * Especifica o canal da notificação.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Gera a URL de verificação de e-mail.
     */
    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)), 
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );
    }

    /**
     * Configura o e-mail enviado ao usuário.
     */
    public function toMail($notifiable)
    {
        // Obter a URL de verificação assinada
        $verificationUrl = $this->verificationUrl($notifiable);

        // Retornar a mensagem personalizada
        return (new MailMessage)
            ->subject('Confirme seu endereço de e-mail - Vex')
            ->greeting('Bem-vindo(a) à nossa plataforma!')
            ->line('Obrigado por se registrar em nosso site. Por favor, clique no botão abaixo para verificar seu endereço de e-mail.')
            ->action('Verificar Endereço de E-mail', $verificationUrl)
            ->line('Se você não criou uma conta, nenhuma ação é necessária.')
            ->salutation('Atenciosamente, Equipe Vex')
            ->view('emails.verify-email', ['verificationUrl' => $verificationUrl]);
    }

    /**
     * Representa a notificação como um array (opcional).
     */
    public function toArray($notifiable)
    {
        return [
            // Dados adicionais podem ser passados aqui se necessário
        ];
    }
}
