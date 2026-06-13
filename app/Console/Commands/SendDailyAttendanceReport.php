<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Spatie\User;
use App\Notifications\DailyAttendanceReportNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailyAttendanceReport extends Command
{
    /**
     * O nome e assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'attendance:send-daily-report';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Envia um relatório diário de atendimentos e ociosidade às 21:00';

    /**
     * Executa o comando.
     */
    public function handle()
    {
        $today = Carbon::today();
        $dateFormatted = $today->format('d/m/Y');

        // Buscar todos os atendimentos do dia
        $attendances = Attendance::query()
            ->whereDate('created_at', $today)
            ->with('services')
            ->get();

        // Calcular horas com atendimento (09:00 - 22:00)
        $hoursWithAttendance = new \stdClass();
        foreach (range(9, 22) as $hour) {
            $hoursWithAttendance->{$hour} = false;
        }

        $attendances->each(function ($attendance) use ($hoursWithAttendance) {
            $hour = $attendance->created_at->hour;
            if ($hour >= 9 && $hour < 22) {
                $hoursWithAttendance->{$hour} = true;
            }
        });

        // Calcular horas ociosas
        $idleHours = [];
        foreach (range(9, 21) as $hour) {
            if (!$hoursWithAttendance->{$hour}) {
                $nextHour = $hour + 1;
                $idleHours[] = sprintf('%02d:%02d - %02d:%02d', $hour, 0, $nextHour, 0);
            }
        }

        // Calcular totais (13 horas de turno: 9 às 22)
        $totalAttendances = $attendances->count();
        $totalRevenue = $attendances->sum('total');
        $occupiedHours = 13 - count($idleHours);
        $occupancyPercentage = round(($occupiedHours / 13) * 100);

        // Preparar dados para a notificação
        $attendanceData = [
            'total_attendances' => $totalAttendances,
            'total_revenue' => $totalRevenue,
            'idle_hours' => $idleHours,
            'occupancy_percentage' => $occupancyPercentage,
            'date' => $dateFormatted,
        ];

        // Enviar e-mail para o usuário responsável (ID 1)
        $responsavel = User::find(1);
        if ($responsavel) {
            try {
                $responsavel->notify(new DailyAttendanceReportNotification($attendanceData));
                $this->info('✓ Relatório diário enviado com sucesso!');
            } catch (\Exception $e) {
                $this->error('✗ Erro ao enviar relatório: ' . $e->getMessage());
            }
        } else {
            $this->error('✗ Usuário responsável não encontrado.');
        }
    }
}
