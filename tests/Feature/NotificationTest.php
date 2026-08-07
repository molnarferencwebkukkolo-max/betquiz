<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\QuizModerationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
