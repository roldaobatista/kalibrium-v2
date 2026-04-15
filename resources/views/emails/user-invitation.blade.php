Voce recebeu um convite para acessar o Kalibrium.

@if ($tenantName)
Laboratorio: {{ $tenantName }}
@endif
@if ($role)
Papel atribuido: {{ $role }}
@endif
@if ($inviterName)
Convidado por: {{ $inviterName }}
@endif

Acesse o link abaixo para definir sua senha e ativar o acesso:
{{ $invitationUrl }}

Se voce nao esperava este convite, ignore este e-mail.
