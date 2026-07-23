<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\User;
use App\Models\Category;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuizController extends Controller
{
    /**
     * Műszerfal nézet
     */
    public function dashboard()
    {
        $user = Auth::user();

        $featuredQuizzes = Quiz::with(['category', 'creator'])
            ->withCount('questions')
            ->where('status', 'approved')
            ->latest()
            ->take(4)
            ->get();

        $myQuizzes = Quiz::withCount('questions')
            ->where('creator_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact('featuredQuizzes', 'myQuizzes', 'user'));
    }

    /**
     * Katalógus nézet
     */
    public function showBetForm(Request $request)
    {
        $user = Auth::user();
        $categories = Category::all();

        $query = Quiz::with(['category', 'creator'])
            ->withCount('questions')
            ->where('status', 'approved');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        $quizzes = $query->latest()->paginate(12)->withQueryString();

        return view('quiz.bet', compact('categories', 'quizzes', 'user'));
    }

    /**
     * Tétbeállító képernyő
     */
    public function setupQuizPlay(Quiz $quiz)
    {
        $user = Auth::user();

        if ($quiz->status !== 'approved') {
            return redirect()->route('dashboard')->with('error', 'Ez a kvíz jelenleg nem elérhető.');
        }

        return view('quiz.show', compact('quiz', 'user'));
    }

    /**
     * JÁTÉKINDÍTÁS: startQuizPlay
     */
    public function startQuizPlay(Request $request, Quiz $quiz)
    {
        $request->validate([
            'mode' => 'required|in:bet,odds',
            'question_count' => 'nullable|integer|in:5,10,15,20',
            'difficulty' => 'required|in:easy,medium,hard',
            'bet_amount' => 'required|integer|min:100',
        ]);

        $betAmount = (int) $request->bet_amount;
        $requestedQuestionCount = $request->mode === 'odds'
            ? (int) ($request->question_count ?? 5)
            : $quiz->questions()->count();

        // 1. Kérdésszám ellenőrzése (Nincs pontlevonás, ha kevés a kérdés)
        $availableQuestionsCount = $quiz->questions()->count();
        if ($availableQuestionsCount < $requestedQuestionCount || $availableQuestionsCount === 0) {
            return back()->withErrors([
                'question_count' => "A kvíz nem tartalmaz elegendő kérdést! Elérhető: {$availableQuestionsCount} db."
            ]);
        }

        // 2. Pontok levonása tranzakcióban és zárolással
        try {
            DB::transaction(function () use ($betAmount) {
                /** @var User $freshUser */
                $freshUser = User::where('id', Auth::id())->lockForUpdate()->first();

                if ($freshUser->points < $betAmount) {
                    throw new \Exception('Nincs elegendő pontod a tét megtételéhez!');
                }

                $freshUser->decrement('points', $betAmount);
            });
        } catch (\Exception $e) {
            return back()->withErrors(['bet_amount' => $e->getMessage()]);
        }

        // 3. Nehézségi szorzók
        $multipliers = [
            'easy'   => 1.3,
            'medium' => 1.5,
            'hard'   => 2.0,
        ];

        $difficulty = $request->difficulty;
        $multiplier = $multipliers[$difficulty] ?? 1.5;

        // 4. KÉRDÉSEK KIVÁLASZTÁSA ÉS KISORSOLÁSA (ID-k mentése a sessionbe!)
        $questionsQuery = $quiz->questions();
        if (Schema::hasColumn('questions', 'difficulty')) {
            $questionsQuery->where('difficulty', $difficulty);
        }

        $questionIds = $questionsQuery->inRandomOrder()->take($requestedQuestionCount)->pluck('id')->toArray();

        // Fallback, ha nehézség alapján nem volt elég kérdés
        if (count($questionIds) < $requestedQuestionCount) {
            $questionIds = $quiz->questions()->inRandomOrder()->take($requestedQuestionCount)->pluck('id')->toArray();
        }

        // 5. EGYSÉGES SESSION RÖGZÍTÉSE
        session([
            'quiz_session' => [
                'quiz_id' => $quiz->id,
                'game_mode' => $request->mode,
                'difficulty' => $difficulty,
                'question_ids' => $questionIds,
                'answered_question_ids' => [], // <-- ITT INICIALIZÁLJUK ÜRES TÖMBKÉNT!
                'total_questions' => count($questionIds),
                'multiplier' => $multiplier,
                'bet_amount' => $betAmount,
                'bet_per_question' => round($betAmount / max(count($questionIds), 1)),
                'current_index' => 0,
                'correct_answers' => 0,
                'total_won' => 0,
                'current_pot' => $betAmount,
                'failed' => false,
            ]
        ]);

        return redirect()->route('quiz.play', $quiz->id);
    }

    /**
     * Tényleges játék képernyő (play.blade.php)
     */
    public function play(Quiz $quiz)
    {
        $session = session('quiz_session');

        if (!$session || $session['quiz_id'] !== $quiz->id) {
            return redirect()->route('quiz.setup', $quiz->id)->with('error', 'Először állítsd be a tétet!');
        }

        $user = Auth::user();

        // Kizárólag a munkamenetben kisorsolt kérdéseket töltjük be!
        $questions = Question::with(['options', 'category'])
            ->whereIn('id', $session['question_ids'])
            ->get()
            ->sortBy(function ($model) use ($session) {
                return array_search($model->id, $session['question_ids']);
            })
            ->values();

        return view('quiz.play', compact('quiz', 'questions', 'session', 'user'));
    }

    /**
     * Válaszfeldolgozó metódus (JSON válasszal a play.blade AJAX kéréséhez)
     */
    public function answer(Request $request)
    {
        $request->validate([
            'option_id' => 'required|exists:options,id',
        ]);

        $quizSession = session('quiz_session');
        if (!$quizSession) {
            return response()->json(['error' => 'Nincs aktív játékmenet.'], 400);
        }

        $currentIndex = $quizSession['current_index'] ?? 0;
        $questionIds = $quizSession['question_ids'] ?? [];

        if (!isset($questionIds[$currentIndex])) {
            return response()->json(['error' => 'Nincs több kérdés ebben a játékmenetben!'], 400);
        }

        // Aktuális kérdés betöltése az ID alapján
        $currentQuestionId = $questionIds[$currentIndex];
        $question = Question::with('options')->find($currentQuestionId);

        if (!$question) {
            return response()->json(['error' => 'A kérdés nem található!'], 444);
        }

        // Kiválasztott opció ellenőrzése
        $selectedOption = $question->options->firstWhere('id', $request->option_id);

        if (!$selectedOption) {
            return response()->json(['error' => 'Ez a válasz nem ehhez a kérdéshez tartozik!'], 422);
        }

        $isCorrect = (bool) $selectedOption->is_correct;
        $gameMode = $quizSession['game_mode'];
        $reward = 0;
        $quizModel = Quiz::find($quizSession['quiz_id']);

        // Tranzakció és sorzárolás
        DB::transaction(function () use ($question, $quizModel, $isCorrect, $gameMode, &$quizSession, &$reward, $currentIndex) {

            $question->increment('times_answered');
            if ($isCorrect) {
                $question->increment('times_correct');
            }

            // 🎯 KÉSZÍTŐI PONT JÓVÁÍRÁSA - SZIGORÍTOTT VÉDELEM
            $currentUserId = Auth::id();
            $alreadyAnsweredIds = $quizSession['answered_question_ids'] ?? [];

            // Csak akkor kap pontot a készítő, ha:
            // 1. Létezik a creator_id
            // 2. A játékos NEM A SAJÁT KVÍZÉT játssza ($currentUserId !== $quizModel->creator_id)
            // 3. Ezt a kérdést a játékos ebben a sessionben MÉG NEM válaszolta meg!
            if (
                $quizModel->creator_id &&
                $currentUserId !== $quizModel->creator_id &&
                !in_array($question->id, $alreadyAnsweredIds)
            ) {
                User::where('id', $quizModel->creator_id)->lockForUpdate()->increment('points', 1);
            }

            // Elmentjük a megválaszolt kérdés ID-ját a sessionbe
            $quizSession['answered_question_ids'][] = $question->id;

            // ... PONTOK KISZÁMÍTÁSA JÁTÉKMÓD SZERINT (bet / odds) ...
        });

        // Léptetjük a szerveroldali indexet
        $quizSession['current_index']++;
        session(['quiz_session' => $quizSession]);

        $correctOption = $question->options->firstWhere('is_correct', true);

        // JSON VÁLASZ A BROWSEREK / AJAX KÉRÉSEK FELE!
        return response()->json([
            'is_correct' => $isCorrect,
            'correct_option_id' => $correctOption ? $correctOption->id : null,
            'reward' => $reward,
            'current_index' => $quizSession['current_index'],
            'total_questions' => $quizSession['total_questions'],
            'is_last_question' => $quizSession['current_index'] >= $quizSession['total_questions'],
        ]);
    }
}
