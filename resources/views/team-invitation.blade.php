<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convite para a Equipe</title>
</head>
<body>
    <h2>Convite para se juntar à equipe "{{ $team->name }}"</h2>

    <p>Olá!</p>

    <p>Você recebeu este e-mail porque foi convidado para se juntar à equipe "{{ $team->name }}".</p>

    @if ($invitation->role)
        <p>Você foi convidado para o papel de "{{ $invitation->role }}".</p>
    @else
        <p>Você foi convidado para se juntar à equipe sem um papel específico definido.</p>
    @endif

    <p>Para aceitar o convite, clique no link abaixo:</p>
    <a href="{{ route('team-invitations.accept', ['invitation' => $invitation->id]) }}">Aceitar Convite</a>

    <p>Ou, se você preferir, ignore este e-mail se não estiver interessado.</p>

    <p>Obrigado,</p>
    <p>A equipe "{{ $team->name }}"</p>
</body>
</html>
