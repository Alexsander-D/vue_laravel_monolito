<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DailyAttendanceReportNotification extends Notification
{
    protected $attendanceData;

    /**
     * Cria uma nova instância da notificação.
     */
    public function __construct(array $attendanceData)
    {
        $this->attendanceData = $attendanceData;
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
        return (new MailMessage)
            ->subject("Relatório Diário de Atendimentos - {$this->attendanceData['date']}")
            ->view('emails.daily-report', [
                'total_attendances' => $this->attendanceData['total_attendances'],
                'total_revenue' => $this->attendanceData['total_revenue'],
                'idle_hours' => $this->attendanceData['idle_hours'],
                'occupancy_percentage' => $this->attendanceData['occupancy_percentage'],
                'date' => $this->attendanceData['date'],
            ]);
    }

    /**
     * Representa a notificação como um array (opcional).
     */
    public function toArray($notifiable)
    {
        return [
            'total_attendances' => $this->attendanceData['total_attendances'],
            'total_revenue' => $this->attendanceData['total_revenue'],
            'occupancy_percentage' => $this->attendanceData['occupancy_percentage'],
        ];
    }
}
