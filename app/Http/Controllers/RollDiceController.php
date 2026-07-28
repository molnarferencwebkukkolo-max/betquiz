<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FinishesQuizGames;
use App\Models\Quiz;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RollDiceController extends Controller
{
    use FinishesQuizGames;

    public function rollDice(Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $game = session('game_session');

        if (!$game || !($game['awaiting_dice'] ?? false)) {
            return redirect()->route('quiz.play.screen', $quiz);
        }

        $todayDate = now()->toDateString();
        $todayRolls = DB::table('user_dice_rolls')
            ->where('user_id', $user->id)
            ->where('roll_date', $todayDate)
            ->first();

        $freeRollsUsed = $todayRolls ? $todayRolls->free_rolls_used : 0;

        if ($freeRollsUsed >= 3) {
            if ($user->points < 100) {
                unset($game['pending_dice_win']);

                return $this->finishGame($quiz, 'failed_dice_no_points');
            }

            $user->decrement('points', 100);
        } else {
            DB::table('user_dice_rolls')->updateOrInsert(
                ['user_id' => $user->id, 'roll_date' => $todayDate],
                ['free_rolls_used' => $freeRollsUsed + 1, 'updated_at' => now()]
            );
        }

        $diceRoll = rand(1, 6);
        $game['awaiting_dice'] = false;

        if ($diceRoll === 6) {
            $pendingWin = $game['pending_dice_win'] ?? null;

            if ($pendingWin) {
                if (($game['game_mode'] ?? 'normal') === 'normal') {
                    $user->increment('points', $pendingWin['round_win']);
                    $game['won_amount'] = $pendingWin['round_win'];
                    $game['awaiting_decision'] = true;
                } else {
                    $game['current_pot'] = round($game['current_pot'] * $pendingWin['total_multiplier']);
                }
            }

            $game['current_step'] = ($game['current_step'] ?? 1) + 1;
            unset($game['pending_dice_win']);
            unset($game['current_question_id']);

            if (($game['game_mode'] ?? 'normal') === 'odds' && $game['current_step'] > $game['target_count']) {
                session()->put('game_session', $game);

                return $this->finishGame($quiz, 'odds_completed');
            }

            session()->put('game_session', $game);

            return redirect()->route('quiz.play.screen', $quiz)->with('success', '6-OST DOBTÁL! Mázli! Jóváírtuk a kör nyereményét!');
        }

        unset($game['pending_dice_win']);
        session()->put('game_session', $game);

        return $this->finishGame($quiz, 'failed_dice', $diceRoll);
    }
}
