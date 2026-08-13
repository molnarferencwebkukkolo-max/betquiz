<?php

namespace App\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyQuizPerformanceNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly CarbonImmutable $periodStart,
        private readonly CarbonImmutable $periodEnd,
        private readonly int $answers,
        private readonly int $correctAnswers,
        private readonly int $quizCount,
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('weekly_report', 'database')) {
            $channels[] = 'database';
        }

        if ($notifiable->wantsNotification('weekly_report', 'mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'weekly_report',
            'title' => 'Heti kvízteljesítményed',
            'message' => $this->summary(),
            'period_start' => $this->periodStart->toDateString(),
            'period_end' => $this->periodEnd->toDateString(),
            'answers' => $this->answers,
            'correct_answers' => $this->correctAnswers,
            'accuracy' => $this->accuracy(),
            'quiz_count' => $this->quizCount,
            'url' => route('profile.results'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('KwizzGo: heti kvízteljesítményed')
            ->greeting("Kedves {$notifiable->name}!")
            ->line($this->periodLabel())
            ->line("Megválaszolt kérdések: {$this->answers}")
            ->line("Helyes válaszok: {$this->correctAnswers}")
            ->line("Találati arány: {$this->accuracy()}%")
            ->line("Játszott kvízek: {$this->quizCount}")
            ->action('Eredményeim megtekintése', route('profile.results'))
            ->line('Ezt az e-mailt a profilod értesítési beállításai alapján kaptad.');
    }

    private function summary(): string
    {
        return "{$this->answers} válaszból {$this->correctAnswers} helyes ({$this->accuracy()}%), {$this->quizCount} kvízben.";
    }

    private function accuracy(): int
    {
        return $this->answers > 0
            ? (int) round(($this->correctAnswers / $this->answers) * 100)
            : 0;
    }

    private function periodLabel(): string
    {
        return 'Időszak: '.$this->periodStart->format('Y. m. d.').' – '.$this->periodEnd->format('Y. m. d.');
    }
}
