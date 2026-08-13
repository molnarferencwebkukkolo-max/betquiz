<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RollDiceController extends Controller
{
    public function rollDice(Quiz $quiz): RedirectResponse
    {
        $user = Auth::user();
        $game = session('game_session');

        if (! $game || ! ($game['awaiting_dice'] ?? false)) {
            return redirect()->route('quiz.play.screen', $quiz);
        }

        $today = now()->toDateString();
        $rolls = DB::table('user_dice_rolls')->where('user_id', $user->id)->where('roll_date', $today)->first();
        $freeUsed = (int) ($rolls->free_rolls_used ?? 0);

        if ($freeUsed >= 3) {
            if ($user->points < 100) {
                $game['awaiting_dice'] = false;
                $game['dice_result'] = ['roll' => null, 'success' => false, 'reason' => 'no_points'];
                session()->put('game_session', $game);
                return redirect()->route('quiz.play.screen', $quiz);
            }
            $user->decrement('points', 100);
        } else {
            DB::table('user_dice_rolls')->updateOrInsert(
                ['user_id' => $user->id, 'roll_date' => $today],
                ['free_rolls_used' => $freeUsed + 1, 'updated_at' => now()]
            );
        }

        $roll = random_int(1, 6);
        $game['awaiting_dice'] = false;
        $game['awaiting_decision'] = false;

        if ($roll === 6) {
            $pendingWin = $game['pending_dice_win'] ?? null;
            if ($pendingWin) {
                if (($game['game_mode'] ?? 'normal') === 'normal') {
                    $user->increment('points', $pendingWin['round_win']);
                    $game['won_amount'] = $pendingWin['round_win'];
                } else {
                    $game['current_pot'] = round($game['current_pot'] * $pendingWin['total_multiplier']);
                }
            }
            $game['current_step'] = ($game['current_step'] ?? 1) + 1;
            unset($game['current_question_id']);
        }

        unset($game['pending_dice_win']);
        $game['dice_result'] = ['roll' => $roll, 'success' => $roll === 6];
        session()->put('game_session', $game);

        return redirect()->route('quiz.play.screen', $quiz);
    }

    /** A kockaeredmény képernyőjét kizárólag a felhasználó zárhatja le. */
    public function finishDiceResult(Quiz $quiz): RedirectResponse
    {
        $game = session('game_session');
        if (! $game || (int) ($game['quiz_id'] ?? 0) !== $quiz->id || empty($game['dice_result'])) {
            return redirect()->route('quizzes.index');
        }

        session()->forget('game_session');
        return redirect()->route('quizzes.index')->with(
            $game['dice_result']['success'] ? 'success' : 'error',
            $game['dice_result']['success'] ? 'Sikeresen befejezted a játékot.' : 'Sajnos nem sikerült 6-ost dobnod.'
        );
    }
}
