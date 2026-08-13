<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizHelperService
{
    public const TYPES = ['fifty_fifty', 'poker', 'blackjack', 'audience', 'bear'];
    public const FREE_USES = 3;
    public const PRICE = 100;

    /**
     * A használat rögzítése és az esetleges PT-levonás egy tranzakcióban fut,
     * így párhuzamos kattintással sem költhető el kétszer ugyanaz az egyenleg.
     */
    public function consume(User $user, string $helper, int $quizId, int $questionId): array
    {
        abort_unless(in_array($helper, self::TYPES, true), 404);

        return DB::transaction(function () use ($user, $helper, $quizId, $questionId) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $used = DB::table('user_quiz_helper_usages')->where('user_id', $user->id)->where('helper', $helper)->count();
            $wasFree = $used < self::FREE_USES;

            if (! $wasFree && $lockedUser->points < self::PRICE) {
                throw ValidationException::withMessages(['helper' => 'Nincs elég zsetonod ehhez a segítséghez.']);
            }
            if (! $wasFree) {
                $lockedUser->decrement('points', self::PRICE);
            }

            DB::table('user_quiz_helper_usages')->insert([
                'user_id' => $user->id, 'helper' => $helper, 'quiz_id' => $quizId,
                'question_id' => $questionId, 'was_free' => $wasFree,
                'points_spent' => $wasFree ? 0 : self::PRICE, 'created_at' => now(), 'updated_at' => now(),
            ]);

            return ['was_free' => $wasFree, 'remaining_free' => max(0, self::FREE_USES - $used - 1)];
        });
    }

    public function balances(User $user): array
    {
        $counts = DB::table('user_quiz_helper_usages')->where('user_id', $user->id)
            ->selectRaw('helper, COUNT(*) as uses')->groupBy('helper')->pluck('uses', 'helper');

        return collect(self::TYPES)->mapWithKeys(fn ($type) => [$type => [
            'remaining_free' => max(0, self::FREE_USES - (int) ($counts[$type] ?? 0)),
            'price' => self::PRICE,
        ]])->all();
    }
}
