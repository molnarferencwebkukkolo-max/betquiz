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
use Illuminate\Support\Facades\Route;

class QuizController extends Controller
{
    /**
     * Műszerfal nézet (Főoldal - /dashboard)
     */
    public function dashboard()
    {
        $user = Auth::user();
        $userId = $user->id;

        // 🟢 1. ELŐSZÖR LEKÉRJÜK A DISLIKE-OLT KVÍZEKET (Adatbázisból vagy relációból)
        $dislikedQuizIds = [];
        if (Schema::hasTable('quiz_user_dislikes')) {
            $dislikedQuizIds = DB::table('quiz_user_dislikes')
                ->where('user_id', $userId)
                ->pluck('quiz_id')
                ->toArray();
        }

        // 🟢 2. AZ ALAP LEKÉRDEZÉSBE AZONNAL BEÉPÍTJÜK A SZŰRÉST
        $baseQuery = Quiz::where('status', 'approved')
            ->where('creator_id', '!=', $userId)
            ->when(!empty($dislikedQuizIds), fn($q) => $q->whereNotIn('quizzes.id', $dislikedQuizIds)) // 🟢 Automatikusan kizárja a dislike-oltakat az összes dobozból!
            ->has('questions', '>=', 100)
            ->with(['category', 'creator'])
            ->withCount('questions');

        // ------------------------------------------------------------------
        // AZ EREDETI DOBOZOK LEKÉRDEZÉSE (NEM VÁLTOZOTT SEMMI!)
        // ------------------------------------------------------------------

        $featuredQuizzes = (clone $baseQuery)
            ->when(Schema::hasColumn('quizzes', 'is_featured'), fn($q) => $q->where('is_featured', true))
            ->latest()
            ->get();

        $latestQuizzes = (clone $baseQuery)->latest()->take(10)->get();

        // Kedvenc kvízek lekérdezése (A kedvenc táblából direktben)
        $favoriteQuizzes = collect();
        if (Schema::hasTable('quiz_user_favorites')) {
            $favIds = DB::table('quiz_user_favorites')->where('user_id', $userId)->pluck('quiz_id')->toArray();
            if (!empty($favIds)) {
                $favoriteQuizzes = Quiz::whereIn('id', $favIds)
                    ->where('status', 'approved')
                    ->with(['category', 'creator'])
                    ->withCount('questions')
                    ->get();
            }
        } elseif (method_exists($user, 'favorites')) {
            $favoriteQuizzes = $user->favorites()->with(['category', 'creator'])->withCount('questions')->get();
        }

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
            $q->where('status', 'approved')
                ->orWhere('status', 'published');
        })
            ->where('creator_id', '!=', $user->id)
            ->has('questions', '>=', 100)
            ->with(['category', 'creator'])
            ->withCount('questions');

        if ($request->filled('category_id')) {
            $quizzesQuery->where('category_id', $request->category_id);
        }

        $quizzes = $quizzesQuery->latest()->paginate(12)->withQueryString();

        return view('play.catalog', compact('quizzes', 'categories', 'user'));
    }

    public function setupQuizPlay(Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $quiz->load(['category', 'creator']);

        $quizQuestionIds = $quiz->questions()->pluck('questions.id')->toArray();
        $totalQuestionsCount = count($quizQuestionIds);

        // Kiszámoljuk a megválaszolt kérdéseket
        $answeredCount = 0;
        if (Schema::hasTable('user_answers') && !empty($quizQuestionIds)) {
            $answeredCount = DB::table('user_answers')
                ->where('user_id', $user->id)
                ->whereIn('question_id', $quizQuestionIds)
                ->where('is_correct', 1)
                ->distinct()
                ->count('question_id');
        }

        $remainingQuestionsCount = max(0, $totalQuestionsCount - $answeredCount);

        // 🟢 ÚJ SZÁMÍTÁSOK A 3 GOMBHOZ
        // app/Http/Controllers/QuizController.php -> setupQuizPlay()

        $isFavorite = DB::table('quiz_user_favorites')
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->exists();

        $isDisliked = DB::table('quiz_user_dislikes')
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->exists();

        $resetCost  = $answeredCount * 20; // 20 PT / megválaszolt kérdés

        $viewName = view()->exists('play.setup')
            ? 'play.setup'
            : (view()->exists('quiz.setup') ? 'quiz.setup' : 'quizzes.setup');

        return view($viewName, compact(
            'quiz',
            'totalQuestionsCount',
            'remainingQuestionsCount',
            'answeredCount',
            'isFavorite',
            'isDisliked',
            'resetCost'
        ));
    }
    /**
     * Kedvencek közé tétel / eltávolítás
     */
    public function toggleFavorite(Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $quizId = (int)$quiz->id;

        // 1. Biztosan töröljük a dislike-ok közül
        DB::table('quiz_user_dislikes')
            ->where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->delete();

        // 2. Megnézzük, kedvenc-e már
        $isFav = DB::table('quiz_user_favorites')
            ->where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->exists();

        if ($isFav) {
            // TÖRLES
            DB::table('quiz_user_favorites')
                ->where('user_id', $user->id)
                ->where('quiz_id', $quizId)
                ->delete();

            $message = 'Kvíz eltávolítva a kedvencek közül.';
        } else {
            // BESZÚRÁS
            DB::table('quiz_user_favorites')->updateOrInsert(
                ['user_id' => $user->id, 'quiz_id' => $quizId],
                ['created_at' => now(), 'updated_at' => now()]
            );

            $message = 'Kvíz hozzáadva a kedvencekhez! ❤️';
        }

        return redirect()->route('quiz.setup', $quizId)->with('success', $message);
    }

    /**
     * Nem tetszik (Dislike) toggle
     */
    public function toggleDislike(Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $quizId = (int)$quiz->id;

        // 1. Biztosan töröljük a kedvencek közül
        DB::table('quiz_user_favorites')
            ->where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->delete();

        // 2. Megnézzük, dislike-olt-e már
        $isDis = DB::table('quiz_user_dislikes')
            ->where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->exists();

        if ($isDis) {
            // TÖRLES (Visszavonás)
            DB::table('quiz_user_dislikes')
                ->where('user_id', $user->id)
                ->where('quiz_id', $quizId)
                ->delete();

            $message = 'Nem tetszik jelölés visszavonva.';
        } else {
            // BESZÚRÁS
            DB::table('quiz_user_dislikes')->updateOrInsert(
                ['user_id' => $user->id, 'quiz_id' => $quizId],
                ['created_at' => now(), 'updated_at' => now()]
            );

            $message = 'Kvíz elrejtve a műszerfalról.';
        }

        return redirect()->route('quiz.setup', $quizId)->with('success', $message);
    }

    /**
     * Kvíz felélesztése (20 PT / megválaszolt kérdés)
     */
    public function resetQuizAnswers(Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $quizQuestionIds = $quiz->questions()->pluck('questions.id')->toArray();

        // Megválaszolt kérdések száma ebben a kvízben
        $answeredCount = DB::table('user_answers')
            ->where('user_id', $user->id)
            ->whereIn('question_id', $quizQuestionIds)
            ->count();

        if ($answeredCount === 0) {
            return back()->with('error', 'Ebben a kvízben még nincs feléleszthető kérdésed!');
        }

        $costPerQuestion = 20;
        $totalCost = $answeredCount * $costPerQuestion;

        if ($user->points < $totalCost) {
            return back()->with('error', "Nincs elegendő pontod a felélesztéshez! Szükséges: {$totalCost} PT, jelenlegi egyenleged: {$user->points} PT.");
        }

        // Pontok levonása és válaszok törlése
        $user->decrement('points', $totalCost);

        DB::table('user_answers')
            ->where('user_id', $user->id)
            ->whereIn('question_id', $quizQuestionIds)
            ->delete();

        return back()->with('success', "⚡ Kvíz sikeresen felélesztve! Lezártunk {$answeredCount} kérdést {$totalCost} PT-ért.");
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

        // 🛡️ SZERVER OLDALI ELLENŐRZÉS: Van-e egyáltalán elég megválaszolatlan kérdés?
        $quizQuestionIds = $quiz->questions()->pluck('questions.id')->toArray();
        $answeredIds = DB::table('user_answers')
            ->where('user_id', $user->id)
            ->whereIn('question_id', $quizQuestionIds)
            ->where('is_correct', 1)
            ->pluck('question_id')
            ->toArray();

        $remainingCount = count($quizQuestionIds) - count($answeredIds);
        $requestedCount = $mode === 'odds' ? ((int)($validated['question_count'] ?? 10)) : 10;

        if ($remainingCount < 10) {
            return back()->withErrors(['game_mode' => 'Ebben a kvízben kevesebb mint 10 megválaszolatlan kérdés maradt, így új játék már nem indítható!']);
        }

        if ($remainingCount < $requestedCount) {
            return back()->withErrors(['question_count' => "Nincs elég megválaszolatlan kérdés! Csak {$remainingCount} kérdés áll rendelkezésre."]);
        }

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

        $targetCount = !empty($validated['question_count']) ? (int) $validated['question_count'] : 10;

        session()->put('game_session', [
            'quiz_id'           => $quiz->id,
            'game_mode'         => $mode,
            'initial_bet'       => $bet,
            'current_pot'       => $mode === 'odds' ? 0 : $bet,
            'won_amount'        => 0,
            'time_limit'        => (int) $validated['time_limit'],
            'time_modifier'     => $timeModifiers[$validated['time_limit']],
            'difficulty'        => $validated['difficulty'],
            'target_count'      => $targetCount,
            'current_step'      => 1,
            'answered_ids'      => [],
            'status'            => 'active',
            'awaiting_dice'     => false,
            'awaiting_decision' => false,
        ]);

        return redirect()->route('quiz.play.screen', $quiz);
    }

    public function playScreen(Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $game = session('game_session');

        if (!$game || $game['quiz_id'] !== $quiz->id || $game['status'] !== 'active') {
            return redirect()->route('quiz.setup', $quiz)->with('error', 'A játékmenet lezárult.');
        }

        // 🟢 DINAMIKUS LÉPTETÉS: Kiszámoljuk a válaszolt kérdések alapján a lépést
        $answeredCount = count($game['answered_ids'] ?? []);
        $game['current_step'] = $answeredCount + 1;
        session()->put('game_session', $game);

        // 🟢 1. LEKÉRJÜK AZ ADATBÁZISBÓL ÉS A SESSIONBŐL IS A MÁR MEGVÁLASZOLT KÉRDÉSEKET
        $dbAnsweredIds = DB::table('user_answers')
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('is_correct', 1)
            ->pluck('question_id')
            ->toArray();

        $sessionAnsweredIds = $game['answered_ids'] ?? [];

        // Összevisszuk a két tömböt, hogy semmilyen duplikáció ne lehessen!
        $allExcludedIds = array_unique(array_merge($dbAnsweredIds, $sessionAnsweredIds));

        // Kérdés lekérdezése a kizárásokkal
        $query = $quiz->questions()->whereNotIn('questions.id', $allExcludedIds);

        if ($game['difficulty'] !== 'mixed') {
            $query->where('difficulty', $game['difficulty']);
        }

        $currentQuestion = $query->inRandomOrder()->first();

        // Ha elfogytak a választható kérdések
        if (!$currentQuestion) {
            return $this->finishGame($quiz, 'out_of_questions');
        }

        // 2. VÁLASZOK BETÖLTÉSE
        if (method_exists($currentQuestion, 'answers')) {
            $currentQuestion->load('answers');
        } elseif (method_exists($currentQuestion, 'options')) {
            $currentQuestion->load('options');
        }

        // 3. NEHÉZSÉGI ÉS IDŐ SZORZÓK MEGÁLLAPÍTÁSA
        $diffMultipliers = [
            'easy'   => 1.2,
            'medium' => 1.5,
            'hard'   => 2.0,
        ];
        $diffKey = strtolower($currentQuestion->difficulty ?? 'medium');
        $difficultyMultiplier = $diffMultipliers[$diffKey] ?? 1.5;

        $timeMultiplier = (float)($game['time_modifier'] ?? 1.0);
        $totalMultiplier = $difficultyMultiplier * $timeMultiplier;

        // 4. NYEREMÉNY SZÁMÍTÁSOK A STATS BARHOZ
        if ($game['game_mode'] === 'normal') {
            $bet = (int)($game['initial_bet'] ?? 50);
            $expectedWin = (int)round($bet * $totalMultiplier);
        } else {
            // 🎲 ODDS MÓD
            $pot = (int)($game['current_pot'] ?? 0);
            $bet = (int)($game['initial_bet'] ?? 10);

            if ($pot === 0) {
                $expectedWin = (int)round($bet * $totalMultiplier);
            } else {
                $expectedWin = (int)round($pot * $totalMultiplier);
            }
        }

        // 5. MAI INGYENES DOBÁSOK LEKÉRDEZÉSE
        $todayRolls = DB::table('user_dice_rolls')
            ->where('user_id', $user->id)
            ->where('roll_date', now()->toDateString())
            ->first();

        $freeRollsUsed = $todayRolls ? $todayRolls->free_rolls_used : 0;
        $remainingFreeRolls = max(0, 3 - $freeRollsUsed);

        return view('play.game', compact(
            'quiz',
            'game',
            'currentQuestion',
            'remainingFreeRolls',
            'difficultyMultiplier',
            'timeMultiplier',
            'totalMultiplier',
            'expectedWin'
        ));
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
        $selectedOptionId = $request->input('selected_option');

        $question = Question::find($questionId);
        if (!$question) {
            return redirect()->route('quiz.play.screen', $quiz)->with('error', 'A kérdés nem található!');
        }

        $options = method_exists($question, 'options') ? $question->options : $question->answers;
        $correctOption = $options->where('is_correct', true)->first();
        $isCorrect = $selectedOptionId && $correctOption && ((int)$selectedOptionId === (int)$correctOption->id);

        if ($isCorrect) {
            // 🟢 HELYES VÁLASZ LOGIKA
            $diffMultipliers = ['easy' => 1.2, 'medium' => 1.5, 'hard' => 2.0];
            $diffKey = strtolower($question->difficulty ?? 'medium');
            $diffMult = $diffMultipliers[$diffKey] ?? 1.5;
            $timeMult = (float)($game['time_modifier'] ?? 1.0);
            $totalMult = $diffMult * $timeMult;

            if ($game['game_mode'] === 'normal') {
                $wonAmount = (int)round($game['initial_bet'] * $totalMult);
                $user->increment('points', $wonAmount);
                $game['won_amount'] = $wonAmount;
            } else {
                // 🎲 ODDS MÓD
                $currentPot = (int)($game['current_pot'] ?? 0);
                $bet = (int)($game['initial_bet'] ?? 10);

                if ($currentPot === 0) {
                    $wonAmount = (int)round($bet * $totalMult);
                    $game['won_amount'] = $wonAmount;
                    $game['current_pot'] = $wonAmount;
                } else {
                    $wonAmount = (int)round($currentPot * $totalMult);
                    $game['won_amount'] = $wonAmount;
                    $game['current_pot'] = $currentPot + $wonAmount;
                }
            }

            // 💾 1. ADATBÁZIS MENTÉS - Elemzés és beszúrás
            DB::table('user_answers')->updateOrInsert(
                [
                    'user_id'     => $user->id,
                    'question_id' => (int)$questionId,
                ],
                [
                    'quiz_id'    => $quiz->id,
                    'is_correct' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // 📌 2. SESSION MENTÉS
            $answered = $game['answered_ids'] ?? [];
            if (!in_array((int)$questionId, $answered, true)) {
                $answered[] = (int)$questionId;
            }
            $game['answered_ids'] = $answered;

            // 🎯 3. LÉPTETÉS ÉS CÉLSZÁM ELLENŐRZÉSE
            $currentStep = count($game['answered_ids']);
            $targetCount = (int)($game['target_count'] ?? 10);

            if ($currentStep >= $targetCount) {
                if ($game['game_mode'] === 'odds') {
                    $finalWin = $game['current_pot'];
                    if ($finalWin > 0) {
                        $user->increment('points', $finalWin);
                    }
                    session()->forget('game_session');

                    return redirect()->route('quizzes.index')->with('success', "🏆 GRATULÁLUNK! Sikeresen teljesítetted mind a {$targetCount} kérdést! A nyereményed: {$finalWin} PT!");
                } else {
                    session()->forget('game_session');
                    return redirect()->route('quizzes.index')->with('success', "🎉 GRATULÁLUNK! Mind a {$targetCount} kérdésre válaszoltál!");
                }
            }

            // Várakozunk a következő gombnyomásra
            $game['awaiting_decision'] = true;

            // 💾 KÖTELEZŐ SESSION MENTÉS
            session()->put('game_session', $game);

            return redirect()->route('quiz.play.screen', $quiz)->with('success', 'Helyes válasz! 🎉');
        } else {
            // 🔴 ROSSZ VÁLASZ VAGY IDŐLEJÁRÁS
            if ($selectedOptionId) {
                $game['awaiting_dice'] = true;
                $game['awaiting_time_travel'] = false;
                session()->put('game_session', $game);

                return redirect()->route('quiz.play.screen', $quiz)->with('error', 'Sajnos a válasz helytelen volt! Dobj 6-ost a megmentésért!');
            } else {
                $game['awaiting_time_travel'] = true;
                $game['awaiting_dice'] = false;
                session()->put('game_session', $game);

                return redirect()->route('quiz.play.screen', $quiz)->with('error', '⏱️ Kifutottál az időből! Használd a Fluxus-Mentőövet!');
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

        if (($game['game_mode'] ?? 'normal') === 'normal') {
            $bet = $game['initial_bet'] ?? 50;

            if ($user->points < $bet) {
                return $this->finishGame($quiz, 'user_cashout');
            }

            $user->decrement('points', $bet);
        }

        // Feloldjuk a döntési állapotot
        $game['awaiting_decision'] = false;
        session()->put('game_session', $game);

        return redirect()->route('quiz.play.screen', $quiz);
    }

    /**
     * 5. Kiszállás (Gomb megnyomása)
     */
    public function cashout(Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $game = session('game_session');

        if (!$game || $game['status'] !== 'active') {
            return redirect()->route('dashboard');
        }

        if ($game['game_mode'] === 'odds') {
            $cashoutAmount = (int)round($game['current_pot'] * 0.20);
            if ($cashoutAmount > 0) {
                $user->increment('points', $cashoutAmount);
            }

            session()->forget('game_session');

            $redirectRoute = Route::has('quizzes.index') ? 'quizzes.index' : 'dashboard';
            return redirect()->route($redirectRoute)->with('success', "🛑 Kiszálltál az Odds játékból! A nyereménybank 20%-át ({$cashoutAmount} PT) jóváírtuk a számládon!");
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
