<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Diário de Atendimentos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #fef3c7 0%, #eab308 100%);
            color: #333;
            padding: 20px 0;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .email-header {
            background: linear-gradient(135deg, #1f2937 0%, #eab308 100%);
            padding: 40px 30px;
            text-align: center;
        }

        .logo {
            max-width: 120px;
            height: auto;
            margin-bottom: 20px;
        }

        .email-header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 600;
            margin: 0;
        }

        .email-header p {
            color: #ffffff;
            font-size: 14px;
            margin-top: 10px;
            opacity: 0.9;
        }

        .email-body {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 16px;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 30px 0;
        }

        .summary-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #eab308;
        }

        .summary-card.warning {
            border-left-color: #dc2626;
            background: #fef2f2;
        }

        .summary-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .summary-value {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }

        .summary-card.warning .summary-value {
            color: #dc2626;
        }

        .idle-hours-section {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 2px solid #fecaca;
            padding: 20px;
            border-radius: 8px;
            margin-top: 25px;
        }

        .idle-hours-title {
            font-size: 16px;
            font-weight: 600;
            color: #dc2626;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .idle-hours-title::before {
            content: '⚠';
            display: inline-block;
            margin-right: 8px;
            font-size: 20px;
        }

        .hours-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .hour-badge {
            background: #ffffff;
            border: 1px solid #fecaca;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-size: 13px;
            color: #dc2626;
            font-weight: 600;
        }

        .no-idle-message {
            text-align: center;
            padding: 20px;
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 8px;
            color: #16a34a;
        }

        .no-idle-message .emoji {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .shift-info {
            background: #f3f4f6;
            border-left: 4px solid #1f2937;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 13px;
            color: #4b5563;
        }

        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 30px 0;
        }

        .email-footer {
            text-align: center;
            padding: 20px 30px;
            background: #f8f9fa;
            color: #999;
            font-size: 12px;
            border-top: 1px solid #e0e0e0;
        }

        @media screen and (max-width: 600px) {
            .email-wrapper {
                margin: 10px;
            }

            .email-header {
                padding: 30px 20px;
            }

            .email-body {
                padding: 25px 20px;
            }

            .email-footer {
                padding: 15px 20px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .hours-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <!-- Header -->
        <div class="email-header">
            <img src="http://3.84.217.247/images/logomarca.png" alt="Barbearia Carioca" class="logo">
            <h1>Relatório Diário</h1>
            <p>{{ $date }}</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <p class="greeting">
                Este relatório é gerado automaticamente para fornecer insights sobre o desempenho do dia.
            </p>

            <!-- Resumo em Cards -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-label">Total de Atendimentos</div>
                    <div class="summary-value">{{ $total_attendances }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Faturamento</div>
                    <div class="summary-value">R$ {{ number_format($total_revenue, 0, ',', '.') }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Taxa de Ocupação</div>
                    <div class="summary-value">{{ $occupancy_percentage }}%</div>
                </div>
                <div class="summary-card warning">
                    <div class="summary-label">Horas Ociosas</div>
                    <div class="summary-value">{{ count($idle_hours) }}h</div>
                </div>
            </div>

            <!-- Horas Ociosas -->
            @if (count($idle_hours) > 0)
            <div class="idle-hours-section">
                <div class="idle-hours-title">Horários sem Atendimento (09:00 - 22:00)</div>
                <div class="hours-grid">
                    @foreach ($idle_hours as $hour)
                    <div class="hour-badge">{{ $hour }}</div>
                    @endforeach
                </div>
            </div>
            @endif
            @if (count($idle_hours) > 4)
            <div class="no-idle-message">
                <div class="emoji">✓</div>
                <div><strong>Excelente!</strong></div>
                <div>Nenhuma hora ociosa! Quase todos os horários tiveram atendimento.</div>
            </div>
            @endif

            <!-- Informações do Turno -->
            <div class="shift-info">
                <strong>Informações do Turno:</strong><br>
                Horário: 09:00 - 22:00 (13 horas)<br>
                Horas com atendimento: {{ 13 - count($idle_hours) }}/13<br>
                Período: {{ $date }}
            </div>
        </div>

        <!-- Divider -->
        <div class="divider"></div>

        <!-- Footer -->
        <div class="email-footer">
            <p>© 2026 Barbearia Carioca. Todos os direitos reservados.</p>
            <p style="margin-top: 10px;">Este é um relatório automático enviado diariamente às 22:05.</p>
        </div>
    </div>
</body>

</html>