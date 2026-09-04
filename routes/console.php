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

// A gyakori futás miatt a napban megadott kampányok röviddel az esedékesség
// után kimennek; az egyedi kézbesítési rekord kizárja a dupla levelet.
Schedule::command('emails:send-scheduled-campaigns')
    ->everyFifteenMinutes()
    ->timezone('Europe/Budapest')
    ->withoutOverlapping();

// A webkérések által fájlba tett 500-as hibákat külön PHP folyamat postázza.
Schedule::command('errors:send-alerts')
    ->everyMinute()
    ->withoutOverlapping();
