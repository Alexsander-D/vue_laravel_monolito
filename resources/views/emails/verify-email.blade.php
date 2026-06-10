<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de E-mail</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #121212;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #1e1e1e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        h1 {
            text-align: center;
            color: #313fee;
            margin-bottom: 20px;
        }

        p {
            font-size: 16px;
            line-height: 1.6;
            color: #ccc;
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .button {
            display: inline-block;
            background-color: #313fee;
            color: white;
            padding: 14px 30px;
            text-align: center;
            border-radius: 4px;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #1221da;
        }

        .button span {
            color: white;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
        }

        .footer a {
            color: #313fee;
            text-decoration: none;
        }

        .footer p {
            color: #bbb;
            margin: 10px 0;
            font-size: 12px;
        }

        .hr {
            border: none;
            height: 0.5px;
            background-color: #bbb;
            margin: 10px 0;
        }

        @media screen and (max-width: 600px) {
            .email-container {
                padding: 20px;
            }

            .button {
                padding: 12px 25px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <h1>Bem-vindo(a) a Barbearia!</h1>
        <p>Estamos felizes em ter você conosco! Para garantir a segurança da sua conta, por favor, verifique seu e-mail clicando no botão abaixo. Isso nos ajudará a confirmar que você é o proprietário deste endereço de e-mail.</p>

        <div class="button-container">
            <a href="{{ $verificationUrl }}" class="button"><span>Verificar Endereço de E-mail</span></a>
        </div>

        <p>Se você não se inscreveu em nossa plataforma, por favor, ignore este e-mail.</p>

        <hr />
        <div class="footer">
            <p>Se você estiver com problemas para clicar no botão "Verificar endereço de e-mail", copie e cole o seguinte link em seu navegador:</p>
            <p><a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a></p>
            <p>Atenciosamente,<br>Equipe Barbearia Carioca</p>
        </div>
    </div>
</body>

</html>