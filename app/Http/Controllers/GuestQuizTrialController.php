<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestQuizTrialController extends Controller
{
    private const QUESTION_COUNT = 10;
    private const BET = 50;

    public function start(Quiz $quiz): RedirectResponse
    {
        $this->ensurePublic($quiz);
        $questions = $quiz->questions()->where('is_active', true)
            ->whereHas('options', fn ($query) => $query->where('is_correct', true))
            ->with('options:id,question_id')->inRandomOrder()->limit(self::QUESTION_COUNT)
            ->get(['id', 'quiz_id'])->map(fn ($question) => [
                'id' => $question->id,
                // A sorrendet egyszer rögzítjük, hogy frissítéskor se ugráljanak a válaszok.
                'option_ids' => $question->options->pluck('id')->shuffle()->values()->all(),
            ])->values()->all();

        if ($questions === []) {
            return redirect()->route('quizzes.share', $quiz)->with('error', 'Ehhez a kvízhez jelenleg nincs kipróbálható kérdés.');
        }

        session()->put('guest_quiz_trial', ['quiz_id' => $quiz->id, 'questions' => $questions, 'current' => 0, 'correct' => 0, 'winnings' => 0]);
        return redirect()->route('quizzes.trial.show', $quiz);
    }

    public function show(Quiz $quiz): View|RedirectResponse
    {
        $this->ensurePublic($quiz);
        $trial = session('guest_quiz_trial');
        if (! $this->matches($trial, $quiz) || $trial['current'] >= count($trial['questions'])) {
            return redirect()->route('quizzes.share', $quiz);
        }
        $entry = $trial['questions'][$trial['current']];
        $question = $quiz->questions()->with('options')->findOrFail($entry['id']);
        $options = collect($entry['option_ids'])->map(fn ($id) => $question->options->firstWhere('id', $id))->filter()->values();
        return view('play.guest-trial', compact('quiz', 'question', 'options') + ['number' => $trial['current'] + 1, 'total' => count($trial['questions'])]);
    }

    public function answer(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublic($quiz);
        $trial = session('guest_quiz_trial');
        if (! $this->matches($trial, $quiz) || $trial['current'] >= count($trial['questions'])) {
            return redirect()->route('quizzes.share', $quiz);
        }
        $validated = $request->validate(['option_id' => ['required', 'integer']]);
        $question = $quiz->questions()->with('options')->findOrFail($trial['questions'][$trial['current']]['id']);
        $option = $question->options->firstWhere('id', (int) $validated['option_id']);
        abort_unless($option, 422);
        if ($option->is_correct) {
            $multipliers = ['easy' => 1.2, 'medium' => 1.5, 'hard' => 2.0];
            $trial['correct']++;
            $trial['winnings'] += (int) round(self::BET * ($multipliers[$question->difficulty] ?? 1.5));
        }
        $trial['current']++;
        session()->put('guest_quiz_trial', $trial);
        return $trial['current'] >= count($trial['questions'])
            ? redirect()->route('quizzes.trial.result', $quiz)
            : redirect()->route('quizzes.trial.show', $quiz);
    }

    public function result(Quiz $quiz): View|RedirectResponse
    {
        $this->ensurePublic($quiz);
        $trial = session('guest_quiz_trial');
        if (! $this->matches($trial, $quiz) || $trial['current'] < count($trial['questions'])) {
            return redirect()->route('quizzes.share', $quiz);
        }
        return view('play.guest-trial-result', compact('quiz') + ['correct' => $trial['correct'], 'total' => count($trial['questions']), 'winnings' => $trial['winnings'], 'bet' => self::BET]);
    }

    private function ensurePublic(Quiz $quiz): void
    {
        abort_unless($quiz->status === 'approved' && $quiz->is_public, 404);
    }

    private function matches(?array $trial, Quiz $quiz): bool
    {
        return $trial && (int) ($trial['quiz_id'] ?? 0) === $quiz->id && ! empty($trial['questions']);
    }
}
