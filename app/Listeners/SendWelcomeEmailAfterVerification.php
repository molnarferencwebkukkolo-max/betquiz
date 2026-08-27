<?php

namespace App\Listeners;

use App\Models\EmailTemplate;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Verified;

class SendWelcomeEmailAfterVerification
{
    /** A Welcome level csak a sikeres dupla opt-in utan indul el. */
    public function handle(Verified $event): void
    {
        if (EmailTemplate::content(EmailTemplate::WELCOME)->is_active) {
            $event->user->notify(new WelcomeNotification());
        }
    }
}
