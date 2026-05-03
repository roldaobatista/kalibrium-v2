<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

final class ResetPasswordNotification extends ResetPassword
{
    /**
     * tenant_id passado na URL para que a página web de reset
     * identifique o tenant correto.
     */
    public function __construct(
        string $token,
        private readonly ?int $tenantId = null,
    ) {
        parent::__construct($token);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);

        if ($this->tenantId !== null) {
            $url .= (str_contains($url, '?') ? '&' : '?').'tenant='.$this->tenantId;
        }

        /** @var string $nome */
        $nome = $notifiable->name ?? $notifiable->email; // @phpstan-ignore-line

        return (new MailMessage)
            ->subject('Redefinir sua senha do Kalibrium')
            ->greeting("Olá {$nome},")
            ->line('Recebemos um pedido pra redefinir a senha da sua conta no Kalibrium.')
            ->action('Redefinir senha', $url)
            ->line('Este link vale por 1 hora. Se você não pediu isso, ignore este e-mail — sua senha continua a mesma.')
            ->salutation('Equipe Kalibrium');
    }
}
