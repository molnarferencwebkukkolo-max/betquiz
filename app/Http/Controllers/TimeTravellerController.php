<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FinishesQuizGames;
use App\Models\Quiz;
use Illuminate\Support\Facades\Auth;

class TimeTravellerController extends Controller
{
    use FinishesQuizGames;

    public function timeTravel(Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $game = session('game_session');

        if (!$game || !($game['awaiting_time_travel'] ?? false)) {
            return redirect()->route('quiz.play.screen', $quiz);
        }

        $freeUsed = $user->lifetime_free_time_travels_used ?? 0;

        if ($freeUsed >= 3) {
            if ($user->points < 100) {
                unset($game['pending_dice_win']);

                return $this->finishGame($quiz, 'failed_dice_no_points');
            }

            $user->decrement('points', 100);
        } else {
            $user->increment('lifetime_free_time_travels_used');
        }

        $game['awaiting_time_travel'] = false;
        unset($game['pending_dice_win']);

        session()->put('game_session', $game);

        $message = ($user->time_travel_theme ?? 'back_to_future') === 'harry_potter'
            ? 'Hermione megpörgette az Időnyerőt. Az óra újraindult a kérdésnél!'
            : 'Emmett Brown segített rajtad: 88 MPH! Az óra újraindult a kérdésnél!';

        return redirect()->route('quiz.play.screen', $quiz)->with('success', $message);
    }
}
