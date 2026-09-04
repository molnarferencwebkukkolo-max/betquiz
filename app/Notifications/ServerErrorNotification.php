<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServerErrorNotification extends Notification
{
    public function __construct(public array $alert) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('KwizzGo szerverhiba – HTTP '.($this->alert['status'] ?? 500))
            ->greeting('KwizzGo hibajelzés')
            ->line('A webalkalmazás szerverhibát rögzített.')
            ->line('Időpont: '.($this->alert['occurred_at'] ?? 'ismeretlen'))
            ->line('Kérés: '.($this->alert['method'] ?? '').' '.($this->alert['route'] ?? ''))
            ->line('Kivétel: '.($this->alert['exception'] ?? 'ismeretlen'))
            ->line('Üzenet: '.($this->alert['message'] ?? 'nincs'))
            ->line('Hely: '.($this->alert['file'] ?? 'ismeretlen').':'.($this->alert['line'] ?? '?'))
            ->line('Az értesítés külön scheduler-futásból érkezett; a részletes stack trace a Laravel naplóban található.');
    }
}
