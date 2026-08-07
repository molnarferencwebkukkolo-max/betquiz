<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\NotificationPreference;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\QuizModerationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_displays_all_notification_events_with_safe_defaults(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertOk()
            ->assertSee('Értesítések')
            ->assertSee('Kvíz jóváhagyása')
            ->assertSee('Kvíz elutasítása')
            ->assertSee('Heti kvízteljesítmény-jelentés');

        $this->assertTrue($user->wantsNotification('approved', 'database'));
        $this->assertFalse($user->wantsNotification('approved', 'mail'));
    }

    public function test_user_can_save_event_and_channel_specific_preferences(): void
    {
        $user = User::factory()->create();
        $preferences = collect(NotificationPreference::EVENTS)
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

        $this->assertCount(count(NotificationPreference::EVENTS), $user->notificationPreferences()->get());
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
