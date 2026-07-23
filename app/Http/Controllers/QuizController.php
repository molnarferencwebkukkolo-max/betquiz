<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    /**
     * Műszerfal / Kezdőlap
     */
    public function dashboard()
    {
        $user = Auth::user();

        // 1. Kiemelt / Legújabb publikus kvízek
        $featuredQuizzes = \App\Models\Quiz::with(['category', 'creator'])
            ->withCount('questions')
            ->where('status', 'approved')
            ->latest()
            ->take(4)
            ->get();

        // 2. Saját kvízek
        $myQuizzes = \App\Models\Quiz::withCount('questions')
            ->where('creator_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // 3. 🛡️ Bírálatra váró kvízek (Csak ha Admin a felhasznaló)
        $pendingQuizzes = collect();
        if ($user->isUseradmin()) {
            $pendingQuizzes = \App\Models\Quiz::with(['category', 'creator'])
                ->withCount('questions')
                ->where('status', 'pending')
                ->latest()
                ->get();
        }

        return view('dashboard', compact('user', 'featuredQuizzes', 'myQuizzes', 'pendingQuizzes'));
    }

    /**
     * Játék indítása vagy Kvíz Választó Katalógus
     */
    public function showBetForm(Request $request)
    {
        $user = Auth::user();

        // 🎯 1. HA VAN QUIZ_ID A KÉRÉSBEN: EGYBŐL INDÍTJUK A JÁTÉKOT!
        if ($request->filled('quiz_id')) {
            $quiz = \App\Models\Quiz::with('questions')->find($request->quiz_id);

            // Ha nem létezik a kvíz vagy nem approved
            if (!$quiz || $quiz->status !== 'approved') {
                return redirect()->route('dashboard')->with('error', 'A kiválasztott kvíz nem található vagy nem elérhető.');
            }

            // Ha nincsenek hozzá kérdések
            if ($quiz->questions->isEmpty()) {
                return redirect()->route('dashboard')->with('error', 'Ebben a kvízben még nincsenek kérdések!');
            }

            // 🚀 EGYBŐL ÁTADJUK A PLAY NÉZETNEK!
            return view('quiz.play', [
                'quiz' => $quiz,
                'user' => $user,
                'questions' => $quiz->questions,
            ]);
        }

        // 🔍 2. HA NINCS QUIZ_ID: KATALÓGUS / SZŰRŐ NÉZET
        $categories = \App\Models\Category::all();

        $query = \App\Models\Quiz::with(['category', 'creator'])
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
     * 1. Tétbeállító képernyő megjelenítése
     */
    public function setupQuizPlay(Quiz $quiz)
    {
        $user = Auth::user();

        if ($quiz->status !== 'approved') {
            return redirect()->route('dashboard')->with('error', 'Ez a kvíz jelenleg nem elérhető.');
        }

        // Visszaadjuk a tétbeállító nézetet
        return view('quiz.show', compact('quiz', 'user'));
    }

    /**
     * Tét levonása, kérdésszám és játékmód beállítása
     */
    public function startQuizPlay(Request $request, Quiz $quiz)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Validáció: mód, kérdésszám, nehézség és tét
        $request->validate([
            'mode' => 'required|in:bet,odds',
            'question_count' => 'nullable|integer|min:1|max:20',
            'difficulty' => 'required|in:easy,medium,hard',
            'bet_amount' => 'required|integer|min:100|max:' . max($user->points, 100),
        ], [
            'bet_amount.max' => 'Nincs elegendő pontod ehhez a téthez!',
        ]);

        $betAmount = (int) $request->bet_amount;

        if ($user->points < $betAmount) {
            return back()->withErrors(['bet_amount' => 'Nincs elegendő pontod!']);
        }

        // Alap nehézségi szorzók
        $baseMultipliers = [
            'easy'   => 1.3,
            'medium' => 1.5,
            'hard'   => 2.0,
        ];

        $difficulty = $request->difficulty;
        $baseMultiplier = $baseMultipliers[$difficulty] ?? 1.5;
        $questionCount = (int) ($request->question_count ?? 5);

        // HA ODDS MÓD: A szorzó skálázódik a válaszolandó kérdések számával
        if ($request->mode === 'odds') {
            // Kombinált odds számítás (például kérdésszám alapján növekvő eredő szorzó)
            $finalMultiplier = round(pow($baseMultiplier, $questionCount / 3), 2);
        } else {
            // FIX TÉTES MÓD
            $finalMultiplier = $baseMultiplier;
        }

        // Tét levonása
        $user->decrement('points', $betAmount);

        // Munkamenet elmentése a játékhoz
        session([
            'quiz_game' => [
                'quiz_id' => $quiz->id,
                'mode' => $request->mode,
                'difficulty' => $difficulty,
                'question_count' => $questionCount,
                'multiplier' => $finalMultiplier,
                'bet_amount' => $betAmount,
                'potential_win' => (int) ($betAmount * $finalMultiplier),
                'score' => 0,
            ]
        ]);

        return redirect()->route('quiz.bet', ['quiz_id' => $quiz->id]);
    }

    /**
     * Kvízjáték indítása és munkamenet (session) felépítése
     */
    public function start(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'game_mode' => 'required|in:per_question,odds',
            'difficulty' => 'required|in:easy,medium,hard',
            'category_id' => 'nullable',
        ]);

        $gameMode = $request->game_mode;
        $difficulty = $request->difficulty;

        // Nehézségi szorzók
        $multipliers = [
            'easy' => 1.3,
            'medium' => 1.5,
            'hard' => 2.0,
        ];
        $multiplier = $multipliers[$difficulty];

        if ($gameMode === 'per_question') {
            // 1) KÉRDÉSENKÉNTI TÉT
            $request->validate([
                'question_count' => 'required|integer|min:1|max:10',
                'bet_per_question' => 'required|integer|min:10',
            ]);

            $questionCount = (int) $request->question_count;
            $betPerQuestion = (int) $request->bet_per_question;
            $totalBet = $questionCount * $betPerQuestion;

            if ($user->points < $totalBet) {
                return back()->withErrors(['error' => "Nincs elég pontod! Ennyi kérdéshez összesen {$totalBet} PT szükséges."]);
            }

            $user->decrement('points', $totalBet);

            $query = Question::query();
            if ($request->filled('category_id') && $request->category_id !== 'all') {
                $query->where('category_id', $request->category_id);
            }
            if ($difficulty !== 'all') {
                $query->where('difficulty', $difficulty);
            }

            $questions = $query->inRandomOrder()->take($questionCount)->pluck('id')->toArray();

            if (count($questions) < $questionCount) {
                return back()->withErrors(['error' => 'Sajnos nincs elegendő kérdés ebben a szűrésben! Próbálj kevesebb kérdést vagy más beállítást választani.']);
            }

            session([
                'quiz_session' => [
                    'game_mode' => 'per_question',
                    'difficulty' => $difficulty,
                    'multiplier' => $multiplier,
                    'question_ids' => $questions,
                    'current_index' => 0,
                    'bet_per_question' => $betPerQuestion,
                    'total_questions' => $questionCount,
                    'correct_answers' => 0,
                    'total_won' => 0,
                ]
            ]);

        } else {
            // 2) ODDS-RA FEL! (HALMOZÓ)
            $request->validate([
                'odds_question_count' => 'required|integer|in:10,20,30,40,50',
                'odds_total_bet' => 'required|integer|min:10',
            ]);

            $questionCount = (int) $request->odds_question_count;
            $totalBet = (int) $request->odds_total_bet;

            if ($user->points < $totalBet) {
                return back()->withErrors(['error' => "Nincs elég pontod a megadott tét megtevéséhez!"]);
            }

            $user->decrement('points', $totalBet);

            $query = Question::query();
            if ($request->filled('category_id') && $request->category_id !== 'all') {
                $query->where('category_id', $request->category_id);
            }
            if ($difficulty !== 'all') {
                $query->where('difficulty', $difficulty);
            }

            $questions = $query->inRandomOrder()->take($questionCount)->pluck('id')->toArray();

            if (count($questions) < $questionCount) {
                // Visszautaljuk a tétet, ha nincs elég kérdés
                $user->increment('points', $totalBet);
                return back()->withErrors(['error' => "Sajnos nincs elegendő ($questionCount) kérdés a kiválasztott szűrőben!"]);
            }

            session([
                'quiz_session' => [
                    'game_mode' => 'odds',
                    'difficulty' => $difficulty,
                    'multiplier' => $multiplier,
                    'question_ids' => $questions,
                    'current_index' => 0,
                    'initial_bet' => $totalBet,
                    'current_pot' => $totalBet,
                    'total_questions' => $questionCount,
                    'correct_answers' => 0,
                    'failed' => false,
                ]
            ]);
        }

        return redirect()->route('quiz.next');
    }

    /**
     * Következő kérdés betöltése és válaszok megkeverése
     */
    public function nextQuestion()
    {
        $quiz = session('quiz_session');

        if (!$quiz || $quiz['current_index'] >= count($quiz['question_ids'])) {
            return redirect()->route('quiz.summary');
        }

        if (($quiz['game_mode'] ?? '') === 'odds' && ($quiz['failed'] ?? false)) {
            return redirect()->route('quiz.summary');
        }

        $questionId = $quiz['question_ids'][$quiz['current_index']];
        $question = Question::with('options')->findOrFail($questionId);

        // Válaszlehetőségek megkeverése
        $question->setRelation('options', $question->options->shuffle());

        return view('quiz.show', compact('question', 'quiz'));
    }

    /**
     * Válasz feldolgozása, nyeremény kiszámítása & +1 PT a Kvízkészítőnek
     */
    public function answer(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer' => 'required|string',
        ]);

        $quiz = session('quiz_session');
        if (!$quiz) {
            return redirect()->route('quiz.bet');
        }

        // Kérdés lekérése a hozzá kapcsolódó Kvízzel együtt
        $question = Question::with(['options', 'quiz'])->findOrFail($request->question_id);
        $user = Auth::user();

        // Helyes válasz ellenőrzése
        $correctOption = $question->options->firstWhere('is_correct', true);
        $correctText = '';
        if ($correctOption) {
            $correctText = is_array($correctOption->option_text)
                ? ($correctOption->option_text['hu'] ?? reset($correctOption->option_text))
                : $correctOption->option_text;
        }

        $isCorrect = ($request->answer === $correctText);
        $gameMode = $quiz['game_mode'];
        $reward = 0;

        // 📊 STATISZTIKA FRISSÍTÉSE A KÉRDÉSNÉL
        $question->increment('times_answered');
        if ($isCorrect) {
            $question->increment('times_correct');
        }

        // 🎯 +1 pont jóváírása a Kvízkészítőnek minden megválaszolt kérdés után
        if ($question->quiz && $question->quiz->creator_id) {
            $question->quiz->creator()->increment('points', 1);
        }

        if ($gameMode === 'per_question') {
            $betPerQuestion = $quiz['bet_per_question'];
            $reward = $isCorrect ? round($betPerQuestion * $quiz['multiplier']) : 0;

            if ($isCorrect) {
                $user->increment('points', $reward);
                $quiz['correct_answers']++;
                $quiz['total_won'] += $reward;
            }
        } else {
            // ODDS MÓD
            if ($isCorrect) {
                $quiz['correct_answers']++;
                $quiz['current_pot'] = round($quiz['current_pot'] * $quiz['multiplier']);
                $reward = $quiz['current_pot'];

                if ($quiz['current_index'] + 1 >= $quiz['total_questions']) {
                    $user->increment('points', $quiz['current_pot']);
                }
            } else {
                $quiz['failed'] = true;
                $quiz['current_pot'] = 0;
            }
        }

        $quiz['current_index']++;
        session(['quiz_session' => $quiz]);

        return view('quiz.result', compact('isCorrect', 'question', 'reward', 'quiz', 'correctText'));
    }

    /**
     * Kvíz összefoglaló nézet
     */
    public function summary()
    {
        $quiz = session('quiz_session');
        if (!$quiz) {
            return redirect()->route('dashboard');
        }

        session()->forget('quiz_session');

        return view('quiz.summary', compact('quiz'));
    }

    // ==========================================
    // 🚀 KVÍZNYITÁS LOGIKA (50.000 PT + 10 KÉRDÉS)
    // ==========================================

    /**
     * 1. Kvíznyitó űrlap megjelenítése
     */
    public function createQuiz()
    {
        $user = Auth::user();
        $categories = Category::all();

        return view('quizzes.create', compact('user', 'categories'));
    }

    /**
     * 2. Új kvíz mentése a 10 minta kérdéssel együtt
     */
    public function storeQuiz(Request $request)
    {
        $user = Auth::user();

        // 1. Egyenleg ellenőrzése
        if (($user->points ?? 0) < 50000) {
            return back()->withErrors(['error' => 'A kvíznyitáshoz legalább 50.000 PT szükséges!'])->withInput();
        }

        // 2. Validálás
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'questions' => 'required|array|size:10',
            'questions.*.text' => 'required|string',
            'questions.*.difficulty' => 'required|in:easy,medium,hard',
            'questions.*.options' => 'required|array|size:4',
            'questions.*.options.*' => 'required|string',
            'questions.*.correct' => 'required|integer|in:0,1,2,3',
        ]);

        // 3. 50.000 pont levonása
        $user->decrement('points', 50000);

        // 4. Kvíz mentése 'pending' státusszal
        $quiz = Quiz::create([
            'creator_id' => $user->id,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        // 5. A 10 minta kérdés és opcióinak mentése
        foreach ($request->questions as $qData) {
            $question = Question::create([
                'quiz_id' => $quiz->id,
                'category_id' => $request->category_id,
                'creator_id' => $user->id,
                'difficulty' => $qData['difficulty'],
                'question_text' => ['hu' => $qData['text']],
                'is_approved' => false,
                'is_active' => true,
            ]);

            foreach ($qData['options'] as $index => $optText) {
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => ['hu' => $optText],
                    'is_correct' => ($index == $qData['correct']),
                ]);
            }
        }

        return redirect()->route('questions.index')->with('success', '🎉 Kvízed sikeresen benyújtva bírálatra! Az adminisztrátorok hamarosan átnézik.');
    }

    /**
     * Kvíz szerkesztése form
     */
    public function edit(Quiz $quiz)
    {
        $user = Auth::user();

        // Jogosultság ellenőrzése
        if (!$user->isUseradmin() && $quiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod ehhez a kvízhez!');
        }

        $categories = Category::all();

        return view('quizzes.edit', compact('quiz', 'categories'));
    }

    /**
     * Kvíz adatainak frissítése
     */
    public function update(Request $request, Quiz $quiz)
    {
        $user = Auth::user();

        if (!$user->isUseradmin() && $quiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod ehhez a kvízhez!');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
        ];

        if ($request->hasFile('cover_image')) {
            if ($quiz->cover_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($quiz->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('quiz_covers', 'public');
        }

        $quiz->update($data);

        return redirect()->route('quizzes.show', $quiz->id)->with('success', '✏️ Kvíz adatai sikeresen frissítve!');
    }

/**
 * Kvíz jóváhagyása (Admin)
 */
public function approveQuiz(Quiz $quiz)
{
    $user = Auth::user();

    // Jogosultság ellenőrzése (csak admin)
    if (!$user->isUseradmin()) {
        abort(403, 'Nincs adminisztrátori jogosultságod!');
    }

    $quiz->update([
        'status' => 'approved'
    ]);

    return back()->with('success', '✅ Kvíz sikeresen jóváhagyva és publikálva!');
}

/**
 * Kvíz elutasítása (Admin)
 */
public function rejectQuiz(Quiz $quiz)
{
    $user = Auth::user();

    if (!$user->isUseradmin()) {
        abort(403, 'Nincs adminisztrátori jogosultságod!');
    }

    $quiz->update([
        'status' => 'rejected'
    ]);

    return back()->with('error', '❌ Kvíz elutasítva.');
}
} // 🎯 ITT VAN A HELYES OSZTÁLY LEZÁRÁS A FÁJL VÉGÉN!
