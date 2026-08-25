<?php

namespace App\Notifications;

use App\Models\QuestionReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuestionReportedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly QuestionReport $report) {}

    public function via(object $notifiable): array
    {
        // A hibajelzés operatív moderációs esemény, ezért minden
        // jogosult címzett belső értesítést kap róla.
        return ['database'];
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
