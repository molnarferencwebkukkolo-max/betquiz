<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\WeeklyQuizPerformanceNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WeeklyQuizReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_previous_week_summary_only_to_eligible_users(): void
    {
        Notification::fake();
        CarbonImmutable::setTestNow('2026-08-10 09:00:00');
        [$user, $quiz, $questionIds] = $this->makeContext();
        $inactive = User::factory()->create(['is_active' => false]);

        $this->answer($user, $quiz, $questionIds[0], true, '2026-08-04 12:00:00');
        $this->answer($user, $quiz, $questionIds[1], false, '2026-08-05 12:00:00');
        $this->answer($inactive, $quiz, $questionIds[2], true, '2026-08-06 12:00:00');

        $this->artisan('reports:send-weekly')
            ->expectsOutputToContain('Heti jelentések elküldve: 1 felhasználó')
            ->assertSuccessful();

        Notification::assertSentTo($user, WeeklyQuizPerformanceNotification::class, function ($notification) use ($user) {
            $data = $notification->toArray($user);

            return $data['answers'] === 2
                && $data['correct_answers'] === 1
                && $data['accuracy'] === 50
                && $data['quiz_count'] === 1
                && $data['period_start'] === '2026-08-03'
                && $data['period_end'] === '2026-08-09';
        });
        Notification::assertNotSentTo($inactive, WeeklyQuizPerformanceNotification::class);
    }

    public function test_disabled_weekly_report_channels_prevent_delivery(): void
    {
        Notification::fake();
        [$user, $quiz, $questionIds] = $this->makeContext();
        $user->notificationPreferences()->create([
            'event' => 'weekly_report',
            'database_enabled' => false,
            'email_enabled' => false,
        ]);
        $this->answer($user, $quiz, $questionIds[0], true, '2026-08-04 12:00:00');

        $this->artisan('reports:send-weekly --from=2026-08-03 --to=2026-08-09')
            ->expectsOutputToContain('Heti jelentések elküldve: 0 felhasználó')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_notification_uses_enabled_channels_and_builds_mail(): void
    {
        $user = User::factory()->create();
        $user->notificationPreferences()->create([
            'event' => 'weekly_report',
            'database_enabled' => true,
            'email_enabled' => true,
        ]);
        $notification = new WeeklyQuizPerformanceNotification(
            CarbonImmutable::parse('2026-08-03'),
            CarbonImmutable::parse('2026-08-09'),
            10,
            8,
            2,
        );

        $this->assertSame(['database', 'mail'], $notification->via($user));
        $this->assertSame('KwizzGo: heti kvízteljesítményed', $notification->toMail($user)->subject);
        $this->assertSame(80, $notification->toArray($user)['accuracy']);
    }

    private function makeContext(): array
    {
        $category = Category::create([
            'name' => ['hu' => 'Általános'],
            'slug' => 'heti-riport-'.uniqid(),
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $quiz = Quiz::create([
            'creator_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Heti riport kvíz',
            'status' => 'approved',
        ]);
        $questionIds = [];

        for ($index = 0; $index < 3; $index++) {
            $questionIds[] = DB::table('questions')->insertGetId([
                'quiz_id' => $quiz->id,
                'category_id' => $category->id,
                'difficulty' => 'medium',
                'question_text' => json_encode(['hu' => "Kérdés {$index}"]),
                'is_approved' => true,
                'is_active' => true,
                'times_answered' => 0,
                'times_correct' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [$user, $quiz, $questionIds];
    }

    private function answer(User $user, Quiz $quiz, int $questionId, bool $correct, string $createdAt): void
    {
        DB::table('user_answers')->insert([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'question_id' => $questionId,
            'is_correct' => $correct,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
