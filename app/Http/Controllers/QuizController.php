<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\User;
use App\Models\Question;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    /**
     * Műszerfal nézet (Főoldal - /dashboard)
     */
    public function dashboard()
    {
        $user = Auth::user();
        $userId = $user->id;

        $baseQuery = Quiz::where('status', 'approved')
            ->where('creator_id', '!=', $userId)
            ->has('questions', '>=', 100)
            ->with(['category', 'creator'])
            ->withCount('questions');

        $featuredQuizzes = (clone $baseQuery)
            ->when(Schema::hasColumn('quizzes', 'is_featured'), fn($q) => $q->where('is_featured', true))
            ->latest()
            ->get();

        $latestQuizzes = (clone $baseQuery)->latest()->take(10)->get();

        $favoriteQuizzes = method_exists($user, 'favorites')
            ? $user->favorites()->with(['category', 'creator'])->withCount('questions')->get()
            : collect();

        $hardestQuizzes = (clone $baseQuery)
            ->withSum('questions as total_answers', 'times_answered')
            ->withSum('questions as total_correct', 'times_correct')
            ->get()
            ->filter(fn($quiz) => $quiz->total_answers > 0)
            ->sortByDesc(function ($quiz) {
                $wrongAnswers = $quiz->total_answers - $quiz->total_correct;
                return $wrongAnswers / $quiz->total_answers;
            })
            ->sortBy(fn($quiz) => $quiz->created_at)
            ->take(10)
            ->values();

        $playedQuizIds = [];
        if (Schema::hasTable('quiz_sessions')) {
            $playedQuizIds = DB::table('quiz_sessions')->where('user_id', $userId)->pluck('quiz_id')->toArray();
        } elseif (Schema::hasTable('user_answers')) {
            $playedQuizIds = DB::table('user_answers')->where('user_id', $userId)->pluck('quiz_id')->toArray();
        }

        $unplayedQuizzes = (clone $baseQuery)
            ->when(!empty($playedQuizIds), fn($q) => $q->whereNotIn('id', $playedQuizIds))
            ->inRandomOrder()
            ->take(10)
            ->get();

        $preferredCategoryId = $user->favorite_category_id ?? null;
        $categoryFavoriteQuizzes = (clone $baseQuery)
            ->when($preferredCategoryId, fn($q) => $q->where('category_id', $preferredCategoryId))
            ->inRandomOrder()
            ->take(10)
            ->get();

        $popularQuizzes = (clone $baseQuery)
            ->withSum('questions as total_answers', 'times_answered')
            ->orderByDesc('total_answers')
            ->take(10)
            ->get();

        $myQuizzes = Quiz::where('creator_id', $userId)->with('category')->latest()->get();

        return view('dashboard', compact(
            'user',
            'featuredQuizzes',
            'latestQuizzes',
            'favoriteQuizzes',
            'hardestQuizzes',
            'unplayedQuizzes',
            'categoryFavoriteQuizzes',
            'popularQuizzes',
            'myQuizzes'
        ));
    }

    /**
     * Katalógus nézet (JÁTÉK menüpont - /quizzes)
     */
    public function showBetForm(Request $request)
    {
        $user = Auth::user();
        $categories = Category::all();

        $quizzesQuery = Quiz::where('status', 'published')
            ->where('creator_id', '!=', $user->id)
            ->where('questions_count', '>=', 100)
            ->with(['category', 'creator'])
            ->withCount('questions');

        if ($request->has('category_id') && $request->category_id) {
            $quizzesQuery->where('category_id', $request->category_id);
        }

        $quizzes = $quizzesQuery->latest()->paginate(12)->withQueryString();

        return view('play.catalog', compact('quizzes', 'categories', 'user'));
    }

    /**
     * Tétbeállító képernyő (/quiz/setup/{quiz})
     */
    public function setupQuizPlay(Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $quiz->load(['category', 'creator']);

        $quizQuestionIds = $quiz->questions()->pluck('id');
        $totalQuestionsCount = $quizQuestionIds->count();

        $answeredCount = 0;
        if (Schema::hasTable('user_answers')) {
            $answeredCount = DB::table('user_answers')
                ->where('user_id', $user->id)
                ->whereIn('question_id', $quizQuestionIds)
                ->distinct('question_id')
                ->count('question_id');
        }

        $remainingQuestionsCount = max(0, $totalQuestionsCount - $answeredCount);

        $viewName = view()->exists('play.setup')
            ? 'play.setup'
            : (view()->exists('quiz.setup') ? 'quiz.setup' : 'quizzes.setup');

        return view($viewName, compact('quiz', 'totalQuestionsCount', 'remainingQuestionsCount'));
    }

    /**
     * 1. Játék indítása (Tét levonása + Session)
     */
    public function startPlay(Request $request, Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'game_mode'      => 'required|in:normal,odds',
            'bet_points'     => 'required|integer|min:10',
            'time_limit'     => 'required|in:60,30,10',
            'difficulty'     => 'required|in:mixed,easy,medium,hard',
            'question_count' => 'nullable|integer|in:10,20,30',
        ]);

        $mode = $validated['game_mode'];
        $bet  = (int) $validated['bet_points'];

        if ($mode === 'normal') {
            if ($bet < 50 || $bet > $user->points) {
                return back()->withErrors(['bet_points' => 'A tét minimum 50 PT és legfeljebb a saját egyenleged lehet!']);
            }
        } else {
            if ($bet < 10 || $bet > 100) {
                return back()->withErrors(['bet_points' => 'Odds játékmódban a tét 10 és 100 PT között lehet!']);
            }
            if ($bet > $user->points) {
                return back()->withErrors(['bet_points' => 'Nincs elegendő pontod a játék indításához!']);
            }
        }

        $user->decrement('points', $bet);

        $timeModifiers = [
            60 => 1.0,
            30 => 1.5,
            10 => 2.0,
        ];

        session()->put('game_session', [
            'quiz_id'        => $quiz->id,
            'game_mode'      => $mode,
            'initial_bet'    => $bet,
            'current_pot'    => $bet,
            'won_amount'     => 0,
            'time_limit'     => (int) $validated['time_limit'],
            'time_modifier'  => $timeModifiers[$validated['time_limit']],
            'difficulty'     => $validated['difficulty'],
            'target_count'   => $mode === 'odds' ? (int) $validated['question_count'] : null,
            'current_step'   => 1,
            'answered_ids'   => [],
            'status'         => 'active',
            'awaiting_dice'  => false,
        ]);

        return redirect()->route('quiz.play.screen', $quiz);
    }

    /**
     * 2. Játék képernyő
     */
    public function playScreen(Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $game = session('game_session');

        if (!$game || $game['quiz_id'] !== $quiz->id || $game['status'] !== 'active') {
            return redirect()->route('quiz.setup', $quiz)->with('error', 'A játékmenet lezárult vagy nem található.');
        }

        $query = $quiz->questions()->whereNotIn('id', $game['answered_ids']);

        if ($game['difficulty'] !== 'mixed') {
            $query->where('difficulty', $game['difficulty']);
        }

        $currentQuestion = $query->inRandomOrder()->first();

        if (!$currentQuestion) {
            return $this->finishGame($quiz, 'out_of_questions');
        }

        // Válaszok betöltése
        if (method_exists($currentQuestion, 'answers')) {
            $currentQuestion->load('answers');
        } elseif (method_exists($currentQuestion, 'options')) {
            $currentQuestion->load('options');
        } else {
            if (Schema::hasTable('answers')) {
                $currentQuestion->answers = DB::table('answers')->where('question_id', $currentQuestion->id)->get();
            } elseif (Schema::hasTable('options')) {
                $currentQuestion->answers = DB::table('options')->where('question_id', $currentQuestion->id)->get();
            }
        }

        // 🎲 MAI INGYENES DOBÁSOK LEKÉRDEZÉSE
        $todayRolls = DB::table('user_dice_rolls')
            ->where('user_id', $user->id)
            ->where('roll_date', now()->toDateString())
            ->first();

        $freeRollsUsed = $todayRolls ? $todayRolls->free_rolls_used : 0;
        $remainingFreeRolls = max(0, 3 - $freeRollsUsed);

        return view('play.game', compact('quiz', 'game', 'currentQuestion', 'remainingFreeRolls'));
    }

    /**
     * 3. Válasz beküldése
     */
    public function submitAnswer(Request $request, Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $game = session('game_session');

        if (!$game || $game['status'] !== 'active' || $game['awaiting_dice']) {
            return redirect()->route('quiz.play.screen', $quiz);
        }

        $request->validate([
            'question_id'     => 'required|exists:questions,id',
            'selected_option' => 'required',
        ]);

        $question = Question::findOrFail($request->question_id);

        $answersList = $question->answers
            ?? $question->options
            ?? DB::table('answers')->where('question_id', $question->id)->get();

        $selectedAnswer = collect($answersList)->first(function ($item) use ($request) {
            $itemId = is_object($item) ? $item->id : ($item['id'] ?? null);
            return (string)$itemId === (string)$request->selected_option;
        });

        $isCorrect = false;
        if ($selectedAnswer) {
            $correctVal = is_object($selectedAnswer) ? $selectedAnswer->is_correct : ($selectedAnswer['is_correct'] ?? false);
            $isCorrect = ($correctVal == true || $correctVal == 1 || $correctVal === 'true');
        }

        // Session frissítése (megválaszolt kérdések eltárolása)
        $game['answered_ids'][] = $question->id;

        // ADATBÁZIS MENTÉS (Garancia, hogy többé ne kapja meg ezt a kérdést)
        try {
            if (Schema::hasTable('user_answers')) {
                DB::table('user_answers')->insert([
                    'user_id'     => $user->id,
                    'quiz_id'     => $quiz->id,
                    'question_id' => $question->id,
                    'is_correct'  => $isCorrect ? 1 : 0,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Ha már be volt szúrva korábban, figyelmen kívül hagyjuk
        }

        // Szorzók kiszámítása nehézség és időkorlát alapján
        $diffMultipliers = [
            'easy'   => 1.3,
            'medium' => 1.5,
            'hard'   => 2.0,
        ];

        $diffKey = strtolower($question->difficulty ?? 'easy');
        $diffMultiplier = $diffMultipliers[$diffKey] ?? 1.3;
        $totalMultiplier = $diffMultiplier * $game['time_modifier'];

        if ($isCorrect) {
            // HELYES VÁLASZ LOGIKA
            if ($game['game_mode'] === 'normal') {
                $roundWin = round($game['initial_bet'] * $totalMultiplier);
                $game['won_amount'] += $roundWin;
            } else {
                $game['current_pot'] = round($game['current_pot'] * $totalMultiplier);
            }

            $game['current_step']++;

            if ($game['game_mode'] === 'odds' && $game['current_step'] > $game['target_count']) {
                session()->put('game_session', $game);
                return $this->finishGame($quiz, 'odds_completed');
            }

            session()->put('game_session', $game);
            return redirect()->route('quiz.play.screen', $quiz)->with('success', 'Helyes válasz! 🎉');

        } else {
            // ROSSZ VÁLASZ LOGIKA (Mázli bónusz előkészítése a dobókockához)
            $game['pending_dice_win'] = [
                'total_multiplier' => $totalMultiplier,
                'round_win'        => round($game['initial_bet'] * $totalMultiplier)
            ];

            $game['awaiting_dice'] = true;
            session()->put('game_session', $game);

            return redirect()->route('quiz.play.screen', $quiz)->with('warning', 'Helytelen válasz! Próbálj 6-ost dobni, hogy mégis megkapd a nyereményt! 🎲');
        }
    }

    /**
     * 4. Dobókocka elgurítása
     */
    /**
     * 4. Dobókocka elgurítása (Rossz válasz mentőöv / Bónusz nyeremény)
     */
    /**
     * 4. Dobókocka elgurítása
     */
    public function rollDice(Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $game = session('game_session');

        if (!$game || !$game['awaiting_dice']) {
            return redirect()->route('quiz.play.screen', $quiz);
        }

        $todayDate = now()->toDateString();
        $todayRolls = DB::table('user_dice_rolls')
            ->where('user_id', $user->id)
            ->where('roll_date', $todayDate)
            ->first();

        $freeRollsUsed = $todayRolls ? $todayRolls->free_rolls_used : 0;

        // EGYENLEG ÉS FIZETŐSSÉG ELLENŐRZÉSE
        if ($freeRollsUsed >= 3) {
            // Elfogytak az ingyenesek -> 100 PT kell
            if ($user->points < 100) {
                // Nincs elég zsetonja a gurításhoz -> Bukta a kört!
                unset($game['pending_dice_win']);
                return $this->finishGame($quiz, 'failed_dice_no_points');
            }
            // Levonjuk a 100 PT-t
            $user->decrement('points', 100);
        } else {
            // Még van ingyenes -> Növeljük a felhasznált ingyenesek számát
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
                if ($game['game_mode'] === 'normal') {
                    $game['won_amount'] += $pendingWin['round_win'];
                } else {
                    $game['current_pot'] = round($game['current_pot'] * $pendingWin['total_multiplier']);
                }
            }

            $game['current_step']++;
            unset($game['pending_dice_win']);

            if ($game['game_mode'] === 'odds' && $game['current_step'] > $game['target_count']) {
                session()->put('game_session', $game);
                return $this->finishGame($quiz, 'odds_completed');
            }

            session()->put('game_session', $game);
            return redirect()->route('quiz.play.screen', $quiz)->with('success', "🎯 6-OST DOBTÁL! Mázli! Jóváírtuk a kör nyereményét!");
        } else {
            unset($game['pending_dice_win']);
            return $this->finishGame($quiz, 'failed_dice', $diceRoll);
        }
    }

    /**
     * 5. Kiszállás
     */
    public function cashout(Quiz $quiz)
    {
        $game = session('game_session');

        if (!$game || $game['status'] !== 'active') {
            return redirect()->route('dashboard');
        }

        return $this->finishGame($quiz, 'user_cashout');
    }

    /**
     * 6. Játék lezárása & Irányítás
     */
    private function finishGame(Quiz $quiz, string $reason, ?int $diceRoll = null)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $game = session('game_session');

        $payout = 0;
        $message = '';

        if ($reason === 'user_cashout') {
            $payout = $game['game_mode'] === 'normal' ? ($game['won_amount'] + $game['initial_bet']) : max(0, $game['current_pot'] - $game['initial_bet']);
            $message = "Sikeresen kiszálltál! Nyereményed: {$payout} PT!";
        } elseif ($reason === 'odds_completed') {
            $payout = $game['current_pot'];
            $message = "🏆 GRATULÁLUNK! Végigvitted az Odds-os játékot! Nyereményed: {$payout} PT!";
        } elseif ($reason === 'out_of_questions') {
            $payout = $game['game_mode'] === 'normal' ? ($game['won_amount'] + $game['initial_bet']) : $game['current_pot'];
            $message = "Elfogyztak a kérdések a kvízben! Nyereményed: {$payout} PT!";
        } elseif ($reason === 'failed_dice') {
            $payout = 0;
            $message = "🎲 A kockán {$diceRoll}-ost dobtál (nem 6-ost). Sajnos a tétet elveszítetted! 😞";
        }

        if ($payout > 0) {
            $user->increment('points', $payout);
        }

        // Töröljük a játék session-t
        session()->forget('game_session');

        // Ha elbukta a kockadobást, felajánljuk az Újrakezdést vagy a Katalógust
        if ($reason === 'failed_dice') {
            return redirect()->route('quiz.setup', $quiz)->with('error', $message . ' Szeretnéd újra megpróbálni ezt a kvízt?');
        }

        return redirect()->route('quizzes.index')->with($payout > 0 ? 'success' : 'error', $message);
    }
}
