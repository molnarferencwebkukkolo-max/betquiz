<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Az előző teljes hét eredményeit hétfő reggel küldjük ki.
Schedule::command('reports:send-weekly')
    ->weeklyOn(1, '08:00')
    ->timezone('Europe/Budapest')
    ->withoutOverlapping();
