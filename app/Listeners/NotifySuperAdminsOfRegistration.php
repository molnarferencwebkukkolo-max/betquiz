<?php

namespace App\Listeners;

use App\Services\AdminNotificationService;
use Illuminate\Auth\Events\Registered;

class NotifySuperAdminsOfRegistration
{
    public function __construct(private readonly AdminNotificationService $notifications)
    {
    }

    public function handle(Registered $event): void
    {
        $this->notifications->userRegistered($event->user);
    }
}
