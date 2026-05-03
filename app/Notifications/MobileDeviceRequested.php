<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\MobileDevice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class MobileDeviceRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly MobileDevice $device,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $techName = $this->device->user->name ?? 'Um técnico';
        $deviceLabel = $this->device->device_label ?? 'celular desconhecido';

        return (new MailMessage)
            ->subject('Pedido de acesso pelo celular — '.$techName)
            ->greeting('Olá!')
            ->line("{$techName} pediu autorização para usar o Kalibrium no celular {$deviceLabel}.")
            ->line('Acesse o painel para aprovar ou recusar o pedido.')
            ->action('Gerenciar celulares', url('/mobile-devices'))
            ->line('Se você não reconhece esse pedido, pode ignorá-lo com segurança.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'device_id' => $this->device->id,
            'tech_name' => $this->device->user?->name,
            'device_label' => $this->device->device_label,
            'requested_at' => now()->toIso8601String(),
        ];
    }
}
