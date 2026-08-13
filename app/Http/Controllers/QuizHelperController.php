<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use App\Services\QuizHelperService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizHelperController extends Controller
{
    public function __construct(private QuizHelperService $helpers) {}

    public function use(Request $request, Quiz $quiz, string $helper): RedirectResponse
    {
        $question = $this->activeQuestion($quiz, $request);
        abort_unless(in_array($helper, ['fifty_fifty', 'audience', 'bear', 'poker'], true), 404);
        $this->helpers->consume($request->user(), $helper, $quiz->id, $question->id);
        $game = session('game_session');

        if ($helper === 'fifty_fifty') {
            $game['helper_results']['fifty_fifty'] = $question->options()->where('is_correct', false)->inRandomOrder()->limit(2)->pluck('id')->map(fn ($id) => (int) $id)->all();
        } elseif ($helper === 'audience') {
            $game['helper_results']['audience'] = $this->audiencePercentages($question);
        } elseif ($helper === 'bear') {
            $game['helper_results']['bear'] = (int) $question->options()->where('is_correct', true)->value('id');
        } else {
            $playerWins = random_int(1, 10) <= 8;
            $game['helper_results']['poker'] = $this->pokerResult($playerWins);
            $game['helper_overlay'] = 'poker';
        }

        session()->put('game_session', $game);
        return back()->with('success', 'A segítség aktiválva.');
    }

    public function startBlackjack(Request $request, Quiz $quiz): RedirectResponse
    {
        $question = $this->activeQuestion($quiz, $request);
        $this->helpers->consume($request->user(), 'blackjack', $quiz->id, $question->id);
        $deck = $this->deck(); shuffle($deck);
        $game = session('game_session');
        $player = [array_pop($deck), array_pop($deck)];
        $dealer = [array_pop($deck), array_pop($deck)];
        $game['blackjack'] = ['player' => $player, 'dealer' => $dealer, 'deck' => $deck, 'finished' => false,
            'player_value' => $this->handValue($player), 'dealer_value' => $this->handValue($dealer)];
        $game['helper_overlay'] = 'blackjack';
        session()->put('game_session', $game);
        return back();
    }

    public function blackjackAction(Request $request, Quiz $quiz): RedirectResponse
    {
        $request->validate(['action' => 'required|in:hit,stand']);
        $game = session('game_session');
        abort_unless($game && ($game['helper_overlay'] ?? null) === 'blackjack' && !empty($game['blackjack']), 409);
        $state = $game['blackjack'];

        if ($request->input('action') === 'hit') {
            $state['player'][] = array_pop($state['deck']);
            $state['player_value'] = $this->handValue($state['player']);
            if ($state['player_value'] <= 21) {
                $game['blackjack'] = $state; session()->put('game_session', $game); return back();
            }
        } else {
            while ($this->handValue($state['dealer']) < 17) $state['dealer'][] = array_pop($state['deck']);
        }

        $player = $this->handValue($state['player']); $dealer = $this->handValue($state['dealer']);
        $state['player_value'] = $player; $state['dealer_value'] = $dealer;
        $state['finished'] = true;
        $state['player_won'] = $player <= 21 && ($dealer > 21 || $player >= $dealer);
        $game['blackjack'] = $state; session()->put('game_session', $game);
        return back();
    }

    /**
     * A megkezdett Blackjack feladása az aktuális kérdés elvesztését jelenti.
     * A normál válaszfeldolgozást használjuk, hogy a statisztika és a tét is
     * pontosan úgy változzon, mint bármely más hibás válasznál.
     */
    public function abandonBlackjack(Request $request, Quiz $quiz): RedirectResponse
    {
        $game = session('game_session');
        abort_unless(
            $game
                && ($game['helper_overlay'] ?? null) === 'blackjack'
                && ! empty($game['blackjack'])
                && empty($game['blackjack']['finished']),
            409
        );

        unset($game['helper_overlay'], $game['blackjack']);
        session()->put('game_session', $game);

        return $this->submitHelperAnswer($request, $quiz, false);
    }

    public function resolve(Request $request, Quiz $quiz): RedirectResponse
    {
        $game = session('game_session');
        $overlay = $game['helper_overlay'] ?? null;
        abort_unless(in_array($overlay, ['poker', 'blackjack'], true), 409);
        $won = $overlay === 'poker' ? (bool) $game['helper_results']['poker']['player_won'] : (bool) ($game['blackjack']['player_won'] ?? false);
        abort_if($overlay === 'blackjack' && empty($game['blackjack']['finished']), 409);
        unset($game['helper_overlay'], $game['blackjack']);
        session()->put('game_session', $game);

        return $this->submitHelperAnswer($request, $quiz, $won);
    }

    /** Poker és Blackjack eredményét közös úton rögzíti válaszként. */
    private function submitHelperAnswer(Request $request, Quiz $quiz, bool $won): RedirectResponse
    {
        $question = $this->activeQuestion($quiz, $request);
        $option = $won
            ? $question->options()->where('is_correct', true)->firstOrFail()
            : $question->options()->where('is_correct', false)->firstOrFail();
        $answerRequest = Request::create('', 'POST', ['question_id' => $question->id, 'selected_option' => $option->id]);
        $answerRequest->setUserResolver(fn () => $request->user());

        return app(QuizController::class)->submitAnswer($answerRequest, $quiz);
    }

    private function activeQuestion(Quiz $quiz, Request $request): Question
    {
        $game = session('game_session');
        abort_unless($game && (int) $game['quiz_id'] === $quiz->id && $game['status'] === 'active' && !empty($game['current_question_id']), 409);
        return $quiz->questions()->with('options')->findOrFail((int) $game['current_question_id']);
    }

    private function audiencePercentages(Question $question): array
    {
        $options = $question->options()->get();
        $total = DB::table('user_answers')->where('question_id', $question->id)->count();
        // A régi válasznapló nem tárol opcióazonosítót, ezért csak a helyes/hibás
        // arány rekonstruálható; a hibás szavazatokat egyenletesen osztjuk el.
        if ($total > 0) {
            $correct = DB::table('user_answers')->where('question_id', $question->id)->where('is_correct', true)->count();
            $correctPct = (int) round($correct / $total * 100); $wrong = $options->where('is_correct', false);
            $base = $wrong->count() ? intdiv(100 - $correctPct, $wrong->count()) : 0; $left = 100 - $correctPct - ($base * $wrong->count());
            return $options->mapWithKeys(function ($option) use ($correctPct, $base, &$left) { $pct = $option->is_correct ? $correctPct : $base + ($left-- > 0 ? 1 : 0); return [$option->id => $pct]; })->all();
        }
        $weights = $options->map(fn () => random_int(10, 45)); $sum = $weights->sum(); $result = []; $used = 0;
        foreach ($options as $i => $option) { $pct = $i === $options->count() - 1 ? 100 - $used : (int) round($weights[$i] / $sum * 100); $result[$option->id] = $pct; $used += $pct; }
        return $result;
    }

    private function deck(): array { $deck=[]; foreach (['♠','♥','♦','♣'] as $suit) foreach (range(2,14) as $rank) $deck[]=['rank'=>$rank,'suit'=>$suit]; return $deck; }
    private function handValue(array $hand): int { $value=0;$aces=0;foreach($hand as $card){if($card['rank']===14){$value+=11;$aces++;}else $value+=min(10,$card['rank']);}while($value>21&&$aces--)$value-=10;return $value; }
    private function pokerResult(bool $playerShouldWin): array
    {
        // Valódi, 52 lapos pakliból osztunk, majd a szabályos póker-rangsor
        // szerint addig osztunk újra, amíg a kisorsolt (80/20) eredmény teljesül.
        do {
            $deck = $this->deck(); shuffle($deck);
            $player = array_splice($deck, 0, 5); $dealer = array_splice($deck, 0, 5);
            $playerRank = $this->pokerRank($player); $dealerRank = $this->pokerRank($dealer);
            $comparison = $playerRank <=> $dealerRank;
        } while ($comparison === 0 || ($playerShouldWin && $comparison < 0) || (! $playerShouldWin && $comparison > 0));

        $labels = ['Magas lap', 'Egy pár', 'Két pár', 'Drill', 'Sor', 'Flöss', 'Full house', 'Póker', 'Színsor'];
        return ['player' => $player, 'dealer' => $dealer, 'player_won' => $comparison > 0,
            'player_label' => $labels[$playerRank[0]], 'dealer_label' => $labels[$dealerRank[0]]];
    }

    private function pokerRank(array $hand): array
    {
        $ranks = array_column($hand, 'rank'); rsort($ranks);
        // Az ász az A-2-3-4-5 sorban egyesként is használható.
        $unique = array_values(array_unique($ranks));
        $straightHigh = count($unique) === 5 && ($unique[0] - $unique[4] === 4) ? $unique[0] : ($unique === [14, 5, 4, 3, 2] ? 5 : 0);
        $flush = count(array_unique(array_column($hand, 'suit'))) === 1;
        $counts = array_count_values($ranks); arsort($counts);
        $groups = [];
        foreach ($counts as $rank => $count) $groups[] = [(int) $count, (int) $rank];
        usort($groups, fn ($a, $b) => $b <=> $a);
        $ordered = array_column($groups, 1);

        if ($straightHigh && $flush) return [8, $straightHigh];
        if ($groups[0][0] === 4) return [7, ...$ordered];
        if ($groups[0][0] === 3 && $groups[1][0] === 2) return [6, ...$ordered];
        if ($flush) return [5, ...$ranks];
        if ($straightHigh) return [4, $straightHigh];
        if ($groups[0][0] === 3) return [3, ...$ordered];
        if ($groups[0][0] === 2 && $groups[1][0] === 2) return [2, ...$ordered];
        if ($groups[0][0] === 2) return [1, ...$ordered];
        return [0, ...$ranks];
    }
}
