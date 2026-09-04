<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use App\Models\EmailCampaignDelivery;
use App\Models\User;
use App\Notifications\CampaignEmailNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SendScheduledCampaignEmails extends Command
{
    protected $signature = 'emails:send-scheduled-campaigns';
    protected $description = 'Kiküldi az esedékes, regisztrációhoz időzített KwizzGo leveleket';

    public function handle(): int
    {
        $sent = 0;
        EmailTemplate::query()->where('template_type', EmailTemplate::TYPE_CAMPAIGN)->where('is_active', true)
            ->orderBy('days_after_registration')->each(function (EmailTemplate $template) use (&$sent): void {
                User::query()->where('is_active', true)->where('is_banned', false)->whereNotNull('email_verified_at')
                    ->where('created_at', '<=', now()->subDays($template->days_after_registration))
                    ->whereHas('notificationPreferences', fn ($query) => $query->where('event', 'recommendations')->where('email_enabled', true))
                    ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('email_campaign_deliveries')
                        ->whereColumn('email_campaign_deliveries.user_id', 'users.id')->where('email_template_id', $template->id))
                    ->orderBy('id')->chunkById(100, function ($users) use ($template, &$sent): void {
                        foreach ($users as $user) {
                            $delivery = null;
                            try {
                                $delivery = EmailCampaignDelivery::create([
                                    'email_template_id' => $template->id,
                                    'user_id' => $user->id,
                                    'tracking_token' => (string) Str::uuid(),
                                    'sent_at' => now(),
                                ]);
                                $user->notify(new CampaignEmailNotification($template, delivery: $delivery));
                                $sent++;
                            } catch (Throwable $exception) {
                                $delivery?->delete();
                                Log::error('Az automatizált e-mail kiküldése sikertelen.', ['template_id' => $template->id, 'user_id' => $user->id, 'exception' => $exception]);
                            }
                        }
                    });
            });
        $this->info("Automatizált levelek elküldve: {$sent}");
        return self::SUCCESS;
    }
}
