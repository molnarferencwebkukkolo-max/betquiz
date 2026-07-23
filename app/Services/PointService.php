<?php

namespace App\Services;

use App\Models\User;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

class PointService
{
    /**
     * Tét levonása zárolással és tranzakcióval
     */
    public function deductBet(int $userId, int $amount): void
    {
        DB::transaction(function () use ($userId, $amount) {
            /** @var User $user */
            $user = User::where('id', $userId)->lockForUpdate()->firstOrFail();

            if ($user->points < $amount) {
                throw new \InvalidArgumentException('Nincs elegendő pontod a tét megtételéhez!');
            }

            $user->decrement('points', $amount);
        });
    }

    /**
     * Nyeremény jóváírása a játékosnak
     */
    public function rewardPlayer(int $userId, int $amount): void
    {
        if ($amount <= 0) return;

        DB::transaction(function () use ($userId, $amount) {
            User::where('id', $userId)->lockForUpdate()->increment('points', $amount);
        });
    }

    /**
     * +1 pont a kvízkészítőnek (Kizárólag ha nem a saját kvízét játssza!)
     */
    public function rewardCreator(Quiz $quiz, int $currentUserId): void
    {
        if ($quiz->creator_id && $quiz->creator_id !== $currentUserId) {
            DB::transaction(function () use ($quiz) {
                User::where('id', $quiz->creator_id)->lockForUpdate()->increment('points', 1);
            });
        }
    }
}
