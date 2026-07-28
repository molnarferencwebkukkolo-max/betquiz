<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Quiz;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

trait FinishesQuizGames
{
    protected function finishGame(Quiz $quiz, string $reason, ?int $diceRoll = null)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $game = session('game_session');

        $payout = 0;
        $message = '';

        if (($game['game_mode'] ?? 'normal') === 'normal') {
            if ($reason === 'user_cashout') {
                $message = 'Sikeresen befejezted a játékot! A megnyert zsetonjaid már a számládon vannak.';
            } elseif ($reason === 'failed_dice') {
                $message = "A kockán {$diceRoll}-ost dobtál (nem 6-ost). Az utolsó tétet elvesztetted, de a korábban megnyert zsetonjaid megmaradtak!";
            } elseif ($reason === 'failed_dice_no_points') {
                $message = 'Elfogytak az ingyenes dobásaid, és nem volt 100 PT-d a fizetős gurításhoz! A játék véget ért.';
            } elseif ($reason === 'out_of_questions') {
                $message = 'Elfogytak a kérdések ebben a kvízben! Szép teljesítmény!';
            } else {
                $message = 'A játék lezárult.';
            }
        } else {
            if ($reason === 'odds_completed' || $reason === 'out_of_questions') {
                $payout = $game['current_pot'] ?? 0;
                if ($payout > 0) {
                    $user->increment('points', $payout);
                }
                $message = "GRATULÁLUNK! Végigvitted az Odds-os játékot! Nyereményed: {$payout} PT!";
            } elseif ($reason === 'failed_dice' || $reason === 'failed_dice_no_points') {
                $message = 'Nem sikerült a kockadobás, így elveszítetted az Odds potot!';
            } elseif ($reason === 'user_cashout') {
                $message = 'Kiszálltál az Odds játékból.';
            }
        }

        session()->forget('game_session');

        if (in_array($reason, ['failed_dice', 'failed_dice_no_points'])) {
            return redirect()->route('quiz.setup', $quiz)->with('error', $message);
        }

        $redirectRoute = Route::has('quizzes.index') ? 'quizzes.index' : 'dashboard';

        return redirect()->route($redirectRoute)->with('success', $message);
    }
}
