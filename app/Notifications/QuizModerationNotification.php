<?php

namespace App\Notifications;

use App\Models\Quiz;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizModerationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Quiz $quiz,
        private readonly string $event,
        private readonly ?string $reason = null,
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

    public function toArray(object $notifiable): array
    {
        [$title, $message] = $this->content();

        return [
            'event' => $this->event,
            'title' => $title,
            'message' => $message,
            'reason' => $this->reason,
            'quiz_id' => $this->quiz->id,
            'quiz_title' => $this->quiz->title,
            // A numerikus route binding akkor is stabil, ha később változik a slug.
            'url' => $this->quizUrl(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$title, $message] = $this->content();
        $mail = (new MailMessage)
            ->subject("KwizzGo: {$title}")
            ->greeting("Kedves {$notifiable->name}!")
            ->line($message)
            ->line("Kvíz: {$this->quiz->title}");

        if ($this->reason) {
            $mail->line("Adminisztrátori indok: {$this->reason}");
        }

        return $mail
            ->action('Kvíz megnyitása', $this->quizUrl())
            ->line('Ezt az e-mailt a profilod értesítési beállításai alapján kaptad.');
    }

    private function content(): array
    {
        return match ($this->event) {
            'approved' => [
                'Kvíz jóváhagyva',
                'A kvíz koncepcióját az adminisztrátor jóváhagyta.',
            ],
            'rejected' => [
                'Kvíz elutasítva',
                'A kvíz koncepcióját az adminisztrátor elutasította.',
            ],
            'published' => [
                'Kvíz publikálva',
                'A kvíz mostantól nyilvánosan elérhető.',
            ],
            'publication_withdrawn' => [
                'Publikálás visszavonva',
                'Az adminisztrátor visszavonta a kvíz nyilvános megjelenését.',
            ],
            default => ['Kvíz állapota megváltozott', 'A kvíz moderációs állapota megváltozott.'],
        };
    }

    private function quizUrl(): string
    {
        return route('my-quizzes.show', ['quiz' => $this->quiz->id]);
    }
}
