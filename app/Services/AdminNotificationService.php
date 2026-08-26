<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\User;
use App\Notifications\AdminActivityNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class AdminNotificationService
{
    /** Az új regisztráció minden aktív super adminhoz (`hostadmin`) eljut. */
    public function userRegistered(User $registeredUser): void
    {
        $this->notifyRoles(['hostadmin'], new AdminActivityNotification(
            event: 'user_registered',
            title: 'Új regisztráció',
            message: "Új felhasználó regisztrált: {$registeredUser->email}",
            url: route('admin.users.index', ['search' => $registeredUser->email]),
            context: [
                'user_id' => $registeredUser->id,
                'context_label' => $registeredUser->username ?: $registeredUser->name ?: $registeredUser->email,
            ],
        ));
    }

    /** Az új kvízigényt az operatív adminok és a super adminok is megkapják. */
    public function quizSubmitted(Quiz $quiz): void
    {
        $this->notifyRoles(['useradmin', 'hostadmin'], new AdminActivityNotification(
            event: 'quiz_submitted',
            title: 'Új kvízigény',
            message: 'Egy új kvízkoncepció adminisztrátori jóváhagyásra vár.',
            url: route('my-quizzes.show', ['quiz' => $quiz->id]),
            context: ['quiz_id' => $quiz->id, 'quiz_title' => $quiz->title],
        ));
    }

    /** Minden tényleges jóváhagyásról a super adminok kapnak auditértesítést. */
    public function quizApproved(Quiz $quiz, User $approvedBy): void
    {
        $this->notifyRoles(['hostadmin'], new AdminActivityNotification(
            event: 'quiz_approved',
            title: 'Új kvízjóváhagyás',
            message: ($approvedBy->username ?: $approvedBy->name ?: $approvedBy->email).' jóváhagyta a kvízkoncepciót.',
            url: route('my-quizzes.show', ['quiz' => $quiz->id]),
            context: [
                'quiz_id' => $quiz->id,
                'quiz_title' => $quiz->title,
                'actor_id' => $approvedBy->id,
            ],
        ));
    }

    /**
     * @param list<string> $roles
     */
    private function notifyRoles(array $roles, Notification $notification): void
    {
        User::query()
            ->whereIn('role', $roles)
            ->where('is_active', true)
            ->where('is_banned', false)
            ->get()
            ->unique('id')
            ->each(function (User $recipient) use ($notification): void {
                try {
                    $recipient->notify($notification);
                } catch (\Throwable $exception) {
                    Log::error('Az adminisztrátori csengőértesítés nem volt kézbesíthető.', [
                        'recipient_id' => $recipient->id,
                        'notification' => $notification::class,
                        'exception' => $exception,
                    ]);
                }
            });
    }
}
