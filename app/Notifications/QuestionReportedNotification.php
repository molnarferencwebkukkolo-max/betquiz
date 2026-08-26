<?php

namespace App\Notifications;

use App\Models\QuestionReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuestionReportedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly QuestionReport $report) {}

    public function via(object $notifiable): array
    {
        $channels = [];
        if ($notifiable->wantsNotification('question_reported', 'database')) {
            $channels[] = 'database';
        }
        if ($notifiable->wantsNotification('question_reported', 'mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $question = $this->report->question;

        return (new MailMessage)
            ->subject('KwizzGo: Hibásnak jelölt kérdés')
            ->greeting('Kedves '.($notifiable->username ?: $notifiable->name).'!')
            ->line('Egy játékos hibát jelzett, ezért a kérdést a kivizsgálás idejére inaktiváltuk.')
            ->line("Kvíz: {$question->quiz->title}")
            ->line("Játékosi hibaleírás: {$this->report->reason}")
            ->action('Hibajelzés kezelése', route('question-reports.index'))
            ->line('Ezt az e-mailt a profilod értesítési beállításai alapján kaptad.');
    }

    public function toArray(object $notifiable): array
    {
        $question = $this->report->question;

        return [
            'event' => 'question_reported',
            'title' => 'Hibásnak jelölt kérdés',
            'message' => 'Egy játékos hibát jelzett. A kérdést a rendszer a kivizsgálásig inaktiválta.',
            'reason' => $this->report->reason,
            'quiz_title' => $question->quiz->title,
            'url' => route('question-reports.index'),
        ];
    }
}
