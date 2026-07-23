<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    /**
     * Bárki játszhat a jóváhagyott kvízzel
     */
    public function play(User $user, Quiz $quiz): bool
    {
        return $quiz->status === 'approved';
    }

    /**
     * Csak a tulajdonos vagy az admin szerkesztheti
     */
    public function update(User $user, Quiz $quiz): bool
    {
        return $user->role === 'admin' || $user->id === $quiz->creator_id;
    }

    /**
     * Kizárólag az admin fogadhatja el
     */
    public function approve(User $user): bool
    {
        return $user->role === 'admin';
    }
}
