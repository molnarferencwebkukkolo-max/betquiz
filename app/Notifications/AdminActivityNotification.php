<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminActivityNotification extends Notification
{
    use Queueable;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly string $event,
        private readonly string $title,
        private readonly string $message,
        private readonly string $url,
        private readonly array $context = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = [];
        if ($notifiable->wantsNotification($this->event, 'database')) {
            $channels[] = 'database';
        }
        if ($notifiable->wantsNotification($this->event, 'mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("KwizzGo: {$this->title}")
            ->greeting('Kedves '.($notifiable->username ?: $notifiable->name).'!')
            ->line($this->message)
            ->action($this->actionLabel(), $this->url)
            ->line('Ezt az e-mailt a profilod értesítési beállításai alapján kaptad.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return array_merge($this->context, [
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ]);
    }

    private function actionLabel(): string
    {
        return match ($this->event) {
            'user_registered' => 'Felhasználó megnyitása',
            'quiz_submitted' => 'Kvízigény megnyitása',
            default => 'Kvíz megnyitása',
        };
    }
}
