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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $categories = Category::all();

        $quizzesQuery = Quiz::where(function($q) {
            // Elfogadjuk az 'approved' és 'published' státuszt is!
            $q->where('status', 'approved')
                ->orWhere('status', 'published');
        })
            ->where('creator_id', '!=', $user->id) // Mások kvízei
            ->has('questions', '>=', 100)          // A reláción keresztül nézzük a legalább 100 kérdést!
            ->with(['category', 'creator'])
            ->withCount('questions');

        // Kategória szűrő
        if ($request->filled('category_id')) {
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
            'awaiting_decision' => false,
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

        $query = $quiz->questions()->whereNotIn('id', $game['answered_ids'] ?? []);

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

        if (!$game || $game['status'] !== 'active') {
            return redirect()->route('quiz.setup', $quiz);
        }

        $questionId = $request->input('question_id');
        $selectedOptionId = $request->input('selected_option'); // Ha lejárt az idő, ez NULL!

        $question = Question::find($questionId);
        if (!$question) {
            return redirect()->route('quiz.play.screen', $quiz)->with('error', 'A kérdés nem található!');
        }

        $options = collect();
        if (method_exists($question, 'options')) {
            $options = $question->options;
        } elseif (method_exists($question, 'answers')) {
            $options = $question->answers;
        }

        $correctOption = $options->where('is_correct', true)->first();
        $isCorrect = $selectedOptionId && $correctOption && ((int)$selectedOptionId === (int)$correctOption->id);

        if ($isCorrect) {
            // --- 🟢 HELYES VÁLASZ ---
            $bet = $game['initial_bet'] ?? 50;
            $multiplier = ($game['time_modifier'] ?? 1.0) * 1.5;
            $wonAmount = (int) round($bet * $multiplier);

            if (($game['game_mode'] ?? 'normal') === 'normal') {
                $user->increment('points', $wonAmount);
                $game['won_amount'] = $wonAmount;
                $game['awaiting_decision'] = true;
            } else {
                $game['current_pot'] = ($game['current_pot'] ?? 0) + $wonAmount;
                $game['won_amount'] = $wonAmount;
            }

            if (!isset($game['answered_ids'])) {
                $game['answered_ids'] = [];
            }
            $game['answered_ids'][] = $questionId;
            $game['current_step'] = ($game['current_step'] ?? 1) + 1;

            session()->put('game_session', $game);

            return redirect()->route('quiz.play.screen', $quiz)->with('success', 'Helyes válasz! 🎉');

        } else {
            // Megőrizzük a nyerési bónuszt mentőöv esetére
            $bet = $game['initial_bet'] ?? 50;
            $game['pending_dice_win'] = [
                'round_win' => (int) round($bet * ($game['time_modifier'] ?? 1.0)),
                'total_multiplier' => 1.5
            ];

            if ($selectedOptionId) {
                // 🔴 ROSSZ VÁLASZT ADOTT ➔ KOCKADOBÁS MENTŐÖV
                $game['awaiting_dice'] = true;
                $game['awaiting_time_travel'] = false;
                session()->put('game_session', $game);

                return redirect()->route('quiz.play.screen', $quiz)->with('error', 'Sajnos a válasz helytelen volt! Dobj 6-ost a megmentésért!');
            } else {
                // ⏱️ LEJÁRT AZ IDŐ ➔ DOKI IDŐUGRÁS MENTŐÖV (88 MPH)
                $game['awaiting_time_travel'] = true;
                $game['awaiting_dice'] = false;
                session()->put('game_session', $game);

                return redirect()->route('quiz.play.screen', $quiz)->with('error', '⏱️ Kifutottál az időből! Használd a Fluxus-Mentőövet az idő visszapörgetéséhez!');
            }
        }
    }

    /**
     * 4. Dobókocka elgurítása
     */
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

        // EGYENLEG ÉS FIZETŐSSÉG ELLENŐRZÉSE
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

            if (($game['game_mode'] ?? 'normal') === 'odds' && $game['current_step'] > $game['target_count']) {
                session()->put('game_session', $game);
                return $this->finishGame($quiz, 'odds_completed');
            }

            session()->put('game_session', $game);
            return redirect()->route('quiz.play.screen', $quiz)->with('success', "🎯 6-OST DOBTÁL! Mázli! Jóváírtuk a kör nyereményét!");
        } else {
            // 🛑 NEM 6-OST DOBOTT (BUKTA A MENTŐÖVET)
            unset($game['pending_dice_win']);
            session()->put('game_session', $game);
            return $this->finishGame($quiz, 'failed_dice', $diceRoll);
        }
    }

    /**
     * ⚡ Doki Időugrása (Időlejárás mentőöv)
     */
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
            // 100 PT kell
            if ($user->points < 100) {
                unset($game['pending_dice_win']);
                return $this->finishGame($quiz, 'failed_dice_no_points');
            }
            $user->decrement('points', 100);
        } else {
            // Levonunk egyet az örök 3 ingyenesből
            $user->increment('lifetime_free_time_travels_used');
        }

        // ⚡ Visszaállítjuk az órát a kérdésnél!
        $game['awaiting_time_travel'] = false;
        unset($game['pending_dice_win']);

        session()->put('game_session', $game);

        return redirect()->route('quiz.play.screen', $quiz)->with('success', '⚡ 88 MPH! Sikeres időugrás! Az óra újraindult a kérdésnél!');
    }

    /**
     * Normál mód: A játékos a következő kérdés gombra kattintott
     */
    public function nextQuestion(Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $game = session('game_session');

        if (!$game || $game['status'] !== 'active') {
            return redirect()->route('quiz.setup', $quiz);
        }

        // 🎯 HA NORMÁL JÁTÉKMÓDBAN VAGYUNK: Levonjuk az új kérdés tétjét!
        if (($game['game_mode'] ?? 'normal') === 'normal') {
            $bet = $game['initial_bet'] ?? 50;

            // Ellenőrizzük, hogy van-e elegendő pontja a következő körre
            if ($user->points < $bet) {
                // Ha nincs elég pontja, lezárjuk a játékot, de a korábbi nyereményei megmaradnak!
                return $this->finishGame($quiz, 'user_cashout');
            }

            // Levonjuk a tétet az egyenlegéből
            $user->decrement('points', $bet);
        }

        // Feloldjuk a döntési állapotot, jöhet a következő kérdés!
        $game['awaiting_decision'] = false;
        session()->put('game_session', $game);

        return redirect()->route('quiz.play.screen', $quiz);
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

        if (($game['game_mode'] ?? 'normal') === 'normal') {
            if ($reason === 'user_cashout') {
                $message = "Sikeresen befejezted a játékot! A megnyert zsetonjaid már a számládon vannak. 🎉";
            } elseif ($reason === 'failed_dice') {
                $message = "🎲 A kockán {$diceRoll}-ost dobtál (nem 6-ost). Az utolsó tétet elvesztetted, de a korábban megnyert zsetonjaid megmaradtak!";
            } elseif ($reason === 'failed_dice_no_points') {
                $message = "Elfogytak az ingyenes dobásaid, és nem volt 100 PT-d a fizetős gurításhoz! A játék véget ért.";
            } elseif ($reason === 'out_of_questions') {
                $message = "Elfogyztak a kérdések ebben a kvízben! Szép teljesítmény! 🏆";
            } else {
                $message = "A játék lezárult.";
            }
        } else {
            if ($reason === 'odds_completed' || $reason === 'out_of_questions') {
                $payout = $game['current_pot'] ?? 0;
                if ($payout > 0) {
                    $user->increment('points', $payout);
                }
                $message = "🏆 GRATULÁLUNK! Végigvitted az Odds-os játékot! Nyereményed: {$payout} PT!";
            } elseif ($reason === 'failed_dice' || $reason === 'failed_dice_no_points') {
                $message = "🎲 Nem sikerült a kockadobás, így elveszítetted az Odds potot! 😞";
            } elseif ($reason === 'user_cashout') {
                $message = "Kiszálltál az Odds játékból.";
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
