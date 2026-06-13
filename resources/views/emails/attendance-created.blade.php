<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Atendimento Registrado</title>
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
            background: linear-gradient(135deg, #eab308 0%, #1f2937 100%);
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

        .email-body {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 16px;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .info-section {
            background: #f8f9fa;
            border-left: 4px solid #eab308;
            padding: 20px;
            border-radius: 5px;
            margin: 25px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #eab308;
        }

        .info-value {
            color: #333;
        }

        .services-section {
            margin: 30px 0;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .section-title::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 20px;
            background: linear-gradient(135deg, #eab308 0%, #1f2937 100%);
            margin-right: 10px;
            border-radius: 2px;
        }

        .service-item {
            background: #f8f9fa;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .service-name {
            color: #333;
            font-weight: 500;
        }

        .service-price {
            color: #eab308;
            font-weight: 600;
        }

        .total-section {
            background: linear-gradient(135deg, #1f2937 0%, #eab308 100%);
            color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .total-label {
            font-size: 16px;
            font-weight: 600;
            display: block;
            width: 100%;
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

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #1f2937 0%, #eab308 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 15px;
            transition: transform 0.3s ease;
        }

        .cta-button:hover {
            transform: translateY(-2px);
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

            .total-section {
                flex-direction: column;
                text-align: center;
            }

            .info-row {
                flex-direction: column;
            }

            .service-item {
                flex-direction: column;
            }

            .service-price {
                margin-top: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <!-- Header -->
        <div class="email-header">
            <img src="http://3.84.217.247/images/logomarca.png" alt="Barbearia Carioca" class="logo">
            <h1>Novo Atendimento</h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            <p class="greeting">
                Um novo atendimento foi registrado com sucesso em nossa barbearia. Confira os detalhes abaixo:
            </p>

            <!-- Informações do Atendimento -->
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">Barbeiro(a):&nbsp;</span>
                    <span class="info-value">{{ $attendance->user->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Forma de Pagamento:&nbsp;</span>
                    <span class="info-value">{{ $attendance->payment_method }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Data e Hora:&nbsp;</span>
                    <span class="info-value">{{ $attendance->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <!-- Serviços -->
            <div class="services-section">
                <div class="section-title">Serviços Prestados</div>
                
                @foreach ($services as $service)
                    <div class="service-item">
                        <span class="service-name">{{ $service->service_name }}:&nbsp;</span>
                        <span class="service-price">R$ {{ number_format($service->price, 2, ',', '.') }}</span>
                    </div>
                @endforeach

                <!-- Total -->
                <div class="total-section">
                    <span class="total-label">TOTAL:&nbsp; R$ {{ number_format($attendance->total, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="divider"></div>

        <!-- Footer -->
        <div class="email-footer">
            <p>© 2026 Barbearia Carioca. Todos os direitos reservados.</p>
            <p style="margin-top: 10px;">Este é um e-mail automático, não responda.</p>
        </div>
    </div>
</body>

</html>
