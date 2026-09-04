<?php

namespace App\Console\Commands;

use App\Notifications\ServerErrorNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendServerErrorAlerts extends Command
{
    protected $signature = 'errors:send-alerts';
    protected $description = 'Send queued server-error alerts from the filesystem spool';

    public function handle(): int
    {
        $recipient = config('error-alerts.recipient');
        if (! config('error-alerts.enabled') || ! is_string($recipient) || $recipient === '') {
            return self::SUCCESS;
        }

        foreach (File::glob(storage_path('app/error-alerts/pending/*.json')) ?: [] as $path) {
            try {
                $alert = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
                Notification::route('mail', $recipient)->notify(new ServerErrorNotification($alert));
                File::delete($path);
            } catch (Throwable $exception) {
                report($exception);
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
