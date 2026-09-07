<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ManualPointAdjustmentNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly int $amount, private readonly string $reason)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isCredit = $this->amount > 0;
        $formattedAmount = ($isCredit ? '+' : '−').number_format(abs($this->amount), 0, ',', ' ').' PT';

        return [
            'event' => 'manual_point_adjustment',
            'title' => $isCredit ? 'Pontjóváírás' : 'Pontlevonás',
            'context_label' => $formattedAmount,
            'message' => $isCredit ? 'Jóváírtunk neked '.$formattedAmount.'.' : 'Leemeltünk az egyenlegedből '.ltrim($formattedAmount, '−').'.',
            'reason' => $this->reason,
            'url' => route('profile.show'),
        ];
    }
}
