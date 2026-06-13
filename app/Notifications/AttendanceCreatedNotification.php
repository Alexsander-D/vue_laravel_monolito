<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AttendanceCreatedNotification extends Notification
{
    protected $attendance;

    /**
     * Cria uma nova instância da notificação.
     */
    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    /**
     * Especifica o canal da notificação.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Configura o e-mail enviado ao usuário.
     */
    public function toMail($notifiable)
    {
        $services = $this->attendance->services()->get();

        return (new MailMessage)
            ->subject('Novo Atendimento Registrado - Barbearia Carioca')
            ->greeting('Olá!')
            ->line('Um novo atendimento foi registrado com sucesso.')
            ->line('**Barbeiro:** ' . $this->attendance->user->name)
            ->line('**Serviços:** ' . $services->map(fn ($s) => $s->service_name)->implode(', '))
            ->line('**Valor Total:** R$ ' . number_format($this->attendance->total, 2, ',', '.'))
            ->line('**Forma de Pagamento:** ' . $this->attendance->payment_method)
            ->line('**Data e Hora:** ' . $this->attendance->created_at->format('d/m/Y H:i'))
            ->line('Obrigado!')
            ->salutation('Atenciosamente, Equipe Barbearia Carioca')
            ->view('emails.attendance-created', [
                'attendance' => $this->attendance,
                'services' => $services,
            ]);
    }

    /**
     * Representa a notificação como um array (opcional).
     */
    public function toArray($notifiable)
    {
        return [
            'attendance_id' => $this->attendance->id,
            'user_name' => $this->attendance->user->name,
            'total' => $this->attendance->total,
        ];
    }
}
