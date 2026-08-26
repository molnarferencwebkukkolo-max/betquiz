<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\NotificationPreference;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\AdminActivityNotification;
use App\Notifications\QuestionReportedNotification;
use App\Notifications\QuizModerationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_displays_relevant_notification_events_with_safe_defaults(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertOk()
            ->assertSee('Értesítések')
            ->assertSee('Kvíz jóváhagyása')
            ->assertSee('Kvíz elutasítása')
            ->assertSee('Hibásnak jelölt kérdés')
            ->assertSee('Heti kvízteljesítmény-jelentés')
            ->assertSee('Reklám- és marketingüzenetek')
            ->assertSee('ajándék PT járhat')
            ->assertDontSee('preferences[marketing][database]', false)
            ->assertSee('preferences[marketing][email]', false)
            ->assertDontSee('Új felhasználói regisztráció')
            ->assertDontSee('Új kvízigény')
            ->assertDontSee('Adminisztrátori kvízjóváhagyás');

        $this->assertTrue($user->wantsNotification('approved', 'database'));
        $this->assertFalse($user->wantsNotification('approved', 'mail'));
        $this->assertFalse($user->wantsNotification('marketing', 'database'));
        $this->assertFalse($user->wantsNotification('marketing', 'mail'));
    }

    public function test_admin_profiles_display_only_their_role_specific_operational_events(): void
    {
        $useradmin = User::factory()->create(['role' => 'useradmin']);
        $this->actingAs($useradmin)->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Új kvízigény')
            ->assertSee('Hibásnak jelölt kérdés')
            ->assertDontSee('Új felhasználói regisztráció')
            ->assertDontSee('Adminisztrátori kvízjóváhagyás');

        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $this->actingAs($hostadmin)->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Új felhasználói regisztráció')
            ->assertSee('Új kvízigény')
            ->assertSee('Adminisztrátori kvízjóváhagyás')
            ->assertSee('Hibásnak jelölt kérdés');
    }

    public function test_marketing_can_only_be_enabled_for_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.notification-preferences'), [
            'preferences' => [
                'marketing' => [
                    'event' => 'marketing',
                    'database' => '1',
                    'email' => '1',
                ],
            ],
        ])->assertSessionHasNoErrors();

        $preference = $user->notificationPreferences()->where('event', 'marketing')->firstOrFail();
        $this->assertFalse($preference->database_enabled);
        $this->assertTrue($preference->email_enabled);
        $this->assertFalse($user->wantsNotification('marketing', 'database'));
        $this->assertTrue($user->wantsNotification('marketing', 'mail'));
    }

    public function test_user_can_save_event_and_channel_specific_preferences(): void
    {
        $user = User::factory()->create();
        $availableEvents = NotificationPreference::eventsFor($user);
        $preferences = collect($availableEvents)
            ->mapWithKeys(fn ($label, $event) => [
                $event => [
                    'event' => $event,
                    'database' => $event === 'approved' ? '1' : null,
                    'email' => $event === 'rejected' ? '1' : null,
                ],
            ])
            ->all();

        $this->actingAs($user)
            ->patch(route('profile.notification-preferences'), compact('preferences'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertCount(count($availableEvents), $user->notificationPreferences()->get());
        $this->assertTrue($user->wantsNotification('approved', 'database'));
        $this->assertFalse($user->wantsNotification('approved', 'mail'));
        $this->assertFalse($user->wantsNotification('rejected', 'database'));
        $this->assertTrue($user->wantsNotification('rejected', 'mail'));
        $this->assertFalse($user->wantsNotification('weekly_report', 'database'));
        $this->assertFalse($user->wantsNotification('weekly_report', 'mail'));
    }

    public function test_disabled_internal_channel_prevents_database_notification(): void
    {
        [$admin, $owner, $quiz] = $this->makeModerationContext();
        $owner->notificationPreferences()->create([
            'event' => 'approved',
            'database_enabled' => false,
            'email_enabled' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.quizzes.approve', $quiz))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $owner->fresh()->notifications()->count());
    }

    public function test_notification_selects_both_channels_and_builds_email_from_preferences(): void
    {
        [, $owner, $quiz] = $this->makeModerationContext();
        $owner->notificationPreferences()->create([
            'event' => 'rejected',
            'database_enabled' => true,
            'email_enabled' => true,
        ]);
        $notification = new QuizModerationNotification(
            $quiz,
            'rejected',
            'A tartalom pontosítást igényel.'
        );

        $this->assertSame(['database', 'mail'], $notification->via($owner));

        $mail = $notification->toMail($owner);
        $this->assertSame('KwizzGo: Kvíz elutasítva', $mail->subject);
        $this->assertContains('Adminisztrátori indok: A tartalom pontosítást igényel.', $mail->introLines);
    }

    public function test_admin_activity_email_channel_and_action_follow_preferences(): void
    {
        $admin = User::factory()->create(['role' => 'hostadmin']);
        $admin->notificationPreferences()->create([
            'event' => 'user_registered',
            'database_enabled' => true,
            'email_enabled' => true,
        ]);
        $notification = new AdminActivityNotification(
            'user_registered',
            'Új regisztráció',
            'Új felhasználó regisztrált: teszt@example.com',
            route('admin.users.index'),
        );

        $this->assertSame(['database', 'mail'], $notification->via($admin));
        $mail = $notification->toMail($admin);
        $this->assertSame('KwizzGo: Új regisztráció', $mail->subject);
        $this->assertSame('Felhasználó megnyitása', $mail->actionText);
    }

    public function test_question_report_email_contains_context_and_respects_disabled_mail(): void
    {
        [$admin, $owner, $quiz] = $this->makeModerationContext();
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'category_id' => $quiz->category_id,
            'difficulty' => 'medium',
            'question_text' => ['hu' => 'Tesztkérdés?'],
            'is_active' => false,
        ]);
        $report = QuestionReport::create([
            'question_id' => $question->id,
            'reporter_id' => User::factory()->create()->id,
            'reason' => 'A megjelölt válasz tényszerűen hibás.',
        ]);
        $notification = new QuestionReportedNotification($report->load('question.quiz'));

        $this->assertSame(['database'], $notification->via($owner));

        $owner->notificationPreferences()->create([
            'event' => 'question_reported',
            'database_enabled' => true,
            'email_enabled' => true,
        ]);
        $this->assertSame(['database', 'mail'], $notification->via($owner));
        $mail = $notification->toMail($owner);
        $this->assertSame('KwizzGo: Hibásnak jelölt kérdés', $mail->subject);
        $this->assertContains('Játékosi hibaleírás: A megjelölt válasz tényszerűen hibás.', $mail->introLines);
    }

    private function makeModerationContext(): array
    {
        $category = Category::create([
            'name' => ['hu' => 'Általános'],
            'slug' => 'preferencia-'.uniqid(),
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'hostadmin']);
        $owner = User::factory()->create(['role' => 'user']);
        $quiz = Quiz::create([
            'creator_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Preferencia tesztkvíz',
            'description' => 'Tesztleírás',
            'status' => 'pending',
        ]);

        return [$admin, $owner, $quiz];
    }
}
