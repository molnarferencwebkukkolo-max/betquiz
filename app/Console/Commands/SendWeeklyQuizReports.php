<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\WeeklyQuizPerformanceNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendWeeklyQuizReports extends Command
{
    protected $signature = 'reports:send-weekly {--from= : Időszak kezdete (YYYY-MM-DD)} {--to= : Időszak vége (YYYY-MM-DD)}';

    protected $description = 'Elküldi a felhasználók előző heti KwizzGo teljesítményjelentését';

    public function handle(): int
    {
        try {
            [$start, $end] = $this->reportPeriod();
        } catch (Throwable) {
            $this->error('A --from és --to értéke YYYY-MM-DD formátumú legyen, a kezdés pedig ne legyen a zárás után.');

            return self::FAILURE;
        }

        $summaries = DB::table('user_answers')
            ->selectRaw('user_id, COUNT(*) as answers, SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers, COUNT(DISTINCT quiz_id) as quiz_count')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $sent = 0;

        User::query()
            ->whereIn('id', $summaries->keys())
            ->where('is_active', true)
            ->where('is_banned', false)
            ->each(function (User $user) use ($summaries, $start, $end, &$sent): void {
                $summary = $summaries->get($user->id);
                $notification = new WeeklyQuizPerformanceNotification(
                    $start,
                    $end,
                    (int) $summary->answers,
                    (int) $summary->correct_answers,
                    (int) $summary->quiz_count,
                );

                // Ha mindkét csatorna ki van kapcsolva, nincs mit sorba állítani.
                if ($notification->via($user) === []) {
                    return;
                }

                $user->notify($notification);
                $sent++;
            });

        $this->info("Heti jelentések elküldve: {$sent} felhasználó ({$start->toDateString()} – {$end->toDateString()}).");

        return self::SUCCESS;
    }

    private function reportPeriod(): array
    {
        // A termék heti határai a magyar naptár szerint értendők akkor is,
        // ha a szerver vagy az alkalmazás alapértelmezett időzónája UTC.
        $timezone = 'Europe/Budapest';

        if ($this->option('from') || $this->option('to')) {
            $start = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->option('from'), $timezone)->startOfDay();
            $end = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->option('to'), $timezone)->endOfDay();

            if ($start->greaterThan($end)) {
                throw new \InvalidArgumentException('Invalid report period.');
            }

            return [$start, $end];
        }

        $start = CarbonImmutable::now($timezone)->startOfWeek()->subWeek();

        return [$start, $start->endOfWeek()];
    }
}
