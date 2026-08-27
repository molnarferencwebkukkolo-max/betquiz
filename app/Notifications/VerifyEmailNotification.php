<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $template = EmailTemplate::content(EmailTemplate::VERIFICATION);
        $mail = (new MailMessage())->subject($template->render($template->subject, $notifiable))
            ->greeting($template->render($template->heading, $notifiable));
        $template->appendLines($mail, $template->body, $notifiable);
        $mail->action($template->render($template->button_text ?: 'E-mail-cim hitelesitese', $notifiable), $this->verificationUrl($notifiable));

        return $template->footer ? $template->appendLines($mail, $template->footer, $notifiable) : $mail;
    }
}
