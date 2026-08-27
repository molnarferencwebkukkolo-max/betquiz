<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    public function __construct(private readonly bool $force = false)
    {
    }

    public function via(object $notifiable): array
    {
        return ($this->force || EmailTemplate::content(EmailTemplate::WELCOME)->is_active) ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::content(EmailTemplate::WELCOME);
        $mail = (new MailMessage())
            ->subject($template->render($template->subject, $notifiable))
            ->greeting($template->render($template->heading, $notifiable));

        $template->appendLines($mail, $template->body, $notifiable);

        if ($template->button_text) {
            $mail->action($template->render($template->button_text, $notifiable), route('dashboard'));
        }

        return $template->footer ? $template->appendLines($mail, $template->footer, $notifiable) : $mail;
    }
}
