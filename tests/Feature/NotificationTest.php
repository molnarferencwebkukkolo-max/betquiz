<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\QuizModerationNotification;
use App\Services\AdminNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_approval_notifies_the_owner_and_appears_in_the_notification_center(): void
    {
        [$admin, $owner, $quiz] = $this->makeModerationContext('pending');

        $this->actingAs($admin)
            ->post(route('admin.quizzes.approve', $quiz))
            ->assertSessionHasNoErrors();

        $notification = $owner->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);
        $this->assertSame('approved', $notification->data['event']);
        $this->assertSame($quiz->title, $notification->data['quiz_title']);

        $this->actingAs($owner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Kvíz jóváhagyva')
            ->assertSee($quiz->title)
            ->assertSee('1 olvasatlan értesítés');
    }

    public function test_new_registration_notifies_only_active_super_admins(): void
    {
        $superAdmin = User::factory()->create(['role' => 'hostadmin']);
        $operationalAdmin = User::factory()->create(['role' => 'useradmin']);
        $inactiveSuperAdmin = User::factory()->create(['role' => 'hostadmin', 'is_active' => false]);
        $registeredUser = User::factory()->create(['role' => 'user', 'email' => 'uj@example.com']);

        event(new Registered($registeredUser));

        $notification = $superAdmin->fresh()->notifications()->sole();
        $this->assertSame('user_registered', $notification->data['event']);
        $this->assertSame($registeredUser->id, $notification->data['user_id']);
        $this->assertStringContainsString('uj@example.com', $notification->data['message']);
        $this->assertSame(0, $operationalAdmin->fresh()->notifications()->count());
        $this->assertSame(0, $inactiveSuperAdmin->fresh()->notifications()->count());
    }

    public function test_pending_quiz_submission_notifies_operational_and_super_admins_once(): void
    {
        $category = Category::create([
            'name' => ['hu' => 'Általános'],
            'slug' => 'uj-kviz-'.uniqid(),
            'is_active' => true,
        ]);
        $owner = User::factory()->create(['role' => 'user', 'points' => 50000]);
        $useradmin = User::factory()->create(['role' => 'useradmin']);
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);

        $this->actingAs($owner)->post(route('my-quizzes.store'), [
            'title' => 'Új jóváhagyandó kvíz',
            'description' => 'Részletes leírás',
            'category_id' => $category->id,
        ])->assertSessionHasNoErrors();

        foreach ([$useradmin, $hostadmin] as $admin) {
            $notification = $admin->fresh()->notifications()->sole();
            $this->assertSame('quiz_submitted', $notification->data['event']);
            $this->assertSame('Új jóváhagyandó kvíz', $notification->data['quiz_title']);
        }

        $this->assertSame(0, $owner->fresh()->notifications()->count());
    }

    public function test_quiz_approval_notifies_super_admin_without_duplicating_repeated_approval(): void
    {
        [, $owner, $quiz] = $this->makeModerationContext('pending');
        $useradmin = User::factory()->create(['role' => 'useradmin']);
        $superAdmin = User::factory()->create(['role' => 'hostadmin']);

        $this->actingAs($useradmin)->post(route('admin.quizzes.approve', $quiz))->assertSessionHasNoErrors();
        $this->actingAs($useradmin)->post(route('admin.quizzes.approve', $quiz))->assertSessionHasNoErrors();

        $adminNotifications = $superAdmin->fresh()->notifications()
            ->where('data->event', 'quiz_approved')
            ->get();
        $this->assertCount(1, $adminNotifications);
        $this->assertSame($quiz->id, $adminNotifications->first()->data['quiz_id']);
        $this->assertSame(0, $useradmin->fresh()->notifications()->count());
        $this->assertSame(1, $owner->fresh()->notifications()->where('data->event', 'approved')->count());
    }

    public function test_admin_notification_delivery_failure_is_logged_without_breaking_the_source_action(): void
    {
        User::factory()->create(['role' => 'hostadmin']);
        $registeredUser = User::factory()->create(['role' => 'user']);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP nem elérhető'));
        $this->app->instance(Dispatcher::class, $dispatcher);
        Log::spy();

        app(AdminNotificationService::class)->userRegistered($registeredUser);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $message, array $context): bool =>
                $message === 'Az adminisztrátori csengőértesítés nem volt kézbesíthető.'
                && $context['recipient_id'] !== null
                && $context['exception'] instanceof \RuntimeException
            );
    }

    public function test_rejection_notification_contains_the_final_admin_reason(): void
    {
        [$admin, $owner, $quiz] = $this->makeModerationContext('pending');

        $this->actingAs($admin)->post(route('admin.quizzes.reject', $quiz), [
            'moderation_reason' => 'A kérdések pontosítást igényelnek.',
        ])->assertSessionHasNoErrors();

        $data = $owner->fresh()->notifications()->firstOrFail()->data;

        $this->assertSame('rejected', $data['event']);
        $this->assertSame('A kérdések pontosítást igényelnek.', $data['reason']);
    }

    public function test_user_can_mark_one_or_all_own_notifications_as_read(): void
    {
        [, $owner, $quiz] = $this->makeModerationContext('approved');
        $owner->notify(new QuizModerationNotification($quiz, 'published'));
        $owner->notify(new QuizModerationNotification($quiz, 'publication_withdrawn', 'Ellenőrzés szükséges.'));
        $notifications = $owner->fresh()->notifications;

        $this->actingAs($owner)
            ->patch(route('notifications.read', $notifications->first()->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $owner->fresh()->unreadNotifications()->count());

        $this->actingAs($owner)
            ->patch(route('notifications.read-all'))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $owner->fresh()->unreadNotifications()->count());
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        [, $owner, $quiz] = $this->makeModerationContext('approved');
        $otherUser = User::factory()->create();
        $owner->notify(new QuizModerationNotification($quiz, 'approved'));
        $notification = $owner->fresh()->notifications()->firstOrFail();

        $this->actingAs($otherUser)
            ->patch(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    private function makeModerationContext(string $status): array
    {
        $category = Category::create([
            'name' => ['hu' => 'Általános'],
            'slug' => 'ertesites-'.uniqid(),
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'hostadmin']);
        $owner = User::factory()->create(['role' => 'user']);
        $quiz = Quiz::create([
            'creator_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Értesítési tesztkvíz',
            'description' => 'Tesztleírás',
            'status' => $status,
        ]);

        return [$admin, $owner, $quiz];
    }
}
