<?php

namespace App\Listeners;

use App\Models\EmailTemplate;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Registered;

class SendWelcomeEmailToVerifiedRegistration
{
    /** A Google-lel letrehozott fiok mar hitelesitett, ezert azonnal udvozolheto. */
    public function handle(Registered $event): void
    {
        if ($event->user->hasVerifiedEmail() && EmailTemplate::content(EmailTemplate::WELCOME)->is_active) {
            $event->user->notify(new WelcomeNotification());
        }
    }
}
