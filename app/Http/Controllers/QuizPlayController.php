<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Question;
use App\Services\QuizGameService;
use Illuminate\Http\Request;

class QuizPlayController extends Controller
{
    public function __construct(private QuizGameService $gameService) {}

    public function start(Request $request, Quiz $quiz)
    {
        $request->validate([
            'mode' => 'required|in:bet,odds',
            'question_count' => 'nullable|integer|in:5,10,15,20',
            'difficulty' => 'required|in:easy,medium,hard',
            'bet_amount' => 'required|integer|min:100',
        ]);

        try {
            $sessionData = $this->gameService->initGameSession(
                $quiz,
                $request->mode,
                $request->difficulty,
                (int) ($request->question_count ?? 5),
                (int) $request->bet_amount
            );

            session(['quiz_session' => $sessionData]);

            return redirect()->route('quiz.play', $quiz->id);

        } catch (\Exception $e) {
            return back()->withErrors(['bet_amount' => $e->getMessage()]);
        }
    }

    public function play(Quiz $quiz)
    {
        $session = session('quiz_session');
        if (!$session || $session['quiz_id'] !== $quiz->id) {
            return redirect()->route('quiz.setup', $quiz->id)->with('error', 'Először állítsd be a tétet!');
        }

        $questions = Question::with(['options', 'category'])
            ->whereIn('id', $session['question_ids'])
            ->get()
            ->sortBy(fn($m) => array_search($m->id, $session['question_ids']))
            ->values();

        return view('play.play', [
            'quiz' => $quiz,
            'questions' => $questions,
            'session' => $session,
            'user' => auth()->user(),
        ]);
    }

    public function answer(Request $request)
    {
        $request->validate(['option_id' => 'required|exists:options,id']);

        $quizSession = session('quiz_session');
        if (!$quizSession) {
            return response()->json(['error' => 'Nincs aktív játékmenet.'], 400);
        }

        try {
            $result = $this->gameService->processAnswer(
                $quizSession,
                (int) $request->option_id,
                auth()->id()
            );

            session(['quiz_session' => $quizSession]);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * 🏁 Játék végi összegzés / eredményjelző képernyő
     */
    public function summary(Quiz $quiz)
    {
        $session = session('quiz_session');

        // Ha lejárt vagy nincs session, visszaküldjük a katalógusba
        if (!$session || $session['quiz_id'] !== $quiz->id) {
            return redirect()->route('quizzes.index');
        }

        return view('play.summary', [
            'quiz' => $quiz,
            'session' => $session,
            'user' => auth()->user(),
        ]);
    }
}
